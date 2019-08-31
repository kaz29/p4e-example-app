import cdk = require('@aws-cdk/core');
import * as ec2 from "@aws-cdk/aws-ec2";
import config from "../config/config";
import {ApplicationLoadBalancer, ApplicationProtocol, BaseLoadBalancer} from "@aws-cdk/aws-elasticloadbalancingv2";
import {Cluster, ContainerImage, HealthCheck} from "@aws-cdk/aws-ecs";
import {AppService} from "./ecs/app-service";
import {Repository} from "@aws-cdk/aws-ecr";

export interface MyServiceProps {
  targetPort: number;
  tag: string;
  production: string;
  identifier: string;
}

export interface ClusterStackProps extends  cdk.StackProps {
  readonly repository: Repository;
  readonly vpc: ec2.Vpc;
  readonly databaseHostname: string;
}

export class ClusterStack extends cdk.Stack {
  public readonly vpc: ec2.Vpc;
  public readonly loadBalancer: BaseLoadBalancer;
  public readonly cluster: Cluster;
  public readonly repository: Repository;
  public readonly services: AppService[];
  public readonly databaseHostname: string;

  constructor(scope: cdk.Construct, id: string, props: ClusterStackProps) {
    super(scope, id, props);

    this.services = [];

    this.repository = props.repository;
    this.vpc = props.vpc;
    this.databaseHostname = props.databaseHostname;

    /**
     * 共通で使用するALBを作成
     */
    this.loadBalancer = new ApplicationLoadBalancer(this, 'LB', {
      vpc: this.vpc,
      internetFacing: true,
    });

    this.cluster = new Cluster(this, config.ecs.clusterId, { vpc: this.vpc });

    this.createService(`${config.app.project_name}-blue`, {
      targetPort: 80,
      identifier: 'Code1',
      tag: 'latest',
      production: 'true',
    });

    this.createService(`${config.app.project_name}-green`, {
      targetPort: 8080,
      identifier: 'Code2',
      tag: 'latest',
      production: 'false',
    });

    new cdk.CfnOutput(this, 'LoadBalancerURL', { value: `http://${this.loadBalancer.loadBalancerDnsName}/` });
  }

  private createService(name: string, props: MyServiceProps)
  {
    const service = new AppService(this, name, {
      cluster: this.cluster,
      serviceName: name,
      containerName: name,
      cpu: 256,
      memoryLimitMiB: 512,
      desiredCount: 1,
      image: ContainerImage.fromEcrRepository(this.repository, props.tag),
      containerPort: 80,
      protocol: ApplicationProtocol.HTTP,
      targetPort: props.targetPort,
      targetGroupTags: {
        'IsProduction': props.production,
        'Identifier': props.identifier,
        'Image': props.tag,
        'ServiceName': name,
      },
      healthCheckPath: '/readiness.php',
      autoscale: {
        maxCapacity: 2,
        policyName: 'CpuScaling',
        targetUtilizationPercent: 50,
        scaleInCooldown: 60,
        scaleOutCooldown: 60,
      },
      loadBalancer: this.loadBalancer,
      environment: {
        TAG: props.tag,
        APP_ENV: 'production',
        DEBUG: '0',
        DB_HOST: this.databaseHostname,
        DB_USER: 'webapp',
        DB_PASSWORD: config.rds.dbpasswd,
        DB_NAME: 'webapp',
        DB_NAME_TEST: 'webapp_test',
        DB_PORT: '5432',
        DB_ENCODING: 'UTF8',
        DB_TIMEZONE: 'UTC',
      },
    });

    this.services.push(service);
  }
}

import cdk = require('@aws-cdk/core');
import * as ec2 from "@aws-cdk/aws-ec2";
import {DatabaseCluster, DatabaseClusterEngine} from "@aws-cdk/aws-rds";
import config from "../config/config";

export interface DatabaseStackProps extends  cdk.StackProps {
  vpc: ec2.Vpc;
}

export class DatabaseStack extends cdk.Stack {
  public readonly databaseCluster: DatabaseCluster;

  constructor(scope: cdk.Construct, id: string, props: DatabaseStackProps) {
    super(scope, id, props);

    const securityGroup = new ec2.SecurityGroup(this, 'DatabaseSG', {
      securityGroupName: 'DatabaseSG',
      vpc: props.vpc,
    });

    securityGroup.addIngressRule(
      ec2.Peer.ipv4('10.200.0.0/16'),
      ec2.Port.tcp(5432),
      'Aurora Postgres'
    );

    this.databaseCluster = new DatabaseCluster(this, 'Database', {
      engine: DatabaseClusterEngine.AURORA_POSTGRESQL,
      engineVersion: "10.7",
      instances: 2,
      port: 5432,
      defaultDatabaseName: 'webapp',
      masterUser: {
        username: 'webapp',
        password: cdk.SecretValue.plainText(config.rds.dbpasswd),
      },
      instanceProps: {
        instanceType: ec2.InstanceType.of(ec2.InstanceClass.BURSTABLE3, ec2.InstanceSize.MEDIUM),
        vpcSubnets: {
          subnetType: ec2.SubnetType.PUBLIC,
        },
        vpc: props.vpc,
        securityGroup,
      },
      parameterGroup: {
        parameterGroupName: "default.aurora-postgresql10",
      } as any,
    });

    new cdk.CfnOutput(this, 'ClusterEndpoint', { value: this.databaseCluster.clusterEndpoint.socketAddress });
  }
}

import {FargateService, FargateTaskDefinition, HealthCheck} from '@aws-cdk/aws-ecs';
import {Construct, Duration} from '@aws-cdk/core';
import { AppBaseService, AppBaseServiceProps } from './app-base-service';

/**
 * The properties for the NearmeFargateService service.
 */
export interface AppFagateServiceProps extends AppBaseServiceProps {
  /**
   * The number of cpu units used by the task.
   * Valid values, which determines your range of valid values for the memory parameter:
   * 256 (.25 vCPU) - Available memory values: 0.5GB, 1GB, 2GB
   * 512 (.5 vCPU) - Available memory values: 1GB, 2GB, 3GB, 4GB
   * 1024 (1 vCPU) - Available memory values: 2GB, 3GB, 4GB, 5GB, 6GB, 7GB, 8GB
   * 2048 (2 vCPU) - Available memory values: Between 4GB and 16GB in 1GB increments
   * 4096 (4 vCPU) - Available memory values: Between 8GB and 30GB in 1GB increments
   *
   * This default is set in the underlying FargateTaskDefinition construct.
   *
   * @default 256
   */
  readonly cpu?: number;

  /**
   * The amount (in MiB) of memory used by the task.
   *
   * This field is required and you must use one of the following values, which determines your range of valid values
   * for the cpu parameter:
   *
   * 0.5GB, 1GB, 2GB - Available cpu values: 256 (.25 vCPU)
   *
   * 1GB, 2GB, 3GB, 4GB - Available cpu values: 512 (.5 vCPU)
   *
   * 2GB, 3GB, 4GB, 5GB, 6GB, 7GB, 8GB - Available cpu values: 1024 (1 vCPU)
   *
   * Between 4GB and 16GB in 1GB increments - Available cpu values: 2048 (2 vCPU)
   *
   * Between 8GB and 30GB in 1GB increments - Available cpu values: 4096 (4 vCPU)
   *
   * This default is set in the underlying FargateTaskDefinition construct.
   *
   * @default 512
   */
  readonly memoryLimitMiB?: number;
}

/**
 * A Fargate service running on an ECS cluster fronted by a load balancer
 */
export class AppFargateService extends AppBaseService {

  /**
   * The Fargate service in this construct
   */
  public readonly service: FargateService;

  /**
   * Constructs a new instance of the LoadBalancedFargateService class.
   */
  constructor(scope: Construct, id: string, props: AppFagateServiceProps) {
    super(scope, id, props);

    const taskDefinition = new FargateTaskDefinition(this, 'TaskDef', {
      memoryLimitMiB: props.memoryLimitMiB,
      cpu: props.cpu,
      executionRole: props.executionRole,
      taskRole: props.taskRole,
    });

    const healthCheekPath = props.healthCheckPath !== undefined ? props.healthCheckPath: '/';

    const containerName = props.containerName !== undefined ? props.containerName : 'web';
    const container = taskDefinition.addContainer(containerName, {
      image: props.image,
      logging: this.logDriver,
      environment: props.environment,
      secrets: props.secrets,
      healthCheck: {
        command: [
          'CMD-SHELL',
          `curl http://127.0.0.1:${props.containerPort}${healthCheekPath} | exit 1`,
        ],
        interval: Duration.seconds(60),
        retries: 10,
        startPeriod: Duration.seconds(60),
        timeout: Duration.seconds(60),
      }
    });
    container.addPortMappings({
      containerPort: props.containerPort || 80,
    });

    this.service = new FargateService(this, "Service", {
      cluster: this.cluster,
      desiredCount: this.desiredCount,
      taskDefinition,
      assignPublicIp: this.assignPublicIp,
      serviceName: props.serviceName,
      vpcSubnets: props.vpcSubnets,
    });
    this.addServiceAsTarget(this.service);

    return this;
  }
}

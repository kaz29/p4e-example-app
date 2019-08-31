import cdk = require('@aws-cdk/core');
import {Construct, Tag} from "@aws-cdk/core";
import {AppFagateServiceProps, AppFargateService} from "./app-fargate-service";
import {BaseLoadBalancer} from '@aws-cdk/aws-elasticloadbalancingv2';

export interface AppServiceAutoscaleProps {
  maxCapacity: number;
  policyName: string;
  targetUtilizationPercent: number;
  scaleInCooldown: number;
  scaleOutCooldown: number;
}

export interface AppServiceProps extends AppFagateServiceProps{
  readonly serviceName: string;
  readonly targetPort: number;
  readonly autoscale?: AppServiceAutoscaleProps;
  readonly targetGroupTags?: {
    [key: string]: string;
  }
  loadBalancer: BaseLoadBalancer;
}

export class AppService extends AppFargateService
{
  constructor(scope: Construct, id: string, props: AppServiceProps)
  {
    super(scope, id, Object.assign({publicLoadBalancer: true, publicTasks: true}, props));

    if (props.targetGroupTags) {
      Object.entries(props.targetGroupTags).forEach(([key, value]) => {
        Tag.add(this.targetGroup, key, value);
      });
    }

    if (props.autoscale) {
      const scaling = this.service.autoScaleTaskCount({ maxCapacity: props.autoscale.maxCapacity });
      scaling.scaleOnCpuUtilization(props.autoscale.policyName, {
        targetUtilizationPercent: props.autoscale.targetUtilizationPercent,
        scaleInCooldown: cdk.Duration.seconds(props.autoscale.scaleInCooldown),
        scaleOutCooldown: cdk.Duration.seconds(props.autoscale.scaleOutCooldown),
        disableScaleIn: false,
      });
    }

    return this;
  }
}
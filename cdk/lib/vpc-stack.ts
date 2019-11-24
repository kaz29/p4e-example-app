import cdk = require('@aws-cdk/core');
import * as ec2 from "@aws-cdk/aws-ec2";

export interface VpcStackProps extends  cdk.StackProps {
}

export class VpcStack extends cdk.Stack {
  public readonly vpc: ec2.Vpc;

  constructor(scope: cdk.Construct, id: string, props?: VpcStackProps) {
    super(scope, id, props);

    this.vpc = new ec2.Vpc(this, `VPC`, {
      cidr: '10.200.0.0/16',
      subnetConfiguration: [
        {
          name: `Public`,
          cidrMask: 24,
          subnetType: ec2.SubnetType.PUBLIC,
        },
      ],
      maxAzs: 2,
      natGateways: 0,
      vpnGateway: false,
    });
  }
}

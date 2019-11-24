import cdk = require('@aws-cdk/core');
import {CodeBuildAction} from "@aws-cdk/aws-codepipeline-actions";
import * as codebuild from "@aws-cdk/aws-codebuild";
import * as codepipeline from "@aws-cdk/aws-codepipeline";
import * as iam from "@aws-cdk/aws-iam";
import { BaseLoadBalancer } from '@aws-cdk/aws-elasticloadbalancingv2';
import config from "../../config/config";

export interface FlipActionProps {
  project_name: string;
  sourceArtifact: codepipeline.Artifact;
  loadBalancer: BaseLoadBalancer;
}

export default class FlipAction extends CodeBuildAction
{
  constructor(scope: cdk.Construct, id: string, props: FlipActionProps)
  {
    const project = new codebuild.Project(scope, 'flipProject', {
      projectName: `${props.project_name}-flip`,
      buildSpec: FlipAction.buildSpec(),
      source: codebuild.Source.gitHub({
        owner: config.github.user,
        repo: config.github.name,
      }),
      environment: {
        environmentVariables: {
          ELB_NAME: {
            type: codebuild.BuildEnvironmentVariableType.PLAINTEXT,
            value: props.loadBalancer.loadBalancerName,
          },
        },
        buildImage: codebuild.LinuxBuildImage.STANDARD_2_0,
        privileged: true,
      }
    });

    project.addToRolePolicy(new iam.PolicyStatement({
      resources: ['*'],
      actions: [
        'elasticloadbalancing:DescribeLoadBalancers',
        'elasticloadbalancing:DescribeListeners',
        'elasticloadbalancing:DescribeRules',
        'elasticloadbalancing:ModifyRule',
        'elasticloadbalancing:AddTags',
      ]
    }));

    super({
      actionName: 'Flip-TargetGroup',
      project: project,
      input: props.sourceArtifact,
      outputs: [new codepipeline.Artifact()],
      runOrder: 3,
    });
  }

  private static  buildSpec(): codebuild.BuildSpec
  {
    const buildSpec = codebuild.BuildSpec.fromObject({
      version: '0.2',
      phases: {
        install: {
          'runtime-versions': {
            python: '3.7',
          },
          commands: [
            'echo Entered the install phase...',
            'apt-get update -y',
            'pip install --upgrade awscli',
            'pip install --upgrade boto3',
            'echo $ELB_NAME',
          ],
        },
        build: {
          commands: [
            'python $CODEBUILD_SRC_DIR/bin/blue_green_flip.py $ELB_NAME',
          ],
        },
      },
    });

    return buildSpec;
  }
}

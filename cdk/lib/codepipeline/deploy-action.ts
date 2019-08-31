import cdk = require('@aws-cdk/core');
import {Action, CodeBuildAction} from "@aws-cdk/aws-codepipeline-actions";
import {PolicyStatement} from "@aws-cdk/aws-iam";
import * as codebuild from "@aws-cdk/aws-codebuild";
import config from "../../config/config";
import {Cluster} from "@aws-cdk/aws-ecs";
import * as codepipeline from "@aws-cdk/aws-codepipeline";
import * as s3 from '@aws-cdk/aws-s3';
import { BaseLoadBalancer } from '@aws-cdk/aws-elasticloadbalancingv2';
import * as iam from "@aws-cdk/aws-iam";

export interface DeployActionProps {
  project_name: string;
  cluster: Cluster;
  buildArtifact: codepipeline.Artifact;
  sourceArtifact: codepipeline.Artifact;
}

export default class DeployAction extends CodeBuildAction
{
  public readonly project: codebuild.Project;

  constructor(scope: cdk.Construct, id: string, props: DeployActionProps)
  {
    const project = new codebuild.Project(scope, 'deployProject', {
      projectName: `${props.project_name}-deploy`,
      buildSpec: DeployAction.buildSpec(),
      source: codebuild.Source.gitHub({
        owner: config.github.user,
        repo: config.github.name,
      }),
      environment: {
        environmentVariables: {
          PROJECT_NAME: {
            type: codebuild.BuildEnvironmentVariableType.PLAINTEXT,
            value: props.project_name,
          },
          CLUSTER_NAME: {
            type: codebuild.BuildEnvironmentVariableType.PLAINTEXT,
            value: props.cluster.clusterName,
          },
        },
        buildImage: codebuild.LinuxBuildImage.STANDARD_2_0,
        privileged: true,
      }
    });

    project.addToRolePolicy(new iam.PolicyStatement({
      resources: ['*'],
      actions: [
        'ecs:DescribeServices',
        'ecs:DescribeTaskDefinition',
        'ecs:RegisterTaskDefinition',
        'ecs:UpdateService',
      ]
    }));

    project.addToRolePolicy(new iam.PolicyStatement({
      actions: ['iam:PassRole'],
      resources: ['*'],
      conditions: {
        StringEqualsIfExists: {
          'iam:PassedToService': [
            'ecs-tasks.amazonaws.com',
          ],
        }
      }
    }));

    super({
      actionName: 'Deploy-To-Green',
      project: project,
      input: props.sourceArtifact,
      extraInputs: [props.buildArtifact],
      outputs: [new codepipeline.Artifact()],
      runOrder: 1,
    });

    this.project = project;
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
          ],
        },
        pre_build: {
          commands: [
            'export IMAGE_URI=`jq -r .imageUri $CODEBUILD_SRC_DIR_BuildArtifact/deploy.json`',
            'export SERVICE_NAME=`jq -r .serviceName $CODEBUILD_SRC_DIR_BuildArtifact/deploy.json`',
          ],
        },
        build: {
          commands: [
            'python $CODEBUILD_SRC_DIR/bin/deploy.py $CLUSTER_NAME $SERVICE_NAME $IMAGE_URI',
            'python $CODEBUILD_SRC_DIR/bin/deploy_check.py $CLUSTER_NAME $SERVICE_NAME',
          ],
        },
      },
    });

    return buildSpec;
  }
}

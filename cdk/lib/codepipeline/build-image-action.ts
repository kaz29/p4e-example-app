import cdk = require('@aws-cdk/core');
import {CodeBuildAction} from "@aws-cdk/aws-codepipeline-actions";
import {Artifact} from "@aws-cdk/aws-codepipeline";
import * as codebuild from "@aws-cdk/aws-codebuild";
import config from "../../config/config";
import {Repository} from "@aws-cdk/aws-ecr";
import * as iam from "@aws-cdk/aws-iam";
import { BaseLoadBalancer } from '@aws-cdk/aws-elasticloadbalancingv2';

export interface BuildActionProps {
  project_name: string;
  sourceArtifact: Artifact;
  repository: Repository;
  buildArtifact: Artifact;
  loadBalancer: BaseLoadBalancer;
}
export default class BuildImageAction extends CodeBuildAction {
  constructor(scope: cdk.Construct, id: string, props: BuildActionProps) {
    const serviceRole = new iam.Role(scope, 'Role', {
      roleName: cdk.PhysicalName.GENERATE_IF_NEEDED,
      assumedBy: new iam.ServicePrincipal('codebuild.amazonaws.com'),
      path: '/'
    });

    const buildProject = new codebuild.Project(scope, 'buildProject', {
      projectName: `${props.project_name}-build`,
      role: serviceRole,
      buildSpec: codebuild.BuildSpec.fromSourceFilename('buildspecs/buildimage.yml'),
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
          REPOSITORY_URL: {
            type: codebuild.BuildEnvironmentVariableType.PLAINTEXT,
            value: props.repository.repositoryUri,
          },
          HTPASSWD: {
            type: codebuild.BuildEnvironmentVariableType.PLAINTEXT,
            value: config.app.htpasswd,
          },
          LOADBARANCER_NAME: {
            type: codebuild.BuildEnvironmentVariableType.PLAINTEXT,
            value: props.loadBalancer.loadBalancerName,
          },
        },
        buildImage: codebuild.LinuxBuildImage.STANDARD_2_0,
        privileged: true,
      }
    });

    buildProject.addToRolePolicy(new iam.PolicyStatement({
      resources: ['*'],
      actions: [
        'ecr:GetAuthorizationToken',
      ]
    }));
    buildProject.addToRolePolicy(new iam.PolicyStatement({
      resources: [props.repository.repositoryArn],
      actions: [
        'ecr:GetAuthorizationToken',
        'ecr:GetDownloadUrlForLayer',
        'ecr:BatchGetImage',
        'ecr:BatchCheckLayerAvailability',
        'ecr:PutImage',
        'ecr:InitiateLayerUpload',
        'ecr:UploadLayerPart',
        'ecr:CompleteLayerUpload',
        'ecr:ListImages',
      ]
    }));

    buildProject.addToRolePolicy(new iam.PolicyStatement({
      resources: ['*'],
      actions: [
        'elasticloadbalancing:Describe*',
      ],
    }));

    super({
      actionName: 'Build',
      project: buildProject,
      input: props.sourceArtifact,
      outputs: [props.buildArtifact],
    });
  }
}

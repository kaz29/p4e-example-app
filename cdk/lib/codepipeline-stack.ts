import cdk = require('@aws-cdk/core');
import * as codepipeline from '@aws-cdk/aws-codepipeline';
import SourceAction from "./codepipeline/source-action";
import UnitTestAction from "./codepipeline/unittest-action";
import BuildImageAction from "./codepipeline/build-image-action";
import config from "../config/config";
import {Repository} from "@aws-cdk/aws-ecr";
import DeployAction from "./codepipeline/deploy-action";
import {Cluster} from "@aws-cdk/aws-ecs";
import { BaseLoadBalancer } from '@aws-cdk/aws-elasticloadbalancingv2';
import {ManualApprovalAction} from "@aws-cdk/aws-codepipeline-actions";
import FlipAction from "./codepipeline/flip-action";

export interface CodepipelineStackProps extends cdk.StackProps {
  readonly repository: Repository;
  readonly cluster: Cluster;
  readonly loadBalancer: BaseLoadBalancer;
}

export default class CodepipelineStack extends cdk.Stack {
  public readonly repository: Repository;
  public readonly cluster: Cluster;
  public readonly loadBalancer: BaseLoadBalancer;

  constructor(scope: cdk.Construct, id: string, props: CodepipelineStackProps) {
    super(scope, id, props);

    this.repository = props.repository;
    this.cluster = props.cluster;
    this.loadBalancer = props.loadBalancer;

    const sourceArtifact = new codepipeline.Artifact();
    const buildArtifact = new codepipeline.Artifact('BuildArtifact');

    new codepipeline.Pipeline(this, 'pipeline', {
      pipelineName: `${config.app.project_name}-pipeline`,
      stages: [
        {
          stageName: 'Source',
          actions: [
            new SourceAction(sourceArtifact)
          ],
        },
        {
          stageName: 'UnitTest',
          actions: [
            new UnitTestAction(this, 'project', {
              project_name: config.app.project_name,
              sourceArtifact,
            }),
          ],
        },
        {
          stageName: 'Build',
          actions: [
            new BuildImageAction(this, 'buildProject', {
              project_name: config.app.project_name,
              sourceArtifact,
              repository: this.repository,
              buildArtifact,
              loadBalancer: this.loadBalancer,
            })
          ],
        },
        {
          stageName: 'Deploy',
          actions: [
            new DeployAction(this, 'deployProject', {
              project_name: config.app.project_name,
              cluster: this.cluster,
              buildArtifact: buildArtifact,
              sourceArtifact,
            }),
            new ManualApprovalAction({
              actionName: 'Approval',
              additionalInformation: 'Continue with blue-green swap ?',
              runOrder: 2,
            }),
            new FlipAction(this, 'flipAction', {
              project_name: config.app.project_name,
              sourceArtifact,
              loadBalancer: this.loadBalancer,
            })
          ]
        },
      ],
    });
  }
}

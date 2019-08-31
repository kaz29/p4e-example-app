import cdk = require('@aws-cdk/core');
import {CodeBuildAction} from "@aws-cdk/aws-codepipeline-actions";
import * as codepipeline from "@aws-cdk/aws-codepipeline";
import * as codebuild from "@aws-cdk/aws-codebuild";
import {Artifact} from "@aws-cdk/aws-codepipeline";
import config from "../../config/config";
import {BuildSpec} from "@aws-cdk/aws-codebuild";

export interface UnitTestActionProps {
  project_name: string;
  sourceArtifact: Artifact;
}

export default class UnitTestAction extends CodeBuildAction
{
  constructor(scope: cdk.Construct, id: string, props: UnitTestActionProps)
  {
    const project = new codebuild.Project(scope, id, {
      projectName: props.project_name,
      buildSpec: BuildSpec.fromSourceFilename('buildspecs/unittest.yml'),
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
        },
        buildImage: codebuild.LinuxBuildImage.STANDARD_2_0,
        privileged: true,
      }
    });

    super({
      actionName: 'UnitTest',
      project,
      input: props.sourceArtifact,
      outputs: [new codepipeline.Artifact()],
    });
  }
}

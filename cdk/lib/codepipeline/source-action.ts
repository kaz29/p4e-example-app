import cdk = require('@aws-cdk/core');
import {GitHubSourceAction} from "@aws-cdk/aws-codepipeline-actions";
import * as codepipeline_actions from "@aws-cdk/aws-codepipeline-actions";
import {Artifact} from "@aws-cdk/aws-codepipeline";
import config from "../../config/config";

export default class SourceAction extends GitHubSourceAction {
  constructor(artifact: Artifact)
  {
    super({
      actionName: 'Github',
      owner: config.github.user,
      repo: config.github.name,
      branch: config.github.branch,
      oauthToken: cdk.SecretValue.plainText(config.github.token),
      trigger: codepipeline_actions.GitHubTrigger.WEBHOOK, // POLL
      output: artifact,
    })
  }
}
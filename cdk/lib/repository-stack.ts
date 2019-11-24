import cdk = require('@aws-cdk/core');
import config from "../config/config";
import {Repository, TagStatus} from "@aws-cdk/aws-ecr";

export interface RepositoryStackProps extends  cdk.StackProps {
}

export class RepositoryStack extends cdk.Stack {
  public readonly repository: Repository;

  constructor(scope: cdk.Construct, id: string, props?: RepositoryStackProps) {
    super(scope, id, props);

    const repofitoryName = `${config.app.project_name}-ecr`;
    this.repository = new Repository(this, repofitoryName, {
      repositoryName: repofitoryName,
      lifecycleRules: [
        {
          rulePriority: 10,
          tagStatus: TagStatus.TAGGED,
          maxImageCount: 1,
          tagPrefixList: [
            'latest'
          ]
        },
        {
          rulePriority: 11,
          tagStatus: TagStatus.TAGGED,
          maxImageCount: 5,
          tagPrefixList: [
            'build-'
          ]
        },
        {
          rulePriority: 100,
          tagStatus: TagStatus.UNTAGGED,
          maxImageCount: 10,
        },
      ]
    });
  }
}

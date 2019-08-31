import cdk = require('@aws-cdk/core');
import config from "../config/config";
import {Repository} from "@aws-cdk/aws-ecr";

export interface RepositoryStackProps extends  cdk.StackProps {
}

export class RepositoryStack extends cdk.Stack {
  public readonly repository: Repository;

  constructor(scope: cdk.Construct, id: string, props?: RepositoryStackProps) {
    super(scope, id, props);

    const repofitoryName = `${config.app.project_name}-ecr`;
    this.repository = new Repository(this, repofitoryName, {
      repositoryName: repofitoryName,
    });
  }
}

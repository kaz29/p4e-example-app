require('dotenv').config();

export interface IConfig {
  github: {
    token: string;
    user: string;
    name: string;
    branch: string;
  },
  app: {
    project_name: string;
    htpasswd: string;
  },
  ecs: {
    clusterId: string;
  },
  rds: {
    dbpasswd: string;
  },
}

export default {
  github: {
    user: 'kaz29',
    name: 'p4e-example-app',
    token: process.env.GITHUB_TOKEN,
    branch: 'feature/cdk',
  },
  app: {
    project_name: 'kwatanabe-test-app',
    htpasswd: process.env.HTPASSWD,
  },
  ecs: {
    clusterId: 'kwatanabe-test-cluster',
  },
  rds: {
    dbpasswd: process.env.DB_PASSWORD,
  }
} as IConfig
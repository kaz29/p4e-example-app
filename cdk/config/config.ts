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
    user: process.env.GITHUB_USER,
    name: process.env.GITHUB_REPO_NAME,
    token: process.env.GITHUB_TOKEN,
    branch: 'master',
  },
  app: {
    project_name: 'p4e-example-app',
    htpasswd: process.env.HTPASSWD,
  },
  ecs: {
    clusterId: 'p4e-example-app-cluster',
  },
  rds: {
    dbpasswd: process.env.DB_PASSWORD,
  }
} as IConfig
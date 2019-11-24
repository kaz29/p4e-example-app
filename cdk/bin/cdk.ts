#!/usr/bin/env node
import 'source-map-support/register';
import cdk = require('@aws-cdk/core');
import { ClusterStack } from '../lib/cluster-stack';
import CodepipelineStack from "../lib/codepipeline-stack";
import {RepositoryStack} from "../lib/repository-stack";
import {VpcStack} from "../lib/vpc-stack";
import {DatabaseStack} from "../lib/database-stack";

const app = new cdk.App();

// RepositoryStack(リポジトリ作成用)生成
const repositoryStack = new RepositoryStack(app, 'RepositoryStack');

// VpcStack(VPC作成用)生成
const vpcStack = new VpcStack(app, 'VpcStack');

// DatabaseStack(Database作成用)生成
const databaseStack = new DatabaseStack(app, 'DatabaseStack', {
  vpc: vpcStack.vpc,
});

// ClusterStack(Fargateクラスタ作成用)生成
const clusterStack = new ClusterStack(app, 'ClusterStack', {
  repository: repositoryStack.repository,
  vpc: vpcStack.vpc,
  databaseHostname: databaseStack.databaseCluster.clusterEndpoint.hostname,
});

// CodepipelineStack(Code Pipeline作成用)生成
new CodepipelineStack(app, 'PipelineStack', {
  repository: repositoryStack.repository,
  cluster: clusterStack.cluster,
  loadBalancer: clusterStack.loadBalancer,
});

from __future__ import print_function

import json
import boto3
import sys
import traceback

ecs = boto3.client('ecs')

def handler(event, cluster_name, service_name, image_name):
  try:
    # サービス詳細を取得
    service = ecs.describe_services(
      cluster=cluster_name,
      services=[service_name]
    )

    # タスク定義名を取得
    result = service['services'][0]['taskDefinition'].split('/', 1)
    taskdef_name, _ = result[1].split(':')

    taskdef = ecs.describe_task_definition(
      taskDefinition=taskdef_name,
    )

    # イメージ名を差し替え
    taskdef['taskDefinition']['containerDefinitions'][0]['image'] = image_name

    # 新しいtask定義を登録
    result = ecs.register_task_definition(
      family=taskdef['taskDefinition']['family'],
      taskRoleArn=taskdef['taskDefinition']['taskRoleArn'],
      executionRoleArn=taskdef['taskDefinition']['executionRoleArn'],
      networkMode='awsvpc',
      containerDefinitions=taskdef['taskDefinition']['containerDefinitions'],
      volumes=taskdef['taskDefinition']['volumes'],
      placementConstraints=taskdef['taskDefinition']['placementConstraints'],
      requiresCompatibilities=taskdef['taskDefinition']['requiresCompatibilities'],
      cpu=taskdef['taskDefinition']['cpu'],
      memory=taskdef['taskDefinition']['memory']
    )

    # サービスを更新
    result = ecs.update_service(
      cluster=cluster_name,
      service=service_name,
      desiredCount=1,
      taskDefinition=taskdef_name
    )

    print("DEPLOY task: " + result['service']['taskDefinition'] + " to " + service_name)
  except Exception as e:
      print('Function failed due to exception.')
      print(e)
      traceback.print_exc()

if __name__ == "__main__":
  if (len(sys.argv) < 4):
    print('Usage: python test.py "ClusterName" "ServiceName", "ImageName"')
  else:
    handler(sys.argv[0], sys.argv[1], sys.argv[2], sys.argv[3])

from __future__ import print_function

import boto3
import sys
import traceback
import time

import json
from datetime import date, datetime

ecs = boto3.client('ecs')

def handler(event, cluster_name, service_name):
  try:
    maxCount = 100
    finish = False
    for count in range(maxCount):
      if (finish):
        break

      # サービス詳細を取得
      service = ecs.describe_services(
        cluster=cluster_name,
        services=[service_name]
      )

      for i in range(len(service['services'][0]['deployments'])):
        if (service['services'][0]['deployments'][i]['status'] == 'PRIMARY'):
          print('id: {0}, runningCount: {1:d}, desiredCount: {2:d}'.format(
            service['services'][0]['deployments'][i]['id'],
            service['services'][0]['deployments'][i]['runningCount'],
            service['services'][0]['deployments'][i]['desiredCount']
          ))

          if (service['services'][0]['deployments'][i]['runningCount'] >= 
              service['services'][0]['deployments'][i]['desiredCount']):
            print('OK')
            finish = True
            break
          else:
            print('Waiting for running... {0}/{1}'.format(count, maxCount))
            time.sleep(10)
  except Exception as e:
      print('Function failed due to exception.')
      print(e)
      traceback.print_exc()

if __name__ == "__main__":
  if (len(sys.argv) < 3):
    print('Usage: python test.py "ClusterName" "ServiceName"')
  else:
    handler(sys.argv[0], sys.argv[1], sys.argv[2])

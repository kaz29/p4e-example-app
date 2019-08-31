from __future__ import print_function

import json
import boto3
import sys
import traceback

elbclient = boto3.client('elbv2')

def gettargetgroups(elbname, greenport):
    elbresponse = elbclient.describe_load_balancers(Names=[elbname])

    listners = elbclient.describe_listeners(LoadBalancerArn=elbresponse['LoadBalancers'][0]['LoadBalancerArn'])
    for x in listners['Listeners']:
        if (x['Port'] == greenport):
            greenlistenerarn = x['ListenerArn']

    greentgresponse = elbclient.describe_rules(ListenerArn=greenlistenerarn)
    for x in greentgresponse['Rules']:
        if x['Priority'] == '1':
          targetgroup = x['Actions'][0]['TargetGroupArn']

    tags = elbclient.describe_tags(ResourceArns=[targetgroup])
    for x in tags['TagDescriptions'][0]['Tags']:
        if x['Key'] == 'ServiceName':
          serviceName = x['Value']

    return serviceName

def handler(event, elb_name):
    try:
        serviceName = gettargetgroups(elb_name, 8080)

        print(serviceName)
    except Exception as e:
        print('Function failed due to exception.')
        print(e)
        traceback.print_exc()

if __name__ == "__main__":
    if (len(sys.argv) < 2):
      print('Usage: python describe_service.py "LoadBalancerName"')
    else:
      handler(sys.argv[0], sys.argv[1])
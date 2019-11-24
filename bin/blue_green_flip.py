from __future__ import print_function

import json
import boto3
import sys
import traceback

elbclient = boto3.client('elbv2')

def gettargetgroups(elbname, liveport, betaport):
    elbresponse = elbclient.describe_load_balancers(Names=[elbname])

    listners = elbclient.describe_listeners(LoadBalancerArn=elbresponse['LoadBalancers'][0]['LoadBalancerArn'])
    for x in listners['Listeners']:
        if (x['Port'] == liveport):
            livelistenerarn = x['ListenerArn']
        if (x['Port'] == betaport):
            betalistenerarn = x['ListenerArn']

    livetgresponse = elbclient.describe_rules(ListenerArn=livelistenerarn)
    for x in livetgresponse['Rules']:
        if x['Priority'] == '1':
          blue_targetgroup = x['Actions'][0]['TargetGroupArn']
          blue_rulearn = x['RuleArn']

    betatgresponse = elbclient.describe_rules(ListenerArn=betalistenerarn)
    for x in betatgresponse['Rules']:
        if x['Priority'] == '1':
          green_targetgroup = x['Actions'][0]['TargetGroupArn']
          green_rulearn = x['RuleArn']

    return blue_targetgroup, blue_rulearn, green_targetgroup, green_rulearn

def swaptargetgroups(blue_targetgroup, blue_rulearn, green_targetgroup, green_rulearn):
    elbclient.modify_rule(
        RuleArn=green_rulearn,
        Actions=[
            {
                'Type': 'forward',
                'TargetGroupArn': blue_targetgroup
            }
        ]
    )

    elbclient.modify_rule(
        RuleArn=blue_rulearn,
        Actions=[
            {
                'Type': 'forward',
                'TargetGroupArn': green_targetgroup
            }
        ]
    )

    modify_tags(blue_targetgroup,"IsProduction","False")
    modify_tags(green_targetgroup, "IsProduction", "True")

def modify_tags(arn,tagkey,tagvalue):
    elbclient.add_tags(
        ResourceArns=[arn],
        Tags=[
            {
                'Key': tagkey,
                'Value': tagvalue
            },
        ]
    )

def handler(event, elb_name):
    try:
        print("ELBNAME="+elb_name)

        blue_targetgroup, blue_rulearn, green_targetgroup, green_rulearn = gettargetgroups(elb_name, 80, 8080)

        swaptargetgroups(blue_targetgroup, blue_rulearn, green_targetgroup, green_rulearn)

        blue_targetgroup, blue_rulearn, green_targetgroup, green_rulearn = gettargetgroups(elb_name, 80, 8080)

        print("\nBlue TargetGroup: " + blue_targetgroup)
        print("Green TargetGroup: " + green_targetgroup)

        return "Complete."

    except Exception as e:
        print('Function failed due to exception.')
        print(e)
        traceback.print_exc()

if __name__ == "__main__":
    if (len(sys.argv) < 2):
      print('Usage: python blue_green_flip.py "LoadBalancerName"')
    else:
      handler(sys.argv[0], sys.argv[1])
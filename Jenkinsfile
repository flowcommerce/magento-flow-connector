#!/usr/local/bin/groovy

def label = "worker-${UUID.randomUUID().toString()}"

def project = "magento-flow-connector"
def ecrRepo = "479720515435.dkr.ecr.us-east-1.amazonaws.com"
def iamRole = "arn:aws:iam::479720515435:role/cicd20181011095611663000000001"

properties([
  buildDiscarder(logRotator(numToKeepStr: '3')),
  pipelineTriggers([githubPush()])
])

phpTemplate(label: label) {
  def scmVars = checkout scm
  def imageTag
  if (env.CHANGE_ID) {
    imageTag = "${env.CHANGE_BRANCH}-pr-${scmVars.GIT_COMMIT}"
  } else {
    imageTag = "${env.BRANCH_NAME}-${env.BUILD_NUMBER}"
  }

  dockerBuild {
    appName = project
    vcsRef = scmVars.GIT_COMMIT
    version = imageTag
    awsRole = iamRole
    awsRoleAccount = '479720515435'
    dockerOrganisation = ecrRepo
    dockerFile = 'Dockerfile.dev'
  }
}
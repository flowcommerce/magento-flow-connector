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

  if (env.BRANCH_NAME == 'master') {
    dockerBuild {
      appName = project
      vcsRef = scmVars.GIT_COMMIT
      version = imageTag
      awsRole = iamRole
      awsRoleAccount = '479720515435'
      dockerOrganisation = ecrRepo
      dockerFile = 'Dockerfile.dev'
    }

    stage('Deploy Helm Chart') {
      container('helm') {
        sh "helm init --client-only"
        sh """helm dependency update ./deploy/$project"""

        withAWSRole() {
          sh """helm upgrade --tiller-namespace production --wait \
                --namespace production \
                --set stacks.dark.version=$imageTag \
                -i $project ./deploy/$project"""
        }
      }
    }
  }
}
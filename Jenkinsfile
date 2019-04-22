properties([pipelineTriggers([githubPush()])])

pipeline {
  options {
    disableConcurrentBuilds()
    buildDiscarder(logRotator(numToKeepStr: '3'))
    timeout(time: 30, unit: 'MINUTES')
  }

  agent {
    kubernetes {
      label 'worker-magento-flow-connector'
      inheritFrom 'default'

      containerTemplates([
        containerTemplate(name: 'helm', image: "lachlanevenson/k8s-helm:v2.12.0", command: 'cat', ttyEnabled: true),
        containerTemplate(name: 'docker', image: 'docker', command: 'cat', ttyEnabled: true)
      ])
    }
  }

  environment {
    DOCKER_ORG = 'flowcommerce'
    APP_NAME   = 'magento-flow-connector'
  }

  stages {
    stage('Checkout') {
      steps {
        checkoutWithTags scm
        script {
          IMAGE_TAG = sh(returnStdout: true, script: 'git describe --tags --dirty --always').trim()
        }
      }
    }

    stage('Build and push docker image release') {
      when { 
          anyOf { 
              branch 'master'
              changeRequest target: 'master'
          }
      }
      steps {
        container('docker') {
          withCredentials([string(credentialsId: 'magento2-repo-keys', variable: 'magento2_repo_private_key')]) {
            script {
              docker.withRegistry('https://index.docker.io/v1/', 'jenkins-dockerhub') {
                image = docker.build( "$DOCKER_ORG/$APP_NAME:$IMAGE_TAG", '--build-arg MAGENTO2_REPO_PRIVATE_KEY=$magento2_repo_private_key -f Dockerfile.dev .' )
                image.push()
              }
            }
          }
        }
      }
    }

    stage('Deploy Helm chart') {
      when { 
          anyOf { 
              branch 'master'
              changeRequest target: 'master'
          }
      }
      steps {
        container('helm') {
          sh('helm init --client-only')
          sh("helm upgrade --wait --install --debug --timeout 900 --namespace production --set deployments.live.version=$IMAGE_TAG -i $APP_NAME ./deploy/$APP_NAME")
        }
      }
    }
  }
}

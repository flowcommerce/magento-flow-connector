properties([pipelineTriggers([githubPush()])])

pipeline {
  options {
    disableConcurrentBuilds()
    buildDiscarder(logRotator(numToKeepStr: '3'))
    timeout(time: 30, unit: 'MINUTES')
  }

  agent {
    kubernetes {
      label 'worker-magento-flow-connector-development'
      inheritFrom 'default'

      containerTemplates([
        containerTemplate(name: 'helm', image: "lachlanevenson/k8s-helm:v2.12.0", command: 'cat', ttyEnabled: true),
        containerTemplate(name: 'docker', image: 'docker', command: 'cat', ttyEnabled: true)
      ])
    }
  }

  environment {
    DOCKER_ORG = 'flowcommerce'
    APP_NAME   = 'magento-flow-connector-development'
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
      when { branch 'master' }
      steps {
        container('docker') {
          script {
            docker.withRegistry('https://index.docker.io/v1/', 'jenkins-dockerhub') {
              image = docker.build("$DOCKER_ORG/$APP_NAME:$IMAGE_TAG", '-f Dockerfile.dev .')
              image.push()
            }
          }
        }
      }
    }

    stage('Deploy Helm chart') {
      when { branch 'master' }
      steps {
        container('helm') {
          sh('helm init --client-only')
          sh("helm upgrade --wait --namespace production --set deployments.live.version=$IMAGE_TAG -i $APP_NAME ./deploy/$APP_NAME")
        }
      }
    }
  }
}

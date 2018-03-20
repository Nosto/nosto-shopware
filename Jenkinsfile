#!/usr/bin/env groovy

pipeline {

  agent { dockerfile true }

  stages {
    stage('Prepare environment') {
      steps {
        checkout scm
      }
    }

  post {
    always {
      checkstyle pattern: 'chk*.xml', unstableTotalAll:'0'
      pmd pattern: 'pmd*.xml', unstableTotalAll:'0'
      deleteDir()
    }
  }
}

#!/usr/bin/env groovy

pipeline {

  agent { dockerfile true }

  stages {
    stage('Prepare environment') {
      steps {
        checkout scm
      }
    }

    stage('Update Dependencies') {
      steps {
        sh "composer install --no-progress --no-suggest"
        sh "composer dump-autoload --optimize"
      }
    }

    stage('Code Sniffer') {
      steps {
        catchError {
          sh "./vendor/bin/phpcs --standard=ruleset.xml --severity=3 --report=checkstyle --report-file=chkphpcs.xml . || true"
        }
        archiveArtifacts 'chkphpcs.xml'
      }
    }

    stage('Copy-Paste Detection') {
      steps {
        catchError {
          sh "./vendor/bin/phpcpd --exclude=vendor --exclude=build --log-pmd=phdpcpd.xml . "
        }
        archiveArtifacts 'phdpcpd.xml'
      }
    }

    stage('Package') {
      steps {
        script {
          sh "./vendor/bin/phing -Dversion=2.3.2"
        }
        archiveArtifacts "build/package/NostoTagging-2.3.2.zip"
      }
    }

    stage('Mess Detection') {
      steps {
        catchError {
          sh "./vendor/bin/phpmd . xml codesize,naming,unusedcode,controversial,design --exclude vendor,var,build,tests --reportfile pmdphpmd.xml || true"
        }
        archiveArtifacts 'pmdphpmd.xml'
      }
    }

    stage('Phan Analysis') {
      steps {
        catchError {
          sh "./vendor/bin/phan --config-file=phan.php --output-mode=checkstyle --output=chkphan.xml || true"
        }
        archiveArtifacts 'chkphan.xml'
      }
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

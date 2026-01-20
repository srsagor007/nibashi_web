pipeline {
    agent any
    environment {
        STAGING_SERVER_SSH_USER = 'dev_one'
        PRODUCTION_SERVER_SSH_USER = 'dev_one'
        STAGING_SERVER = 'dev.appinionbd.com'
        PRODUCTION_SERVER = 'dev.appinionbd.com'
        SSH_CREDENTIAL_ID_STAGING = 'appinion-dev' // Jenkins SSH credential ID Staging
        SSH_CREDENTIAL_ID_PRODUCTION = 'appinion-dev' // Jenkins SSH credential ID Production
    }
    parameters {
        // string(name: 'BRANCH', defaultValue: 'master', description: 'Git branch to deploy')
        choice(name: 'ENVIRONMENT', choices: ['staging', 'production'], description: 'Deployment environment')
        choice(name: 'BRANCH', choices: ['develop', 'master'], description: 'Git branch to deploy')
    }
    stages {
        stage('Build') {
            steps {
                echo 'Building the application...'
                // Add your build steps here (e.g., npm, mvn, etc.)
            }
        }
        stage('Deploy') {
            steps {
                script {
                    if (params.ENVIRONMENT == 'staging') {
                        deployToStaging()
                    } else if (params.ENVIRONMENT == 'production') {
                        deployToProduction()
                    } else {
                        error "Unknown environment: ${params.ENVIRONMENT}"
                    }
                }
            }
        }
    }
    post {
        always {
            echo 'Pipeline execution completed.'
        }
        success {
            echo 'Deployment successful!'
        }
        failure {
            echo 'Deployment failed.'
        }
    }
}

def deployToStaging() {
    echo "Deploying to the Staging server..."
    sshagent(credentials: [env.SSH_CREDENTIAL_ID_STAGING]) {
        sh """
            ssh -o StrictHostKeyChecking=no ${STAGING_SERVER_SSH_USER}@${STAGING_SERVER} << EOF
                cd /var/www/jenkins_lnd_test
                echo "Deploy to STAGING"  > sample.txt
EOF
        """
    }
}

def deployToProduction() {
    echo "Deploying to the Production server..."
    sshagent(credentials: [env.SSH_CREDENTIAL_ID_PRODUCTION]) {
        sh """
            ssh -o StrictHostKeyChecking=no $PRODUCTION_SERVER_SSH_USER@$PRODUCTION_SERVER << EOF
                cd /var/www/jenkins_lnd_test
                echo "Deploy to PRODUCTION"  > sample.txt
EOF
        """
    }
}

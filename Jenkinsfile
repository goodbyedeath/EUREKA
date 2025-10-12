pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                git branch: 'main', url: 'https://github.com/goodbyedeath/EUREKA.git'
            }
        }
        stage('Build') {
            steps {
                echo 'Building project...'
            }
        }
        stage('Deploy') {
            steps {
                echo 'Deploying to production...'
                // Tambahkan perintah deploy (misalnya SSH ke Hostinger via scp)
            }
        }
    }
}

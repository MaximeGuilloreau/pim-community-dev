#!groovy

stage 'Build'
node {
    step([$class: 'GitHubSetCommitStatusBuilder'])

    deleteDir()
    checkout scm
    stash "project_files"
}

stage 'Acceptance Tests'
userInput = input(message: 'Launch acceptance tests?', parameters: [
    [
        $class: 'ChoiceParameterDefinition',
        name: 'pim_version',
        choices: 'pim-enterprise-dev\npim-community-dev'
    ],
    [
        $class: 'ChoiceParameterDefinition',
        name: 'storage',
        choices: 'odm\norm',
        description: 'Storage used for the build, Mysql Or MongoDb'
    ],
    [
        $class: 'TextParameterDefinition',
        name: 'features',
        defaultValue: 'features,vendor/akeneo/pim-community-dev/features',
        description: 'Features directories to build'
    ],
    [
        $class: 'ChoiceParameterDefinition',
        name: 'priority',
        choices: '5\n1\n2\n3\n4\n6\n7\n8\n9',
        description: 'Smaller the better, (And no, that\'s not what she said)'
    ],
    [
        $class: 'ChoiceParameterDefinition',
        name: 'attempts',
        choices: '3\n1\n2\n4\n5'
    ],
    [
        $class: 'ChoiceParameterDefinition',
        name: 'php_version',
        choices: '5.6\n7.0',
        description: 'PHP version to run the tests with'
    ],
    [
        $class: 'ChoiceParameterDefinition',
        name: 'mysql_version',
        choices: '5.5\n5.7',
        description: 'MySQL version to run the tests with'
    ]
])

node {
    unstash "project_files"

    phpBinary = 'php'
    if ('5.6' != userInput['php_version']) {
        phpBinary += userInput['php_version']
    }

    sh "${phpBinary} /usr/local/bin/composer update -o -n --no-progress --prefer-dist --ignore-platform-reqs"

    sh "/usr/bin/php7.0 /var/lib/distributed-ci/dci-master/bin/build" +
        " -p " + userInput['php_version'] +
        " -m " + userInput['mysql_version'] +
        " " + env.WORKSPACE +
        " " + env.BUILD_NUMBER +
        " " + userInput['pim_version'] +
        " " + userInput['storage'] +
        " " + userInput['features'] +
        " pim-community-dev/job/" + env.JOB_BASE_NAME +
        " " + userInput['attempts']
}

stage 'Results'
node {
    step([$class: 'ArtifactArchiver', allowEmptyArchive: true, artifacts: 'app/build/screenshots/*.png,app/build/logs/consumer/*.log', defaultExcludes: false, excludes: null])
    step([$class: 'JUnitResultArchiver', testResults: 'app/build/phpunit.xml, app/build/phpspec.xml, app/build/logs/behat/*.xml'])
    step([$class: 'GitHubCommitStatusSetter', resultOnFailure: 'FAILURE', statusMessage: [content: 'Build finished']])
}

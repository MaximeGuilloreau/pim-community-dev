#!groovy

import groovy.json.JsonOutput
import groovy.json.JsonSlurper

stage 'Build'
node {
    step([$class: 'GitHubSetCommitStatusBuilder'])

    deleteDir()
}

stage 'Acceptance Tests'
userInput = input(message: 'Launch acceptance tests?', parameters: [
    [
        $class: 'ChoiceParameterDefinition',
        name: 'pim_version',
        choices: 'pim-enterprise-dev\npim-community-dev'
    ],
    [
        $class: 'TextParameterDefinition',
        name: 'ee_branch',
        defaultValue: '1.4',
        description: 'Enterprise Edition branch used for the build (useless if "pim_version" is set on "pim-community-dev")'
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
    eeBranch = userInput['ee_branch'] ? userInput['ee_branch'] : '1.4'
    git url: 'https://github.com/akeneo/pim-enterprise-dev.git', branch: eeBranch

    phpBinary = 'php'
    if ('5.6' != userInput['php_version']) {
        phpBinary += userInput['php_version']
    }

    composerFile = readFile('composer.json')
    composerFile = updateComposerFile(composerFile, userInput['pim_version'], userInput['php_version'], env.BRANCH_NAME)
    writeFile file: 'composer.json', text: composerFile

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
    // The workers did not handle build interruption...

    step([$class: 'ArtifactArchiver', allowEmptyArchive: true, artifacts: 'app/build/screenshots/*.png,app/build/logs/consumer/*.log', defaultExcludes: false, excludes: null])
    step([$class: 'JUnitResultArchiver', testResults: 'app/build/logs/behat/*.xml'])
    step([$class: 'GitHubCommitStatusSetter', resultOnFailure: 'FAILURE', statusMessage: [content: 'Build finished']])
}

/**
 * Updates the composer.json file according to pull request and user settings.
 *
 * @param String workspace
 * @param String edition
 * @param String phpVersion
 * @param String ceBranch
 *
 * @return String
 */
def static updateComposerFile(composerFile, edition, phpVersion, ceBranch)
{
    JsonSlurper jsonSlurper = new JsonSlurper();
    def parsedComposerFile = jsonSlurper.parseText(composerFile);

    if ('pim-enterprise-dev' == edition) {
        parsedComposerFile['require']['akeneo/pim-community-dev'] = 'dev-' + ceBranch;

        /**
         * We need to find a way to get the branch owner automatically. Sadly, env.BRANCH_OWNER does not exists...
         *
         * if ('akeneo' != env.BRANCH_OWNER) {
         *     if (!parsedComposerFile['repositories']) {
         *         parsedComposerFile['repositories'] = [];
         *     }
         *     parsedComposerFile['repositories'][] = [
         *         'type' => 'vcs',
         *         'url' => 'https://github.com/' + env.BRANCH_OWNER + '/pim-community-dev.git',
         *         'branch' => 'master',
         *     ];
         * }
         */
    }

    if ('7.0' == phpVersion) {
        parsedComposerFile['require-dev']['alcaeus/mongo-php-adapter'] = '1.0.*';
    }

    return JsonOutput.prettyPrint(JsonOutput.toJson(parsedComposerFile));
}

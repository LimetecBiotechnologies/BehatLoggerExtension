# BehatLoggerExtension

this package provides an extension for behat to log the test results in an json-file.
this package also provide commands to validate and merge this json files.

## Installation

> INFORMATION: if you want to use the behat logger extension for your project, please read the "PHP Integration" section!
> The installation section installs only the cli-commands as standalone application

For users is now a docker-image available. For developers see PHP Integration

## Migration from 1.x to 2.x

since the major release 2, the identification of tests for synchronization has changed.
In version 1 the title of the scenario was used for synchronization.
in this release, an id will used, given by an behat-tag.
every test requires an unique identifier tag. for example "@testrail-case-1,@testrail-case-2, e.t.c."
update your dependency to the last 1.x version and add the following properties to your .testrail.yml

```yml
api:
    ...
    identifier_tag_field: yourNewIdentifierField
```

execute the testrail:push:cases command to add all the ids to your cases in testrail.
Now you can update to 2.x (but some .testrail.yml properties has changed!!!)

### docker image

```bash
docker run -v /path/to/project/:/behat/ seretos/behat-logger-cli behat-logger-cli list
```

add the docker-container to your docker-compose.yml
```yml
...
  features-push:
    image: seretos/behat-logger-cli
    command: push
    volumes:
      - ./:/behat/
    environment:
      TESTRAIL_SERVER: http://testrail:80
      TESTRAIL_USER: yourTestrailLogin
      TESTRAIL_PASSWORD: yourTestrailPassword
      TESTRAIL_PROJECT: yourTestrailProject
      TESTRAIL_SUITE: yourTestrailSuite

  features-validate:
    image: seretos/behat-logger-cli
    command: validate
    volumes:
      - ./:/behat/
```

## PHP Integration

add the package to your project as below
```bash
composer require seretos/behat-logger-extension --dev
vendor/bin/behat-logger-cli --help
```

activate the logger in your behat.yml:

```yml
default:
    formatters:
        logger: ~
    extensions:
        seretos\BehatLoggerExtension\BehatLoggerExtension:
            output_path: '%paths.base%/build/behat'
```

> OPTIONAL: if you use an symfony application, you can add this extension (seretos\BehatLoggerExtension\BehatLoggerExtensionBundle) to your Symfony Kernel and integrate the commands in your cli

## Command line usage

combine different result jsons into one file and one suite:
```bash
behat-logger-cli combine:logs [suite-name] --output=/output/path/ --regex=results/firefox*
```
> if different jsons contain a testresult for one test with the same environment, this command throws an exception

convert all found feature-files to one json-file without results:
```bash
behat-logger-cli feature:to:log [suite-name] --output=/output/path/ --regex=features/
```

check that all scenarios in the log file has an unique title:
> This command is deprecated please use validate:scenario:id in future!
```bash
behat-logger-cli validate:scenario:title [log-file.json]
```

check that all schenarios in the log file has an unique id tag:
```bash
behat-logger-cli validate:scenario:id [log-file.json] --identifier_tag_regex="/^testrail-case-([0-9]*)$/"
```

check that all tests are executed in the given environments:
```bash
# check that all browserless tests are executed
behat-logger-cli validate:execution actual.json expected.json --tags=~javascript --environments=unknown
# check that all browser tests are executed in firefox and chrome
behat-logger-cli validate:execution actual.json expected.json --tags=javascript --environments=firefox --environments=chrome
```

send a json-result to testrail and create sections and cases
```bash
behat-logger-cli testrail:push:cases testRailSuiteName actual.json
```

send a json-result to testrail and create environment configurations:
```bash
behat-logger-cli testrail:push:configs actual.json
```

send a json-result to testrail and create results
```bash
behat-logger-cli testrail:push:results testRailSuiteName actual.json testResultName --milestone=v2.8.0
```

the commands testrail:push:cases and testrail:push:results requires an .testrail.yml in the current work directory with the following informations:
```yml
api:
  server: https://yourTestrail.testrail.io/
  user: yourUser@mail.de
  password: yourPassword
  project: youtProject
  template: Test Case (Steps)
  type: Automated
  title_field: custom_preconds
  group_field: custom_automation_type
  identifier_field: custom_identifier
  identifier_regex: /^testrail-case-([0-9]*)$/

# set field an priorities on specific tags
fields:
  /^.*$/:
    custom_automation_type: Behat

priorities:
  /^priority_low$/: Low
```

## log format

first of all, the json file contains the behat suite. if the log-writer can not detect the suite name, they use a suite named "default"

```json
{
  "suites": [
    {
      "name": "default",
      "features": {
        ...
      }
    }
  ]
}
```

the suite contains a list of features:

```json
"features": {
  "features\/featurefile.feature": {
    "title": "feature title",
    "filename": "features\/featurefile.feature",
    "description": null,
    "language": "en",
    "scenarios": {
      ...
    }
  },
  ...
}
```

and a feature contains scenarios with steps and results

```json
"scenarios": {
  "scenariotitle": {
    "title": "scenariotitle",
    "tags": ["behattag1","behattag2"],
    "steps": [
      {
        "line": 0,
        "text": "the user 'test' exists",
        "keyword": "Given",
        "arguments": []
      },
      {
        "line": 1,
        "text": "i logged in as 'test'",
        "keyword": "And",
        "arguments": []
      }
    ],
    "results": [
      ...
    ]
  },
  ...
}
```

and last but not least, contains the features results. the environment property is the browser name.
on guette the environment name is setted to "unknown"

```json
"results": [
"firefox": {
  "environment": "firefox",
  "duration": "1.00",
  "message": null,
  "stepResults": [
    {
      "line": 0,
      "passed": true,
      "screenshot": null,
      "message": null
    },
    {
      "line": 1,
      "passed": true,
      "screenshot": null,
      "message": null
    }
  ]
  }
]
```
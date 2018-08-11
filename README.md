# BehatLoggerExtension

this package provides an extension for behat to log the test results in an json-file.
this package also provide commands to validate and merge this json files.

## Installation

> INFORMATION: if you want to use the behat logger extension for your project, please read the "PHP Integration" section!
> The installation section installs only the cli-commands as standalone application

### npm installation

```bash
npm install behat-logger-cli -g
behat-logger-cli --help
```

### manual installation

1. download the latest behat-logger-cli.phar from the [github releases page](https://github.com/Seretos/BehatLoggerExtension/releases)
2. make the phar-file executable

```bash
chmod u+x behat-logger-cli.phar
php behat-logger-cli.phar --help
# optional:
mv behat-logger-cli.phar behat-logger-cli
mv behat-logger-cli /usr/local/bin/
behat-logger-cli --help
```

### docker image

```bash
docker run seretos/behat-logger-cli --help
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

TODO

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
  "duration": "1.00"
  "stepResults": [
    {
      "line": 0,
      "passed": true,
      "screenshot": null
    },
    {
      "line": 1,
      "passed": true,
      "screenshot": null
    }
  ]
  }
]
```
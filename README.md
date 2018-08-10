# BehatLoggerExtension

this package provides an extension for behat to log the test results in an json-file.
this package also provide commands to validate and merge this json files.

## Installation

> INFORMATION: if you want to use the behat logger extension for your project, please read the "PHP Integration" section!
> The installation section installs only the cli-commands as standalone application

### npm installation

TODO

### manual installation

TODO

## PHP Integration

add the package to your project as below
```bash
composer require seretos/behat-logger-extension --dev
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

TODO
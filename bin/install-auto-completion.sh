#!/bin/bash

script=$(readlink -f "$0")
scriptPath=$(dirname "$script")
projectPath=$(dirname "$scriptPath")

if [[ -x "$(command -v ${projectPath}/behat-logger-cli)" ]]; then
    ${projectPath}/behat-logger-cli _completion --generate-hook --shell-type=bash &>/dev/null
    if [[ $? -eq 0 ]]; then
        echo "install auto-completion for user `id -u -n`"
        echo "$(${projectPath}/behat-logger-cli _completion --generate-hook --shell-type=bash)" >> ~/.bashrc
    else
        echo "failed to generate cli auto completion"
    fi
fi
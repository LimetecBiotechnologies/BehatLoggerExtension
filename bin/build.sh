#!/bin/bash

dev="true"
script=$(readlink -f "$0")
scriptPath=$(dirname "$script")
projectPath=$(dirname "$scriptPath")

if [[ "$1" = "-h" ]] || [[ "$1" = "help" ]]; then
    echo -e "
This script download the application dependencies for you.
By default the command download also the dev dependencies. you can prevent this by adding the parameter dev=false

parameters:
-h | help           show help message
dev=[true/false]    download dev dependencies   default: $dev
"
    exit 0
fi

for ARGUMENT in "$@"
do
    KEY=$(echo ${ARGUMENT} | cut -f1 -d=)
    VALUE=$(echo ${ARGUMENT} | cut -f2 -d=)

    case "$KEY" in
            dev)        dev=${VALUE} ;;
            *)
    esac
done

if [[ "$dev" = "true" ]]; then
    composer update --working-dir=${projectPath}
else
    composer install --no-dev --working-dir=${projectPath}
    composer dump-autoload --no-dev --working-dir=${projectPath}
fi
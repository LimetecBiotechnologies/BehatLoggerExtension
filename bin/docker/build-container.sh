#!/bin/bash

script=$(readlink -f "$0")
dockerPath=$(dirname "$script")
scriptPath=$(dirname "$dockerPath")
projectPath=$(dirname "$scriptPath")

${scriptPath}/build.sh dev=false

rsync -a --progress ${projectPath}/ ${projectPath}/bin/docker/app/behat-logger-cli/ \
         --exclude bin/docker/ \
         --exclude=".[!.]*" \
         --exclude tests/ \
         --exclude bin/build.sh \
         --exclude docker-compose.yml \
         --exclude "*.dist" \
         --exclude features/

docker build --target=container -t seretos/behat-logger-cli ${dockerPath}/app/

rm -rf ${dockerPath}/app/behat-logger-cli
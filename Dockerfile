FROM php:7.2-cli-alpine

WORKDIR /behat

COPY behat-logger-cli.phar /usr/local/bin/
RUN mv /usr/local/bin/behat-logger-cli.phar /usr/local/bin/behat-logger-cli

ENTRYPOINT ["behat-logger-cli"]
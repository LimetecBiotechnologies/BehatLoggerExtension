ARG PHP_VERSION=7.2
ARG PHPUNIT_VERSION=7.5.1

# ----------------------------------
# staging environment
# ----------------------------------
FROM debian:stretch-slim AS stage
ARG PHP_VERSION

# add php repositories and install php
RUN apt-get update -y && \
    apt-get install -y curl wget lsb-release ca-certificates apt-transport-https sudo unzip gnupg mysql-client && \
    wget -q https://packages.sury.org/php/apt.gpg -O- | apt-key add - && \
    echo "deb https://packages.sury.org/php/ stretch main" | tee /etc/apt/sources.list.d/php.list && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list && \
    apt-get update -y && \
    apt-get install -y php${PHP_VERSION}-cli \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-curl \
        bash-completion git && \
    echo 'error_log = /dev/stderr' >> /etc/php/${PHP_VERSION}/cli/php.ini

# create environment user
RUN groupadd behat && \
    useradd --create-home --home-dir /home/behat/ --shell /bin/bash --gid behat behat && \
    echo "behat:behat" | chpasswd && \
    mkdir /behat && touch /behat/behat-logger-cli && \
    ln -s /behat/behat-logger-cli /usr/bin/behat-logger-cli && \
    rm /behat/behat-logger-cli

COPY .bash_aliases /home/behat/
RUN echo 'PS1=" $(echo -e "\xF0\x9F\x90\xB3")  \[\033[1;36m\]stage@\h\[\e[0;0m\]\n     \[\033[1;34m\][\u:\w]$\[\e[0;0m\] "' > /home/behat/.bashrc_custom

COPY validate /usr/bin/validate
COPY push /usr/bin/push

RUN chmod +x /usr/bin/validate && chmod +x /usr/bin/push

WORKDIR /behat
USER behat

# ----------------------------------
# ready to use container
# ----------------------------------
FROM stage AS container
USER root

COPY behat-logger-cli /usr/share/behat-logger-cli/
COPY .testrail.yml /home/behat/
RUN chmod +x /usr/share/behat-logger-cli/behat-logger-cli && \
    rm -rf /usr/bin/behat-logger-cli && \
    ln -s /usr/share/behat-logger-cli/behat-logger-cli /usr/bin/behat-logger-cli

WORKDIR /behat
USER behat
RUN behat-logger-cli _completion --generate-hook --shell-type=bash >> ~/.bashrc
CMD behat-logger-cli && bash

# ----------------------------------
# dev environment
# ----------------------------------
FROM stage AS dev
ARG PHPUNIT_VERSION
ARG PHP_VERSION
USER root

# install composer
RUN curl --silent --show-error https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    apt-get install -y bash && \
    wget https://github.com/box-project/box2/releases/download/2.7.5/box-2.7.5.phar && \
    mv box-2.7.5.phar /usr/local/bin/box && \
    chmod +x /usr/local/bin/composer && \
    chmod +x /usr/local/bin/box

# install phpunit + xdebug
RUN apt-get install -y  php-pear php${PHP_VERSION}-dev && \
    wget https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar && \
    mv phpunit-${PHPUNIT_VERSION}.phar phpunit.phar && \
    chmod +x phpunit.phar && \
    mv phpunit.phar /usr/local/bin/phpunit && \
    yes | pecl install xdebug && \
    echo "zend_extension=$(find /usr/lib/php -name xdebug.so)" > /etc/php/${PHP_VERSION}/cli/conf.d/20-xdebug.ini && \
    apt-get remove -y php${PHP_VERSION}-dev && apt-get -y autoremove

# install utility tools and enable remote debugging
RUN apt-get install -y net-tools vim inetutils-ping && \
    echo "xdebug.remote_enable=on" >> /etc/php/${PHP_VERSION}/cli/conf.d/20-xdebug.ini && \
    echo "xdebug.remote_autostart=off" >> /etc/php/${PHP_VERSION}/cli/conf.d/20-xdebug.ini && \
    echo 'phar.readonly = Off' >> /etc/php/${PHP_VERSION}/cli/php.ini

RUN echo 'PS1=" $(echo -e "\xF0\x9F\x90\xB3")  \[\033[1;36m\]dev@\h\[\e[0;0m\]\n     \[\033[1;34m\][\u:\w]$\[\e[0;0m\] "' > /home/behat/.bashrc_custom

# add labbase sudoers file
COPY sudoers /etc/sudoers.d/behat

USER behat
CMD /behat/bin/install-auto-completion.sh && bash
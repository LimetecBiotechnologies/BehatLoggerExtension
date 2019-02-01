alias php-xdebug='XDEBUG_CONFIG="remote_host=`echo ${HOSTIP}`" PHP_IDE_CONFIG="serverName=dev" php'
if [[ -f ~/.bashrc_custom ]]; then
    . ~/.bashrc_custom
fi

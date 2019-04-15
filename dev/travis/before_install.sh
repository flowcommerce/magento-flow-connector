#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR
echo '==> Adjusting memory limit to -1.'
echo 'memory_limit = -1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
echo '==> Disabling xdebug'
phpenv config-rm xdebug.ini
phpenv rehash;
echo '==> Install RabbitMQ Server'
apt install -y rabbitmq-server;

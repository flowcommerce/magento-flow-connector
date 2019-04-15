#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR
echo '==> Adjusting memory limit to -1.'
echo 'memory_limit = -1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
echo '==> Disabling xdebug'
phpenv config-rm xdebug.ini
phpenv rehash;
echo '==> Install RabbitMQ Server'
echo "deb http://www.rabbitmq.com/debian/ testing main"  | sudo tee  /etc/apt/sources.list.d/rabbitmq.list > /dev/null
wget https://www.rabbitmq.com/rabbitmq-signing-key-public.asc
sudo apt-key add rabbitmq-signing-key-public.asc
sudo apt-get update
sudo apt-get install rabbitmq-server -y
sudo service rabbitmq-server start
sudo rabbitmq-plugins enable rabbitmq_management
sudo service rabbitmq-server restart

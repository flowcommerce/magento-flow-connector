#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR
echo '==> Adjusting memory limit to -1.'
echo 'memory_limit = -1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
echo '==> Disabling xdebug'
phpenv config-rm xdebug.ini
phpenv rehash;
echo '==> Install RabbitMQ Server'
wget http://packages.erlang-solutions.com/ubuntu/erlang_solutions.asc
sudo apt-key add erlang_solutions.asc
sudo apt-get update
sudo apt-get install erlang
sudo apt-get install erlang-nox
sudo dpkg -i rabbitmq-server_3.2.1-1_all.deb

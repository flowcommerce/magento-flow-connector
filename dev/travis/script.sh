#!/usr/bin/env bash

cd $HOME/magento

php $HOME/magento/bin/magento app:config:dump
cat $HOME/magento/app/etc/config.php

# if [ "$TEST_SUITE" = "integration_core" ]; then
#     echo '==> Run Magento Core Integration tests.'
#     php bin/magento dev:tests:run integration -vvv
if [ "$TEST_SUITE" = "static_flow" ]; then
    echo '==> Configure PHP code sniffer'
    composer require "magento-ecg/coding-standard"
    php $HOME/magento/vendor/bin/phpcs --config-set installed_paths $HOME/magento/vendor/magento-ecg/coding-standard
    echo '==> Run PHP code sniffer'
    php $HOME/magento/vendor/bin/phpcs --standard=EcgM2 $HOME/magento/vendor/flowcommerce/flowconnector/ || true
    echo '==> Run PHP mess detector'
    php $HOME/magento/vendor/bin/phpmd $HOME/magento/vendor/flowcommerce/flowconnector text $HOME/magento/dev/tests/static/testsuite/Magento/Test/Php/_files/phpmd/ruleset.xml || true
elif [ "$TEST_SUITE" = "integration_flow" ]; then
    echo '==> Prepare Flow Connector integration tests.'
    cp $HOME/magento/vendor/flowcommerce/flowconnector/phpunit.xml.dist $HOME/magento/dev/tests/integration/phpunit.xml
    echo '==> Run Flow Connector integration tests.'
    php bin/magento dev:tests:run integration -vvv
fi;

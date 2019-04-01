#!/bin/bash -e

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

echo "Initilizing magento..."
echo "NAMI_DEBUG=$NAMI_DEBUG"
echo "NAMI_LOG_LEVEL=$NAMI_LOG_LEVEL"

usermod -aG sudo bitnami

. /init.sh
chown -R bitnami:daemon /opt/bitnami/magento/htdocs/var
chown -R bitnami:daemon /opt/bitnami/magento/htdocs/generated
chown -R bitnami:daemon /opt/bitnami/magento/htdocs/app/etc
echo -e '\nSetEnvIf X-Forwarded-Proto https HTTPS=on' >> .htaccess

nami_initialize apache php mysql-client libphp magento
info "Starting magento... "

php bin/magento maintenance:enable
php bin/magento app:config:dump
php bin/magento setup:static-content:deploy -f
php bin/magento module:enable --all
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
php bin/magento sampledata:deploy
php bin/magento maintenance:disable

find . -type d -print0 | xargs -0 chmod 775 && \
find . -type f -print0 | xargs -0 chmod 664 && \
chown -R bitnami:daemon /opt/bitnami/magento/htdocs

exec tini -- "$@"

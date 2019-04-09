#!/bin/bash -e
M2_ROOT="/opt/bitnami/magento/htdocs/"

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

echo "Initilizing magento..."
echo "NAMI_DEBUG=$NAMI_DEBUG"
echo "NAMI_LOG_LEVEL=$NAMI_LOG_LEVEL"

usermod -aG sudo bitnami

. /init.sh
chown -RH bitnami:daemon ${M2_ROOT}var
chown -RH bitnami:daemon ${M2_ROOT}generated
chown -RH bitnami:daemon ${M2_ROOT}app/etc
rm -rf ${M2_ROOT}var/di/* ${M2_ROOT}var/generation/* ${M2_ROOT}var/cache/* ${M2_ROOT}var/page_cache/* ${M2_ROOT}var/view_preprocessed/* ${M2_ROOT}var/composer_home/cache/*
php ${M2_ROOT}bin/magento config:set web/secure/base_url "https://$MAGENTO_BASE_URL/"
php ${M2_ROOT}bin/magento indexer:reindex
php ${M2_ROOT}bin/magento setup:di:compile
php ${M2_ROOT}bin/magento setup:static-content:deploy -f
php ${M2_ROOT}bin/magento cache:clean
php ${M2_ROOT}bin/magento cache:flush
echo -e '\nSetEnvIf X-Forwarded-Proto https HTTPS=on' >> .htaccess

nami_initialize apache php mysql-client libphp magento
info "Starting magento... "

exec tini -- "$@"

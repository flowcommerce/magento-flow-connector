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

php bin/magento config:set web/secure/base_url "https://$MAGENTO_BASE_URL/"
php bin/magento config:set web/unsecure/base_url "https://$MAGENTO_BASE_URL/"
php bin/magento config:set web/secure/use_in_frontend 1
php bin/magento config:set web/secure/use_in_adminhtml 1
php bin/magento indexer:reindex
php bin/magento cache:flush
info "Configuring magento... "

exec tini -- "$@"

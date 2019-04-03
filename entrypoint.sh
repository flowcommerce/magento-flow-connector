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

echo "Configuring magento... "
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/base_url \"https://$MAGENTO_BASE_URL/\""
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/unsecure/base_url \"https://$MAGENTO_BASE_URL/\""
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/use_in_frontend 1"
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/use_in_adminhtml 1"

echo "Re-apply permissions..."
find /opt/bitnami/magento/htdocs -type d -exec chmod 775 {} \;
find /opt/bitnami/magento/htdocs -type f -exec chmod 664 {} \;
rm -rf /opt/bitnami/magento/htdocs/var/cache/*
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento setup:di:compile"
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento cache:clean"
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento cache:flush"
chown -R bitnami:daemon /opt/bitnami/magento/htdocs

exec tini -- "$@"

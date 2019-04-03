#!/bin/bash -e

sudo su
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

su - bitnami
/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/base_url "https://$MAGENTO_BASE_URL/"
/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/unsecure/base_url "https://$MAGENTO_BASE_URL/"
/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/use_in_frontend 1
/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/use_in_adminhtml 1
/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento cache:flush 

sudo su
exec tini -- "$@"

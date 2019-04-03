#!/bin/bash -e

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

echo "Initilizing magento..."
echo "NAMI_DEBUG=$NAMI_DEBUG"
echo "NAMI_LOG_LEVEL=$NAMI_LOG_LEVEL"

usermod -aG sudo bitnami

. /init.sh
rm -rf /opt/bitnami/magento/htdocs/var/cache/*
find /opt/bitnami/magento/htdocs -type d -print0 | xargs -0 chmod 775
find /opt/bitnami/magento/htdocs -type f -print0 | xargs -0 chmod 664
chown -R bitnami:daemon /opt/bitnami/magento/htdocs/var \
                        /opt/bitnami/magento/htdocs/generated \
                        /opt/bitnami/magento/htdocs/app/etc
echo -e '\nSetEnvIf X-Forwarded-Proto https HTTPS=on' >> .htaccess
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento maintenance:disable"

nami_initialize apache php mysql-client libphp magento
info "Starting magento... "

echo "Configuring magento... "
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/base_url \"https://$MAGENTO_BASE_URL/\""
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/unsecure/base_url \"https://$MAGENTO_BASE_URL/\""
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/use_in_frontend 1"
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento config:set web/secure/use_in_adminhtml 1"
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento cache:clean"
runuser -l bitnami -c "/opt/bitnami/php/bin/php /opt/bitnami/magento/htdocs/bin/magento cache:flush"

exec tini -- "$@"

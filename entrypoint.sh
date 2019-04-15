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
echo -e '\nSetEnvIf X-Forwarded-Proto https HTTPS=on' >> .htaccess

nami_initialize apache php mysql-client libphp magento
info "Starting magento... "

exec tini -- "$@"

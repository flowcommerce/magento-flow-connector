#!/bin/bash -e
M2_ROOT="/opt/bitnami/magento/htdocs/"

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

if [[ "$1" == "nami" && "$2" == "start" ]] || [[ "$1" == "/run.sh" ]]; then
    echo -e "\nSetEnvIf X-Forwarded-Proto https HTTPS=on" >> /opt/bitnami/apache/conf/vhosts/htaccess/magento-htaccess.conf
    echo -e "\nServerName $MAGENTO_BASE_URL" >> /opt/bitnami/apache/conf/vhosts/htaccess/magento-htaccess.conf
    . /magento-init.sh

    nami_initialize apache php mysql-client libphp magento
    info "Starting magento... "
fi

exec tini -- "$@"

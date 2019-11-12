#!/bin/bash -e
M2_ROOT="/opt/bitnami/magento/htdocs/"

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

if [[ "$1" == "nami" && "$2" == "start" ]] || [[ "$1" == "/run.sh" ]]; then
    echo -e "SetEnvIf X-Forwarded-Proto https HTTPS=on\n
ServerName $MAGENTO_BASE_URL" >> /opt/bitnami/apache/conf/httpd.conf

    . /magento-init.sh
    nami_initialize apache php mysql-client libphp magento
    info "Starting magento... "
fi

exec tini -- "$@"

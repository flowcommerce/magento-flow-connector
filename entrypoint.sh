#!/bin/bash -e
M2_ROOT="/opt/bitnami/magento/htdocs/"

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

if [[ "$1" == "nami" && "$2" == "start" ]] || [[ "$1" == "/run.sh" ]]; then
    . /apache-init.sh
    . /magento-init.sh
    nami_initialize apache php mysql-client magento
    chown -RH bitnami:daemon ${M2_ROOT}var
    chown -RH bitnami:daemon ${M2_ROOT}generated
    chown -RH bitnami:daemon ${M2_ROOT}app/etc
    echo -e '\nSetEnvIf X-Forwarded-Proto https HTTPS=on' >> .htaccess

    nami_initialize apache php mysql-client libphp magento
    info "Starting magento... "
fi

exec tini -- "$@"

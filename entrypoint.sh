#!/bin/bash -e
M2_ROOT="/opt/bitnami/magento/htdocs/"

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

if [[ "$1" == "nami" && "$2" == "start" ]] || [[ "$1" == "/run.sh" ]]; then
    . /apache-init.sh
    . /magento-init.sh

    find ${M2_ROOT} -type d -print0 | xargs -0 chmod 775
    find ${M2_ROOT} -type f -print0 | xargs -0 chmod 664
    chown -RH bitnami:daemon ${M2_ROOT}
    echo -e '\nSetEnvIf X-Forwarded-Proto https HTTPS=on' >> .htaccess

    nami_initialize apache php mysql-client libphp magento
    info "Starting magento... "
fi

exec tini -- "$@"

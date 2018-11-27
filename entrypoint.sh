#!/bin/bash -e

. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

if [[ "$1" == "nami" && "$2" == "start" ]] || [[ "$1" == "httpd" ]]; then
  . /init.sh
  usermod -aG sudo bitnami
  chown -R bitnami:daemon /opt/bitnami/magento/htdocs/var
  chown -R bitnami:daemon /opt/bitnami/magento/htdocs/generated
  chown -R bitnami:daemon /opt/bitnami/magento/htdocs/app/etc
  echo -e '\nSetEnvIf X-Forwarded-Proto https HTTPS=on' >> .htaccess

  nami_initialize apache php mysql-client libphp magento
  info "Starting magento... "
fi

exec tini -- "$@"
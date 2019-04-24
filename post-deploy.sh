#!/bin/bash -e
M2_ROOT="/opt/bitnami/magento/htdocs/"

rm -rf \
    ${M2_ROOT}var/di/* \
    ${M2_ROOT}var/generation/* \
    ${M2_ROOT}var/generated/* \
    ${M2_ROOT}generated/* \
    ${M2_ROOT}var/cache/* \
    ${M2_ROOT}var/page_cache/* \
    ${M2_ROOT}var/view_preprocessed/* \
    ${M2_ROOT}var/composer_home/cache/*

cp ${M2_ROOT}vendor/magento/module-cms-sample-data/fixtures/styles.css ${M2_ROOT}pub/media/styles.css

php ${M2_ROOT}bin/magento config:set web/unsecure/base_url "https://$MAGENTO_BASE_URL/"
php ${M2_ROOT}bin/magento indexer:reindex
php ${M2_ROOT}bin/magento setup:di:compile
php ${M2_ROOT}bin/magento setup:static-content:deploy -f
php ${M2_ROOT}bin/magento cache:clean
php ${M2_ROOT}bin/magento cache:flush

find ${M2_ROOT} -type d -print0 | xargs -0 chmod 775
find ${M2_ROOT} -type f -print0 | xargs -0 chmod 664
chown -R bitnami:daemon ${M2_ROOT}

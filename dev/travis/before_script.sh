#!/usr/bin/env bash
set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

echo "==> Installing Magento 2 CE (Version $MAGENTO_VERSION) over composer create-project ..."
cd $HOME
composer create-project --repository-url="https://repo.magento.com/" "magento/project-community-edition:$MAGENTO_VERSION" magento
cd $HOME/magento

if [ "$TRAVIS_TAG" != "" ]; then
    cd $HOME/build/$GITHUB_ORGANIZATION_NAME/$GITHUB_REPOSITORY_NAME
    REAL_BRANCH=$(git ls-remote origin | sed -n "\|$TRAVIS_COMMIT\s\+refs/heads/|{s///p}")
    if [ "$REAL_BRANCH" == "" ]; then
        build_branch="dev-master#$TRAVIS_TAG"
    else
        build_branch="dev-$REAL_BRANCH#$TRAVIS_TAG"
    fi
    cd $HOME/magento
elif [ "$TRAVIS_PULL_REQUEST" != "false" ]; then
    build_branch="dev-$TRAVIS_PULL_REQUEST_BRANCH"
else
    build_branch="dev-$TRAVIS_BRANCH"
fi

echo "==> Requiring flowcommerce/flowconnector from the $build_branch branch"
composer config repositories.flowconnector git git@github.com:$GITHUB_ORGANIZATION_NAME/$GITHUB_REPOSITORY_NAME.git
composer config "allow-plugins.cweagans/composer-patches" true
composer config --json "extra.patches.magento/framework" '{"Magento 2 bug #33802 patch": "https://gist.githubusercontent.com/Marko-M/cddfcc65a4edf1b0c9ba7b6021977bad/raw/152a87ebba6cda29fc1883e3ae92dc535c6712da/gistfile1.txt"}'
composer require --no-plugins --no-interaction "flowcommerce/flowconnector:$build_branch"
composer require --no-interaction "cweagans/composer-patches"
composer update

curl https://gist.githubusercontent.com/Marko-M/6133134472d1619fb391539e9c4e26c1/raw/ecd93f118a9f53104e3d5d3d6199d5f5c57ef33a/gistfile1.txt > 35604.patch
patch -p1 < 35604.patch


if [ "$TEST_SUITE" != "static_flow" ]; then
    echo "==> Installing Magento 2"
    mysql -uroot -e 'CREATE DATABASE magento;'
    php bin/magento setup:install --base-url="http://$MAGENTO_HOST_NAME/" --backend-frontname=admin --db-host=127.0.0.1 --db-name=magento --db-user=root --admin-firstname=Magento --admin-lastname=User --admin-email=hi@flow.io --admin-user=admin --admin-password=admin123 --language=en_US --currency=USD --timezone=America/New_York --use-rewrites=1
    echo "==> Enable extension and compile magento..."
    php bin/magento module:enable FlowCommerce_FlowConnector
    php bin/magento setup:di:compile
fi

if [ "$TEST_SUITE" = "integration_core" ]; then
    echo '==> Prepare Magento Core integration tests.'
    cd dev/tests/integration
    test_set_list=$(find testsuite/* -maxdepth 1 -mindepth 1 -type d | sort)
    test_set_count=$(printf "$test_set_list" | wc -l)
    test_set_size[1]=$(printf "%.0f" $(echo "$test_set_count*0.12" | bc))  #12%
    test_set_size[2]=$(printf "%.0f" $(echo "$test_set_count*0.32" | bc))  #32%
    test_set_size[3]=$((test_set_count-test_set_size[1]-test_set_size[2])) #56%
    echo "Total = ${test_set_count}; Batch #1 = ${test_set_size[1]}; Batch #2 = ${test_set_size[2]}; Batch #3 = ${test_set_size[3]};";

    echo "==> preparing integration testsuite on index $INTEGRATION_INDEX with set size of ${test_set_size[$INTEGRATION_INDEX]}"
    cp phpunit.xml.dist phpunit.xml

    # remove memory usage tests if from any set other than the first
    if [[ $INTEGRATION_INDEX > 1 ]]; then
        echo "  - removing testsuite/Magento/MemoryUsageTest.php"
        perl -pi -0e 's#^\s+<!-- Memory(.*?)</testsuite>\n##ims' phpunit.xml
    fi

    # divide test sets up by indexed testsuites
    i=0; j=1; dirIndex=1; testIndex=1;
    for test_set in $test_set_list; do
        test_xml[j]+="            <directory suffix=\"Test.php\">$test_set</directory>\n"

        if [[ $j -eq $INTEGRATION_INDEX ]]; then
            echo "$dirIndex: Batch #$j($testIndex of ${test_set_size[$j]}): + including $test_set"
        else
            echo "$dirIndex: Batch #$j($testIndex of ${test_set_size[$j]}): + excluding $test_set"
        fi

        testIndex=$((testIndex+1))
        dirIndex=$((dirIndex+1))
        i=$((i+1))
        if [ $i -eq ${test_set_size[$j]} ] && [ $j -lt $INTEGRATION_SETS ]; then
            j=$((j+1))
            i=0
            testIndex=1
        fi
    done

    # temporarily adding excludes on failing tests
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/ConfigurableImportExport/Model/Import/Product/Type/ConfigurableTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Framework/Api/ExtensionAttribute/JoinProcessorTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Quote/Model/QuoteRepositoryTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Sales/Model/AdminOrder/CreateTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/InstantPurchase/Model/InstantPurchaseTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Setup/Model/ObjectManagerProviderTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Usps/Api/GuestCouponManagementTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Setup/Console/Command/GenerateFixturesCommandTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Setup/Model/FixtureGenerator/ProductGeneratorTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Setup/Fixtures/FixtureModelTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Ui/Component/ConfigurationTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Deploy/Console/Command/App/ApplicationDumpCommandTest.php</exclude>\n"
    test_xml[$INTEGRATION_INDEX]+="            <exclude>testsuite/Magento/Ups/Model/CarrierTest.php</exclude>\n"

    # replace test sets for current index into testsuite
    perl -pi -e "s#\s+<directory.*>testsuite</directory>#${test_xml[INTEGRATION_INDEX]}#g" phpunit.xml
    cat phpunit.xml

    echo "==> testsuite preparation complete"

    cd ../../..
fi

# create integration database and move integration db configuration file into place
mysql -uroot -e '
    SET @@global.sql_mode = NO_ENGINE_SUBSTITUTION;
    CREATE DATABASE magento_integration_tests;
'
cp ./vendor/flowcommerce/flowconnector/install-config-mysql.travis.php.dist dev/tests/integration/etc/install-config-mysql.php

<?php

namespace FlowCommerce\FlowConnector\Model\FixtureGenerator\ProductGenerator;

class Handler
{
    /**
     *
     * Work around "Integrity constraint violation: 1062 Duplicate entry '1-template_sku534221971' for key
     * 'FLOW_CONNECTOR_SYNC_SKUS_STORE_ID_SKU'" issue with
     * Magento\Setup\Fixtures\FixtureModelTest::testFixtureGeneration.
     *
     * Entity generator works by creating a single product with profiler enabled, and then pulling all of the data
     * required to create product from profiler, after which original product gets deleted. All of the subsequent
     * products are then created using raw INSERT queries using data pulled from profiler for performance reasons.
     *
     * This does not work with our code because we create entry in flow_connector_sync_sku upon creating/deleting
     * any product, and we do not use foreign key constraint with cascade option to remove such entry since we cleanup
     * manually after making sure that product has been removed from Flow as well. This means that deleting the original
     * product leaves entry inside flow_connector_sync_sku, something that produces "Integrity constraint violation"
     * error for first entry being created using raw INSERT queries later on. We work around by adjusting sku bind value
     * for first product created using raw INSERT queries to dummy sku. This does not have practical downsides besides
     * one extra dummy entry in flow_connector_sync_sku table.
     *
     * @param $entityId
     * @param $entityNumber
     * @param $fixtureMap
     * @param $binds
     * @return mixed
     */
    public function __invoke($entityId, $entityNumber, $fixtureMap, $binds)
    {
        if($entityId === 1) {
            $binds[0]['sku'] = $binds[0]['sku'] . '_dummy_entry';
        }
        return $binds;
    }
}
<?php

namespace FlowCommerce\FlowConnector\Api;

/**
 * Interface FlowCartManagementInterface
 * @package FlowCommerce\FlowConnector\Api
 */
interface FlowCartManagementInterface
{
    /**
     * Retrieve cart data from session in progress
     * @return array|null
     */
    public function getFlowCartData();

    /**
     * Sync cart data
     * @return mixed
     */
    public function syncCartData();
}


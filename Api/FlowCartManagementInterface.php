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
     * Retrieve experience key from flow cart information
     * @return mixed
     */
    public function getFlowCartExperienceKey();

}
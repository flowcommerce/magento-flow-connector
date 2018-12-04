<?php

namespace FlowCommerce\FlowConnector\Api\Data;

/**
 * Interface DiscountInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface DiscountEntitlementInterface
{
    /**
     * @return string
     */
    public function getEntitlementKey();

    /**
     * @return \FlowCommerce\FlowConnector\Api\Data\DiscountOfferFormInterface
     */
    public function getOfferForm();
}

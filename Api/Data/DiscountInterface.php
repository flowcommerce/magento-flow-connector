<?php

namespace FlowCommerce\FlowConnector\Api\Data;

/**
 * Interface DiscountInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface DiscountInterface
{
    /**
     * @param float $amount
     * @param string $currency
     * @return $this
     */
    public function addSubtotalDiscount($amount, $currency);

    /**
     * @return \FlowCommerce\FlowConnector\Api\Data\DiscountEntitlementFormInterface
     */
    public function getOrderForm();
}

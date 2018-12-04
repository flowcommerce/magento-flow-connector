<?php

namespace FlowCommerce\FlowConnector\Api\Data;

/**
 * Interface DiscountInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface DiscountEntitlementFormInterface
{
    /**
     * @param string $discriminator
     * @param string $entitlementKey
     * @param float $amount
     * @param string $currency
     * @return $this
     */
    public function addEntitlement($discriminator, $entitlementKey, $amount, $currency);

    /**
     * @return \FlowCommerce\FlowConnector\Api\Data\DiscountEntitlementInterface[]
     */
    public function getOrderEntitlementForms();
}

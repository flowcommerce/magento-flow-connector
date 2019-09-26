<?php
namespace FlowCommerce\FlowConnector\Model\Discount;

use FlowCommerce\FlowConnector\Api\Data\DiscountEntitlementFormInterface;

class EntitlementForm implements DiscountEntitlementFormInterface
{
    public function __construct()
    {
        $this->order_entitlement_forms = [];
    }

    /**
     * @param string $discriminator
     * @param string $entitlementKey
     * @param float $amount
     * @param string $currency
     * @return $this
     */
    public function addEntitlement($discriminator, $entitlementKey, $amount, $currency)
    {
        $this->order_entitlement_forms[] = new Entitlement($discriminator, $entitlementKey, $amount, $currency);
    }

    /**
     * @return \FlowCommerce\FlowConnector\Api\Data\DiscountEntitlementInterface[]
     */
    public function getOrderEntitlementForms()
    {
        return $this->order_entitlement_forms;
    }
}

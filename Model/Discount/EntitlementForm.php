<?php
namespace FlowCommerce\FlowConnector\Model\Discount;

use \FlowCommerce\FlowConnector\Model\Discount\Entitlement;

class EntitlementForm
{
    public function __construct(){
        $this->order_entitlement_forms = [];
    }

    /**
     * @param string $discriminator
     * @param string $entitlementKey
     * @param float $amount
     * @param string $currency
     * @return $this
     */
    public function addEntitlement($discriminator, $entitlementKey, $amount, $currency) {
        $this->order_entitlement_forms[] = new Entitlement($discriminator, $entitlementKey, $amount, $currency);
    }
}

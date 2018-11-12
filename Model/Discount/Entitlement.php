<?php
namespace FlowCommerce\FlowConnector\Model\Discount;

use FlowCommerce\FlowConnector\Model\Discount\OfferForm;

class Entitlement
{
    public function __construct(
        string $discriminator,
        string $entitlementKey,
        float $amount,
        string $currency
    ){
        $this->entitlement_key = $entitlementKey;
        $this->offer_form = new OfferForm($discriminator, $amount, $currency);
    }

    /**
     * @return string
     */
    public function getEntitlementKey () {
        return $this->entitlement_key;
    }

    /**
     * @return \FlowCommerce\FlowConnector\Model\Discount\OfferForm
     */
    public function getOfferForm () {
        return $this->offer_form;
    }
}


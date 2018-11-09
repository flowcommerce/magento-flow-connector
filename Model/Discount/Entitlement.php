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
}


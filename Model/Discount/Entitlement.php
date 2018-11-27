<?php
namespace FlowCommerce\FlowConnector\Model\Discount;

use FlowCommerce\FlowConnector\Api\Data\DiscountEntitlementInterface;

class Entitlement implements DiscountEntitlementInterface
{
    public function __construct(
        string $discriminator,
        string $entitlementKey,
        float $amount,
        string $currency
    ) {
        $this->entitlement_key = $entitlementKey;
        $this->offer_form = new OfferForm($discriminator, $amount, $currency);
    }

    /**
     * @return string
     */
    public function getEntitlementKey()
    {
        return $this->entitlement_key;
    }

    /**
     * @return \FlowCommerce\FlowConnector\Api\Data\DiscountOfferFormInterface
     */
    public function getOfferForm()
    {
        return $this->offer_form;
    }
}


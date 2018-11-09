<?php
namespace FlowCommerce\FlowConnector\Model\Discount;

class OfferForm
{
    public function __construct(
        string $discriminator,
        float $amount,
        string $currency
    ){
        $this->discriminator = $discriminator;
        $this->amount = $amount;
        $this->currency = $currency;
    }
}

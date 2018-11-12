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

    /**
     * @return string
     */
    public function getDiscriminator () {
        return $this->discriminator;
    }

    /**
     * @return float
     */
    public function getAmount () {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrency () {
        return $this->currency;
    }
}

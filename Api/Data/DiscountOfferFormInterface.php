<?php

namespace FlowCommerce\FlowConnector\Api\Data;

/**
 * Interface DiscountInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface DiscountOfferFormInterface
{
    /**
     * @return string
     */
    public function getDiscriminator();

    /**
     * @return float
     */
    public function getAmount();

    /**
     * @return string
     */
    public function getCurrency();
}


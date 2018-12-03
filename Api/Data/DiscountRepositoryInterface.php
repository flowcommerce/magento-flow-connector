<?php

namespace FlowCommerce\FlowConnector\Api\Data;

/**
 * Discount interface
 * @api
 */
interface DiscountRepositoryInterface
{
    /**
     * Discount
     *
     * @param mixed $order
     * @param string $code
     *
     * @return \FlowCommerce\FlowConnector\Api\Data\DiscountInterface
     * @throws \FlowCommerce\FlowConnector\Exception\DiscountException
     */
    public function getDiscount($order, $code);
}

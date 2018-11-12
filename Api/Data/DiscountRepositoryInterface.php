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
     * @return \FlowCommerce\FlowConnector\Model\Discount
     */
    public function getDiscount($order,$code);
}

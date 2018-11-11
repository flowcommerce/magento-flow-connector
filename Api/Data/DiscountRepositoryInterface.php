<?php

namespace FlowCommerce\FlowConnector\Api\Data;

/**
 * Interface DiscountRepositoryInterface
 * @package FlowCommerce\FlowConnector\Api\Data
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

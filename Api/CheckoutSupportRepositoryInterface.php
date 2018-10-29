<?php
namespace FlowCommerce\FlowConnector\Api;
/**
 * Interface CheckoutSupportRepositoryInterface
 * @package FlowCommerce\FlowConnector\Api
 */
interface CheckoutSupportRepositoryInterface
{
    /**
     * @param mixed $order
     * @return mixed
     */
    public function discountRequest($order);
}

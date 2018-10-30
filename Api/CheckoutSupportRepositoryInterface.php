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
     * @param string $code
     * @return mixed
     */
    public function discountRequest($order,$code);
}

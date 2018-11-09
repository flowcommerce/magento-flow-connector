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
     * @return \FlowCommerce\FlowConnector\Model\Discount
     */
    public function discountRequest($order,$code);
}

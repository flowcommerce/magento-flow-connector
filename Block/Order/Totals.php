<?php

namespace FlowCommerce\FlowConnector\Block\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Block\Order\Totals as SalesTotals;
use LogicException;

/**
 * @package FlowCommerce\FlowConnector\Block\Order
 */
class Totals extends SalesTotals
{
    /**
     * @return $this
     * @throws LocalizedException
     * @throws LocalizedException
     * @throws LogicException
     */
    protected function _initTotals()
    {
        $return = parent::_initTotals();

        $order = $this->getOrder();
        if($order && $order->getId()) {
            $paymentMethod = $order->getPayment()->getMethod();
            if ($paymentMethod === 'flowpayment') {
                if(isset($this->_totals['base_grandtotal'])) {
                    unset($this->_totals['base_grandtotal']);
                }
            }
        }

        return $return;
    }
}

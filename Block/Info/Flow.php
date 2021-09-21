<?php

namespace FlowCommerce\FlowConnector\Block\Info;

use FlowCommerce\FlowConnector\Model\WebhookEvent;
use FlowCommerce\FlowConnector\Model\Payment\FlowPaymentMethod;

/**
 * Flow payment info block
 */
class Flow extends \Magento\Payment\Block\Info
{
    /**
     * Add reference id to payment method information
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $info = $this->getInfo();

        $transportData = [];

        $flowPaymentReference = $info->getAdditionalInformation(
            WebhookEvent::FLOW_PAYMENT_REFERENCE
        );
        if ($flowPaymentReference) {
            $transportData[(string)__('Flow Payment Reference')] = $flowPaymentReference;
        }

        $flowPaymentType = $info->getAdditionalInformation(
            WebhookEvent::FLOW_PAYMENT_TYPE
        );
        // Paypal payment type is confusing (online), we'll hide it from the order detail page
        if ($flowPaymentType && ($flowPaymentType !== FlowPaymentMethod::PAYMENT_CODE_TYPE_PAYPAL)) {
            $transportData[(string)__('Flow Payment Type')] = $flowPaymentType;
        }

        $flowPaymentDescription = $info->getAdditionalInformation(
            WebhookEvent::FLOW_PAYMENT_DESCRIPTION
        );
        if ($flowPaymentDescription) {
            $transportData[(string)__('Flow Payment Description')] = $flowPaymentDescription;
        }

        $flowPaymentOrderNumber = $info->getAdditionalInformation(
            WebhookEvent::FLOW_PAYMENT_ORDER_NUMBER
        );
        if ($flowPaymentOrderNumber) {
            $transportData[(string)__('Flow Order Number')] = $flowPaymentOrderNumber;
        }

        $flowShippingEstimate = $info->getAdditionalInformation(
            WebhookEvent::FLOW_SHIPPING_ESTIMATE
        );
        if ($flowPaymentOrderNumber) {
            $transportData[(string)__('Flow Shipping Estimate')] = $flowShippingEstimate;
        }

        $flowShippingCarrier = $info->getAdditionalInformation(
            WebhookEvent::FLOW_SHIPPING_CARRIER
        );
        if ($flowShippingCarrier) {
            $transportData[(string)__('Flow Shipping Carrier')] = $flowShippingCarrier;
        }

        $flowShippingMethod = $info->getAdditionalInformation(
            WebhookEvent::FLOW_SHIPPING_METHOD
        );
        if ($flowShippingMethod) {
            $transportData[(string)__('Flow Shipping Method')] = $flowShippingMethod;
        }

        $transport = new \Magento\Framework\DataObject($transportData);
        $transport = parent::_prepareSpecificInformation($transport);

        return $transport;
    }
}

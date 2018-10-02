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

        $transport = new \Magento\Framework\DataObject($transportData);
        $transport = parent::_prepareSpecificInformation($transport);

        return $transport;
    }
}

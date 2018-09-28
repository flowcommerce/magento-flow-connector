<?php

namespace FlowCommerce\FlowConnector\Block\Info;

/**
 * Flow payment info block
 */
class Flow extends \Magento\Payment\Block\Info
{
    /**
     * Add reference id to payment method information
     *
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
            \FlowCommerce\FlowConnector\Model\WebhookEvent::FLOW_PAYMENT_REFERENCE
        );
        if($flowPaymentReference) {
            $transportData[(string)__('Flow Payment Reference')] = $flowPaymentReference;
        }

        $flowPaymentType = $info->getAdditionalInformation(
            \FlowCommerce\FlowConnector\Model\WebhookEvent::FLOW_PAYMENT_TYPE
        );
        if($flowPaymentType) {
            $transportData[(string)__('Flow Payment Type')] = $flowPaymentType;
        }

        $flowPaymentDescription = $info->getAdditionalInformation(
            \FlowCommerce\FlowConnector\Model\WebhookEvent::FLOW_PAYMENT_DESCRIPTION
        );
        if($flowPaymentDescription) {
            $transportData[(string)__('Flow Payment Description')] = $flowPaymentDescription;
        }

        $transport = new \Magento\Framework\DataObject($transportData);
        $transport = parent::_prepareSpecificInformation($transport);

        return $transport;
    }
}

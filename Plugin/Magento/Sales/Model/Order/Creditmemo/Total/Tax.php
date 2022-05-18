<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Sales\Model\Order\Creditmemo\Total;

use LogicException;
use DomainException;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo\Total\Tax as Subject;

/**
 * @package FlowCommerce\FlowConnector\Plugin\Magento\Sales\Model\Order\Creditmemo\Total
 */
class Tax
{
    /**
     * @param Subject $subject
     * @param Creditmemo $creditmemo
     * @return Creditmemo[]
     * @throws LogicException
     * @throws LocalizedException
     * @throws LocalizedException
     * @throws DomainException
     */
    public function beforeCollect(Subject $subject, Creditmemo $creditmemo)
    {
        $return = [$creditmemo];
        $entity = null;

        if(($invoice = $creditmemo->getInvoice()) && $invoice->getId()) {
            $entity = $invoice;
        }

        if(($order = $creditmemo->getOrder()) && $order->getId() && is_null($entity)) {
            $entity = $order;
        }

        if(!$entity || !$order) {
            return $return;
        }

        $taxAmount = (float)$entity->getTaxAmount();
        $baseTaxAmount = (float)$entity->getBaseTaxAmount();

        if($taxAmount && $baseTaxAmount) {
            return $return;
        }

        $orderFlowConnectorVat = $order->getFlowConnectorVat();
        $orderFlowConnectorBaseVat = $order->getFlowConnectorBaseVat();

        $orderFlowConnectorDuty = $order->getFlowConnectorDuty();
        $orderFlowConnectorBaseDuty = $order->getFlowConnectorBaseDuty();

        if(($orderFlowConnectorVat && $orderFlowConnectorBaseVat)) {
            $taxAmount = round($taxAmount + $orderFlowConnectorVat, 2);
            $baseTaxAmount = round($baseTaxAmount + $orderFlowConnectorBaseVat, 2);
        }

        if(($orderFlowConnectorDuty && $orderFlowConnectorBaseDuty)) {
            $taxAmount = round($taxAmount + $orderFlowConnectorDuty, 2);
            $baseTaxAmount = round($baseTaxAmount + $orderFlowConnectorBaseDuty, 2);
        }

        if($taxAmount && $baseTaxAmount) {
            if($entity instanceof InvoiceInterface) {
                $entity->setTaxAmount($taxAmount);
                $entity->setBaseTaxAmount($baseTaxAmount);
            }
            if($entity instanceof OrderInterface) {
                $entity->setTaxInvoiced($taxAmount);
                $entity->setBaseTaxInvoiced($baseTaxAmount);
            }
        }

        return $return;
    }
}

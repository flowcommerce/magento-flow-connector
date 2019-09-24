<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Quote\Model;

use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\QuoteValidator as Subject;

class QuoteValidator
{
    /**
     * Entirely disable quote validation for Flow orders
     *
     * @param Subject $subject
     * @param QuoteEntity $quote
     * @param callable $proceed
     * @return Subject
     */
    public function aroundValidateBeforeSubmit(Subject $subject, callable $proceed, QuoteEntity $quote)
    {
        $paymentMethod = $quote->getPayment()->getMethod();
        if ($paymentMethod !== 'flowpayment') {
            $returnValue = $proceed($quote);
        } else {
            $returnValue = $subject;
        }

        return $returnValue;
    }
}


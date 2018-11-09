<?php
namespace FlowCommerce\FlowConnector\Model;

use \FlowCommerce\FlowConnector\Model\Discount\EntitlementForm;

class Discount
{
    public function __construct(
        EntitlementForm $entitlementForm
    ){
        $this->order_form = $entitlementForm;
    }

    /**
     * @param float $amount
     * @param string $currency
     * @return $this
     */
    public function addSubtotalDiscount($amount, $currency) {
        $this->order_form->addEntitlement('discount_request_offer_fixed_amount_form', 'subtotal', $amount, $currency);
    }
}

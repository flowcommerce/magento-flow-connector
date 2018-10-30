<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\CheckoutSupportRepositoryInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class CheckoutSupportRepository
 * @package FlowCommerce\FlowConnector\Model
 */
class CheckoutSupportRepository implements CheckoutSupportRepositoryInterface
{
    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

     
    /**
     * @param $order
     * @param $code
     * @return mixed
     */
    public function discountRequest($order = false, $code = false)
    {
        $this->logger->info('Fired discountRequest');
        // TODO THIS ISNT THE RIGHT STRUCTURE, REFERENCE https://app.apibuilder.io/flow/experience-internal/0.6.27#model-discount_request_order_form
        $result = [
            "order_form" => [
                "order_entitlement_forms" => [
                    [
                        "entitlement_key" => [
                            "subtotal" => "subtotal"
                        ],
                        "offer_form" => [
                            "discriminator" => "discount_request_offer_fixed_amount_form",
                            "amount" => -10,
                            "currency" => "USD" 
                        ]
                    ]
                ]
            ]
        ];

        $this->logger->info(json_encode([$order,$code]));
        return json_encode($result);
    }
}

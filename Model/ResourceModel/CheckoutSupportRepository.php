<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\CheckoutSupportRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class CheckoutSupportRepository
 * @package FlowCommerce\FlowConnector\Model
 */
class CheckoutSupportRepository implements CheckoutSupportRepositoryInterface
{
    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     */
    public function __construct(
        JsonSerializer $jsonSerializer,
        Logger $logger
    ) {
        $this->jsonSerializer = $jsonSerializer;
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
        $data = $this->jsonSerializer->unserialize($order);

        // Check if order is present in payload
        if (!array_key_exists('order', $data)) {
            $this->logger->info('Order data not present in payload, skipping.');
            return;
        }

        $receivedOrder = $data['order'];

        if ($storeId = $this->getStoreId()) {
            $store = $this->storeManager->getStore($storeId);
        } else {
            $store = $this->storeManager->getStore();
        }
        $this->logger->info('Store: ' . $storeId);
        
        ////////////////////////////////////////////////////////////
        // Create quote
        ////////////////////////////////////////////////////////////

        $quote = $this->quoteFactory->create();
        $quote->setStoreId($store->getId());
        $quote->setQuoteCurrencyCode($receivedOrder['total']['currency']);
        $quote->setBaseCurrencyCode($receivedOrder['total']['base']['currency']);
        $quote->assignCustomer($customer);

        ////////////////////////////////////////////////////////////
        // Add order items
        // https://docs.flow.io/type/localized-line-item
        ////////////////////////////////////////////////////////////

        foreach($receivedOrder['lines'] as $line) {
            $this->logger->info('Looking up product: ' . $line['item_number']);
            $product = $this->productRepository->get($line['item_number']);
            $product->setPrice($line['price']['amount']);
            $product->setBasePrice($line['price']['base']['amount']);

            $this->logger->info('Adding product to quote: ' . $product->getSku());
            $quote->addProduct($product, $line['quantity']);
        }

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

        $this->logger->info(json_encode($order));
        $this->logger->info(json_encode($code));
        $this->logger->info(json_encode($result));
        return json_encode($result);
    }
}

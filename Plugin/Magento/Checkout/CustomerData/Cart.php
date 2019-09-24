<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Checkout\CustomerData;

use Magento\Checkout\CustomerData\Cart as CustomerDataCart;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Cart
 * @package FlowCommerce\FlowConnector\Plugin\Magento\Checkout\CustomerData
 */
class Cart
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Renderer constructor.
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Modify mini cart with custom data
     *
     * @param CustomerDataCart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetSectionData(CustomerDataCart $subject, $result)
    {
        try {
            if (isset($result['subtotal'])) {
                $subtotalFields['subtotal'] = $result['subtotal'];
            }
            if (isset($result['subtotal_incl_tax'])) {
                $subtotalFields['subtotal_incl_tax'] = $result['subtotal_incl_tax'];
            }
            if (isset($result['subtotal_excl_tax'])) {
                $subtotalFields['subtotal_excl_tax'] = $result['subtotal_excl_tax'];
            }

            foreach ($subtotalFields as $subtotalKey => $subtotalHtml) {
                $position = strpos($subtotalHtml, 'class="price"');
                $result[$subtotalKey] = substr_replace(
                    $subtotalHtml,
                    ' data-flow-localize="cart-subtotal" ',
                    $position, 0
                );
            }

            if (is_array($result['items'])) {
                foreach ($result['items'] as $key => $itemAsArray) {
                    $sku = $result['items'][$key]['product_sku'];
                    $qty = $result['items'][$key]['qty'];
                    $productPrice = $result['items'][$key]['product_price'];
                    $position = strpos($productPrice, 'class="price"');
                    $productPrice = substr_replace(
                        $productPrice,
                        ' data-flow-localize="cart-item-price" ',
                        $position,
                        0
                    );
                    $result['items'][$key]['product_price'] = '<span data-flow-cart-item-number="' .
                        $sku .
                        '" data-flow-cart-item-quantity="' .
                        $qty .
                        '">' .
                        $productPrice .
                        '</span>';
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Unable to localize mini cart due to %s', $e->getMessage()));
        }

        return $result;
    }
}

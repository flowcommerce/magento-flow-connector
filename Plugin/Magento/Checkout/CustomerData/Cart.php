<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Checkout\CustomerData;

use Magento\Customer\Model\Session;
use Magento\Checkout\CustomerData\Cart as CustomerDataCart;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\FlowCartManager;

/**
 * Class Cart
 * @package FlowCommerce\FlowConnector\Plugin\Magento\Checkout\CustomerData
 */
class Cart
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var FlowCartManager
     */
    private $flowCartManager;

    /**
     * Renderer constructor.
     * @param Session $checkoutSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param Logger $logger
     * @param FlowCartManager $flowCartManager
     */
    public function __construct(
        Session $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        Logger $logger,
        FlowCartManager $flowCartManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->flowCartManager = $flowCartManager;
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
            $this->logger->info('Pre-processing: '.json_encode($result));
            $subtotalFields = [
                'subtotal' => $result['subtotal'],
                'subtotal_incl_tax' => $result['subtotal_incl_tax'],
                'subtotal_excl_tax' => $result['subtotal_excl_tax'],
            ];

            foreach ($subtotalFields as $subtotalKey => $subtotalHtml) {
                $position = strpos($subtotalHtml, 'class="price"');
                $result[$subtotalKey] = substr_replace($subtotalHtml, ' data-flow-localize="cart-subtotal" ', $position, 0);
            }

            if (is_array($result['items'])) {
                foreach ($result['items'] as $key => $itemAsArray) {
                    $sku = $result['items'][$key]['product_sku'];
                    $qty = $result['items'][$key]['qty'];
                    $productPrice = $result['items'][$key]['product_price'];
                    $position = strpos($productPrice, 'class="price"');
                    $productPrice = substr_replace($productPrice, ' data-flow-localize="cart-item-price" ', $position, 0);
                    $result['items'][$key]['product_price'] = '<span data-flow-cart-item-number="'.$sku.'" data-flow-cart-item-quantity="'.$qty.'">'.$productPrice.'</span>';
                }
            }
            $this->logger->info('Post-processing: '.json_encode($result));
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Unable to localize mini cart due to %s', $e->getMessage()));
        }

        return $result;
    }
}

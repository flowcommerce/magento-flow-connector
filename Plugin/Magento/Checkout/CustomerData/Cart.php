<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Checkout\CustomerData;

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
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        Logger $logger,
        FlowCartManager $flowCartManager
    ) {
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
            $flowCart = $this->flowCartManager->getFlowCartData();
            if(!$flowCart) {
                $this->logger->info('Unable to localize mini cart because Magento cart is empty');

                return $result;
            }

            if(!isset($flowCart['prices']) || !is_array($flowCart['prices'])) {
                $this->logger->error('Unable to localize mini cart due to Flow cart being incomplete');

                return $result;
            }

            $subtotal = null;
            $currency = null;
            foreach ($flowCart['prices'] as $price) {
                if($price['key'] === 'subtotal') {
                    $subtotal = $price['amount'];
                    $currency = $price['currency'];
                }
                break;
            }

            if(!$subtotal || !$currency) {
                $this->logger->error('Unable to localize mini cart due to Flow cart missing subtotal or currency');

                return $result;
            }

            $result['subtotalAmount'] = $subtotal;
            $result['subtotal'] = $this->priceCurrency->format(
                $subtotal,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                null,
                $currency
            );
            $result['subtotal_incl_tax'] = $this->priceCurrency->format(
                $subtotal,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                null,
                $currency
            );
            $result['subtotal_excl_tax'] = $this->priceCurrency->format(
                $subtotal,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                null,
                $currency
            );
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Unable to localize mini cart due to %s', $e->getMessage()));
        }

        return $result;
    }

}

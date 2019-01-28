<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Checkout\Model;

use Exception;
use Magento\Checkout\Model\DefaultConfigProvider as CheckoutDefaultConfigProvider;
use Magento\Framework\Locale\FormatInterface as LocaleFormat;
use FlowCommerce\FlowConnector\Model\FlowCartManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class DefaultConfigProvider
 * @package FlowCommerce\FlowConnector\Plugin\Magento\Checkout\Model
 */
class DefaultConfigProvider
{
    /**
     * @var LocaleFormat
     */
    private $localeFormat;

    /**
     * @var FlowCartManager
     */
    private $flowCartManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * DefaultConfigProvider constructor.
     * @param LocaleFormat $localeFormat
     * @param FlowCartManager $flowCartManager
     * @param Logger $logger
     */
    public function __construct(
        LocaleFormat $localeFormat,
        FlowCartManager $flowCartManager,
        Logger $logger
    ) {
        $this->localeFormat = $localeFormat;
        $this->flowCartManager = $flowCartManager;
        $this->logger = $logger;
    }

    /**
     * @param CheckoutDefaultConfigProvider $subject
     * @param array $config
     * @return array
     */
    public function afterGetConfig(CheckoutDefaultConfigProvider $subject, array $config)
    {
        try {
            $flowCart = $this->flowCartManager->getFlowCartData();
            if(!$flowCart) {
                $this->logger->error('Unable to localize cart item due to inability to fetch Flow cart');

                return $config;
            }

            if(!isset($config['totalsData']) || !isset($flowCart['prices']) || !is_array($flowCart['prices'])) {
                $this->logger->error('Unable to localize cart item due to Flow cart being incomplete');

                return $config;
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
                $this->logger->error('Unable to localize cart item due to Flow cart missing subtotal or currency');

                return $config;
            }

            $config['totalsData'] = array_merge(
                $config['totalsData'],
                [
                    'subtotal' => $subtotal,
                    'base_subtotal' => $subtotal,
                    'subtotal_with_discount' => $subtotal,
                    'base_subtotal_with_discount' => $subtotal,
                    'tax_amount' => 0,
                    'base_tax_amount' => 0,
                    'shipping_amount' => 0,
                    'base_shipping_amount' => 0,
                    'shipping_tax_amount' => 0,
                    'base_shipping_tax_amount' => 0,
                    'discount_amount' => 0,
                    'base_discount_amount' => 0,
                    'grand_total' => $subtotal,
                    'base_grand_total' => $subtotal,
                    'shipping_discount_amount' => 0,
                    'base_shipping_discount_amount' => 0.0000,
                    'subtotal_incl_tax' => $subtotal,
                    'shipping_incl_tax' => 0,
                    'base_shipping_incl_tax' => 0,
                    'base_currency_code' => $currency,
                    'quote_currency_code' => $currency
                ]
            );

            foreach ($config['totalsData']['total_segments'] as &$segment) {
                if(isset($segment['code'])) {
                    switch ($segment['code']) {
                        case 'subtotal':
                            $segment['value'] = $subtotal;
                            break;
                        case 'shipping':
                            $segment['value'] = 0;
                            break;
                        case 'discount':
                            $segment['value'] = 0;
                            break;
                        case 'tax':
                            $segment['value'] = 0;
                            break;
                        case 'grand_total':
                            $segment['value'] = $subtotal;
                            break;
                    }
                }
            }

            $config['priceFormat'] = $this->localeFormat->getPriceFormat(
                null,
                $currency
            );

            $config['basePriceFormat'] = $this->localeFormat->getPriceFormat(
                null,
                $currency
            );
        } catch (Exception $e) {
            $this->logger->error(sprintf('Unable to localize cart totals due to exception %s', $e->getMessage()));
        }

        return $config;
    }

}
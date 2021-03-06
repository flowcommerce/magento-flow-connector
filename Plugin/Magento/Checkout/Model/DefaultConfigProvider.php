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
            if (!$flowCart) {
                $this->logger->error('Unable to localize cart item due to inability to fetch Flow cart');

                return $config;
            }

            if (!isset($config['totalsData']) || !isset($flowCart['prices']) || !is_array($flowCart['prices'])) {
                $this->logger->error('Unable to localize cart item due to Flow cart being incomplete');

                return $config;
            }

            $subtotal = null;
            $currency = null;
            foreach ($flowCart['prices'] as $price) {
                if ($price['key'] === 'subtotal') {
                    $subtotal = $price['amount'];
                    $currency = $price['currency'];
                }
            }

            if (!$subtotal || !$currency) {
                $this->logger->error('Unable to localize cart item due to Flow cart missing subtotal or currency');

                return $config;
            }

            $discountPct = 0;
            if (isset($config['totalsData']['discount_amount']) && isset($config['totalsData']['subtotal'])) {
                $discountPct = $config['totalsData']['discount_amount'] / $config['totalsData']['subtotal'];
            }

            $config['totalsData']['totals_flow'] = [
                'total_segments' => [
                    [
                        'code' => 'subtotal',
                        'value' => $this->localeFormat->getNumber($subtotal),
                    ],
                    [
                        'code' => 'shipping',
                        'value' => 0,
                    ],
                    [
                        'code' => 'discount',
                        'value' => $this->localeFormat->getNumber($subtotal * $discountPct),
                    ],
                    [
                        'code' => 'tax',
                        'value' => 0,
                    ],
                    [
                        'code' => 'grand_total',
                        'value' => $this->localeFormat->getNumber($subtotal * (1 + $discountPct)),
                    ],
                ],
                'subtotal' => $this->localeFormat->getNumber($subtotal),
                'base_subtotal' => $this->localeFormat->getNumber($subtotal),
                'subtotal_with_discount' => $this->localeFormat->getNumber($subtotal * (1 + $discountPct)),
                'base_subtotal_with_discount' => $this->localeFormat->getNumber($subtotal * (1 + $discountPct)),
                'tax_amount' => 0,
                'base_tax_amount' => 0,
                'shipping_amount' => 0,
                'base_shipping_amount' => 0,
                'shipping_tax_amount' => 0,
                'base_shipping_tax_amount' => 0,
                'discount_amount' => $this->localeFormat->getNumber($subtotal * $discountPct),
                'base_discount_amount' => $this->localeFormat->getNumber($subtotal * $discountPct),
                'grand_total' => $this->localeFormat->getNumber($subtotal * (1 + $discountPct)),
                'base_grand_total' => $this->localeFormat->getNumber($subtotal * (1 + $discountPct)),
                'shipping_discount_amount' => 0,
                'base_shipping_discount_amount' => 0,
                'subtotal_incl_tax' => $this->localeFormat->getNumber($subtotal),
                'shipping_incl_tax' => 0,
                'base_shipping_incl_tax' => 0,
                'base_currency_code' => $currency,
                'quote_currency_code' => $currency
            ];
            $this->logger->info(json_encode($config));
        } catch (Exception $e) {
            $this->logger->error(sprintf('Unable to localize cart totals due to exception %s', $e->getMessage()));
        }

        return $config;
    }
}

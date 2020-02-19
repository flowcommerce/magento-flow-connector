<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Framework\Pricing\Render;

use \FlowCommerce\FlowConnector\Model\Configuration;
use Psr\Log\LoggerInterface as Logger;

class PriceBox
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Configuration $configuration
     * @param Logger $logger
     */
    public function __construct(
        Configuration $configuration,
        Logger $logger
    ) {
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function afterRenderAmount(\Magento\Framework\Pricing\Render\PriceBox $priceBox, $result)
    {
        if (!$this->configuration->isCatalogPriceLocalizationEnabled() || !$this->configuration->isFlowEnabled()) {
            return $result;
        }
        $arguments = $priceBox->getData();

        $flowPriceCode = 'final_price';
        if (isset($arguments['price_type'])) {
            switch ($arguments['price_type']) {
                case 'regularPrice':
                    $flowPriceCode = 'regular_price';
                    break;

                case 'basePrice':
                    $flowPriceCode = 'base_price';
                    break;

                case 'finalPrice':
                    $flowPriceCode = 'final_price';
                    break;
            }
        }
        $saleableSku = $priceBox->getSaleableItem()->getSku();
        $position = strpos($result, 'class="price"');
        if ($flowPriceCode != 'final_price') {
            $result = substr_replace(
                $result,
                ' data-flow-localize="item-price-attribute" data-flow-item-price-attribute="'.$flowPriceCode.'" ',
                $position,
                0
            );
        } else {
            $result = substr_replace($result, ' data-flow-localize="item-price" ', $position, 0);
        }
        $taxDutyMessaging = '<span data-flow-localize="item-tax-duty-message"</span>';
        $result = '<span data-flow-item-number="'.$saleableSku.'">' . $result . $taxDutyMessaging . '</span>';
        return $result;
    }
}

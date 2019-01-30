<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Framework\Pricing\Render;

use \FlowCommerce\FlowConnector\Model\Configuration;

class PriceBox
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(     
        Configuration $configuration
    ) {
        $this->configuration = $configuration;
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
            $result = substr_replace($result, ' data-flow-localize="item-price-attribute" data-flow-item-price-attribute="'.$flowPriceCode.'" ', $position, 0);
        } else {
            $result = substr_replace($result, ' data-flow-localize="item-price" ', $position, 0);
        }
        $result = '<span data-flow-item-number="'.$saleableSku.'">' . $result . '</span>';
        return $result;
    }
}

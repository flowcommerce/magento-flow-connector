<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Swatches\Block\Product\Renderer\Listing;

use \FlowCommerce\FlowConnector\Model\Api\Item\Prices as FlowPrices;
use \FlowCommerce\FlowConnector\Model\Configuration;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class Configurable
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var FlowPrices
     */
    private $flowPrices;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param Configuration $configuration
     * @param FlowPrices $flowPrices
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(     
        Configuration $configuration,
        FlowPrices $flowPrices,
        JsonSerializer $jsonSerializer
    ) {
        $this->configuration = $configuration;
        $this->flowPrices = $flowPrices;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function afterGetPricesJson(\Magento\Swatches\Block\Product\Renderer\Listing\Configurable $configurable, $result)
    {
        $config = $this->jsonSerializer->unserialize($result);
        if (!$this->configuration->isCatalogPriceLocalizationEnabled()) {
            $config['flow_localization_enabled'] = false;
            return $this->jsonSerializer->serialize($config);
        }
        $ids = [];
        $product = $configurable->getProduct();
        $ids[] = $product->getId();
        if ($product->getTypeId() === 'configurable') {
            $relatedSimples = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($relatedSimples as $simple) {
                $ids[] = $simple->getId();
            }
        }
        $config['flow_localized_prices'] = $this->flowPrices->localizePrices($ids);
        return $this->jsonSerializer->serialize($config);
    }
}

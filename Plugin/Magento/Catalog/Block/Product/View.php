<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Catalog\Block\Product;

use \FlowCommerce\FlowConnector\Model\Api\Item\Prices as FlowPrices;
use \FlowCommerce\FlowConnector\Model\Configuration;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class View
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

    public function afterGetJsonConfig(\Magento\Catalog\Block\Product\View $view, $result)
    {
        $config = $this->jsonSerializer->unserialize($result);
        if (!$this->configuration->isCatalogPriceLocalizationEnabled() || !$this->configuration->isFlowEnabled()) {
            return $this->jsonSerializer->serialize($config);
        }
        $skus = [];
        $product = $view->getProduct();
        $skus[] = $product->getSku();
        if ($product->getTypeId() === 'configurable') {
            $relatedSimples = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($relatedSimples as $simple) {
                $skus[] = $simple->getSku();
            }
        }
        $config['flow_localized_prices'] = $this->flowPrices->localizePrices($skus);
        return $this->jsonSerializer->serialize($config);
    }
}

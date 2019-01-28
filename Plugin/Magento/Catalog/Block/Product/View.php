<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Catalog\Block\Product;

use \FlowCommerce\FlowConnector\Model\Api\Item\Prices as FlowPrices;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class View
{
    /**
     * @var FlowPrices
     */
    private $flowPrices;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param FlowPrices $flowPrices
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(     
        FlowPrices $flowPrices,
        JsonSerializer $jsonSerializer
    ) {
        $this->flowPrices = $flowPrices;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function afterGetJsonConfig(\Magento\Catalog\Block\Product\View $view, $result)
    {
        $ids = [];
        $product = $view->getProduct();
        $ids[] = $product->getId();
        if ($product->getTypeId() === 'configurable') {
            $relatedSimples = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($relatedSimples as $simple) {
                $ids[] = $simple->getId();
            }
        }
        $config = $this->jsonSerializer->unserialize($result);
        $labelsKeyedOnExperienceCountryCurrency = $this->flowPrices->localizePrices($ids);
        if ($labelsKeyedOnExperienceCountryCurrency) {
            $config['flow_localized_prices'] = $labelsKeyedOnExperienceCountryCurrency;
        }
        return $this->jsonSerializer->serialize($config);
    }
}

<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Swatches\Block\Product\Renderer\Listing;

use \FlowCommerce\FlowConnector\Model\Api\Item\Prices as FlowPrices;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class Configurable
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

    public function afterGetPricesJson(\Magento\Swatches\Block\Product\Renderer\Listing\Configurable $configurable, $result)
    {
        $ids = [];
        $product = $configurable->getProduct();
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

<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\SyncSkuPriceAttributesManagementInterface;
use FlowCommerce\FlowConnector\Model\Api\Attribute\Save as AttributeApiClientSave;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class SyncSkuPriceAttributesManager
 * @package FlowCommerce\FlowConnector\Model
 */
class SyncSkuPriceAttributesManager implements SyncSkuPriceAttributesManagementInterface
{
    /**
     * Price attribute codes, to be created in Flow
     */
    const PRICE_ATTRIBUTE_CODES = [
        'base_price',
        'bundle_option',
        'bundle_selection',
        'catalog_rule_price',
        'configured_price',
        'configured_regular_price',
        'custom_option_price',
        'final_price',
        'link_price',
        'max_price',
        'min_price',
        'msrp_price',
        'regular_price',
        'special_price',
        'tier_price',
    ];

    /**
     * AttributeApiClientSave
     */
    private $attributeApiClientSave;

    /**
     * @var Util
     */
    private $util;

    /**
     * SyncSkuPriceAttributesManager constructor.
     * @param AttributeApiClientSave $attributeApiClientSave
     * @param Util $util
     */
    public function __construct(
        AttributeApiClientSave $attributeApiClientSave,
        Util $util
    ) {
        $this->attributeApiClientSave = $attributeApiClientSave;
        $this->util = $util;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function createPriceAttributesInFlow($storeId)
    {
        $isSuccess = false;

        $enabled = $this->util->isFlowEnabled($storeId);
        if ($enabled) {
            $isSuccess = true;
            $priceCodes = $this->getPriceAttributeCodes();

            foreach ($priceCodes as $priceCode) {
                $result = $this->attributeApiClientSave->execute(
                    $storeId,
                    $priceCode,
                    AttributeApiClientSave::INTENT_PRICE,
                    AttributeApiClientSave::TYPE_DECIMAL,
                    false,
                    false,
                    false
                );
                $isSuccess = $isSuccess && $result;
            }
        }
        return $isSuccess;
    }

    /**
     * Returns all price attribute codes
     * @return string[]
     */
    private function getPriceAttributeCodes()
    {
        return self::PRICE_ATTRIBUTE_CODES;
    }
}

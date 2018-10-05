<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\CatalogRule\Model\ResourceModel;

use FlowCommerce\FlowConnector\Plugin\Magento\Catalog\Model\Indexer\Product\Price as PriceIndexerPlugin;
use Magento\CatalogRule\Model\ResourceModel\Rule as CatalogRuleResourceModel;
use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\Framework\FlagManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Rule
 * @package FlowCommerce\FlowConnector\Plugin\Magento\CatalogRule\Model
 */
class Rule
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var PriceIndexerPlugin
     */
    private $priceIndexerPlugin;

    /**
     * Rule constructor.
     * @param FlagManager $flagManager
     * @param JsonSerializer $jsonSerializer
     * @param PriceIndexerPlugin $priceIndexerPlugin
     */
    public function __construct(
        FlagManager $flagManager,
        JsonSerializer $jsonSerializer,
        PriceIndexerPlugin $priceIndexerPlugin
    ) {
        $this->flagManager = $flagManager;
        $this->jsonSerializer = $jsonSerializer;
        $this->priceIndexerPlugin = $priceIndexerPlugin;
    }

    /**
     * @param CatalogRuleResourceModel $ruleResourceModel
     * @param CatalogRule $rule
     * @return CatalogRule[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(CatalogRuleResourceModel $ruleResourceModel, CatalogRule $rule)
    {
        if ($rule->isObjectNew() || $this->isRuleBehaviorChanged($rule)) {
            $this->scheduleFlowFullSyncAfterPriceReindex();
        }
        return [$rule];
    }

    /**
     * Diffs two given arrays
     * @param $array1
     * @param $array2
     * @return array
     */
    private function dataDiff($array1, $array2)
    {
        $result = [];
        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if ($value != $array2[$key]) {
                    $result[$key] = true;
                }
            } else {
                $result[$key] = true;
            }
        }
        return $result;
    }

    /**
     * Checks if rule behavior has changed since last save
     * @param CatalogRule $rule
     * @return bool
     */
    private function isRuleBehaviorChanged(CatalogRule $rule)
    {
        if (!$rule->isObjectNew()) {
            $ruleData = $rule->getData();
            $ruleOrigData = $rule->getOrigData();
            $conditionsSerialized = $this->jsonSerializer->serialize($rule->getConditions()->asArray());
            $actionsSerialized = $this->jsonSerializer->serialize($rule->getActions()->asArray());
            $ruleData['conditions_serialized'] = $conditionsSerialized;
            $ruleData['actions_serialized'] = $actionsSerialized;
            $arrayDiff = $this->dataDiff($ruleData, $ruleOrigData);
            unset($arrayDiff['name']);
            unset($arrayDiff['description']);
            unset($arrayDiff['form_key']);
            unset($arrayDiff['auto_apply']);
            if (empty($arrayDiff)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Schedules Flow Full sync after price reindex
     * @return void
     */
    private function scheduleFlowFullSyncAfterPriceReindex()
    {
        $this->priceIndexerPlugin->scheduleFlowFullSync();
    }
}

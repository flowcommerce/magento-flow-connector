<?php

namespace FlowCommerce\FlowConnector\Model;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Center\GetAllCenterKeys as FlowCentersApiClient;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class InventoryCenterManager
 * @package FlowCommerce\FlowConnector\Model
 */
class InventoryCenterManager
{
    /**
     * Config Path - Default Center Key
     */
    const CONFIG_PATH_DEFAULT_CENTER_KEY = 'flowcommerce/flowconnector/default_center_key';

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var FlowCentersApiClient
     */
    private $flowCentersApiClient;

    /**
     * @var Util
     */
    private $flowUtil;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfig
     */
    private $scopeConfig;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * InventoryCenterManager constructor.
     * @param ConfigWriter $configWriter
     * @param FlowCentersApiClient $flowCentersApiClient
     * @param LoggerInterface $logger
     * @param ScopeConfig $scopeConfig
     * @param StoreManager $storeManager
     * @param Util $util
     */
    public function __construct(
        ConfigWriter $configWriter,
        FlowCentersApiClient $flowCentersApiClient,
        LoggerInterface $logger,
        ScopeConfig $scopeConfig,
        StoreManager $storeManager,
        Util $util
    ) {
        $this->configWriter = $configWriter;
        $this->flowCentersApiClient = $flowCentersApiClient;
        $this->flowUtil = $util;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Fetches inventory center keys from Flow's api and stores them in magento's config
     * @param int[]|null $storeIds
     * @return bool
     */
    public function fetchInventoryCenterKeys($storeIds = [])
    {
        try {
            if (!count((array) $storeIds)) {
                $storeIds = [];
                foreach ($this->storeManager->getStores() as $store) {
                    if ($this->flowUtil->isFlowEnabled($store->getStoreId())) {
                        array_push($storeIds, $store->getStoreId());
                        $this->logger->info('Including products from store: ' . $store->getName() .
                            ' [id=' . $store->getStoreId() . ']');
                    } else {
                        $this->logger->info('Not including products from store: ' . $store->getName() .
                            ' [id=' . $store->getStoreId() . '] - Flow disabled');
                    }
                }
            }

            foreach ($storeIds as $storeId) {
                $centers = $this->flowCentersApiClient->execute($storeId);
                if (count($centers)) {
                    $defaultCenterKey = array_shift($centers);
                    $this->saveDefaultCenterKeyForStore($storeId, $defaultCenterKey);
                }
            }
        } catch (Exception $e) {
            $return = false;
            $this->logger->error($e->getMessage());
        }
        return $return;
    }

    /**
     * Given a store id, returns the configured default center key
     * @param $storeId
     * @return string
     */
    public function getDefaultCenterKeyForStore($storeId)
    {
        return $this->scopeConfig
            ->getValue(self::CONFIG_PATH_DEFAULT_CENTER_KEY, ScopeInterface::SCOPE_STORES, $storeId);
    }

    /**
     * Saves the center
     * @param $storeId
     * @param $defaultCenterKey
     * @return void
     */
    private function saveDefaultCenterKeyForStore($storeId, $defaultCenterKey)
    {
        $this->configWriter
            ->save(
                self::CONFIG_PATH_DEFAULT_CENTER_KEY,
                $defaultCenterKey,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
    }
}

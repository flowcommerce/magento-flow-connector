<?php

namespace FlowCommerce\FlowConnector\Model;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Center\GetAllCenterKeys as FlowCentersApiClient;
use FlowCommerce\FlowConnector\Api\InventoryCenterManagementInterface;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class InventoryCenterManager
 * @package FlowCommerce\FlowConnector\Model
 */
class InventoryCenterManager implements InventoryCenterManagementInterface
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
     * @var Configuration
     */
    private $configuration;

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
     * @param Configuration $configuration
     */
    public function __construct(
        ConfigWriter $configWriter,
        FlowCentersApiClient $flowCentersApiClient,
        LoggerInterface $logger,
        ScopeConfig $scopeConfig,
        StoreManager $storeManager,
        Configuration $configuration
    ) {
        $this->configWriter = $configWriter;
        $this->flowCentersApiClient = $flowCentersApiClient;
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchInventoryCenterKeys($storeIds = [])
    {
        try {
            if (!count((array) $storeIds)) {
                $storeIds = [];
                /** @var StoreInterface $store */
                foreach ($this->storeManager->getStores() as $store) {
                    if ($this->configuration->isFlowEnabled($store->getId())) {
                        array_push($storeIds, $store->getId());
                        $this->logger->info('Including products from store: ' . $store->getName() .
                            ' [id=' . $store->getId() . ']');
                    } else {
                        $this->logger->info('Not including products from store: ' . $store->getName() .
                            ' [id=' . $store->getId() . '] - Flow disabled');
                    }
                }
            }

            foreach ($storeIds as $storeId) {
                if ($this->configuration->isFlowEnabled($storeId)) {
                    $centers = $this->flowCentersApiClient->execute($storeId);
                    if (count($centers)) {
                        $defaultCenterKey = array_shift($centers);
                        $this->saveDefaultCenterKeyForStore($storeId, $defaultCenterKey);
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
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

        # This is needed, as config is cached in scope config in case the value is read right after it's written
        $this->scopeConfig->clean();
    }
}

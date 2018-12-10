<?php

namespace Flowcommerce\FlowConnector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use FlowCommerce\FlowConnector\Api\IntegrationManagementInterface as IntegrationManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Recurring
 * @package Flowcommerce\FlowConnector\Setup
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @var IntegrationManager
     */
    private $integrationManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * Recurring constructor.
     * @param IntegrationManager $integrationManager
     * @param Logger $logger
     * @param StoreManager $storeManager
     */
    public function __construct(
        IntegrationManager $integrationManager,
        Logger $logger,
        StoreManager $storeManager
    ) {
        $this->integrationManager = $integrationManager;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->initializeFlowIntegration();
    }

    /**
     * Loops through all store views and attempts to register webhooks for
     * all of them
     * @return void
     */
    private function initializeFlowIntegration()
    {
        foreach ($this->storeManager->getStores() as $store) {
            try {
                $result = $this->integrationManager->initializeIntegrationForStoreView($store->getId());
                if ($result) {
                    $this->logger
                        ->info('Successfully initialized integration configuration for store ' . $store->getId());
                } else {
                    $this->logger->error(
                        'An error occurred while initializing integration configuration for store ' . $store->getId()
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    'An error occurred while initializing integration configuration for store ' .
                    $store->getId() . ': ' . $e->getMessage(),
                    ['exception' => $e]
                );
            }
        }
    }
}

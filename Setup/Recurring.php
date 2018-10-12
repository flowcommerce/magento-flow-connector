<?php

namespace Flowcommerce\FlowConnector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Recurring
 * @package Flowcommerce\FlowConnector\Setup
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * Recurring constructor.
     * @param Logger $logger
     * @param StoreManager $storeManager
     * @param WebhookEventManager $webhookEventManager
     */
    public function __construct(
        Logger $logger,
        StoreManager $storeManager,
        WebhookEventManager $webhookEventManager
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->webhookEventManager = $webhookEventManager;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->registerWebhooks();
    }

    /**
     * Loops through all store views and attempts to register webhooks for
     * all of them
     * @return void
     */
    private function registerWebhooks()
    {
        foreach ($this->storeManager->getStores() as $store) {
            try {
                $this->webhookEventManager->registerWebhooks($store->getId());
                $this->logger->info('Successfully registered Flow.io webhooks for store ' . $store->getId());
            } catch (\Exception $e) {
                $this->logger->error(
                    'An error occurred while registering Flow.io webhooks for store ' . $store->getId() . ': ' .
                        $e->getMessage(),
                    ['exception' => $e]
                );
            }
        }
    }
}

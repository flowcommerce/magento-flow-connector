<?php

namespace FlowCommerce\FlowConnector\Cron;

use Exception;
use FlowCommerce\FlowConnector\Api\InventoryCenterManagementInterface as InventoryCenterManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Cron Task wrapper fetch default inventory center keys
 * @package FlowCommerce\FlowConnector\Cron
 */
class FetchInventoryCenterKeys
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var InventoryCenterManager
     */
    private $centerManager;

    /**
     * UpdateDefaultCenterKeys constructor.
     * @param Logger $logger
     * @param InventoryCenterManager $centerManager
     */
    public function __construct(
        Logger $logger,
        InventoryCenterManager $centerManager
    ) {
        $this->logger = $logger;
        $this->centerManager = $centerManager;
    }

    /**
     * Defers queue processing to the the catalog sync model
     * @return void
     */
    public function execute()
    {
        try {
            $this->logger->info('Running FetchInventoryCenterKeys cron job.');
            $this->centerManager->fetchInventoryCenterKeys();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}

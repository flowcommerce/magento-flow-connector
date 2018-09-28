<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use FlowCommerce\FlowConnector\Api\InventorySyncManagementInterface as InventorySyncManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to sync the entire catalog to Flow.
 */
final class InventorySyncQueueAllCommand extends BaseCommand
{
    /**
     * @var InventorySyncManager
     */
    private $inventorySyncManager;

    /**
     * CatalogSyncProcessCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param InventorySyncManager $inventorySyncManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        InventorySyncManager $inventorySyncManager
    ) {
        parent::__construct($appState, $registry);
        $this->inventorySyncManager = $inventorySyncManager;
    }

    /**
     * Configures this command
     * @return void
     */
    public function configure()
    {
        $this->setName('flow:flow-connector:inventory-sync-queue-all')
            ->setDescription('Queue all products for sync to Flow inventory.');
    }

    /**
     * Defers enqueueing of all products to the sync sku manager
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initCLI();
        $this->inventorySyncManager->enqueueAllStockItems();
    }
}

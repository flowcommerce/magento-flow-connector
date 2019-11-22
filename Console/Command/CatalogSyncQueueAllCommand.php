<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to sync the entire catalog to Flow.
 */
class CatalogSyncQueueAllCommand extends BaseCommand
{

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * CatalogSyncProcessCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param SyncSkuManager $syncSkuManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        SyncSkuManager $syncSkuManager
    ) {
        parent::__construct($appState, $registry);
        $this->syncSkuManager = $syncSkuManager;
    }

    /**
     * Configures this command
     * @return void
     */
    public function configure()
    {
        $this->setName('flow:connector:catalog-enqueue')
            ->setDescription('Enqueue all products for sync to Flow catalog.');
    }

    /**
     * Defers enqueueing of all products to the sync sku manager
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new FlowConsoleLogger($output);
        $this->initCLI();
        $this->syncSkuManager->setLogger($logger);
        $this->syncSkuManager->enqueueAllProducts();
    }
}

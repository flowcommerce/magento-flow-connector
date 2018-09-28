<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use FlowCommerce\FlowConnector\Model\InventoryCenterManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to sync the entire catalog to Flow.
 */
final class FetchInventoryCenterKeysCommand extends BaseCommand
{

    /**
     * @var InventoryCenterManager
     */
    private $inventoryCenterManager;

    /**
     * CatalogSyncProcessCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param InventoryCenterManager $inventoryCenterManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        InventoryCenterManager $inventoryCenterManager
    ) {
        parent::__construct($appState, $registry);
        $this->inventoryCenterManager = $inventoryCenterManager;
    }


    /**
     * Configures this command
     * @return void
     */
    public function configure()
    {
        $this->setName('flow:flow-connector:fetch-inventory-center-keys')
            ->setDescription(
                'Fetches inventory center keys and stores them on the config. Used by inventory sync cron.'
            );
    }

    /**
     * Defers enqueueing of all products to the sync sku manager
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initCLI();
        $this->inventoryCenterManager->fetchInventoryCenterKeys();
    }
}

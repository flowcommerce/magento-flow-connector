<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Api\InventorySyncManagementInterface as InventoryManager;

/**
 * Command to process the Inventory Sync queue.
 */
class InventorySyncProcessCommand extends BaseCommand
{
    /**
     * @var InventoryManager
     */
    private $inventoryManager;

    /**
     * CatalogSyncProcessCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param InventoryManager $inventoryManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        InventoryManager $inventoryManager
    ) {
        parent::__construct($appState, $registry);
        $this->inventoryManager = $inventoryManager;
    }

    /**
     * Configures this command
     */
    public function configure()
    {
        $this->setName('flow:connector:inventory-sync')
            ->setDescription('Sync inventory queue to Flow.')
            ->addArgument(
                'num-to-process',
                InputArgument::OPTIONAL,
                'Number of records to process. Defaults to processing all records.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $numToProcess = $input->getArgument('num-to-process');
        if (!isset($numToProcess)) {
            $numToProcess = -1;
        }

        $this->initCLI();
        $this->inventoryManager->process($numToProcess, 1);
    }
}

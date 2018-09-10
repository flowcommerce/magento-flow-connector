<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Model\Sync\CatalogSync;

/**
 * Command to sync the entire catalog to Flow.
 */
final class CatalogSyncQueueAllCommand extends BaseCommand
{

    /**
     * @var CatalogSync
     */
    private $catalogSync;

    /**
     * CatalogSyncProcessCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param CatalogSync $catalogSync
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        CatalogSync $catalogSync
    ) {
        parent::__construct($appState, $registry);
        $this->catalogSync = $catalogSync;
    }

    public function configure()
    {
        $this->setName('flow:flow-connector:catalog-sync-queue-all')
            ->setDescription('Queue all products for sync to Flow catalog.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new FlowConsoleLogger($output);
        $this->initCLI();
        $this->catalogSync->setLogger($logger);
        $this->catalogSync->queueAll();
    }
}

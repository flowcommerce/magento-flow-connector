<?php

namespace Flow\FlowConnector\Console\Command;

use Symfony\Component\Console\{
    Command\Command,
    Logger\ConsoleLogger,
    Input\InputInterface,
    Input\InputArgument,
    Input\InputOption,
    Output\OutputInterface
};

/**
 * Command to sync the entire catalog to Flow.
 */
final class CatalogSyncQueueAllCommand extends BaseCommand {

    private $catalogSync;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Flow\FlowConnector\Model\Sync\CatalogSync $catalogSync
    ) {
        $this->catalogSync = $catalogSync;
        parent::__construct($objectManager);
    }

    public function configure() {
        $this->setName('flow:flow-connector:catalog-sync-queue-all')
            ->setDescription('Queue all products for sync to Flow catalog.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $logger = new ConsoleLogger($output);
        $this->initCLI();
        $this->catalogSync->setLogger($logger);
        $this->catalogSync->queueAll();
    }
}

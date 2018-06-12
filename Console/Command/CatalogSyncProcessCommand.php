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
 * Command to process the SyncSku queue.
 */
final class CatalogSyncProcessCommand extends BaseCommand {

    private $catalogSync;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Flow\FlowConnector\Model\Sync\CatalogSync $catalogSync
    ) {
        $this->catalogSync = $catalogSync;
        parent::__construct($objectManager);
    }

    public function configure() {
        $this->setName('flow:flow-connector:catalog-sync-process')
            ->setDescription('Process sync skus queue and send to Flow.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $logger = new ConsoleLogger($output);
        $this->initCLI();
        $this->catalogSync->setLogger($logger);
        $this->catalogSync->process();
    }
}

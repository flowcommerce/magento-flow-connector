<?php

namespace FlowCommerce\FlowConnector\Console\Command;

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
        \FlowCommerce\FlowConnector\Model\Sync\CatalogSync $catalogSync
    ) {
        $this->catalogSync = $catalogSync;
        parent::__construct($objectManager);
    }

    public function configure() {
        $this->setName('flow:flow-connector:catalog-sync-process')
            ->setDescription('Process sync skus queue and send to Flow.')
            ->addArgument('num-to-process', InputArgument::OPTIONAL, 'Number of records to process. Defaults to processing all records.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $numToProcess = $input->getArgument('num-to-process');
        if (!isset($numToProcess)) {
            $numToProcess = -1;
        }

        $logger = new ConsoleLogger($output);
        $this->initCLI();
        $this->catalogSync->setLogger($logger);
        $this->catalogSync->process($numToProcess);
    }
}

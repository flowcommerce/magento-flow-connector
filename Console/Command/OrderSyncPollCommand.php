<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Model\OrderSyncManager;

/**
 * Command to process the Order Sync Poll
 */
class OrderSyncPollCommand extends BaseCommand
{
    /**
     * @var OrderSyncManager
     */
    private $orderSyncManager;

    /**
     * OrderSyncPollCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param OrderSyncManager $orderSyncManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        OrderSyncManager $orderSyncManager
    ) {
        parent::__construct($appState, $registry);
        $this->orderSyncManager = $orderSyncManager;
    }

    public function configure()
    {
        $this->setName('flow:connector:order-sync')
            ->setDescription('Process Flow orders which have not been synced yet.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new FlowConsoleLogger($output);
        $this->initCLI();
        $this->orderSyncManager->setLogger($logger);
        $this->orderSyncManager->process();
    }
}

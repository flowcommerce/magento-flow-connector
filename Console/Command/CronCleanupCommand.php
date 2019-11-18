<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use FlowCommerce\FlowConnector\Model\CronManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to cleanup Flow cron tasks.
 */
class CronCleanupCommand extends BaseCommand
{

    /**
     * @var CronManager
     */
    private $cronManager;

    /**
     * CatalogSyncProcessCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param CronManager $cronManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        CronManager $cronManager
    ) {
        parent::__construct($appState, $registry);
        $this->cronManager = $cronManager;
    }

    /**
     * Configures this command
     * @return void
     */
    public function configure()
    {
        $this->setName('flow:flow-connector:cron-cleanup')
            ->setDescription('Remove Flow cron tasks older than 5 minutes and still marked as running.');
    }

    /**
     * Clean up Flow cron tasks
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new FlowConsoleLogger($output);
        $this->initCLI();
        $this->cronManager->setLogger($logger);
        $this->cronManager->cleanupCronTasks();
    }
}

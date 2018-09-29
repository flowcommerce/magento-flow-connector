<?php

namespace FlowCommerce\FlowConnector\Cron;

use \Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\CronManager;

/**
* Cron cleanup task.
* @package FlowCommerce\FlowConnector\Cron
*/
class CronCleanupTask
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CronManager
     */
    private $cronManager;

    /**
     * CronCleanupTask constructor.
     * @param Logger $logger
     * @param CronManager $cronManager
     */
    public function __construct(
        Logger $logger,
        CronManager $cronManager
    ) {
        $this->logger = $logger;
        $this->cronManager = $cronManager;
    }

    /**
     * Clean up stale cron schedule rows.
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Running CronCleanupTask execute.');
        $this->cronManager->cleanupCronTasks();
        $this->logger->info('Finished CronCleanupTask.');
    }
}

<?php

namespace FlowCommerce\FlowConnector\Model;

use \Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\ResourceConnection;

/**
 * Class CronManager
 * @package FlowCommerce\FlowConnector\Model
 */
class CronManager
{

    /**
     * Maximum number of minutes a Flow cron job is allowed to run.
     */
    const MAX_CRON_JOB_RUNTIME_MINUTES = 5;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * CronManager constructor.
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        ResourceConnection $resourceConnection
    ) {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Clean up stale cron schedule rows.
     * @return void
     */
    public function cleanupCronTasks()
    {
        $this->logger->info('Running CronManager cleanupCronTasks()');
        $connection = $this->resourceConnection->getConnection();
        $sql = '
            delete from cron_schedule
             where executed_at < date_sub(now(), interval ' . self::MAX_CRON_JOB_RUNTIME_MINUTES . ' minute)
               and status = \'running\'
               and job_code like \'flowcommerce_flowconnector_%\';
        ';
        $num = $connection->exec($sql);
        $this->logger->info('Cleaned up ' . $num . ' tasks.');
    }

    /**
     * Allows logger to be overridden
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }
}

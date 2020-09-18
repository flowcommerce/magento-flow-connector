<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use FlowCommerce\FlowConnector\Model\Api\Allocation\GetByNumber;
use Psr\Log\LoggerInterface as Logger;

/**
 * Model class for storing Flow order data.
 */
class Allocation extends AbstractModel implements IdentityInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var GetByNumber
     */
    private $getByNumber;

    /**
     * @param Logger $logger
     * @param GetByNumber $getByNumber
     */
    public function __construct(
        Logger $logger,
        GetByNumber $getByNumber
    ) {
        $this->logger = $logger;
        $this->getByNumber = $getByNumber;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function getByNumber($storeId, $number)
    {
        $this->logger->info('Getting allocation by number: ' . $number);
        return $this->getByNumber->execute($storeId, $number);
    }
}

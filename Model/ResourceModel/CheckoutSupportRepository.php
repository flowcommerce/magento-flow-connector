<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\CheckoutSupportRepositoryInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class CheckoutSupportRepository
 * @package FlowCommerce\FlowConnector\Model
 */
class CheckoutSupportRepository implements CheckoutSupportRepositoryInterface
{
    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

     
    /**
     * @param $order
     * @return mixed
     */
    public function discountRequest($order)
    {
        $this->logger->info('Fired discountRequest');
        $result = ["false", "0.0"];
        $discountCode = 'TESTCODE';
        $this->logger->info(json_encode([$order,$discountCode]));
        return $result;
    }
}

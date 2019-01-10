<?php

namespace FlowCommerce\FlowConnector\Model;

use Exception;
use FlowCommerce\FlowConnector\Exception\FlowException;
use Psr\Log\LoggerInterface as Logger;
use Zend\Http\Request;
use FlowCommerce\FlowConnector\Model\OrderFactory as FlowOrderFactory;

/**
 * Class Notification
 * @package FlowCommerce\FlowConnector\Model
 */
class Notification
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var FlowOrderFactory
     */
    private $flowOrderFactory;

    /**
     * Notification constructor.
     * @param Logger $logger
     * @param OrderFactory $flowOrderFactory
     */
    public function __construct(
        Logger $logger,
        FlowOrderFactory $flowOrderFactory
    ) {
        $this->logger = $logger;
        $this->flowOrderFactory = $flowOrderFactory;
    }

    /**
     * Set the logger (used by console command).
     * @param Logger $logger
     * @return void
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Notifies Flow cross dock that order is enroute.
     * https://docs.flow.io/module/logistics/resource/shipping_notifications#put-organization-shipping-notifications-key
     * https://docs.flow.io/type/shipping-label-package
     * @param $order - The Magento order object.
     * @param $trackingNumber - The tracking number for order sent to cross dock.
     * @param $shippingLabelPackage - A Flow Shipping Label Package object.
     * @param $service - Carrier service level used for generation and shipment of this label.
     * @throws Exception
     * @TODO
     */
    public function notifyCrossDock($order, $trackingNumber, $shippingLabelPackage, $service)
    {
        $flowOrder = $this->flowOrderFactory->create()->find('order_id', $order->getId());
        $storeId = $order->getStoreId();

        $data = [
            'carrier_tracking_number' => $trackingNumber,
            'destination' => $flowOrder->getCrossDockAddress(),
            'order_number' => $order->getId(),
            'package' => $shippingLabelPackage,
            'service' => $service
        ];

        $client = $this->getFlowClient('shipping-notifications/' . $order->getId(), $storeId);
        $client->setMethod(Request::METHOD_PUT);
        $client->setRawBody($this->jsonHelper->jsonEncode($data));

        if ($response->isSuccess()) {
            $this->logger->info('Notify Cross Dock: success');
            $this->logger->info('Status code: ' . $response->getStatusCode());
            $this->logger->info('Body: ' . $response->getBody());
        } else {
            $this->logger->error('Notify Cross Dock: failed');
            $this->logger->error('Status code: ' . $response->getStatusCode());
            $this->logger->error('Body: ' . $response->getBody());
            throw new FlowException('Failed to notify cross dock with tracking number ' . $trackingNumber .
                ': ' . $response->getBody());
        }
    }
}

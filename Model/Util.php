<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as GuzzleClient;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Utility class for Flow settings and endpoints.
 */
class Util
{
    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * Util constructor.
     * @param Notification $notification
     * @param GuzzleClient $guzzleClient
     * @param Configuration $configuration
     * @param StoreManager $storeManager
     */
    public function __construct(
        Notification $notification,
        GuzzleClient $guzzleClient,
        Configuration $configuration,
        StoreManager $storeManager
    ) {
        $this->notification =$notification;
        $this->guzzleClient = $guzzleClient;
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
    }

    /**
     * Set the logger (used by console command).
     * @param Logger $logger
     * @return void
     * @deprecated
     */
    public function setLogger($logger)
    {
        $this->notification->setLogger($logger);
    }

    /**
     * Returns true if Flow is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     * @deprecated
     */
    public function isFlowEnabled($storeId = null)
    {
        return $this->configuration->isFlowEnabled($storeId);
    }

    /**
     * Returns the Flow Client user agent
     * @return string
     * @deprecated
     */
    public function getFlowClientUserAgent()
    {
        return $this->guzzleClient->getFlowClientUserAgent();
    }

    /**
     * Returns a Zend Client preconfigured for the Flow API.
     * @param $urlStub
     * @param int|null $storeId
     * @return GuzzleClient
     * @deprecated
     */
    public function getFlowClient($urlStub, $storeId = null)
    {
        return $this->guzzleClient->getFlowClient($urlStub, $storeId);
    }

    /**
     * Wrapper function to retry on timeout for http client send().
     * @param GuzzleClient $client
     * @param int|null $numRetries
     * @deprecated
     */
    public function sendFlowClient($client, $numRetries = 3)
    {
        $this->guzzleClient->sendFlowClient($client, $numRetries);
    }

    /**
     * Returns the Flow Checkout Url
     * @param int|null $storeId
     * @throws NoSuchEntityException
     * @return string
     * @deprecated
     */
    public function getFlowCheckoutUrl($storeId = null)
    {
        return $this->configuration->getFlowCheckoutUrl($storeId);
    }

    /**
     * Returns the ID of the current store
     * @return int
     * @throws NoSuchEntityException
     * @deprecated
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Returns an array of Stores that are enabled for Flow.
     * @return StoreInterface[]
     * @throws NoSuchEntityException
     * @deprecated
     */
    public function getEnabledStores()
    {
        return $this->configuration->getEnabledStores();
    }

    /**
     * Returns Flow Invoice Event
     * @see \FlowCommerce\FlowConnector\Model\Config\Source\InvoiceEvent::toOptionArray()
     * @param null $storeId
     * @return int
     * @throws NoSuchEntityException
     * @deprecated
     */
    public function getFlowInvoiceEvent($storeId = null)
    {
        return $this->configuration->getFlowShipmentEvent($storeId);
    }

    /**
     * Returns Flow Shipment Event
     *
     * @see \FlowCommerce\FlowConnector\Model\Config\Source\ShipmentEvent::toOptionArray()
     *
     * @param int|null $storeId
     * @return int
     * @throws NoSuchEntityException
     * @deprecated
     */
    public function getFlowShipmentEvent($storeId = null)
    {
        return $this->configuration->getFlowShipmentEvent($storeId);
    }

    /**
     * Returns true if send invoice email is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function sendInvoiceEmail($storeId = null)
    {
        return $this->configuration->sendInvoiceEmail($storeId);
    }

    /**
     * Returns true if send shipment email is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     * @deprecated
     */
    public function sendShipmentEmail($storeId = null)
    {
        return $this->configuration->sendShipmentEmail($storeId);
    }
}

<?php

namespace FlowCommerce\FlowConnector\Model;

use Exception;
use FlowCommerce\FlowConnector\Exception\FlowException;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as GuzzleClient;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Module\ModuleListInterface as ModuleList;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Psr\Log\LoggerInterface as Logger;
use Zend\Http\Client;
use Zend\Http\ClientFactory as HttpClientFactory;
use Zend\Http\Request;
use Zend\Http\Client\Adapter\Exception\RuntimeException;
use Zend\Http\Response;

/**
 * Utility class for Flow settings and endpoints.
 */
class Util
{
    // Store configuration key for Flow Enabled
    const FLOW_ENABLED = 'flowcommerce/flowconnector/enabled';

    // Store configuration key for Flow Invoice Event
    const FLOW_INVOICE_EVENT = 'flowcommerce/flowconnector/invoice_event';

    // Store configuration key for Flow Shipment Event
    const FLOW_SHIPMENT_EVENT = 'flowcommerce/flowconnector/shipment_event';

    // Store configuration key for Flow Invoice Event
    const FLOW_INVOICE_SEND_EMAIL = 'flowcommerce/flowconnector/invoice_email';

    // Store configuration key for Flow Invoice Event
    const FLOW_SHIPMENT_SEND_EMAIL = 'flowcommerce/flowconnector/shipment_email';

    // Store configuration key for Checkout Price Source
    const FLOW_CHECKOUT_PRICE_SOURCE = 'flowcommerce/flowconnector/price_source';

    // Store configuration key for Checkout Discount Source
    const FLOW_CHECKOUT_DISCOUNT_SOURCE = 'flowcommerce/flowconnector/discount_source';

    // Flow API base endpoint
    const FLOW_API_BASE_ENDPOINT = 'https://api.flow.io/';

    // Flow checkout base url
    const FLOW_CHECKOUT_BASE_URL = 'https://checkout.flow.io/';

    // Name of Flow session cookie
    const FLOW_SESSION_COOKIE = '_f60_session';

    // Timeout for Flow http client
    const FLOW_CLIENT_TIMEOUT = 30;

    // Number of seconds to delay before retrying
    const FLOW_CLIENT_RETRY_DELAY = 30;

    // User agent for connecting to Flow
    const HTTP_USERAGENT = 'Flow-M2';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ScopeConfig
     */
    private $scopeConfig;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * Util constructor.
     * @param Auth $auth
     * @param Logger $logger
     * @param HttpClientFactory $httpClientFactory
     * @param ModuleList $moduleList
     * @param ScopeConfig $scopeConfig
     * @param StoreManager $storeManager
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        Logger $logger,
        ModuleList $moduleList,
        ScopeConfig $scopeConfig,
        StoreManager $storeManager,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
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
     * Returns true if Flow is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isFlowEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (bool) $this->scopeConfig->getValue(self::FLOW_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the Flow Client user agent
     * @return string
     */
    public function getFlowClientUserAgent()
    {
        return GuzzleClient::HTTP_USERAGENT . '-' . $this->getModuleVersion();
    }

    /**
     * Returns a Zend Client preconfigured for the Flow API.
     * @param string $urlStub
     * @param int|null $storeId
     * @return Client
     * @throws NoSuchEntityException
     */
    public function getFlowClient($urlStub, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        $userAgent = $this->getFlowClientUserAgent();
        $url = $this->urlBuilder->getFlowApiEndpoint($urlStub, $storeId);
        $this->logger->info('Flow Client [' . $userAgent . '] URL: ' . $url);

        $client = $this->getHttpClient($url, $userAgent);
        $client->setMethod(Request::METHOD_GET);
        $client->setAuth($this->auth->getFlowApiToken($storeId), '');
        $client->setEncType('application/json');
        return $client;
    }

    /**
     * Returns Zend client instance
     * @param $url
     * @param $userAgent
     * @return Client
     */
    private function getHttpClient($url, $userAgent)
    {
        return $this->httpClientFactory->create([
            'uri' => $url,
            'options' => [
                'useragent' => $userAgent,
                'timeout' => self::FLOW_CLIENT_TIMEOUT
            ]
        ]);
    }

    /**
     * Returns current Flow Connector version
     * @return string
     */
    private function getModuleVersion()
    {
        return (string) $this->moduleList->getOne('FlowCommerce_FlowConnector')['setup_version'];
    }

    /**
     * Wrapper function to retry on timeout for http client send().
     * @param Client $client
     * @param int|null $numRetries
     * @return Response
     */
    public function sendFlowClient($client, $numRetries = 3)
    {
        try {
            return $client->send();
        } catch (RuntimeException $e) {
            if ($numRetries <= 0) {
                throw $e;
            } else {
                $this->logger->info('Error sending client request, retries remaining: ' . $numRetries .
                    ', trying again in ' . self::FLOW_CLIENT_RETRY_DELAY . ' seconds');
                sleep(self::FLOW_CLIENT_RETRY_DELAY);
                return $this->sendFlowClient($client, $numRetries - 1);
            }
        }
    }

    /**
     * Returns the Flow Checkout Url
     * @param int|null $storeId
     * @throws NoSuchEntityException
     * @return string
     */
    public function getFlowCheckoutUrl($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return self::FLOW_CHECKOUT_BASE_URL .
            $this->auth->getFlowOrganizationId($storeId) . '/order/';
    }

    /**
     * Returns the ID of the current store
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
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

    /**
     * Returns an array of Stores that are enabled for Flow.
     * @return StoreInterface[]
     * @throws NoSuchEntityException
     */
    public function getEnabledStores()
    {
        $stores = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isFlowEnabled($store->getId())) {
                array_push($stores, $store);
            }
        }
        return $stores;
    }

    /**
     * Returns Flow Invoice Event
     * @see \FlowCommerce\FlowConnector\Model\Config\Source\InvoiceEvent::toOptionArray()
     * @param null $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getFlowInvoiceEvent($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (int)$this->scopeConfig->getValue(self::FLOW_INVOICE_EVENT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns Flow Shipment Event
     *
     * @see \FlowCommerce\FlowConnector\Model\Config\Source\ShipmentEvent::toOptionArray()
     *
     * @param int|null $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getFlowShipmentEvent($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (int)$this->scopeConfig->getValue(self::FLOW_SHIPMENT_EVENT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns true if send invoice email is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function sendInvoiceEmail($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (bool) $this->scopeConfig->getValue(
            self::FLOW_INVOICE_SEND_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if send shipment email is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function sendShipmentEmail($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_SHIPMENT_SEND_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns Checkout Price Source from Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getCheckoutPriceSource($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (int)$this->scopeConfig->getValue(
            self::FLOW_CHECKOUT_PRICE_SOURCE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns Checkout Discount Source from Admin Store Configuration.
     * @param int|null $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCheckoutDiscountSource($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (int)$this->scopeConfig->getValue(
            self::FLOW_CHECKOUT_DISCOUNT_SOURCE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

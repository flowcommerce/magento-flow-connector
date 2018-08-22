<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Zend\Http\{
    Client,
    Request,
    Client\Adapter\Exception\RuntimeException
};
use FlowCommerce\FlowConnector\Exception\FlowException;

/**
 * Utility class for Flow settings and endpoints.
 */
class Util {

    // Store configuration key for Flow Enabled
    const FLOW_ENABLED = 'flowcommerce/flowconnector/enabled';

    // Store configuration key for Flow Organization Id
    const FLOW_ORGANIZATION_ID = 'flowcommerce/flowconnector/organization_id';

    // Store configuration key for Flow API Token
    const FLOW_API_TOKEN = 'flowcommerce/flowconnector/api_token';

    // Flow API base endpoint
    const FLOW_API_BASE_ENDPOINT = 'https://api.flow.io/';

    // Flow checkout base url
    const FLOW_CHECKOUT_BASE_URL = 'https://checkout.flow.io/';

    // Name of Flow session cookie
    const FLOW_SESSION_COOKIE = '_f60_session';

    // Timeout for Flow http client
    const FLOW_CLIENT_TIMEOUT = 10;

    // Number of seconds to delay before retrying
    const FLOW_CLIENT_RETRY_DELAY = 10;

    // User agent for connecting to Flow
    const HTTP_USERAGENT = 'Flow-M2';

    protected $logger;
    protected $scopeConfig;
    protected $storeManager;
    protected $moduleList;
    protected $moduleVersion;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->moduleVersion = $this->moduleList->getOne('FlowCommerce_FlowConnector')['setup_version'];
    }

    /**
    * Set the logger (used by console command).
    */
    public function setLogger($logger) {
        $this->logger = $logger;
    }

    /**
     * Returns true if Flow is enabled in the Admin Store Configuration.
     * @param storeId ID of store, if null defaults to current store.
     */
    public function isFlowEnabled($storeId = null) {
        if (is_null($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->scopeConfig->getValue(self::FLOW_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the Flow Organization Id set in the Admin Store Configuration.
     * @param storeId ID of store, if null defaults to current store.
     */
    public function getFlowOrganizationId($storeId = null) {
        if (is_null($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->scopeConfig->getValue(self::FLOW_ORGANIZATION_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the Flow API Token set in the Admin Store Configuration.
     * @param storeId ID of store, if null defaults to current store.
     */
    public function getFlowApiToken($storeId = null) {
        if (is_null($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->scopeConfig->getValue(self::FLOW_API_TOKEN, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the Flow API endpoint with the specified url stub.
     * @param urlStub Url stub for the client
     * @param storeId ID of store, if null defaults to current store.
     */
    public function getFlowApiEndpoint($urlStub, $storeId = null) {
        if (is_null($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        return self::FLOW_API_BASE_ENDPOINT .
            $this->getFlowOrganizationId($storeId) . $urlStub;
    }

    /**
     * Returns the Flow Client user agent.
     */
    public function getFlowClientUserAgent() {
        return self::HTTP_USERAGENT . '-' . $this->moduleVersion;
    }

    /**
     * Returns a Zend Client preconfigured for the Flow API.
     * @param urlStub Url stub for the client
     * @param storeId ID of store, if null defaults to current store.
     */
    public function getFlowClient($urlStub, $storeId = null) {
        if (is_null($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        $useragent = $this->getFlowClientUserAgent();
        $url = $this->getFlowApiEndpoint($urlStub, $storeId);
        $this->logger->info('Flow Client [' . $useragent . '] URL: ' . $url);

        $client = new Client($url, [
            'useragent' => $useragent,
            'timeout' => self::FLOW_CLIENT_TIMEOUT
        ]);
        $client->setMethod(Request::METHOD_GET);
        $client->setAuth($this->getFlowApiToken($storeId), '');
        $client->setEncType('application/json');
        return $client;
    }

    /**
     * Wrapper function to retry on timeout for http client send().
     * @param client Flow http client
     * @param numRetries Defaults to 3 retries
     */
    public function sendFlowClient($client, $numRetries = 3) {
        try {
            return $client->send();
        } catch (RuntimeException $e) {
            if ($numRetries <= 0) {
                throw $e;
            } else {
                $this->logger->info('Error sending client request, retries remaining: ' . $numRetries . ', trying again in ' . self::FLOW_CLIENT_RETRY_DELAY . ' seconds');
                sleep(self::FLOW_CLIENT_RETRY_DELAY);
                return $this->sendFlowClient($client, $numRetries - 1);
            }
        }
    }

    /**
     * Returns the Flow Checkout Url
     * @param storeId ID of store, if null defaults to current store.
     */
    public function getFlowCheckoutUrl($storeId = null) {
        if (is_null($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        return self::FLOW_CHECKOUT_BASE_URL .
            $this->getFlowOrganizationId($storeId) . '/order/';
    }

    /**
     * Returns the ID of the current store
     */
    public function getCurrentStoreId() {
        $this->storeManager->getStore()->getId();
    }

    /**
     * Notifies Flow cross dock that order is enroute.
     *
     * https://docs.flow.io/module/logistics/resource/shipping_notifications#put-organization-shipping-notifications-key
     * https://docs.flow.io/type/shipping-label-package
     *
     * @param order The Magento order object.
     * @param trackingNumber The tracking number for order sent to cross dock.
     * @param shippingPackageLabel A Flow Shipping Label Package object.
     * @param service Carrier service level used for generation and shipment of this label.
     */
    public function notifyCrossDock($order, $trackingNumber, $shippingLabelPackage, $service) {
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
            throw new FlowException('Failed to notify cross dock with tracking number ' . $trackingNumber . ': ' . $response->getBody());
        }

    }
}

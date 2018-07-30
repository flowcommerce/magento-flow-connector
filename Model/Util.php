<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\UrlInterface;
use \Magento\Store\Model\ScopeInterface;
use Zend\Http\{
    Client,
    Request,
    Client\Adapter\Exception\RuntimeException
};

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

    protected $logger;
    protected $scopeConfig;
    protected $storeManager;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
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
     * Returns a Zend Client preconfigured for the Flow API.
     * @param urlStub Url stub for the client
     * @param storeId ID of store, if null defaults to current store.
     */
    public function getFlowClient($urlStub, $storeId = null) {
        if (is_null($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        $url = $this->getFlowApiEndpoint($urlStub, $storeId);
        $this->logger->info('Flow Client URL: ' . $url);

        $client = new Client($url, [
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
                $this->sendFlowClient($client, $numRetries - 1);
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

}

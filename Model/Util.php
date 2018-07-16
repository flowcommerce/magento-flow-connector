<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\UrlInterface;
use \Magento\Store\Model\ScopeInterface;
use Zend\Http\{
    Client,
    Request
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

    protected $logger;
    protected $scopeConfig;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
    * Set the logger (used by console command).
    */
    public function setLogger($logger) {
        $this->logger = $logger;
    }

    /**
     * Returns true if Flow is enabled in the Admin Store Configuration.
     * @param storeId ID of store
     */
    public function isFlowEnabled($storeId) {
        return $this->scopeConfig->getValue(self::FLOW_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the Flow Organization Id set in the Admin Store Configuration.
     * @param storeId ID of store
     */
    public function getFlowOrganizationId($storeId) {
        return $this->scopeConfig->getValue(self::FLOW_ORGANIZATION_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the Flow API Token set in the Admin Store Configuration.
     * @param storeId ID of store
     */
    public function getFlowApiToken($storeId) {
        return $this->scopeConfig->getValue(self::FLOW_API_TOKEN, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the Flow API endpoint with the specified url stub.
     * @param storeId ID of store
     * @param urlStub Url stub for the client
     */
    public function getFlowApiEndpoint($storeId, $urlStub) {
        return self::FLOW_API_BASE_ENDPOINT .
            $this->getFlowOrganizationId($storeId) . $urlStub;
    }

    /**
     * Returns a Zend Client preconfigured for the Flow API.
     * @param storeId ID of store
     * @param urlStub Url stub for the client
     */
    public function getFlowClient($storeId, $urlStub) {
        $url = $this->getFlowApiEndpoint($storeId, $urlStub);
        $this->logger->info('Flow Client URL: ' . $url);

        $client = new Client($url);
        $client->setMethod(Request::METHOD_GET);
        $client->setAuth($this->getFlowApiToken($storeId), '');
        $client->setEncType('application/json');
        return $client;
    }

    /**
     * Returns the Flow Checkout Url
     * @param storeId ID of store
     */
    public function getFlowCheckoutUrl($storeId) {
        return self::FLOW_CHECKOUT_BASE_URL .
            $this->getFlowOrganizationId($storeId) . '/order/';
    }
}

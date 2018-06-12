<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\UrlInterface;
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
     * Returns true if Flow is enabled in the Admin Store Configuration.
     */
    public function isFlowEnabled() {
        return $this->scopeConfig->getValue(self::FLOW_ENABLED);
    }

    /**
     * Returns the Flow Organization Id set in the Admin Store Configuration.
     */
    public function getFlowOrganizationId() {
        return $this->scopeConfig->getValue(self::FLOW_ORGANIZATION_ID);
    }

    /**
     * Returns the Flow API Token set in the Admin Store Configuration.
     */
    public function getFlowApiToken() {
        return $this->scopeConfig->getValue(self::FLOW_API_TOKEN);
    }

    /**
     * Returns the Flow API endpoint with the specified url stub.
     */
    public function getFlowApiEndpoint($urlStub) {
        return self::FLOW_API_BASE_ENDPOINT .
            $this->getFlowOrganizationId() . $urlStub;
    }

    /**
     * Returns a Zend Client preconfigured for the Flow API.
     */
    public function getFlowClient($urlStub) {
        $url = $this->getFlowApiEndpoint($urlStub);
        $this->logger->info('Flow Client URL: ' . $url);

        $client = new Client($url);
        $client->setMethod(Request::METHOD_GET);
        $client->setAuth($this->getFlowApiToken(), '');
        $client->setEncType('application/json');
        return $client;
    }

    /**
     * Returns the Flow Checkout Url
     */
    public function getFlowCheckoutUrl() {
        return self::FLOW_CHECKOUT_BASE_URL .
            $this->getFlowOrganizationId() . '/order/';
    }
}

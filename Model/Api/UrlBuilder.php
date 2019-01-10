<?php

namespace FlowCommerce\FlowConnector\Model\Api;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class UrlBuilder
 * @package FlowCommerce\FlowConnector\Model\Api
 */
class UrlBuilder
{
    /**
     * Flow API base endpoint
     */
    const FLOW_API_BASE_ENDPOINT = 'https://api.flow.io/';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * Auth constructor.
     * @param Auth $auth
     */
    public function __construct(
        Auth $auth
    ) {
        $this->auth = $auth;
    }

    /**
     * Returns the Flow API endpoint with the specified url stub.
     * @param string $urlStub
     * @param int|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFlowApiEndpoint($urlStub, $storeId = null)
    {
        return self::FLOW_API_BASE_ENDPOINT . $this->auth->getFlowOrganizationId($storeId) . $urlStub;
    }

    /**
     * Returns the Flow API endpoint with the specified url stub but without organization specified.
     * @param string $urlStub
     * @return string
     */
    public function getFlowApiEndpointWithoutOrganization($urlStub)
    {
        return self::FLOW_API_BASE_ENDPOINT . $urlStub;
    }
}

<?php

namespace FlowCommerce\FlowConnector\Model\GuzzleHttp;

use GuzzleHttp\ClientFactory as GuzzleHttpClientFactory;
use FlowCommerce\FlowConnector\Model\Configuration;

/**
 * Class ClientFactory
 *
 * Factory to adjust \GuzzleHttp\Client for Flow Connector
 *
 * @package FlowCommerce\FlowConnector\Model\GuzzleHttp
 */
class ClientFactory
{
    /**
     * @var GuzzleHttpClientFactory
     */
    private $guzzleHttpClientFactory;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * ClientFactory constructor.
     * @param GuzzleHttpClientFactory $guzzleHttpClientFactory
     * @param Configuration $configuration
     */
    public function __construct(
        GuzzleHttpClientFactory $guzzleHttpClientFactory,
        Configuration $configuration
    ) {
        $this->guzzleHttpClientFactory = $guzzleHttpClientFactory;
        $this->configuration = $configuration;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function create()
    {
        $config = [
            'config' => [
                'headers' => [
                    'User-Agent' => $this->configuration->getFlowClientUserAgent()
                ],
                'timeout' => Configuration::FLOW_CLIENT_TIMEOUT
            ]
        ];

        return $this->guzzleHttpClientFactory->create($config);
    }
}

<?php

namespace FlowCommerce\FlowConnector\Model\GuzzleHttp;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Configuration;
use GuzzleHttp\Client as GuzzleClient;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleListInterface as ModuleList;
use Magento\Store\Model\StoreManager;
use Zend\Http\Client\Adapter\Exception\RuntimeException;
use Zend\Http\Client as HttpClient;
use Zend\Http\ClientFactory as HttpClientFactory;
use Zend\Http\Request;
use Psr\Log\LoggerInterface as Logger;


/**
 * Class Client
 * @package FlowCommerce\FlowConnector\Model\GuzzleHttp
 */
class Client extends GuzzleClient
{
    /**
     * Timeout for Flow http client
     *
     * @deprecated
     */
    const FLOW_CLIENT_TIMEOUT = 30;

    /**
     * Number of seconds to delay before retrying
     *
     * @deprecated
     */
    const FLOW_CLIENT_RETRY_DELAY = 30;

    /**
     * Static portion of HTTP User-Agent
     *
     * @deprecated
     */
    const HTTP_USERAGENT = 'Flow-M2';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * @var string
     */
    private $moduleVersion;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * Client constructor.
     *
     * Adjusts FlowCommerce_FlowConnector specific configuration settings for \GuzzleHttp\Client
     *
     * @param ModuleList $moduleList
     * @param HttpClientFactory $httpClientFactory
     * @param UrlBuilder $urlBuilder
     * @param Logger $logger
     * @param Configuration $configuration
     * @param StoreManager $storeManager
     * @param array $config
     * @param Auth $auth
     */
    public function __construct(
        ModuleList $moduleList,
        HttpClientFactory $httpClientFactory,
        UrlBuilder $urlBuilder,
        Logger $logger,
        Configuration $configuration,
        StoreManager $storeManager,
        Auth $auth,
        array $config = []
    ) {
        $this->moduleList = $moduleList;
        $this->httpClientFactory = $httpClientFactory;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
        $this->auth = $auth;
        $this->moduleVersion = $this->moduleList
            ->getOne('FlowCommerce_FlowConnector')['setup_version'];

        // Adjust HTTP user agent to for example 'Flow-M2-1.0.29'
        $config['headers']['User-Agent'] = self::HTTP_USERAGENT . '-' . $this->moduleVersion;

        parent::__construct($config);
    }

    /**
     * Returns the Flow Client user agent
     *
     * @deprecated 1.1.5
     * @return string
     */
    public function getFlowClientUserAgent()
    {
        return self::HTTP_USERAGENT . '-' . $this->configuration->getModuleVersion();
    }

    /**
     * Returns a Zend Client preconfigured for the Flow API.
     *
     * @deprecated 1.1.5
     * @param string $urlStub
     * @param int|null $storeId
     * @return HttpClient
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
     *
     * @deprecated 1.1.5
     * @param $url
     * @param $userAgent
     * @return HttpClient
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
     * Wrapper function to retry on timeout for http client send().
     *
     * @deprecated 1.1.5
     * @param HttpClient $client
     * @param int|null $numRetries
     * @return mixed
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
     * Returns the ID of the current store
     *
     * @deprecated 1.1.5
     * @return int
     * @throws NoSuchEntityException
     */
    private function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
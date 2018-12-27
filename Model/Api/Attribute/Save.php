<?php

namespace FlowCommerce\FlowConnector\Model\Api\Attribute;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Save
 * @package FlowCommerce\FlowConnector\Model\Api\Attribute
 */
class Save
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/attributes';

    /**
     * Attribute Intent - Brand
     */
    const INTENT_BRAND = 'brand';

    /**
     * Attribute Intent - Color
     */
    const INTENT_COLOR = 'color';

    /**
     * Attribute Intent - Consumer URL
     */
    const INTENT_CONSUMER_URL = 'consumer_url';

    /**
     * Attribute Intent - Countries of Origin
     */
    const INTENT_COUNTRIES_OF_ORIGIN = 'countries_of_origin';

    /**
     * Attribute Intent - Fulfillment method
     */
    const INTENT_FULFILLMENT_METHOD = 'fulfillment_method';

    /**
     * Attribute Intent - GTIN
     */
    const INTENT_GTIN = 'gtin';

    /**
     * Attribute Intent - Hazardous
     */
    const INTENT_HAZARDOUS = 'hazardous';

    /**
     * Attribute Intent - Price
     */
    const INTENT_PRICE = 'price';

    /**
     * Attribute Intent - Product ID
     */
    const INTENT_PRODUCT_ID = 'product_id';

    /**
     * Attribute Intent - Size
     */
    const INTENT_SIZE = 'size';

    /**
     * Attribute Intent - SKU
     */
    const INTENT_SKU = 'sku';

    /**
     * Attribute Intent - Taxability
     */
    const INTENT_TAXABILITY = 'taxability';

    /**
     * Attribute Intent - MPN
     */
    const INTENT_MPN = 'mpn';

    /**
     * Attribute Type - Boolean
     */
    const TYPE_BOOLEAN = 'boolean';

    /**
     * Attribute Type - Decimal
     */
    const TYPE_DECIMAL = 'decimal';

    /**
     * Attribute Type - String
     */
    const TYPE_STRING = 'string';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var HttpRequestFactory
     */
    private $httpRequestFactory;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Logger|null
     */
    private $logger = null;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * Delete constructor.
     * @param Auth $auth
     * @param HttpClientFactory $httpClientFactory
     * @param HttpRequestFactory $httpRequestFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieves all webhooks registered with flow
     * @param int $storeId
     * @param string $key
     * @param string $intent
     * @param string $type
     * @param bool $required
     * @param bool $showInCatalog
     * @param bool $showInHarmonization
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute($storeId, $key, $intent, $type, $required, $showInCatalog, $showInHarmonization)
    {
        $return = false;

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId) . '/' . $key;

        $body = [
            'key' => $key,
            'intent' => $intent,
            'type' => $type,
            'options' => [
                'required' => $required,
                'show_in_catalog' => $showInCatalog,
                'show_in_harmonization' => $showInHarmonization,
            ]
        ];

        try {
            $response = $client->put($url, [
                'auth' => $this->auth->getAuthHeader($storeId),
                'body' => $this->jsonSerializer->serialize($body)
            ]);

            if (in_array((int) $response->getStatusCode(), [200, 201])) {
                $this->logger->info('Attribute successfully saved: ' . $response->getBody());
                $return = true;
            } else {
                $this->logger->info('Attribute save failed: ' . $response->getBody());
            }
        } catch (Exception $e) {
            $this->logger->info('Attribute save failed: ' . $e->getMessage());
        }

        return $return;
    }
}

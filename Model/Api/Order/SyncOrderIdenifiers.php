<?php
declare(strict_types=1);

namespace FlowCommerce\FlowConnector\Model\Api\Order;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client as HttpClient;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use Magento\Framework\Exception\LocalizedException;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use InvalidArgumentException;

/**
 * @package FlowCommerce\FlowConnector\Model\Api\Order
 */
class SyncOrderIdenifiers
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/order-identifiers';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Logger|null
     */
    private $logger;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @param Auth $auth
     * @param HttpClientFactory $httpClientFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param UrlBuilder $urlBuilder
     * @return void
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->httpClientFactory = $httpClientFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Sync Magento and Flow order identifiers using POST request.
     *
     * @link https://docs.flow.io/reference/order_identifier#post-organization-orderidentifiers
     *
     * @param mixed $storeId
     * @param mixed $magentoOrderIncrementId
     * @param mixed $flowOrderId
     * @return string
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     */
    public function execute($storeId, $magentoOrderIncrementId, $flowOrderId): string
    {
        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId);

        $body = [
            'order' => $flowOrderId,
            'name' => 'magento_id',
            'identifier' => $magentoOrderIncrementId,
            'number' => $magentoOrderIncrementId,
            'primary' => true
        ];

        $seralizedBody = $this->jsonSerializer->serialize($body);

        $this->logger->info('Order identifiers sync: ' . $seralizedBody);

        $response = $client->post($url, [
            'auth' => $this->auth->getAuthHeader($storeId),
            RequestOptions::JSON => $body
        ]);

        if ((int) $response->getStatusCode() === 201) {
            $this->logger->info(
                sprintf(
                    'Order identifiers sync successful for order %s: %s',
                    $magentoOrderIncrementId,
                    $response->getBody()
                )
            );
            $return = (string) $response->getBody();
        } else {
            throw new LocalizedException(
                __(
                    'Order identifiers sync was not successful for order increment ID %1: %2',
                    $magentoOrderIncrementId,
                    $response->getBody()
                )
            );
        }

        return $return;
    }
}

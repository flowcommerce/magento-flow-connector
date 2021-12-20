<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Fixtures;

use FlowCommerce\FlowConnector\Controller\Webhooks\CaptureUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\CardAuthorizationUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\FraudStatusChanged;
use FlowCommerce\FlowConnector\Controller\Webhooks\LabelUpserted;
use FlowCommerce\FlowConnector\Controller\Webhooks\OnlineAuthorizationUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\OrderPlaced;
use FlowCommerce\FlowConnector\Controller\Webhooks\RefundCaptureUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\RefundUpsertedV2;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class CreateWebhookEvents
 * @package FlowCommerce\FlowConnector\Test\Integration\Fixtures
 */
class CreateWebhookEvents
{

    /**
     * Test Organization ID
     */
    const ORGANIZATION_ID = 'magento-integration-sandbox';

    /**
     * Number of orders to create
     */
    const NUMBER_ORDERS = 30;

    /**
     * Test Store Id
     */
    const STORE_ID = 1;

    /**
     * Integration tests file directory
     */
    const FILE_DIR = __DIR__ . '/_files/';

    /** @var JsonSerializer */
    private $jsonSerializer;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ReadFactory
     */
    private $readFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    public function __construct()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->jsonSerializer = $this->objectManager->create(JsonSerializer::class);
        $this->orderRepository = $this->objectManager->create(OrderRepository::class);
        $this->readFactory = $this->objectManager->create(ReadFactory::class);
        $this->webhookEventManager = $this->objectManager->create(WebhookEventManager::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
    }

    /**
     * Create and process webhook event
     * @param string[] $filenames
     * @return WebhookEvent[]
     */
    public function createCaptureUpsertedWebhooks($filenames = null)
    {
        return $this->createWebhooks(CaptureUpsertedV2::EVENT_TYPE, $filenames);
    }

    /**
     * Create and process webhook event
     * @param string[] $filenames
     * @return WebhookEvent[]
     */
    public function createCardAuthorizationUpsertedWebhooks($filenames = null)
    {
        return $this->createWebhooks(CardAuthorizationUpsertedV2::EVENT_TYPE, $filenames);
    }

    /**
     * Create and process webhook event
     * @param string[] $filenames
     * @return WebhookEvent[]
     */
    public function createFraudStatusChangeWebhooks($filenames = null)
    {
        return $this->createWebhooks(FraudStatusChanged::EVENT_TYPE, $filenames);
    }

    /**
     * Create and process webhook event
     * @param string[] $filenames
     * @return WebhookEvent[]
     */
    public function createLabelUpsertedWebhooks($filenames = null)
    {
        return $this->createWebhooks(LabelUpserted::EVENT_TYPE, $filenames);
    }

    /**
     * Create and process webhook event
     * @param string[] $filenames
     * @return WebhookEvent[]
     */
    public function createOnlineAuthorizationUpsertedWebhooks($filenames = null)
    {
        return $this->createWebhooks(OnlineAuthorizationUpsertedV2::EVENT_TYPE, $filenames);
    }

    /**
     * Create and process webhook event
     * @param string[] $filenames
     * @return WebhookEvent[]
     */
    public function createOrderPlacedWebhooks($filenames = null)
    {
        return $this->createWebhooks(OrderPlaced::EVENT_TYPE, $filenames);
    }

    /**
     * Create and process webhook event
     * @param null|string[] $filenames
     * @return WebhookEvent[]
     */
    public function createRefundCaptureUpsertedWebhooks($filenames = null)
    {
        return $this->createWebhooks(RefundCaptureUpsertedV2::EVENT_TYPE, $filenames);
    }

    /**
     * Create and process webhook event
     * @param null|string[] $filenames
     * @return WebhookEvent[]
     */
    public function createRefundUpsertedWebhooks($filenames = null)
    {
        return $this->createWebhooks(RefundUpsertedV2::EVENT_TYPE, $filenames);
    }

    /**
     * Create Webhooks by event name
     * @param string $eventName
     * @param string[] $filenames
     * @return WebhookEvent[]
     */
    public function createWebhooks($eventName, $filenames = null)
    {
        $events = [];
        $path = $this->getPath($eventName);
        if (!$filenames) {
            $files = $this->readFilesFromPath($path);
        } else {
            $files = $filenames;
        }
        foreach ($files as $file) {
            try {
                $payload = $this->getPayloadFromFile($path, $file);
                if ($payload) {
                    $referenceNumber = $this->getRefNumberFromPayload($payload, $eventName);
                    $events[$referenceNumber]= $this->webhookEventManager->queue($eventName, $payload, self::STORE_ID);
                }
            } catch (\Exception $exception) {
                //No payload file found
            }
        }
        return $events;
    }

    /**
     * @return OrderSearchResultInterface
     */
    public function getOrders()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->orderRepository->getList($searchCriteria);
    }

    /**
     * Read files from specific event name directory
     * @param $path
     * @return array
     */
    private function readFilesFromPath($path)
    {
        $directory = $this->readFactory->create($path);
        $files = array_diff(
            $directory->read(),
            ['..', '.']
        );
        return $files;
    }

    /**
     * Get directory path based on event name
     * @param $eventName
     * @return string
     */
    private function getPath($eventName)
    {
        return self::FILE_DIR.$eventName.'/';
    }

    /**
     * Extract order number from payload
     * @param $payloadJson
     * @param $eventName
     * @return array|bool|float|int|mixed|null|string
     */
    private function getRefNumberFromPayload($payloadJson, $eventName)
    {
        $payload = $this->jsonSerializer->unserialize($payloadJson);
        switch ($eventName) {
            case FraudStatusChanged::EVENT_TYPE:
                return $payload['order']['number'];
            case LabelUpserted::EVENT_TYPE:
                return $payload['order'];
            case CaptureUpsertedV2::EVENT_TYPE:
                return $payload['capture']['authorization']['order']['number'];
            case CardAuthorizationUpsertedV2::EVENT_TYPE:
                return $payload['authorization']['order']['number'];
            case RefundCaptureUpsertedV2::EVENT_TYPE:
                return $payload['refund_capture']['refund']['authorization']['id'];
            case OnlineAuthorizationUpsertedV2::EVENT_TYPE:
                return $payload['authorization']['order']['number'];
            default:
                return $payload['order']['number'];
        }
    }

    /**
     * @param $path
     * @param $file
     * @return string[]
     */
    private function getPayloadFromFile($path, $file)
    {
        $return = file_get_contents($path.$file);
        return $return;
    }
}

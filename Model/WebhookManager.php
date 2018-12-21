<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\WebhookManagementInterface;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Delete as WebhookDeleteApiClient;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Get as WebhookGetApiClient;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Save as WebhookSaveApiClient;
use FlowCommerce\FlowConnector\Model\Sync\CatalogSync;
use FlowCommerce\FlowConnector\Model\WebhookManager\EndpointsConfiguration as WebhookEndpointConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class WebhookEventManager
 * @package FlowCommerce\FlowConnector\Model
 */
class WebhookManager implements WebhookManagementInterface
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var WebhookDeleteApiClient
     */
    private $webhookDeleteApiClient;

    /**
     * @var WebhookEndpointConfig
     */
    private $webhookEndpointConfig;

    /**
     * @var WebhookGetApiClient
     */
    private $webhookGetApiClient;

    /**
     * @var WebhookSaveApiClient
     */
    private $webhookSaveApiClient;

    /**
     * @var CatalogSync
     */
    private $catalogSync;

    /**
     * @var InventoryCenterManager
     */
    private $inventoryCenterManager;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * WebhookEventManager constructor.
     * @param Notification $notification
     * @param Logger $logger
     * @param StoreManager $storeManager
     * @param WebhookDeleteApiClient $webhookDeleteApiClient
     * @param WebhookEndpointConfig $webhookEndpointConfig
     * @param WebhookGetApiClient $webhookGetApiClient
     * @param WebhookSaveApiClient $webhookSaveApiClient
     * @param CatalogSync $catalogSync
     * @param InventoryCenterManager $inventoryCenterManager
     * @param Configuration $configuration
     */
    public function __construct(
        Notification $notification,
        Logger $logger,
        StoreManager $storeManager,
        WebhookDeleteApiClient $webhookDeleteApiClient,
        WebhookEndpointConfig $webhookEndpointConfig,
        WebhookGetApiClient $webhookGetApiClient,
        WebhookSaveApiClient $webhookSaveApiClient,
        CatalogSync $catalogSync,
        InventoryCenterManager $inventoryCenterManager,
        Configuration $configuration
    ) {
        $this->notification = $notification;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->webhookDeleteApiClient = $webhookDeleteApiClient;
        $this->webhookEndpointConfig = $webhookEndpointConfig;
        $this->webhookGetApiClient = $webhookGetApiClient;
        $this->webhookSaveApiClient = $webhookSaveApiClient;
        $this->catalogSync = $catalogSync;
        $this->inventoryCenterManager = $inventoryCenterManager;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function deleteAllWebhooks($storeId)
    {
        $webhooks = $this->getRegisteredWebhooks($storeId);
        foreach ($webhooks as $webhook) {
            if (strpos($webhook['url'], '/flowconnector/webhooks/') &&
                strpos($webhook['url'], 'storeId=' . $storeId)) {
                $this->logger->info('Deleting webhook: ' . $webhook['url']);
                $this->webhookDeleteApiClient->execute($storeId, $webhook['id']);
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function getRegisteredWebhooks($storeId)
    {
        if (!$this->configuration->isFlowEnabled($storeId)) {
            return [];
        }
        return $this->webhookGetApiClient->execute($storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function registerAllWebhooks($storeId)
    {
        $return = false;
        $this->logger->info('Updating Flow webhooks for store: ' . $storeId);

        $enabled = $this->configuration->isFlowEnabled($storeId);

        if ($enabled) {
            try {
                $this->deleteAllWebhooks($storeId);
                foreach ($this->webhookEndpointConfig->getEndpointsConfiguration() as $stub => $events) {
                    $this->registerWebhook($storeId, $stub, $events);
                }
                $this->logger->info(sprintf('Successfully registered webhooks for store %d.', $storeId));
                $return = true;
            } catch (\Exception $e) {
                $message = sprintf(
                    'Error occurred registering webhooks for store %d: %s.',
                    $storeId,
                    $e->getMessage()
                );
                $this->logger->critical($message);
                throw new \Exception($message);
            }
        } else {
            $message = sprintf('Flow connector disabled or missing API credentials for store %d', $storeId);
            $this->logger->notice($message);
            throw new \Exception($message);
        }
        return $return;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function registerWebhook($storeId, $endpointStub, $events)
    {
        $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $url = $baseUrl . 'flowconnector/webhooks/' . $endpointStub . '?storeId=' . $storeId;
        $this->logger->info('Registering webhook events ' . var_export($events, true) . ': ' . $url);
        $this->webhookSaveApiClient->execute($storeId, $url, $events);
    }

    /**
     * Set the logger (used by console command).
     * @param Logger $logger
     * @return void
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        $this->notification->setLogger($logger);
    }
}

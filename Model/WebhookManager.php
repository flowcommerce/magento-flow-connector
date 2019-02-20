<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\WebhookManagementInterface;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Delete as WebhookDeleteApiClient;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Get as WebhookGetApiClient;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Save as WebhookSaveApiClient;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Settings as WebhookSettingsApiClient;
use FlowCommerce\FlowConnector\Model\Sync\CatalogSync;
use FlowCommerce\FlowConnector\Model\WebhookManager\EndpointsConfiguration as WebhookEndpointConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\WebhookManager\PayloadValidator;

/**
 * Class WebhookEventManager
 * @package FlowCommerce\FlowConnector\Model
 */
class WebhookManager implements WebhookManagementInterface
{
    /**
     * Webhook retry max attempts
     */
    const RETRY_MAX_ATTEMPTS = 6;

    /**
     * Webhook retry sleep time in ms
     */
    const RETRY_SLEEP_MS = 6000;

    /**
     * Webhook sleep time in ms
     */
    const SLEEP_MS = 0;

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
     * @var WebhookSettingsApiClient
     */
    private $webhookSettingsApiClient;

    /**
     * @var PayloadValidator
     */
    private $payloadValidator;

    /**
     * WebhookEventManager constructor.
     * WebhookManager constructor.
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
     * @param WebhookSettingsApiClient $webhookSettingsApiClient
     * @param PayloadValidator $payloadValidator
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
        Configuration $configuration,
        WebhookSettingsApiClient $webhookSettingsApiClient,
        PayloadValidator $payloadValidator
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
        $this->webhookSettingsApiClient = $webhookSettingsApiClient;
        $this->payloadValidator = $payloadValidator;
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
        $webhooks = $this->getRegisteredWebhooks($storeId);
        $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
        $url = $baseUrl . 'flowconnector/webhooks/' . $endpointStub . '?storeId=' . $storeId;
        foreach ($webhooks as $webhook) {
            if ($webhook['url'] == $url && count(array_diff($webhook['events'], $events)) > 0) {
                // Already exists
                $this->logger->info('Webhook already exists ' . var_export($events, true) . ': ' . $url . ', ID:' . $webhook['id']);
                return false;
            }
            if ($webhook['url'] == $url || count(array_diff($webhook['events'], $events)) > 0) {
                // Requires update
                $this->logger->info('Webhook already exists ' . var_export($events, true) . ': ' . $url . ', ID:' . $webhook['id'] . ' but must be updated');
                $this->webhookSaveApiClient->execute($storeId, $url, $events, $webhook['id']);
                return true;
            }
        }
        // Must be created new
        $this->logger->info('Registering webhook events ' . var_export($events, true) . ': ' . $url);

        $this->webhookSaveApiClient->execute($storeId, $url, $events);
        return true;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function updateWebhookSettings($storeId)
    {
        $this->logger->info(sprintf('Updating webhook settings for store %s', $storeId));
        return $this->webhookSettingsApiClient->execute(
            $storeId,
            $this->payloadValidator->getSecret(),
            self::RETRY_MAX_ATTEMPTS,
            self::RETRY_SLEEP_MS,
            self::SLEEP_MS
        );
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

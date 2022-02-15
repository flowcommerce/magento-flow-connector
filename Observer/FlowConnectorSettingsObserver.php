<?php

namespace FlowCommerce\FlowConnector\Observer;

use FlowCommerce\FlowConnector\Api\IntegrationManagementInterface as IntegrationManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use FlowCommerce\FlowConnector\Model\Configuration;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class FlowConnectorSettingsObserver
 * @package FlowCommerce\FlowConnector\Observer
 */
class FlowConnectorSettingsObserver implements ObserverInterface
{
    /**
     * @var IntegrationManager
     */
    private $integrationManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * FlowConnectorSettingsObserver constructor.
     * @param IntegrationManager $integrationManager
     * @param MessageManager $messageManager
     * @param Configuration $configuration
     * @param Logger $logger
     * @return void
     */
    public function __construct(
        IntegrationManager $integrationManager,
        MessageManager $messageManager,
        Configuration $configuration,
        Logger $logger
    ) {
        $this->integrationManager = $integrationManager;
        $this->messageManager = $messageManager;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * This observer triggers after Flow connector settings are updated in the Admin Store Configuration.
     * It in turn triggers an initialization of the integration parameters with Flow.io
     * @param Observer $observer
     * @return void
     * @throws
     */
    public function execute(Observer $observer)
    {
        $storeId = $observer->getStore();
        if ($this->configuration->isFlowEnabled($storeId)) {
            try {
                $this->integrationManager->initializeIntegrationForStoreView($storeId);
                $this->messageManager->addSuccessMessage('Successfully initialized Flow configuration.');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(sprintf(
                    'An error occurred while initializing Flow configuration: %s.',
                    $e->getMessage()
                ));

                try {
                    $this->configuration->disableFlow($storeId);
                } catch (\Exception $ex) {
                    $this->logger->error(
                        'An error occurred while trying to disable Flow Connector.',
                        ['exception' => $ex]
                    );
                }
            }
        }
    }
}

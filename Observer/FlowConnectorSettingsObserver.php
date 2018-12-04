<?php

namespace FlowCommerce\FlowConnector\Observer;

use FlowCommerce\FlowConnector\Api\IntegrationManagementInterface as IntegrationManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;

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
     * FlowConnectorSettingsObserver constructor.
     * @param IntegrationManager $integrationManager
     * @param MessageManager $messageManager
     */
    public function __construct(
        IntegrationManager $integrationManager,
        MessageManager $messageManager
    ) {
        $this->integrationManager = $integrationManager;
        $this->messageManager = $messageManager;
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

        try {
            $this->integrationManager->initializeIntegrationForStoreView($storeId);
            $this->messageManager->addSuccessMessage('Successfully initialized Flow configuration.');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(sprintf(
                'An error occurred while initializing Flow configuration: %s.',
                $e->getMessage()
            ));
        }
    }
}

<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Api\IntegrationManagementInterface as IntegrationManager;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class IntegrationInitializeCommand
 * @package FlowCommerce\FlowConnector\Console\Command
 */
class IntegrationInitializeCommand extends BaseCommand
{
    /**
     * @var IntegrationManager
     */
    private $integrationManager;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * WebhookRegisterWebhooksCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param IntegrationManager $integrationManager
     * @param StoreManager $storeManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        IntegrationManager $integrationManager,
        StoreManager $storeManager
    ) {
        parent::__construct($appState, $registry);
        $this->integrationManager = $integrationManager;
        $this->storeManager = $storeManager;
    }

    public function configure()
    {
        $this->setName('flow:flow-connector:integration-initialize')
            ->setDescription('Initializes integration with flow.io. This is a wrapper for webhooks ' .
                'registration, attributes creation and inventory center key fetching.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initCLI();
        foreach ($this->storeManager->getStores() as $store) {
            try {
                $result = $this->integrationManager->initializeIntegrationForStoreView($store->getId());
                if ($result) {
                    $output->writeln(sprintf(
                        'Successfully initialized Flow configuration for store %d.',
                        $store->getId()
                    ));
                } else {
                    $output->writeln(
                        sprintf(
                            'An error occurred while initializing Flow configuration for store %d: %s.',
                            $store->getId()
                        )
                    );
                }
            } catch (\Exception $e) {
                $output->writeln(
                    sprintf(
                        'An error occurred while initializing Flow configuration for store %d: %s.',
                        $store->getId(),
                        $e->getMessage()
                    )
                );
            }
        }
    }
}

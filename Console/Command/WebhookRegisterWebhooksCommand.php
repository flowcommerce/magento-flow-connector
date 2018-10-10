<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Command to register webhooks with Flow.
 */
final class WebhookRegisterWebhooksCommand extends BaseCommand
{
    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * WebhookRegisterWebhooksCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param WebhookEventManager $webhookEventManager
     * @param StoreManager $storeManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        WebhookEventManager $webhookEventManager,
        StoreManager $storeManager
    ) {
        parent::__construct($appState, $registry);
        $this->webhookEventManager = $webhookEventManager;
        $this->storeManager = $storeManager;
    }

    public function configure()
    {
        $this->setName('flow:flow-connector:webhook-register-webhooks')
            ->setDescription('Register webhooks with Flow.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new FlowConsoleLogger($output);
        $this->initCLI();
        $this->webhookEventManager->setLogger($logger);
        foreach ($this->storeManager->getStores() as $store) {
            try {
                $this->webhookEventManager->registerWebhooks($store->getId());
                $output->writeln(sprintf('Successfully initialized Flow configuration for store %d.', $store->getId()));
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

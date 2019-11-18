<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Api\WebhookManagementInterface as WebhookManager;
use FlowCommerce\FlowConnector\Model\Configuration;

/**
 * Command to register webhooks with Flow.
 */
class WebhookRegisterWebhooksCommand extends BaseCommand
{
    /**
     * @var WebhookManager
     */
    private $webhookManager;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * WebhookRegisterWebhooksCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param WebhookManager $webhookManager
     * @param Configuration $configuration
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        WebhookManager $webhookManager,
        Configuration $configuration
    ) {
        parent::__construct($appState, $registry);
        $this->webhookManager = $webhookManager;
        $this->configuration = $configuration;
    }

    public function configure()
    {
        $this->setName('flow:connector:webhook-register')
            ->setDescription('Register or update existing webhooks with Flow.');
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
        $logger = new FlowConsoleLogger($output);
        $this->initCLI();
        $this->webhookManager->setLogger($logger);
        foreach ($this->configuration->getEnabledStores() as $store) {
            try {
                $this->webhookManager->registerAllWebhooks($store->getId());
                $output->writeln(sprintf('Successfully registered webhooks for store %d.', $store->getId()));
            } catch (\Exception $e) {
                $output->writeln(
                    sprintf(
                        'An error occurred while registering webhooks for store %d: %s.',
                        $store->getId(),
                        $e->getMessage()
                    )
                );
            }
        }
    }
}

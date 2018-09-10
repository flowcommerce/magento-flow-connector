<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;

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
     * CatalogSyncProcessCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param WebhookEventManager $webhookEventManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        WebhookEventManager $webhookEventManager
    ) {
        parent::__construct($appState, $registry);
        $this->webhookEventManager = $webhookEventManager;
    }

    public function configure()
    {
        $this->setName('flow:flow-connector:webhook-register-webhooks')
            ->setDescription('Register webhooks with Flow.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new FlowConsoleLogger($output);
        $this->initCLI();
        $this->webhookEventManager->setLogger($logger);
        $this->webhookEventManager->registerWebhooks();
    }
}

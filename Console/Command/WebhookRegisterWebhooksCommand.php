<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\{
    Command\Command,
    Logger\ConsoleLogger,
    Input\InputInterface,
    Input\InputArgument,
    Input\InputOption,
    Output\OutputInterface
};

/**
 * Command to register webhooks with Flow.
 */
final class WebhookRegisterWebhooksCommand extends BaseCommand {

    private $webhookEventManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \FlowCommerce\FlowConnector\Model\WebhookEventManager $webhookEventManager
    ) {
        $this->webhookEventManager = $webhookEventManager;
        parent::__construct($objectManager);
    }

    public function configure() {
        $this->setName('flow:flow-connector:webhook-register-webhooks')
            ->setDescription('Register webhooks with Flow.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $logger = new ConsoleLogger($output);
        $this->initCLI();
        $this->webhookEventManager->setLogger($logger);
        $this->webhookEventManager->registerWebhooks();
    }
}

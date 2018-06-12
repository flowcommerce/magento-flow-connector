<?php

namespace Flow\FlowConnector\Console\Command;

use Symfony\Component\Console\{
    Command\Command,
    Logger\ConsoleLogger,
    Input\InputInterface,
    Input\InputArgument,
    Input\InputOption,
    Output\OutputInterface
};

/**
 * Command to process the webhook event queue.
 */
final class WebhookEventProcessCommand extends BaseCommand {

    private $webhookEventManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Flow\FlowConnector\Model\WebhookEventManager $webhookEventManager
    ) {
        $this->webhookEventManager = $webhookEventManager;
        parent::__construct($objectManager);
    }

    public function configure() {
        $this->setName('flow:flow-connector:webhook-event-process')
            ->setDescription('Process Flow webhook events.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $logger = new ConsoleLogger($output);
        $this->initCLI();
        $this->webhookEventManager->setLogger($logger);
        $this->webhookEventManager->process();
    }
}

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
 * Base Command class that provides initialization for the CLI.
 */
abstract class BaseCommand extends Command {

    protected $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        parent::__construct();
    }

    /**
     * Initialize Magento for CLI usage.
     */
    protected function initCLI() {
        $registry = $this->objectManager->get('Magento\Framework\Registry');
        $registry->register('isSecureArea', true);

        $appState = $this->objectManager->get('Magento\Framework\App\State');
        try {
            // Function call throws an exception if area code not set.
            $appState->getAreaCode();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        }
    }
}

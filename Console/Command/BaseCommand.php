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
        [$registry, $appState] = array_map([$this->objectManager, 'get'], ['Magento\Framework\Registry', 'Magento\Framework\App\State']);
        $registry->register('isSecureArea', true);
        $appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
    }
}

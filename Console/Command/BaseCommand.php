<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

/**
 * Base Command class that provides initialization for the CLI.
 */
abstract class BaseCommand extends Command
{
    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * BaseCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     */
    public function __construct(
        AppState $appState,
        Registry $registry
    ) {
        parent::__construct();
        $this->appState = $appState;
        $this->registry = $registry;
    }

    /**
     * Initialize Magento for CLI usage.
     */
    protected function initCLI()
    {
        $this->registry->register('isSecureArea', true);
        try {
            // Function call throws an exception if area code not set.
            $this->appState->getAreaCode();
        } catch (LocalizedException $e) {
            $this->appState->setAreaCode(AppArea::AREA_GLOBAL);
        }
    }
}

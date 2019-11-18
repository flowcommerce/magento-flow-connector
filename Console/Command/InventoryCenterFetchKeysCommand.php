<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Api\InventoryCenterManagementInterface as InventoryCenterManager;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class InventoryCenterFetchKeysCommand
 * @package FlowCommerce\FlowConnector\Console\Command
 */
class InventoryCenterFetchKeysCommand extends BaseCommand
{
    /**
     * @var InventoryCenterManager
     */
    private $inventoryCenterManager;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * WebhookRegisterWebhooksCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param InventoryCenterManager $inventoryCenterManager
     * @param StoreManager $storeManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        InventoryCenterManager $inventoryCenterManager,
        StoreManager $storeManager
    ) {
        parent::__construct($appState, $registry);
        $this->inventoryCenterManager = $inventoryCenterManager;
        $this->storeManager = $storeManager;
    }

    public function configure()
    {
        $this->setName('flow:flow-connector:inventory-center-fetch-keys')
            ->setDescription('Fetch inventory center keys for all store views where flowconnector is configured.');
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
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            array_push($storeIds, $store->getId());
        }

        try {
            $this->inventoryCenterManager->fetchInventoryCenterKeys($storeIds);
            $output->writeln(sprintf(
                'Successfully fetched inventory keys for stores with ids %d.',
                implode(', ', $storeIds)
            ));
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    'An error occurred while fetching inventory keys for stores with ids %d: %s.',
                    implode(', ', $storeIds),
                    $e->getMessage()
                )
            );
        }
    }
}

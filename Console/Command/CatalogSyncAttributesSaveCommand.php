<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use FlowCommerce\FlowConnector\Api\SyncSkuPriceAttributesManagementInterface as SyncSkuPriceAttributesManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CatalogSyncAttributesSave
 * @package FlowCommerce\FlowConnector\Console\Command
 */
class CatalogSyncAttributesSaveCommand extends BaseCommand
{
    /**
     * @var SyncSkuPriceAttributesManager
     */
    private $syncSkuPriceAttributesManager;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * WebhookRegisterWebhooksCommand constructor.
     * @param AppState $appState
     * @param Registry $registry
     * @param StoreManager $storeManager
     * @param SyncSkuPriceAttributesManager $syncSkuPriceAttributesManager
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        StoreManager $storeManager,
        SyncSkuPriceAttributesManager $syncSkuPriceAttributesManager
    ) {
        parent::__construct($appState, $registry);
        $this->storeManager = $storeManager;
        $this->syncSkuPriceAttributesManager = $syncSkuPriceAttributesManager;
    }

    /**
     * Configures the command
     * @return void
     */
    public function configure()
    {
        $this->setName('flow:connector:catalog-attributes-save')
            ->setDescription('Save product attributes needed for catalog integration to Flow.');
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
                $result = $this->syncSkuPriceAttributesManager->createPriceAttributesInFlow($store->getId());
                if ($result) {
                    $output->writeln(sprintf(
                        'Successfully saved product attributes for store with id %d.',
                        $store->getId()
                    ));
                } else {
                    $output->writeln(
                        sprintf(
                            'An error occurred while saving product attributes for store with id %d.',
                            $store->getId()
                        )
                    );
                }
            } catch (\Exception $e) {
                $output->writeln(
                    sprintf(
                        'An error occurred while saving product attributes for store with id %d: %s.',
                        $store->getId(),
                        $e->getMessage()
                    )
                );
            }
        }
    }
}

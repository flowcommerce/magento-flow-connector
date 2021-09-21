<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use FlowCommerce\FlowConnector\Model\OrderIdentifiersSyncManager;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @package FlowCommerce\FlowConnector\Console\Command
 */
class OrderIdentifiersSyncQueueCommand extends BaseCommand
{
    const INPUT_OPTION_STORE_ID = 'store_id';
    const INPUT_OPTION_MAGENTO_ORDER_ID = 'magento_order_id';
    const INPUT_OPTION_FLOW_ORDER_ID = 'flow_order_id';

    /**
     * @var OrderIdentifiersSyncManager
     */
    private $orderIdentifierSyncManager;

    /**
     * @param AppState $appState
     * @param Registry $registry
     * @param OrderIdentifiersSyncManager $orderIdentifierSyncManager
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(
        AppState $appState,
        Registry $registry,
        OrderIdentifiersSyncManager $orderIdentifierSyncManager
    ) {
        parent::__construct($appState, $registry);
        $this->orderIdentifierSyncManager = $orderIdentifierSyncManager;
    }

    public function configure()
    {
        $this->setName('flow:connector:order-identifiers-queue')
            ->setDescription('Queue orders for syncing their Magento and Flow identifiers.');

            $this->addOption(
                self::INPUT_OPTION_STORE_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'Product store ID'
            );

            $this->addOption(
                self::INPUT_OPTION_MAGENTO_ORDER_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'Magento order increment ID'
            );

            $this->addOption(
                self::INPUT_OPTION_FLOW_ORDER_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'Flow order ID'
            );

            parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getOption(self::INPUT_OPTION_STORE_ID);
        if(!$storeId) {
            $output->writeln(__('%1 option is required', self::INPUT_OPTION_STORE_ID));
            return;
        }

        $magentoOrderId = $input->getOption(self::INPUT_OPTION_MAGENTO_ORDER_ID);
        if(!$magentoOrderId) {
            $output->writeln(__('%1 option is required', self::INPUT_OPTION_MAGENTO_ORDER_ID));
            return;
        }

        $flowOrderID = $input->getOption(self::INPUT_OPTION_FLOW_ORDER_ID);
        if(!$flowOrderID) {
            $output->writeln(__('%1 option is required', self::INPUT_OPTION_FLOW_ORDER_ID));
            return;
        }

        $this->initCLI();
        $this->orderIdentifierSyncManager->queueOrderIdentifiersforSync((int) $storeId, [
            $magentoOrderId => $flowOrderID
        ]);
    }
}

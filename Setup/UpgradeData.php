<?php
namespace FlowCommerce\FlowConnector\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    private $salesSetupFactory;

    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        if ($context->getVersion() && version_compare($context->getVersion(), '1.0.2', '<')) {
            $attributes = [
                'base_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
                'duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            ];

            foreach ($attributes as $attributeCode => $attributeParams) {
                $salesSetup->addAttribute('order', $attributeCode, $attributeParams);
            }
        }
    }
}

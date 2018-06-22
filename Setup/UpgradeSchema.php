<?php

namespace FlowCommerce\FlowConnector\Setup;

use Magento\Framework\{
    Setup\ModuleContextInterface,
    Setup\SchemaSetupInterface,
    Setup\UpgradeSchemaInterface,
    DB\Ddl\Table,
    DB\Adapter\AdapterInterface
};

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->installWebhookEventsTable($installer);
            $this->updateSalesOrderExtOrderId($installer);
            $this->installSyncSkusTable($installer);
        }

        $installer->endSetup();
    }

    /**
     * Creates the webhook events table.
     */
    private function installWebhookEventsTable($installer) {
        $tableName = $installer->getTable('flow_connector_webhook_events');

        if ($installer->getConnection()->isTableExists($tableName)) {
            return;
        } // table already exists, no need to install now

        $table = $installer->getConnection()
            ->newTable($tableName)
            ->addColumn('id', Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Primary key')
            ->addColumn('type', Table::TYPE_TEXT, 255, ['nullable' => false], 'Webhook type.')
            ->addColumn('payload', Table::TYPE_TEXT, Table::MAX_TEXT_SIZE, ['nullable' => false], 'Webhook payload')
            ->addColumn('status', Table::TYPE_TEXT, 255, ['nullable' => false], 'Processing status')
            ->addColumn('message', Table::TYPE_TEXT, 255, ['nullable' => true], 'Processing message')
            ->addColumn('triggered_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT], 'Time event was triggered')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT], 'Created At')
            ->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE], 'Updated At')
            ->addColumn('deleted_at', Table::TYPE_TIMESTAMP, null, ['nullable' => true], 'Deleted At')
            ->setComment('Flow Webhook Events')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');

        $installer->getConnection()->createTable($table);

        $installer->getConnection()->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['deleted_at'], AdapterInterface::INDEX_TYPE_INDEX),
            ['deleted_at'],
            AdapterInterface::INDEX_TYPE_INDEX
        );
    }

    /**
     * Updates sales_order.ext_order_id from 32 to 64 chars.
     */
    private function updateSalesOrderExtOrderId($installer) {
        $installer->getConnection()->changeColumn(
            $installer->getTable('sales_order'),
            'ext_order_id',
            'ext_order_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 64,
                'comment' => 'Ext Order Id'
            ]
        );
    }

    /**
     * Creates a table to hold skus to sync to Flow.
     */
    private function installSyncSkusTable($installer) {
        $tableName = $installer->getTable('flow_connector_sync_skus');

        if ($installer->getConnection()->isTableExists($tableName)) {
            return;
        } // table already exists, no need to install now

        $table = $installer->getConnection()
            ->newTable($tableName)
            ->addColumn('id', Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Primary key')
            ->addColumn('sku', Table::TYPE_TEXT, 64, ['nullable' => false], 'Sku to sync')
            ->addColumn('priority', Table::TYPE_INTEGER, null, ['nullable' => false, 'unsigned' => true, 'default' => 0], 'Processing priority')
            ->addColumn('status', Table::TYPE_TEXT, 255, ['nullable' => false], 'Processing status')
            ->addColumn('message', Table::TYPE_TEXT, 255, ['nullable' => true], 'Processing message')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT], 'Created At')
            ->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE], 'Updated At')
            ->addColumn('deleted_at', Table::TYPE_TIMESTAMP, null, ['nullable' => true], 'Deleted At')
            ->setComment('Flow Catalog Sync Skus')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');

        $installer->getConnection()->createTable($table);

        $installer->getConnection()->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['deleted_at'], AdapterInterface::INDEX_TYPE_INDEX),
            ['deleted_at'],
            AdapterInterface::INDEX_TYPE_INDEX
        );
    }
}

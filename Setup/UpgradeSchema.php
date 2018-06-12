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

        if (version_compare($context->getVersion(), '1.0.1', '<=')) {
            $this->installWebhookEventsTable($installer);
            $this->installLocalItemsTable($installer);
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
     * Create the local items table.
     */
    private function installLocalItemsTable($installer) {
        $tableName = $installer->getTable('flow_connector_local_items');

        if ($installer->getConnection()->isTableExists($tableName)) {
            return;
        } // table already exists, no need to install now

        $table = $installer->getConnection()
            ->newTable($tableName)
            ->addColumn('id', Table::TYPE_TEXT, 255, ['nullable' => false, 'primary' => true], 'Globally unique identifier')
            ->addColumn('experience_id', Table::TYPE_TEXT, 255, ['nullable' => false], 'Experience ID')
            ->addColumn('experience_key', Table::TYPE_TEXT, 255, ['nullable' => false], 'Experience Key')
            ->addColumn('experience_name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Experience Name')
            ->addColumn('experience_country', Table::TYPE_TEXT, 3, ['nullable' => true], 'ISO 3166 3 currency code as defined in https://api.flow.io/reference/countries. Example: CAN')
            ->addColumn('experience_currency', Table::TYPE_TEXT, 3, ['nullable' => true], 'The ISO 4217-3 code for the currency. Case insensitive. See https://api.flow.io/reference/currencies. Example: CAD')
            ->addColumn('experience_language', Table::TYPE_TEXT, 2, ['nullable' => true], 'ISO 639 2 language code as defined in https://api.flow.io/reference/languages. Example: en')
            ->addColumn('catalog_item_id', Table::TYPE_TEXT, 255, ['nullable' => false], 'Catalog Item Reference ID')
            ->addColumn('catalog_item_number', Table::TYPE_TEXT, 255, ['nullable' => false], 'Client\'s unique identifier for this object')
            ->addColumn('local_item_price_currency', Table::TYPE_TEXT, 255, ['nullable' => false], 'Price currency')
            ->addColumn('local_item_price_amount', Table::TYPE_TEXT, 255, ['nullable' => false], 'Price amount')
            ->addColumn('local_item_price_label', Table::TYPE_TEXT, 255, ['nullable' => false], 'Price label')
            ->addColumn('local_item_price_base_currency', Table::TYPE_TEXT, 255, ['nullable' => false], 'Base price currency')
            ->addColumn('local_item_price_base_amount', Table::TYPE_TEXT, 255, ['nullable' => false], 'Base price currency')
            ->addColumn('local_item_price_base_label', Table::TYPE_TEXT, 255, ['nullable' => false], 'Base price currency')
            ->addColumn('status', Table::TYPE_TEXT, 255, ['nullable' => false], 'Status indicating availability of a subcatalog item in an experience.')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT], 'Created At')
            ->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE], 'Updated At')
            ->addColumn('deleted_at', Table::TYPE_TIMESTAMP, null, ['nullable' => true], 'Deleted At')
            ->setComment('Flow Local Items')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');

        $installer->getConnection()->createTable($table);
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

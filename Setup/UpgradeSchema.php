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

        if (version_compare($context->getVersion(), '1.0.0', '<')) {
            $this->installWebhookEventsTable($installer);
            $this->updateSalesOrderExtOrderId($installer);
            $this->installSyncSkusTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->addStoreIdToWebhookEventsTable($installer);
            $this->addStoreIdToSyncSkusTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.9', '<')) {
            $this->installOrdersTable($installer);
        }

        $installer->endSetup();
    }

    /**
     * Creates the webhook events table.
     */
    private function installWebhookEventsTable($installer) {
        $tableName = $installer->getTable('flow_connector_webhook_events');
        $connection = $installer->getConnection();

        if ($connection->isTableExists($tableName)) {
            return;
        } // table already exists, no need to install now

        $table = $connection
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

        $connection->createTable($table);

        $connection->addIndex(
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
        $connection = $installer->getConnection();

        if ($connection->isTableExists($tableName)) {
            return;
        } // table already exists, no need to install now

        $table = $connection
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

        $connection->createTable($table);

        $connection->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['deleted_at'], AdapterInterface::INDEX_TYPE_INDEX),
            ['deleted_at'],
            AdapterInterface::INDEX_TYPE_INDEX
        );
    }

    /**
     * Adds a store_id columen to webhook events table.
     */
    private function addStoreIdToWebhookEventsTable($installer) {
        $tableName = $installer->getTable('flow_connector_webhook_events');
        $connection = $installer->getConnection();
        $columnName = 'store_id';

        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type'      => Table::TYPE_SMALLINT,
                'nullable'  => false,
                'unsigned'  => true,
                'after'     => 'id',
                'comment'   => 'Store ID'
            ]);

            $connection->addIndex(
                $tableName,
                $installer->getIdxName($tableName, ['store_id'], AdapterInterface::INDEX_TYPE_INDEX),
                ['store_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            );

            $connection->addForeignKey(
                $installer->getFkName('flow_connector_webhook_events', 'store_id', 'store', 'store_id'),
                $tableName,
                'store_id',
                $installer->getTable('store'),
                'store_id',
                Table::ACTION_CASCADE
            );
        }
    }

    /**
     * Adds a store_id column to sync skus table.
     */
    private function addStoreIdToSyncSkusTable($installer) {
        $tableName = $installer->getTable('flow_connector_sync_skus');
        $connection = $installer->getConnection();
        $columnName = 'store_id';

        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type'      => Table::TYPE_SMALLINT,
                'nullable'  => false,
                'unsigned'  => true,
                'after'     => 'id',
                'comment'   => 'Store ID'
            ]);

            $connection->addIndex(
                $tableName,
                $installer->getIdxName($tableName, ['store_id'], AdapterInterface::INDEX_TYPE_INDEX),
                ['store_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            );

            $connection->addForeignKey(
                $installer->getFkName('flow_connector_sync_skus', 'store_id', 'store', 'store_id'),
                $tableName,
                'store_id',
                $installer->getTable('store'),
                'store_id',
                Table::ACTION_CASCADE
            );
        }
    }

    /**
     * Creates a table to hold flow order data.
     */
    private function installOrdersTable($installer) {
        $tableName = $installer->getTable('flow_connector_orders');
        $connection = $installer->getConnection();

        if ($connection->isTableExists($tableName)) {
            return;
        } // table already exists, no need to install now

        $table = $connection
            ->newTable($tableName)
            ->addColumn('id', Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Primary key')
            ->addColumn('order_id', Table::TYPE_INTEGER, 10, ['unsigned' => true, 'nullable' => false], 'Magento order id')
            ->addColumn('flow_order_id', Table::TYPE_TEXT, 255, ['nullable' => false], 'Flow order id')
            ->addColumn('data', Table::TYPE_TEXT, Table::MAX_TEXT_SIZE, ['nullable' => false], 'Order data')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT], 'Created At')
            ->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE], 'Updated At')
            ->addColumn('deleted_at', Table::TYPE_TIMESTAMP, null, ['nullable' => true], 'Deleted At')
            ->setComment('Flow Orders')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');

        $connection->createTable($table);

        $connection->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['deleted_at'], AdapterInterface::INDEX_TYPE_INDEX),
            ['deleted_at'],
            AdapterInterface::INDEX_TYPE_INDEX
        );

        $connection->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['order_id'], AdapterInterface::INDEX_TYPE_UNIQUE),
            ['order_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );

        $connection->addForeignKey(
            $installer->getFkName('flow_connector_orders', 'order_id', 'sales_order', 'entity_id'),
            $tableName,
            'order_id',
            $installer->getTable('sales_order'),
            'entity_id',
            Table::ACTION_CASCADE
        );
    }

}

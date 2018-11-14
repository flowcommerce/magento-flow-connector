<?php

namespace FlowCommerce\FlowConnector\Setup;

use FlowCommerce\FlowConnector\Api\Data\SyncSkuInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var SalesSetupFactory|null
     */
    private $salesSetupFactory = null;

    /**
     * UpgradeSchema constructor.
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->installWebhookEventsTable($installer);
            $this->updateSalesOrderExtOrderId($installer);
            $this->installSyncSkusTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->addStoreIdToWebhookEventsTable($installer);
            $this->addStoreIdToSyncSkusTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.10', '<')) {
            $this->installOrdersTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.16', '<')) {
            $this->addShouldSyncChildrenToSyncSkuTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.20', '<')) {
            $this->installInventorySyncTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.30', '<')) {
            $this->addResponseAndRequestFieldsToSyncSkusTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.35', '<')) {
            $this->addUniqueIndexSyncSkusTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.36', '<')) {
            $this->addStateToSyncSkusTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.38', '<')) {
            $this->updateWebhookEventsTableMessageColumn($installer);
        }

        if (version_compare($context->getVersion(), '1.0.39', '<=')) {
            $this->addDutyVatAndRoundingToOrder($setup);
            $this->addDutyVatAndRoundingToOrderItem($setup);
            $this->addDutyVatAndRoundingToInvoice($setup);
            $this->addDutyVatAndRoundingToInvoiceItem($setup);
            $this->addDutyVatAndRoundingToCreditMemo($setup);
            $this->addDutyVatAndRoundingToCreditMemoItem($setup);
        }

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addOrderReadyToOrder($setup);
        }

        $installer->endSetup();
    }

    /**
     * Creates the webhook events table.
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function installWebhookEventsTable(SchemaSetupInterface $installer)
    {
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
     * @param SchemaSetupInterface $installer
     */
    private function updateSalesOrderExtOrderId(SchemaSetupInterface $installer)
    {
        $installer->getConnection()->changeColumn(
            $installer->getTable('sales_order'),
            'ext_order_id',
            'ext_order_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 64,
                'comment' => 'Ext Order Id',
            ]
        );
    }

    /**
     * Creates a table to hold skus to sync to Flow.
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function installSyncSkusTable(SchemaSetupInterface $installer)
    {
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
     * @param SchemaSetupInterface $installer
     */
    private function addStoreIdToWebhookEventsTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('flow_connector_webhook_events');
        $connection = $installer->getConnection();
        $columnName = 'store_id';

        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'unsigned' => true,
                'after' => 'id',
                'comment' => 'Store ID',
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
     * @param SchemaSetupInterface $installer
     */
    private function addStoreIdToSyncSkusTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('flow_connector_sync_skus');
        $connection = $installer->getConnection();
        $columnName = 'store_id';

        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'unsigned' => true,
                'after' => 'id',
                'comment' => 'Store ID',
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
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function installOrdersTable(SchemaSetupInterface $installer)
    {
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

    /**
     * Adds should_sync_children column to sync sku table
     * @param SchemaSetupInterface $installer
     */
    private function addShouldSyncChildrenToSyncSkuTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('flow_connector_sync_skus');
        $connection = $installer->getConnection();
        $columnName = 'should_sync_children';

        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type' => Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default' => 0,
                'after' => 'sku',
                'comment' => 'Should sync children products?',
            ]);
        }
    }

    /**
     * Creates a table to hold skus to sync to Flow.
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function installInventorySyncTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('flow_connector_sync_inventory');
        $connection = $installer->getConnection();

        if ($connection->isTableExists($tableName)) {
            return;
        } // table already exists, no need to install now

        $table = $connection
            ->newTable($tableName)
            ->addColumn('id', Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Primary key')
            ->addColumn('product_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false], 'Product id')
            ->addColumn('store_id', Table::TYPE_SMALLINT, null, ['unsigned' => true, 'nullable' => false], 'Store ID')
            ->addColumn('priority', Table::TYPE_INTEGER, null, ['nullable' => false, 'unsigned' => true, 'default' => 0], 'Processing priority')
            ->addColumn('status', Table::TYPE_TEXT, 255, ['nullable' => false], 'Processing status')
            ->addColumn('message', Table::TYPE_TEXT, 255, ['nullable' => true], 'Processing message')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT], 'Created At')
            ->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE], 'Updated At')
            ->addColumn('deleted_at', Table::TYPE_TIMESTAMP, null, ['nullable' => true], 'Deleted At')
            ->setComment('Flow Inventory Sync Queue table')
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
            $installer->getIdxName($tableName, ['status']),
            ['status']
        );

        $connection->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['product_id']),
            ['product_id']
        );

        $connection->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['priority']),
            ['priority']
        );

        $connection->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['store_id'], AdapterInterface::INDEX_TYPE_INDEX),
            ['store_id'],
            AdapterInterface::INDEX_TYPE_INDEX
        );

        $connection->addForeignKey(
            $installer->getFkName($tableName, 'store_id', 'store', 'store_id'),
            $tableName,
            'store_id',
            $installer->getTable('store'),
            'store_id',
            Table::ACTION_CASCADE
        );

        $connection->addForeignKey(
            $installer->getFkName($tableName, 'product_id', 'catalog_product_entity', 'entity_id'),
            $tableName,
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        );
    }



    /**
     * Adds request_body, response_body, response_headers and response_status columns to sync sku table
     * @param SchemaSetupInterface $installer
     */
    private function addResponseAndRequestFieldsToSyncSkusTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('flow_connector_sync_skus');
        $connection = $installer->getConnection();

        $columnName = 'request_url';
        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'after' => 'deleted_at',
                'comment' => 'Request URL',
            ]);
        }

        $columnName = 'request_body';
        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'after' => 'request_url',
                'comment' => 'Request body',
            ]);
        }

        $columnName = 'response_headers';
        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'after' => 'request_body',
                'comment' => 'Response Headers',
            ]);
        }

        $columnName = 'response_body';
        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'after' => 'response_headers',
                'comment' => 'Response Body',
            ]);
        }
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function addUniqueIndexSyncSkusTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('flow_connector_sync_skus');
        $connection = $installer->getConnection();

        // Truncating goes into data upgrade script, but adding unique index will fail on table that contains duplicates
        $connection->truncateTable($tableName);

        $connection->addIndex(
            $tableName,
            $installer->getIdxName($tableName, ['store_id', 'sku'], AdapterInterface::INDEX_TYPE_INDEX),
            ['store_id', 'sku'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function addStateToSyncSkusTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable('flow_connector_sync_skus');
        $connection = $installer->getConnection();

        $columnName = 'state';
        if ($connection->tableColumnExists($tableName, $columnName) === false) {
            $connection->addColumn($tableName, $columnName, [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'after' => 'status',
                'comment' => 'Sync state',
                'default' => SyncSkuInterface::STATUS_NEW
            ]);
        }
    }

    /**
     * Updates flow_connector_webhook_events.message from 255 to MAX_TEXT_SIZE
     * @param SchemaSetupInterface $installer
     */
    private function updateWebhookEventsTableMessageColumn(SchemaSetupInterface $installer)
    {
        $installer->getConnection()->changeColumn(
            $installer->getTable('flow_connector_webhook_events'),
            'message',
            'message',
            [
                'type' => Table::TYPE_TEXT,
                'length' => Table::MAX_TEXT_SIZE,
            ]
        );
    }

    /**
     * Add Duty, VAT and Rounding attributes to order.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addDutyVatAndRoundingToOrder(SchemaSetupInterface $setup)
    {
        $salesSetup = $this->salesSetupFactory->create();

        $attributes = [
            'flow_connector_base_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
        ];

        foreach ($attributes as $attributeCode => $attributeParams) {
            $salesSetup->addAttribute('order', $attributeCode, $attributeParams);
        }
    }

    /**
     * Add Duty, VAT and Rounding attributes to order item.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addDutyVatAndRoundingToOrderItem(SchemaSetupInterface $setup)
    {
        $salesSetup = $this->salesSetupFactory->create();

        $attributes = [
            'flow_connector_base_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
        ];

        foreach ($attributes as $attributeCode => $attributeParams) {
            $salesSetup->addAttribute('order_item', $attributeCode, $attributeParams);
        }
    }

    /**
     * Add Duty, VAT and Rounding attributes to invoice.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addDutyVatAndRoundingToInvoice(SchemaSetupInterface $setup)
    {
        $salesSetup = $this->salesSetupFactory->create();

        $attributes = [
            'flow_connector_base_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
        ];

        foreach ($attributes as $attributeCode => $attributeParams) {
            $salesSetup->addAttribute('invoice', $attributeCode, $attributeParams);
        }
    }

    /**
     * Add Duty, VAT and Rounding attributes to invoice item.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addDutyVatAndRoundingToInvoiceItem(SchemaSetupInterface $setup)
    {
        $salesSetup = $this->salesSetupFactory->create();

        $attributes = [
            'flow_connector_base_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
        ];

        foreach ($attributes as $attributeCode => $attributeParams) {
            $salesSetup->addAttribute('invoice_item', $attributeCode, $attributeParams);
        }
    }

    /**
     * Add Duty, VAT and Rounding attributes to Credit Memo.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addDutyVatAndRoundingToCreditMemo(SchemaSetupInterface $setup)
    {
        $salesSetup = $this->salesSetupFactory->create();

        $attributes = [
            'flow_connector_base_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
        ];

        foreach ($attributes as $attributeCode => $attributeParams) {
            $salesSetup->addAttribute('creditmemo', $attributeCode, $attributeParams);
        }
    }

    /**
     * Add Duty, VAT and Rounding attributes to Credit Memo item.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addDutyVatAndRoundingToCreditMemoItem(SchemaSetupInterface $setup)
    {
        $salesSetup = $this->salesSetupFactory->create();

        $attributes = [
            'flow_connector_base_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_item_price' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_vat' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_base_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            'flow_connector_rounding' => ['type' => 'decimal', 'visible' => false, 'required' => false],
        ];

        foreach ($attributes as $attributeCode => $attributeParams) {
            $salesSetup->addAttribute('creditmemo_item', $attributeCode, $attributeParams);
        }
    }

    /**
     * Add Order Ready to Order
     *
     * @param SchemaSetupInterface $setup
     */
    private function addOrderReadyToOrder(SchemaSetupInterface $setup)
    {
        $salesSetup = $this->salesSetupFactory->create();

        $attributes = [
            'flow_connector_order_ready' => ['type' => 'int', 'visible' => false, 'required' => false, 'default' => 0]
        ];

        foreach ($attributes as $attributeCode => $attributeParams) {
            $salesSetup->addAttribute('order', $attributeCode, $attributeParams);
        }
    }
}

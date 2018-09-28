<?php

namespace FlowCommerce\FlowConnector\Api\Data;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;

/**
 * Class InventorySyncInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface InventorySyncInterface
{
    /**
     * Status - Done
     */
    const STATUS_DONE = 'done';

    /**
     * Status - Error
     */
    const STATUS_ERROR = 'error';

    /**
     * Data Key - ID
     */
    const DATA_KEY_ID = 'id';

    /**
     * Status - New
     */
    const STATUS_NEW = 'new';

    /**
     * Status - Processing
     */
    const STATUS_PROCESSING = 'processing';

    /**
     * Data Key - Store ID
     */
    const DATA_KEY_STORE_ID = 'store_id';

    /**
     * Data Key - Product ID
     */
    const DATA_KEY_PRODUCT_ID = 'product_id';

    /**
     * Data Key - Priority
     */
    const DATA_KEY_PRIORITY = 'priority';

    /**
     * Data Key - Status
     */
    const DATA_KEY_STATUS = 'status';

    /**
     * Data Key - Message
     */
    const DATA_KEY_MESSAGE = 'message';

    /**
     * Data Key - Created At
     */
    const DATA_KEY_CREATED_AT = 'created_at';

    /**
     * Data Key - Updated At
     */
    const DATA_KEY_UPDATED_AT = 'updated_at';

    /**
     * Data Key - Deleted At
     */
    const DATA_KEY_DELETED_AT = 'deleted_at';

    /**
     * Getter - ID
     * @return int|null
     */
    public function getId();

    /**
     * Getter - Store ID
     * @return int|null
     */
    public function getStoreId();

    /**
     * Getter - Product ID
     * @return int|null
     */
    public function getProductId();

    /**
     * Getter - Priority
     * @return int|null
     */
    public function getPriority();

    /**
     * Getter - Status
     * @return string|null
     */
    public function getStatus();

    /**
     * Getter - Message
     * @return string|null
     */
    public function getMessage();

    /**
     * Getter - Created At
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Getter - Updated At
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Getter - Deleted At
     * @return string|null
     */
    public function getDeletedAt();

    /**
     * Getter - Product
     * @return ProductInterface|null
     */
    public function getProduct();

    /**
     * Getter - Stock Item
     * @return StockItemInterface|null
     */
    public function getStockItem();

    /**
     * Setter - ID
     * @param int $value
     * @return SyncSkuInterface
     */
    public function setId($value);

    /**
     * Setter - Store ID
     * @param int $value
     * @return SyncSkuInterface
     */
    public function setStoreId($value);

    /**
     * Setter - Product
     * @param ProductInterface $value
     * @return SyncSkuInterface
     */
    public function setProduct(ProductInterface $value);

    /**
     * Setter - Product ID
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setProductId($value);

    /**
     * Setter - Priority
     * @param int $value
     * @return SyncSkuInterface
     */
    public function setPriority($value);

    /**
     * Setter - Status
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setStatus($value);

    /**
     * Setter - Stock Item
     * @param StockItemInterface $value
     * @return SyncSkuInterface
     */
    public function setStockItem(StockItemInterface $value);

    /**
     * Setter - Message
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setMessage($value);

    /**
     * Setter - Created At
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setCreatedAt($value);

    /**
     * Setter - Updated At
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setUpdatedAt($value);

    /**
     * Setter - Deleted At
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setDeletedAt($value);
}

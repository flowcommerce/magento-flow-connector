<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface;
use FlowCommerce\FlowConnector\Model\ResourceModel\InventorySync as InventorySyncResourceModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class InventorySync
 * @package FlowCommerce\FlowConnector\Model
 */
class InventorySync extends AbstractModel implements InventorySyncInterface
{
    /**
     * Event Prefix
     * @var string
     */
    protected $_eventPrefix = 'flow_connector_inventory_sync';

    /**
     * Stock Item
     * @var ProductInterface
     */
    protected $product = null;

    /**
     * Stock Item
     * @var StockItemInterface
     */
    protected $stockItem = null;

    /**
     * Initializes the model
     */
    protected function _construct()
    {
        $this->_init(InventorySyncResourceModel::class);
    }

    /**
     * Before Save - Sets status as new in case it is null
     * @return void
     */
    public function beforeSave()
    {
        if ($this->getStatus() == null) {
            $this->setStatus(self::STATUS_NEW);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        return $this->getData(self::DATA_KEY_STORE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductId()
    {
        return $this->getData(self::DATA_KEY_PRODUCT_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->getData(self::DATA_KEY_PRIORITY);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->getData(self::DATA_KEY_STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->getData(self::DATA_KEY_MESSAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(self::DATA_KEY_CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::DATA_KEY_UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeletedAt()
    {
        return $this->getData(self::DATA_KEY_DELETED_AT);
    }

    /**
     * @return ProductInterface|null
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return StockItemInterface|null
     */
    public function getStockItem()
    {
        return $this->stockItem;
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreId($value)
    {
        $this->setData(self::DATA_KEY_STORE_ID, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setProductId($value)
    {
        $this->setData(self::DATA_KEY_PRODUCT_ID, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($value)
    {
        $this->setData(self::DATA_KEY_PRIORITY, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($value)
    {
        $this->setData(self::DATA_KEY_STATUS, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($value)
    {
        $this->setData(self::DATA_KEY_MESSAGE, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($value)
    {
        $this->setData(self::DATA_KEY_CREATED_AT, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt($value)
    {
        $this->setData(self::DATA_KEY_UPDATED_AT, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeletedAt($value)
    {
        $this->setData(self::DATA_KEY_DELETED_AT, $value);
        return $this;
    }

    /**
     * Set product associated with this SyncSku
     * @param ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * Set product associated with this SyncSku
     * @param StockItemInterface $stockItem
     * @return $this
     */
    public function setStockItem(StockItemInterface $stockItem)
    {
        $this->stockItem = $stockItem;
        return $this;
    }
}

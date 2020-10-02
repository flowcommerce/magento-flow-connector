<?php

namespace FlowCommerce\FlowConnector\Model;

use \FlowCommerce\FlowConnector\Api\Data\SyncOrderInterface;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\DataObject\IdentityInterface;
use \Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;

/**
 * Class SyncOrder
 * @package FlowCommerce\FlowConnector\Model
 */
class SyncOrder extends AbstractModel implements SyncOrderInterface, IdentityInterface
{
    /**
     * Sync sku cache tag
     */
    const CACHE_TAG = 'flow_connector_sync_orders';

    /**
     * Cache tag
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * SyncOrder constructor.
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initializes the model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\SyncOrder::class);
    }

    /**
     * Return cache identities
     * @return array|string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
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
    public function getValue()
    {
        return $this->getData(self::DATA_KEY_VALUE);
    }

    /**
     * {@inheritdoc}
     */
    public function getIncrementId()
    {
        return $this->getData(self::DATA_KEY_INCREMENT_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages()
    {
        return $this->getData(self::DATA_KEY_MESSAGES);
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
    public function setStoreId($value)
    {
        $this->setData(self::DATA_KEY_STORE_ID, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->setData(self::DATA_KEY_VALUE, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setIncrementId($value)
    {
        $this->setData(self::DATA_KEY_INCREMENT_ID, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMessages($value)
    {
        $this->setData(self::DATA_KEY_MESSAGES, $value);
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
}

<?php

namespace FlowCommerce\FlowConnector\Model;

use \FlowCommerce\FlowConnector\Api\Data\SyncSkuInterface;
use \Magento\Catalog\Api\Data\ProductInterface;
use \Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\DataObject\IdentityInterface;
use \Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;

/**
 * Class SyncSku
 * @package FlowCommerce\FlowConnector\Model
 */
class SyncSku extends AbstractModel implements SyncSkuInterface, IdentityInterface
{
    /**
     * Cache Tag
     */
    const CACHE_TAG = 'flow_connector_sync_skus';

    /**
     * Cache tag
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Event Prefix
     * @var string
     */
    protected $_eventPrefix = 'flow_connector_sync_skus';

    /**
     * Product
     * @var ProductInterface
     */
    protected $product = null;

    /**
     * Product Repository
     * @var ProductRepository
     */
    protected $productRepository = null;

    /**
     * SyncSku constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ProductRepository $productRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ProductRepository $productRepository,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->productRepository = $productRepository;
    }


    /**
     * Initializes the model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\SyncSku::class);
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
     * Returns associated product
     * @return ProductInterface|null
     */
    public function getProduct()
    {
        if ($this->product === null && $this->getSku()) {
            try {
                $product = $this->productRepository->get($this->getSku());
            } catch (NoSuchEntityException $e) {
                $product = false;
            }
            $this->product = $product;
        }
        return $this->product;
    }

    /**
     * Before Save - Sets status as new in case it is null
     * @return AbstractModel|void
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
    public function getSku()
    {
        return $this->getData(self::DATA_KEY_SKU);
    }

    /**
     * {@inheritdoc}
     */
    public function isShouldSyncChildren()
    {
        return (bool) $this->getData(self::DATA_KEY_SHOULD_SYNC_CHILDREN);
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
     * {@inheritdoc}
     */
    public function getRequestUrl()
    {
        return $this->getData(self::DATA_KEY_REQUEST_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestBody()
    {
        return $this->getData(self::DATA_KEY_REQUEST_BODY);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeaders()
    {
        return $this->getData(self::DATA_KEY_RESPONSE_HEADERS);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseBody()
    {
        return $this->getData(self::DATA_KEY_RESPONSE_BODY);
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
    public function setSku($value)
    {
        $this->setData(self::DATA_KEY_SKU, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setShouldSyncChildren($value)
    {
        $this->setData(self::DATA_KEY_SHOULD_SYNC_CHILDREN, (bool) $value);
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
     * {@inheritdoc}
     */
    public function setRequestUrl($value)
    {
        $this->setData(self::DATA_KEY_REQUEST_URL, (string) $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestBody($value)
    {
        $this->setData(self::DATA_KEY_REQUEST_BODY, (string) $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponseHeaders($value)
    {
        $this->setData(self::DATA_KEY_RESPONSE_HEADERS, (string) $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponseBody($value)
    {
        $this->setData(self::DATA_KEY_RESPONSE_BODY, (string) $value);
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
     * Getter - State
     * @return string|null
     */
    public function getState()
    {
        return $this->getData(self::DATA_KEY_STATE);
    }

    /**
     * Setter - State
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setState($value)
    {
        $this->setData(self::DATA_KEY_STATE, $value);
        return $this;
    }
}

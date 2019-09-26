<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Model class for storing Flow order data.
 */
class Order extends AbstractModel implements IdentityInterface
{
    /**
     * Possible values for status
     */
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ERROR = 'error';
    const STATUS_DONE = 'done';

    const CACHE_TAG = 'flow_connector_orders';
    protected $_cacheTag = 'flow_connector_orders';
    protected $_eventPrefix = 'flow_connector_orders';

    protected $logger;
    protected $jsonHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    protected function _construct()
    {
        $this->_init(ResourceModel\Order::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        return [];
    }

    /**
     * Returns the cross dock address for the order.
     *
     * https://docs.flow.io/type/shipping-address
     *
     * @return array A Flow shipping address object
     */
    public function getCrossDockAddress()
    {
        $data = $this->jsonHelper->jsonDecode($this->getData());

        foreach ($data['deliveries'] as $delivery) {
            if (array_key_exists('options', $delivery)) {
                foreach ($delivery['options'] as $option) {
                    if (array_key_exists('send_to', $option)) {
                        return $option['send_to'];
                    }
                }
            }
        }

        return null;
    }
}

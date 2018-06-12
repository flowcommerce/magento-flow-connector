<?php

namespace Flow\FlowConnector\Model;

use Magento\Framework\{
    Model\AbstractModel,
    DataObject\IdentityInterface
};

/**
 * Model class for storing skus to sync to Flow.
 */
class SyncSku extends AbstractModel implements IdentityInterface {

    // SyncSku status values
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ERROR = 'error';
    const STATUS_DONE = 'done';

    const CACHE_TAG = 'flow_connector_sync_skus';
    protected $_cacheTag = 'flow_connector_sync_skus';
    protected $_eventPrefix = 'flow_connector_sync_skus';

    protected function _construct() {
        $this->_init(ResourceModel\SyncSku::class);
    }

    public function getIdentities() {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues() {
        return [];
    }

    public function beforeSave() {
        if ($this->getStatus() == null) {
            $this->setStatus(self::STATUS_NEW);
        }
    }
}

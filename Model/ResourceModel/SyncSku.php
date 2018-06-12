<?php

namespace Flow\FlowConnector\Model\ResourceModel;

use Magento\Framework\{
    Model\ResourceModel\Db\AbstractDb,
    Model\ResourceModel\Db\Context
};

class SyncSku extends AbstractDb {

    public function __construct(Context $ctx) {
        parent::__construct($ctx);
    }

    protected function _construct() {
        $this->_init('flow_connector_sync_skus', 'id');
    }

    protected function _getDefaultAttributes() {
        return [
            'created_at',
            'updated_at',
        ];
    }
}

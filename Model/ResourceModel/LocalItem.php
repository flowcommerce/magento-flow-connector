<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use Magento\Framework\{
    Model\ResourceModel\Db\AbstractDb,
    Model\AbstractModel,
    Model\ResourceModel\Db\Context,
    Encryption\EncryptorInterface
};

class LocalItem extends AbstractDb {

    public function __construct(Context $ctx) {
        parent::__construct($ctx);
    }

    protected function _construct() {
        $this->_init('flow_connector_local_items', 'id');
    }

    protected function _getDefaultAttributes() {
        return [
            'created_at',
            'updated_at',
        ];
    }
}

<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\{
    Model\AbstractModel,
    DataObject\IdentityInterface
};

/**
 * Model class for storing Flow localized item data.
 */
class LocalItem extends AbstractModel implements IdentityInterface {

	const CACHE_TAG = 'flow_connector_local_items';
	protected $_cacheTag = 'flow_connector_local_items';
	protected $_eventPrefix = 'flow_connector_local_items';

	protected function _construct() {
		$this->_init(ResourceModel\LocalItem::class);
	}

	public function getIdentities() {
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues() {
		return [];
	}
}

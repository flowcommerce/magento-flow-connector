<?php

namespace FlowCommerce\FlowConnector\Api\Data;

use \FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface as InventorySync;
use \Magento\Framework\Api\Search\SearchResultInterface;

/**
 * Class InventorySyncSearchResultsInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface InventorySyncSearchResultsInterface extends SearchResultInterface
{
    /**
     * Returns Items
     * @return InventorySync[]
     */
    public function getItems();

    /**
     * Set items list.
     *
     * @param InventorySync[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}

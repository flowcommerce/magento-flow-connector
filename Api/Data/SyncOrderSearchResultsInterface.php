<?php

namespace FlowCommerce\FlowConnector\Api\Data;

use \FlowCommerce\FlowConnector\Model\SyncOrder;
use \Magento\Framework\Api\Search\SearchResultInterface;

/**
 * Class SyncOrderSearchResultsInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface SyncOrderSearchResultsInterface extends SearchResultInterface
{
    /**
     * Returns Items
     * @return SyncOrder[]
     */
    public function getItems();

    /**
     * Set items list.
     *
     * @param SyncOrder[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}

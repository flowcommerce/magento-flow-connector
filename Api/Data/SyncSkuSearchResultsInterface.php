<?php

namespace FlowCommerce\FlowConnector\Api\Data;

use \FlowCommerce\FlowConnector\Model\SyncSku;
use \Magento\Framework\Api\Search\SearchResultInterface;

/**
 * Class SyncSkuSearchResultsInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface SyncSkuSearchResultsInterface extends SearchResultInterface
{
    /**
     * Returns Items
     * @return SyncSku[]
     */
    public function getItems();

    /**
     * Set items list.
     *
     * @param SyncSku[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}

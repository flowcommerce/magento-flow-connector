<?php

namespace FlowCommerce\FlowConnector\Model\Api\Inventory\Updates;

use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface;
use FlowCommerce\FlowConnector\Model\InventoryCenterManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class InventoryDataMapper
 * @package FlowCommerce\FlowConnector\Model\Api\Inventory\Updates
 */
class InventoryDataMapper
{
    /**
     * Inventory Update Type - SET
     */
    const INVENTORY_UPDATE_TYPE_SET = 'set';

    /**
     * @var string[]
     */
    private $inventoryCenterKeyByStore = [];

    /**
     * @var InventoryCenterManager
     */
    private $inventoryCenterManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * InventoryDataMapper constructor.
     * @param InventoryCenterManager $inventoryCenterManager
     * @param Logger $logger
     */
    public function __construct(
        InventoryCenterManager $inventoryCenterManager,
        Logger $logger
    ) {
        $this->inventoryCenterManager = $inventoryCenterManager;
        $this->logger = $logger;
    }

    /**
     * Returns default center key
     * @param int $storeId
     * @return string
     */
    private function getDefaultCenterKey($storeId)
    {
        if (!array_key_exists($storeId, $this->inventoryCenterKeyByStore)) {
            $this->inventoryCenterKeyByStore[$storeId] =
                $this->inventoryCenterManager->getDefaultCenterKeyForStore($storeId);
        }
        return $this->inventoryCenterKeyByStore[$storeId];
    }

    /**
     * Idempotency Key for inventory update request
     * @param InventorySyncInterface $inventorySync
     * @return string
     */
    private function getIdempotencyKey(InventorySyncInterface $inventorySync)
    {
        return md5($inventorySync->getId() . $inventorySync->getCreatedAt() . $inventorySync->getUpdatedAt());
    }

    /**
     * Maps an inventory sync o
     * @param InventorySyncInterface $inventorySync
     * @return string[]
     */
    public function map(InventorySyncInterface $inventorySync)
    {
        $this->logger
            ->info(
                'Converting product stock item to Flow inventory_updates request. Product ID:'
                . $inventorySync->getProduct()->getSku()
            );

        $itemData = [
            'center' => $this->getDefaultCenterKey($inventorySync->getStoreId()),
            'idempotency_key' => $this->getIdempotencyKey($inventorySync),
            'item_number' => $inventorySync->getProduct()->getSku(),
            'quantity' => $inventorySync->getStockItem()->getQty(),
            'type' => self::INVENTORY_UPDATE_TYPE_SET,
        ];

        return $itemData;
    }
}

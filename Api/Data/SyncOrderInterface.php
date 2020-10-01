<?php

namespace FlowCommerce\FlowConnector\Api\Data;

use \FlowCommerce\FlowConnector\Model\ResourceModel\SyncOrder as SyncOrderResourceModel;

/**
 * Class SyncOrderInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface SyncOrderInterface
{
    /**
     * Data Key - Store ID
     */
    const DATA_KEY_STORE_ID = 'store_id';

    /**
     * Data Key - Value
     */
    const DATA_KEY_VALUE = 'value';

    /**
     * Data Key - Increment Id
     */
    const DATA_KEY_INCREMENT_ID = 'increment_id';

    /**
     * Data Key - Messages
     */
    const DATA_KEY_MESSAGES = 'messages';

    /**
     * Data Key - Created At
     */
    const DATA_KEY_CREATED_AT = 'created_at';

    /**
     * Data Key - Updated At
     */
    const DATA_KEY_UPDATED_AT = 'updated_at';

    /**
     * Getter - Value
     * @return int|null
     */
    public function getValue();

    /**
     * Getter - Increment Id
     * @return int|null
     */
    public function getIncrementId();

    /**
     * Getter - Store ID
     * @return int|null
     */
    public function getStoreId();

    /**
     * Getter - Messages
     * @return string|null
     */
    public function getMessages();

    /**
     * Getter - Created At
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Getter - Updated At
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Getter - Deleted At
     * @return string|null
     */
    public function getDeletedAt();

    /**
     * Setter - Value
     * @param int $value
     * @return SyncOrderInterface
     */
    public function setValue($value);

    /**
     * Setter - Increment Id
     * @param int $value
     * @return SyncOrderInterface
     */
    public function setIncrementId($value);

    /**
     * Setter - Store ID
     * @param int $value
     * @return SyncOrderInterface
     */
    public function setStoreId($value);

    /**
     * Setter - Messages
     * @param string $value
     * @return SyncOrderInterface
     */
    public function setMessages($value);

    /**
     * Setter - Created At
     * @param string $value
     * @return SyncOrderInterface
     */
    public function setCreatedAt($value);

    /**
     * Setter - Updated At
     * @param string $value
     * @return SyncOrderInterface
     */
    public function setUpdatedAt($value);
}

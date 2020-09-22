<?php

namespace FlowCommerce\FlowConnector\Api\Data;

use \FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent as WebhookResourceModel;

/**
 * Interface WebhookEventInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface WebhookEventInterface
{
    /**
     * Data Key - ID
     */
    const DATA_KEY_ID = 'id';

    /**
     * Data Key - Store ID
     */
    const DATA_KEY_STORE_ID = 'store_id';

    /**
     * Data Key - Type
     */
    const DATA_KEY_TYPE = 'type';

    /**
     * Data Key - Payload
     */
    const DATA_KEY_PAYLOAD = 'payload';

    /**
     * Data Key - Status
     */
    const DATA_KEY_STATUS = 'status';

    /**
     * Data Key - Message
     */
    const DATA_KEY_MESSAGE = 'message';

    /**
     * Data Key - Triggered at
     */
    const DATA_KEY_TRIGGERED_AT = 'triggered_at';

    /**
     * Data Key - Created at
     */
    const DATA_KEY_CREATED_AT = 'created_at';

    /**
     * Data Key - Updated at
     */
    const DATA_KEY_UPDATED_AT = 'updated_at';

    /**
     * Data Key - Deleted at
     */
    const DATA_KEY_DELETED_AT = 'deleted_at';

    /**
     * Status - New
     */
    const STATUS_NEW = WebhookResourceModel::STATUS_NEW;

    /**
     * Status - Processing
     */
    const STATUS_PROCESSING = WebhookResourceModel::STATUS_PROCESSING;

    /**
     * Status - Error
     */
    const STATUS_ERROR = WebhookResourceModel::STATUS_ERROR;

    /**
     * Status - Done
     */
    const STATUS_DONE = WebhookResourceModel::STATUS_DONE;

    /**
     * Getter - Id
     * @return int|null
     */
    public function getId();

    /**
     * Getter - Type
     * @return string|null
     */
    public function getType();

    /**
     * Getter - Payload
     * @return string|null
     */
    public function getPayload();

    /**
     * Getter - Status
     * @return string|null
     */
    public function getStatus();

    /**
     * Getter - Message
     * @return string|null
     */
    public function getMessage();

    /**
     * Getter - Triggered at
     * @return string|null
     */
    public function getTriggeredAt();

    /**
     * Getter - Created at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Getter - Updated at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Getter - Deleted at
     * @return string|null
     */
    public function getDeletedAt();

    /**
     * Setter - Id
     * @param int|null $value
     * @return $this
     */
    public function setId($value);

    /**
     * Setter - Type
     * @param string|null $value
     * @return $this
     */
    public function setType($value);

    /**
     * Setter - Payload
     * @param string|null $value
     * @return $this
     */
    public function setPayload($value);

    /**
     * Setter - Status
     * @param string|null $value
     * @return $this
     */
    public function setStatus($value);

    /**
     * Setter - Message
     * @param string|null $value
     * @return $this
     */
    public function setMessage($value);

    /**
     * Setter - Triggered at
     * @param string|null $value
     * @return $this
     */
    public function setTriggeredAt($value);

    /**
     * Setter - Created at
     * @param string|null $value
     * @return $this
     */
    public function setCreatedAt($value);

    /**
     * Setter - Updated at
     * @param string|null $value
     * @return $this
     */
    public function setUpdatedAt($value);

    /**
     * Setter - Deleted at
     * @param string|null $value
     * @return $this
     */
    public function setDeletedAt($value);
}

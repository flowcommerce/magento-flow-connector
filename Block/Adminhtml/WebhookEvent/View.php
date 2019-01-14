<?php

namespace FlowCommerce\FlowConnector\Block\Adminhtml\WebhookEvent;

use Exception;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Backend\Block\Template;
use Magento\Framework\Registry;

/**
 * Class View
 * @package FlowCommerce\FlowConnector\Block\Adminhtml\WebhookEvent
 */
class View extends Template
{
    /**
     * Registry Key - Webhook
     */
    const REGISTRY_KEY_WEBHOOK_EVENT = 'flowcommerce_flowconnector_webhookevent';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var WebhookEvent
     */
    private $webhookEvent;

    /**
     * View constructor.
     * @param Template\Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * Webhooks event getter
     * @return WebhookEvent
     */
    public function getWebhookEvent()
    {
        if ($this->webhookEvent === null) {
            $this->webhookEvent = $this->registry->registry(self::REGISTRY_KEY_WEBHOOK_EVENT);
        }
        return $this->webhookEvent;
    }

    /**
     * @param string $jsonString
     * @return string
     */
    public function beautifyJson($jsonString)
    {
        try {
            $return = '<pre>' . json_encode(json_decode($jsonString), JSON_PRETTY_PRINT) . '</pre>';
        } catch (Exception $e) {
            $return = $jsonString;
        }
        return $return;
    }
}

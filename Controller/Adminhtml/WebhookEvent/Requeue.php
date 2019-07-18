<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\WebhookEvent;

use Exception;
use FlowCommerce\FlowConnector\Model\WebhookEventFactory;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class Requeue
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\WebhookEvent
 */
class Requeue extends Action
{
    /**
     * Authorization level of a basic admin session
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var WebhookEventFactory
     */
    private $webhookEventFactory;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * @param Context $context
     * @param WebhookEventManager $webhookEventManager
     * @param WebhookEventFactory $webhookEventFactory
     */
    public function __construct(
        Context $context,
        WebhookEventManager $webhookEventManager,
        WebhookEventFactory $webhookEventFactory
    ) {
        parent::__construct($context);
        $this->webhookEventManager = $webhookEventManager;
        $this->webhookEventFactory = $webhookEventFactory;
    }

    /**
     * Requeue action
     * @return Redirect
     */
    public function execute()
    {
        $return = $this->resultRedirectFactory->create();
        $return->setRefererUrl();

        $id = $this->getRequest()->getParam('id');

        if ($id) {
            $webhookEvent = $this->webhookEventFactory->create();
            $webhookEvent->load($id);
            if ($webhookEvent->getId()) {
                try {
                    $this->webhookEventManager->requeue($webhookEvent, '', true);
                    $this->messageManager->addSuccessMessage('The webhook has been requeued');
                } catch (Exception $e) {
                    $this->messageManager->addExceptionMessage($e);
                }
            } else {
                $this->messageManager->addErrorMessage('The webhook event no longer exists');
            }
        } else {
            $this->messageManager->addErrorMessage('No webhook event has been informed.');
        }
        return $return;
    }
}

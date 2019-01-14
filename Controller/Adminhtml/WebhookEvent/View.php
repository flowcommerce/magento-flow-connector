<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\WebhookEvent;

use FlowCommerce\FlowConnector\Model\WebhookEventFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface as DataPersistor;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class View
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\WebhookEvent
 */
class View extends Action
{
    /**
     * Authorization level of a basic admin session
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * Registry Key - Webhook
     */
    const REGISTRY_KEY_WEBHOOK_EVENT = 'flowcommerce_flowconnector_webhookevent';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var DataPersistor
     */
    private $dataPersistor;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var WebhookEventFactory
     */
    private $webhookEventFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DataPersistor $dataPersistor
     * @param WebhookEventFactory $webhookEventFactory
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataPersistor $dataPersistor,
        WebhookEventFactory $webhookEventFactory,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor;
        $this->webhookEventFactory = $webhookEventFactory;
        $this->registry = $registry;
    }

    /**
     * View action
     * @return Page|Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $webhookEvent = $this->webhookEventFactory->create();

        if ($id) {
            $webhookEvent->load($id);
            if (!$webhookEvent->getId()) {
                $this->messageManager->addError(__('This webhook event no longer exists.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->registry->register(self::REGISTRY_KEY_WEBHOOK_EVENT, $webhookEvent);

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('FlowConnector::webhook_events');
        $resultPage->addBreadcrumb(__('Flow Connector'), __('Flow Connector'));
        $resultPage->addBreadcrumb(__('Webhook Events'), __('Webhook Events'));
        $resultPage->addBreadcrumb(__('View'), __('View'));
        $resultPage->getConfig()->getTitle()->prepend(__('View Webhook Event'));

        return $resultPage;
    }
}

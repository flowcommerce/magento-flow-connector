<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\SyncOrder;

use FlowCommerce\FlowConnector\Model\SyncOrderFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface as DataPersistor;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class View
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\SyncOrder
 */
class View extends Action
{
    /**
     * Authorization level of a basic admin session
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * Registry Key - Order Sync
     */
    const REGISTRY_KEY_SYNC_ORDER = 'flowcommerce_flowconnector_sync_order';

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
     * @var SyncOrderFactory
     */
    private $syncOrderFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DataPersistor $dataPersistor
     * @param SyncOrderFactory $syncOrderFactory
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataPersistor $dataPersistor,
        SyncOrderFactory $syncOrderFactory,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor;
        $this->syncOrderFactory = $syncOrderFactory;
        $this->registry = $registry;
    }

    /**
     * View action
     * @return Page|Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $syncOrder = $this->syncOrderFactory->create();

        if ($id) {
            $syncOrder->load($id);
            if (!$syncOrder->getId()) {
                $this->messageManager->addError(__('This Order Sync no longer exists.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->registry->register(self::REGISTRY_KEY_SYNC_ORDER, $syncOrder);

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('FlowConnector::sync_order');
        $resultPage->addBreadcrumb(__('Flow Connector'), __('Flow Connector'));
        $resultPage->addBreadcrumb(__('Order Sync'), __('Order Sync'));
        $resultPage->addBreadcrumb(__('View'), __('View'));
        $resultPage->getConfig()->getTitle()->prepend(__('View Order Sync'));

        return $resultPage;
    }
}

<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\SyncOrder;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Request\DataPersistorInterface as DataPersistor;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var DataPersistor
     */
    protected $dataPersistor;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DataPersistor $dataPersistor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataPersistor $dataPersistor
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * Index action
     * @return Page
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('FlowConnector::sync_order');
        $resultPage->addBreadcrumb(__('Flow Connector'), __('Flow Connector'));
        $resultPage->addBreadcrumb(__('Order Sync'), __('Order Sync'));
        $resultPage->getConfig()->getTitle()->prepend(__('Order Sync'));

        $this->dataPersistor->clear('flowconnector_sync_order');

        return $resultPage;
    }
}

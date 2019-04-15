<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\SyncSku;

use FlowCommerce\FlowConnector\Model\SyncSkuFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface as DataPersistor;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class View
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\SyncSku
 */
class View extends Action
{
    /**
     * Authorization level of a basic admin session
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * Registry Key - Catalog Sync
     */
    const REGISTRY_KEY_SYNC_SKU = 'flowcommerce_flowconnector_sync_sku';

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
     * @var SyncSkuFactory
     */
    private $syncSkuFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DataPersistor $dataPersistor
     * @param SyncSkuFactory $syncSkuFactory
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataPersistor $dataPersistor,
        SyncSkuFactory $syncSkuFactory,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor;
        $this->syncSkuFactory = $syncSkuFactory;
        $this->registry = $registry;
    }

    /**
     * View action
     * @return Page|Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $syncSku = $this->syncSkuFactory->create();

        if ($id) {
            $syncSku->load($id);
            if (!$syncSku->getId()) {
                $this->messageManager->addError(__('This Catalog Sync no longer exists.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->registry->register(self::REGISTRY_KEY_SYNC_SKU, $syncSku);

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('FlowConnector::sync_sku');
        $resultPage->addBreadcrumb(__('Flow Connector'), __('Flow Connector'));
        $resultPage->addBreadcrumb(__('Catalog Sync'), __('Catalog Sync'));
        $resultPage->addBreadcrumb(__('View'), __('View'));
        $resultPage->getConfig()->getTitle()->prepend(__('View Catalog Sync'));

        return $resultPage;
    }
}

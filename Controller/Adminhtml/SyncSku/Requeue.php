<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\SyncSku;

use Exception;
use FlowCommerce\FlowConnector\Model\SyncSkuFactory;
use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class Requeue
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\SyncSku
 */
class Requeue extends Action
{
    /**
     * Authorization level of a basic admin session
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var SyncSkuFactory
     */
    private $syncSkuFactory;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * @param Context $context
     * @param SyncSkuManager $syncSkuManager
     * @param SyncSkuFactory $syncSkuFactory
     */
    public function __construct(
        Context $context,
        SyncSkuManager $syncSkuManager,
        SyncSkuFactory $syncSkuFactory
    ) {
        parent::__construct($context);
        $this->syncSkuManager = $syncSkuManager;
        $this->syncSkuFactory = $syncSkuFactory;
    }

    /**
     * Requeue action
     * @return Redirect
     */
    public function execute()
    {
        $return = $this->resultRedirectFactory->create();
        $return->setPath('*/*/');

        $id = $this->getRequest()->getParam('id');

        if ($id) {
            $syncSku = $this->syncSkuFactory->create();
            $syncSku->load($id);
            if ($syncSku->getId()) {
                try {
                    $this->syncSkuManager
                        ->enqueueMultipleProductsByProductSku([$syncSku->getSku()], $syncSku->getStoreId());
                    $this->messageManager->addSuccessMessage('The sku has been requeued');
                } catch (Exception $e) {
                    $this->messageManager->addExceptionMessage($e);
                }
            } else {
                $this->messageManager->addErrorMessage('The sync sku no longer exists');
            }
        } else {
            $this->messageManager->addErrorMessage('No sync sku has been informed.');
        }
        return $return;
    }
}

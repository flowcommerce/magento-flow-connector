<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\SyncOrder;

use Exception;
use FlowCommerce\FlowConnector\Model\SyncOrderFactory;
use FlowCommerce\FlowConnector\Model\SyncOrderManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class Fail
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\SyncOrder
 */
class Fail extends Action
{
    /**
     * Authorization level of a basic admin session
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var SyncOrderFactory
     */
    private $syncOrderFactory;

    /**
     * @var SyncOrderManager
     */
    private $syncOrderManager;

    /**
     * @param Context $context
     * @param SyncOrderManager $syncOrderManager
     * @param SyncOrderFactory $syncOrderFactory
     */
    public function __construct(
        Context $context,
        SyncOrderManager $syncOrderManager,
        SyncOrderFactory $syncOrderFactory
    ) {
        parent::__construct($context);
        $this->syncOrderManager = $syncOrderManager;
        $this->syncOrderFactory = $syncOrderFactory;
    }

    /**
     * Fail action
     * @return Redirect
     */
    public function execute()
    {
        $return = $this->resultRedirectFactory->create();
        $return->setPath('*/*/');

        $value = $this->getRequest()->getParam('value');

        if ($value) {
            $syncOrder = $this->syncOrderFactory->create();
            $syncOrder->load($value);
            if ($syncOrder->getValue()) {
                try {
                    $this->syncOrderManager->failByValue($syncOrder->getValue(), 'other', ['Manually marked as failed in Magento back office'], $syncOrder->getStoreId());
                    $this->messageManager->addSuccessMessage('The order has failed');
                } catch (Exception $e) {
                    $this->messageManager->addExceptionMessage($e);
                }
            } else {
                $this->messageManager->addErrorMessage('The order no longer exists');
            }
        } else {
            $this->messageManager->addErrorMessage('No order has been selected.');
        }
        return $return;
    }
}

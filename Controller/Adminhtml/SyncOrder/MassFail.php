<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\SyncOrder;

use Exception;
use FlowCommerce\FlowConnector\Model\ResourceModel\SyncOrder\CollectionFactory;
use FlowCommerce\FlowConnector\Model\SyncOrder;
use FlowCommerce\FlowConnector\Model\SyncOrderManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class MassRetry
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\SyncOrder
 */
class MassRetry extends Action
{
    /**
     * Authorization level of a basic admin session
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var SyncOrderManager
     */
    private $syncOrderManager;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     * @param SyncOrderManager $syncOrderManager
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        SyncOrderManager $syncOrderManager
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->syncOrderManager = $syncOrderManager;
    }

    /**
     * Mass Retry action
     * @return Redirect
     * @throws LocalizedException|Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        /** @var SyncOrder $syncOrder */
        foreach ($collection as $syncOrder) {
            $this->syncOrderManager->failByValue($syncOrder->getValue(), 'other', ['Manually marked as failed in Magento back office'], $syncOrder->getStoreId());
        }

        $this->messageManager->addSuccess(__('A total of %1 order(s) have been retried.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}

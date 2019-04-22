<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\SyncSku;

use Exception;
use FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\CollectionFactory;
use FlowCommerce\FlowConnector\Model\SyncSku;
use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class MassRequeue
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\SyncSku
 */
class MassRequeue extends Action
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
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     * @param SyncSkuManager $syncSkuManager
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        SyncSkuManager $syncSkuManager
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->syncSkuManager = $syncSkuManager;
    }

    /**
     * Mass Requeue action
     * @return Redirect
     * @throws LocalizedException|Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        $skusIndexedByStoreId = [];
        /** @var SyncSku $syncSku */
        foreach ($collection as $syncSku) {
            if (!array_key_exists($syncSku->getStoreId(), $skusIndexedByStoreId)) {
                $skusIndexedByStoreId[$syncSku->getStoreId()] = [];
            }
            array_push($skusIndexedByStoreId[$syncSku->getStoreId()], $syncSku->getSku());
        }

        foreach ($skusIndexedByStoreId as $storeId => $skus) {
            $this->syncSkuManager->enqueueMultipleProductsByProductSku($skus, $storeId);
        }

        $this->messageManager->addSuccess(__('A total of %1 sku(s) have been requeued.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}

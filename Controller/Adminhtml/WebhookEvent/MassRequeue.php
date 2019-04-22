<?php

namespace FlowCommerce\FlowConnector\Controller\Adminhtml\WebhookEvent;

use Exception;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\CollectionFactory;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class MassRequeue
 * @package FlowCommerce\FlowConnector\Controller\Adminhtml\WebhookEvent
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
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     * @param WebhookEventManager $webhookEventManager
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        WebhookEventManager $webhookEventManager
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->webhookEventManager = $webhookEventManager;
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

        foreach ($collection as $webhookEvent) {
            $this->webhookEventManager->requeue($webhookEvent, '');
        }

        $this->messageManager->addSuccess(__('A total of %1 webhook event(s) have been requeued.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setRefererUrl();
    }
}

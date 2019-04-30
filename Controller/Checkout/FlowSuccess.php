<?php

namespace FlowCommerce\FlowConnector\Controller\Checkout;

use Magento\Sales\Model\Order as OrderRepository;
use Magento\Quote\Model\Quote as QuoteRepository;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;

/**
 * Controller class for handling success callbacks for Flow order submissions
 */
class FlowSuccess extends \Magento\Framework\App\Action\Action
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FlowSuccess constructor.
     * @param OrderRepository $orderRepository
     * @param QuoteRepository $quoteRepository
     * @param LoggerInterface $logger
     * @param Context $context
     */
    public function __construct(
        OrderRepository $orderRepository,
        QuoteRepository $quoteRepository,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Redirect Flow order submission to Magento Success page
     *
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $order = $this->orderRepository->load('50');
        $quote = $this->quoteRepository->load($order->getQuoteId());
        /* var_dump(json_encode($quote->getData())); die(); */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success');
        return $resultRedirect;
    }
} 

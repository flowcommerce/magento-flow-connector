<?php

namespace FlowCommerce\FlowConnector\Controller\Checkout;

use Magento\Sales\Model\Order as OrderRepository;
use Magento\Quote\Model\Quote as QuoteRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
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
     * @var CheckoutSession
     */
    private $checkoutSession;

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
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
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
        /* $order = $this->orderRepository->load('50'); */
        /* $quote = $this->quoteRepository->load($order->getQuoteId()); */ 

        /* $session = $this->getOnepage()->getCheckout(); */
        /* $session->clearQuote(); */

        /* $quote = $this->quoteRepository->load('564'); */ 
        /* $quote->removeAllItems()->save(); */

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success');
        return $resultRedirect;
    }
} 

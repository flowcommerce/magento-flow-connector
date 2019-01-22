<?php

namespace FlowCommerce\FlowConnector\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\FlowCartManager;

/**
 * Class SyncFlowCartAfterCartUpdate
 * @package FlowCommerce\FlowConnector\Observer
 */
class SyncFlowCartAfterCartUpdate implements ObserverInterface
{

    /** @var FlowCartManager */
    private $flowCartManager;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var Logger */
    private $logger;

    public function __construct(
        FlowCartManager $flowCartManager,
        CheckoutSession $checkoutSession,
        Logger $logger

    ) {
        $this->flowCartManager = $flowCartManager;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * Sync cart
     * @param Observer $observer
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        //Update flow cart
        /** @var Quote $quoteData */
        $quoteData = $observer->getCart()->getQuote();
        $this->flowCartManager->syncCartData($quoteData);
    }
}

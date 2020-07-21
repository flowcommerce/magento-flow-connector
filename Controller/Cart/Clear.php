<?php

namespace FlowCommerce\FlowConnector\Controller\Cart;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller class for clearing user cart and redirecting to base url
 */
class Clear extends Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;


    /**
     * EmptyCart constructor.
     *
     * @param Context $context
     * @param Session $session
     * @param JsonFactory $jsonFactory
     * @param Data $jsonHelper
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $session,
        JsonFactory $jsonFactory,
        Data $jsonHelper,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->checkoutSession = $session;
        $this->jsonFactory = $jsonFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->cart = $cart;
        parent::__construct($context);
    }

    /**
     * Clears cart and redirects to Base Url
     *
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        try {
            $this->cart->truncate()->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        $url = $this->storeManager->getStore()->getBaseUrl();
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result->setUrl($url);
        return $result;
    }
}

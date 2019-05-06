<?php

namespace FlowCommerce\FlowConnector\Controller\Checkout;

use FlowCommerce\FlowConnector\Helper\Data;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Config\Source\DataSource;
use FlowCommerce\FlowConnector\Model\SessionManager;
use FlowCommerce\FlowConnector\Model\Util;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller class for redirecting to Flow's hosted checkout.
 */
class RedirectToFlow extends \Magento\Framework\App\Action\Action
{
    const URL_STUB_PREFIX = '/order/';
    const FLOW_RETURN_URL = 'flow_return_url';
    const FLOW_SUCCESS_CALLBACK = 'flowconnector/checkout/flowsuccess';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var Data
     */
    private $flowHelper;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * RedirectToFlow constructor.
     * @param AddressRepositoryInterface $addressRepository
     * @param CookieManagerInterface $cookieManager
     * @param CustomerSession $customerSession
     * @param Data $flowHelper
     * @param JsonHelper $jsonHelper
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param SessionManager $sessionManager
     * @param StoreManagerInterface $storeManager
     * @param UrlBuilder $urlBuilder
     * @param Util $util
     * @param Context $context
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        CookieManagerInterface $cookieManager,
        CustomerSession $customerSession,
        Data $flowHelper,
        JsonHelper $jsonHelper,
        LoggerInterface $logger,
        Session $checkoutSession,
        SessionManager $sessionManager,
        StoreManagerInterface $storeManager,
        UrlBuilder $urlBuilder,
        Util $util,
        Context $context
    ) {
        $this->addressRepository = $addressRepository;
        $this->checkoutSession = $checkoutSession;
        $this->cookieManager = $cookieManager;
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
        $this->flowHelper = $flowHelper;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->util = $util;
        parent::__construct($context);
    }

    /**
     * Redirects to Flow checkout
     *
     * The Flow experience can be set by passing in the "country" URL param.
     *
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $url = null;

        $quote = $this->checkoutSession->getQuote();
        if ($quote->hasItems()) {
            $url = $this->sessionManager->getCheckoutUrlWithCart($this->getRequest()->getParam("country"));
        } else {
            $url = $this->storeManager->getStore()->getBaseUrl();
        }

        $this->logger->info('URL: ' . $url);

        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result->setUrl($url);
        return $result;
    }
}

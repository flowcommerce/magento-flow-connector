<?php

namespace FlowCommerce\FlowConnector\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use FlowCommerce\FlowConnector\Model\SessionManager;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller class for redirecting to Flow's hosted checkout.
 */
class RedirectToFlow extends \Magento\Framework\App\Action\Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * RedirectToFlow constructor.
     * @param Context $context
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param SessionManager $sessionManager
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        SessionManager $sessionManager
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->sessionManager = $sessionManager;
        parent::__construct($context);
    }

    /**
     * Redirects to Flow checkout
     * The Flow experience can be set by passing in the "country" URL param.
     * @return ResultInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute()
    {
        if (!($url = $this->sessionManager->getCheckoutUrlWithCart($this->getRequest()->getParam("country")))) {
            $url = $this->storeManager->getStore()->getBaseUrl();
        }

        $this->logger->info('URL: ' . $url);

        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result->setUrl($url);
        return $result;
    }
}

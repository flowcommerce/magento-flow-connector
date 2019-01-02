<?php

namespace FlowCommerce\FlowConnector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Helper\Cart as CartHelper;
use FlowCommerce\FlowConnector\Model\Configuration;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\UrlInterface;

class FlowConnectorCheckoutRedirectObserver implements ObserverInterface
{
    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * @var CartHelper
     */
    private $cartHelper;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * FlowConnectorCheckoutRedirectObserver constructor.
     * @param ActionFlag $actionFlag
     * @param CartHelper $cartHelper
     * @param Configuration $configuration
     * @param UrlInterface $url
     */
    public function __construct(
        ActionFlag $actionFlag,
        CartHelper $cartHelper,
        Configuration $configuration,
        UrlInterface $url
    ) {
        $this->actionFlag = $actionFlag;
        $this->cartHelper = $cartHelper;
        $this->configuration = $configuration;
        $this->url = $url;
    }

    /**
     * Redirect to checkout cart if Flow enabled
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->configuration->isFlowEnabled() && $this->configuration->isRedirectEnabled()) {
            $redirectUrl = $this->url->getRedirectUrl($this->url->getUrl('flowconnector/checkout/redirecttoflow'));
            $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
            $observer->getControllerAction()->getResponse()->setRedirect($redirectUrl);
        }
    }
}

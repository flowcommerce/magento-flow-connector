<?php

namespace FlowCommerce\FlowConnector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use FlowCommerce\FlowConnector\Model\Configuration;
use Magento\Framework\App\ActionFlag;
use FlowCommerce\FlowConnector\Model\SessionManager;

class FlowConnectorCheckoutRedirectObserver implements ObserverInterface
{
    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * FlowConnectorCheckoutRedirectObserver constructor.
     * @param ActionFlag $actionFlag
     * @param Configuration $configuration
     * @param SessionManager $sessionManager
     */
    public function __construct(
        ActionFlag $actionFlag,
        Configuration $configuration,
        SessionManager $sessionManager
    ) {
        $this->actionFlag = $actionFlag;
        $this->configuration = $configuration;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Redirect to checkout cart if Flow enabled
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $experienceCountry = $this->sessionManager->getSessionExperienceCountry();
        $experienceCurrency = $this->sessionManager->getSessionExperienceCurrency();
        if ($this->configuration->isFlowEnabled()
            && $this->configuration->isRedirectEnabled()
            && ($redirectUrl = $this->sessionManager->getCheckoutUrlWithCart($experienceCountry, $experienceCurrency))) {
            $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
            $observer->getControllerAction()->getResponse()->setRedirect($redirectUrl);
        }
    }
}

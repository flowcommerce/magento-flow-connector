<?php

namespace FlowCommerce\FlowConnector\Controller\Session;

use FlowCommerce\FlowConnector\Model\SessionManager;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Psr\Log\LoggerInterface as Logger;
use Exception;

/**
 * Controller class that sets Flow experience and overrides current session if there is any.
 */
class Start extends Action
{
    /**
     * Experience query parameter
     */
    const EXPERIENCE_PARAM_KEY = 'flow_experience';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * Set constructor.
     * @param Context $context
     * @param Logger $logger
     * @param SessionManager $sessionManager
     */
    public function __construct(
        Context $context,
        Logger $logger,
        SessionManager $sessionManager
    ) {
        $this->logger = $logger;
        $this->sessionManager = $sessionManager;
        parent::__construct($context);
    }

    /**
     * Set experience based on url then redirect to referrer or base URL
     *
     * @see \Magento\Store\App\Response\Redirect::_getUrl
     * @return \Magento\Framework\App\ResponseInterface|Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $this->sessionManager->startFlowSession($this->getExperienceFromUrl());
        } catch (Exception $e) {
            $this->logger->error(sprintf('Unable to start Flow Session due to %s', $e->getMessage()));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();

        return $resultRedirect;
    }

    /**
     * Get experience from URL
     * @return bool|string
     */
    private function getExperienceFromUrl()
    {
        return substr($this->getRequest()->getParam(self::EXPERIENCE_PARAM_KEY), 0, 3);
    }
}


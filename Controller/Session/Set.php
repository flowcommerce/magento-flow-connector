<?php

namespace FlowCommerce\FlowConnector\Controller\Session;

use FlowCommerce\FlowConnector\Model\Api\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Psr\Log\LoggerInterface as Logger;

/**
 * Controller class that sets experience and add cookie to add flow session cookie with experience information,
 * overrides current session.
 */
class Set extends Action
{
    const EXPERIENCE_PARAM_KEY = 'flow_experience';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Session
     */
    private $session;

    /**
     * Set constructor.
     * @param Context $context
     * @param Logger $logger
     * @param Session $session
     */
    public function __construct(
        Context $context,
        Logger $logger,
        Session $session
    ) {
        $this->logger = $logger;
        $this->session = $session;
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
        $this->session->setFlowSessionData($this->getExperienceFromUrl());

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
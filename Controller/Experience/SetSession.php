<?php

namespace FlowCommerce\FlowConnector\Controller\Experience;

use FlowCommerce\FlowConnector\Model\Api\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface as Logger;

/**
 * Controller class that sets experience and add cookie to add flow session cookie with experience information,
 * overrides current session.
 */
class SetSession extends Action
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
     * Set experience based on url
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->session->setFlowSessionData($this->getExperienceFromUrl());
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
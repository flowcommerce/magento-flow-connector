<?php

namespace FlowCommerce\FlowConnector\Controller\Session;

use FlowCommerce\FlowConnector\Model\SessionManager;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface as Logger;
use Exception;

/**
 * Controller class that gets current Flow experience.
 */
class Get extends Action
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * Get constructor.
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
            $return = $this->sessionManager->getFlowSessionData();
        } catch (Exception $e) {
            $errorMessage = sprintf('Unable to get Flow Session due to %s', $e->getMessage());
            $this->logger->error($errorMessage);
            $return = [
                'error' => $errorMessage
            ];
        }

        /** @var Redirect $resultRedirect */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData($return);

        return $result;
    }
}

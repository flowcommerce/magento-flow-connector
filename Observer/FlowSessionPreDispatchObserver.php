<?php

namespace FlowCommerce\FlowConnector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use FlowCommerce\FlowConnector\Model\SessionManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\RequestInterface;


/**
 * Class FlowConnectorSettingsObserver
 * @package FlowCommerce\FlowConnector\Observer
 */
class FlowSessionPreDispatchObserver implements ObserverInterface
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
     * FlowConnectorSettingsObserver constructor.
     * @param Logger $logger
     * @param SessionManager $sessionManager
     */
    public function __construct(
        Logger $logger,
        SessionManager $sessionManager
    ) {
        $this->logger = $logger;
        $this->sessionManager = $sessionManager;
    }

    /**
     * This observer sets flow session using url param flow_experience
     * @param Observer $observer
     * @return void
     * @throws
     */
    public function execute(Observer $observer)
    {
        $request = $observer->getRequest();
        try {
            $experience = $this->getExperienceFromUrl($request);
            if ($experience !== null) {
                $this->sessionManager->startFlowSession($experience);
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Unable to start Flow Session due to %s', $e->getMessage()));
        }
    }

    /**
     * Get experience from URL
     * @param RequestInterface $request
     * @return null|string
     */
    private function getExperienceFromUrl(RequestInterface $request)
    {
        $experience = null;
        if ($request->getParam(self::EXPERIENCE_PARAM_KEY) !== null) {
            $experience = substr($request->getParam(self::EXPERIENCE_PARAM_KEY), 0, 3);
        }
        return $experience;
    }
}

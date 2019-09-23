<?php

namespace FlowCommerce\FlowConnector\Test\Unit\Model;

use FlowCommerce\FlowConnector\Model\Api\Session;
use FlowCommerce\FlowConnector\Model\Configuration;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Notification;
use FlowCommerce\FlowConnector\Model\Util;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as GuzzleClient;

/**
 * Test class for Util.
 */
class UtilTest extends \PHPUnit\Framework\TestCase
{

    const SCOPE_CONFIG_VALUE_MAP = [
        Configuration::FLOW_ENABLED => true,
        Auth::FLOW_ORGANIZATION_ID => 'test-organization',
        Auth::FLOW_API_TOKEN => 'abcdefghijklmnopqrstuvwxyz'
    ];

    /**
     * @var Util
     */
    private $util;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var StoreManager
     */
    private $storeManager;

    protected function setUp()
    {

        $this->notification = $this->createMock(Notification::class);

        $this->guzzleClient = $this->createMock(GuzzleClient::class);

        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('isFlowEnabled')->willReturn(true);

        $this->storeManager = $this->createMock(StoreManager::class);

        $this->util = new Util(
            $this->notification,
            $this->guzzleClient,
            $this->configuration,
            $this->storeManager
        );
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testIsFlowEnabled()
    {
        $this->assertEquals(self::SCOPE_CONFIG_VALUE_MAP[Configuration::FLOW_ENABLED], $this->util->isFlowEnabled());
    }
}


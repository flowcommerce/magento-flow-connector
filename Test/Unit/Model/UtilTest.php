<?php

namespace Flow\FlowConnector\Test\Unit\Model;

use Flow\FlowConnector\Model\Util;

/**
 * Test class for Util.
 */
class UtilTest extends \PHPUnit\Framework\TestCase {

    const SCOPE_CONFIG_VALUE_MAP = [
        Util::FLOW_ENABLED => true,
        Util::FLOW_ORGANIZATION_ID => 'test-organization',
        Util::FLOW_API_TOKEN => 'abcdefghijklmnopqrstuvwxyz'
    ];

    protected $logger;
    protected $scopeConfig;
    protected $util;

    protected function setUp() {

        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->scopeConfig = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $this->scopeConfig->method('getValue')
            ->will($this->returnCallback(function($key) {
                return self::SCOPE_CONFIG_VALUE_MAP[$key];
            }));

        $this->util = new Util(
            $this->logger,
            $this->scopeConfig
        );
    }

    public function testIsFlowEnabled() {
        $this->assertEquals(self::SCOPE_CONFIG_VALUE_MAP[Util::FLOW_ENABLED], $this->util->isFlowEnabled());
    }

    public function testGetFlowOrganizationId() {
        $this->assertEquals(self::SCOPE_CONFIG_VALUE_MAP[Util::FLOW_ORGANIZATION_ID], $this->util->getFlowOrganizationId());
    }

    public function testGetFlowApiToken() {
        $this->assertEquals(self::SCOPE_CONFIG_VALUE_MAP[Util::FLOW_API_TOKEN], $this->util->getFlowApiToken());
    }

    public function testGetFlowApiEndpoint() {
        $urlStub = '/hello';
        $this->assertTrue(substr($this->util->getFlowApiEndpoint('/hello'), -strlen($urlStub)) === $urlStub);
    }

    public function testGetFlowClient() {
        $client = $this->util->getFlowClient('/hello');

        $this->assertEquals(\Zend\Http\Request::METHOD_GET, $client->getMethod());
        $this->assertEquals('application/json', $client->getEncType());
    }

}

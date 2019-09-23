<?php
namespace FlowCommerce\FlowConnector\Test\Integration\Model\Api;

use FlowCommerce\FlowConnector\Model\Api\Auth as Subject;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

/**
 * Test class for AuthTest.
 */
class AuthTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Scope Config
     */
    const SCOPE_CONFIG_VALUE_MAP = [
        Auth::FLOW_ORGANIZATION_ID => 'test-organization',
        Auth::FLOW_API_TOKEN => 'abcdefghijklmnopqrstuvwxyz'
    ];

    /**
     * Store Id
     */
    const TEST_STORE_ID = '1';

    /**
     * @var string
     */
    private $className = null;

    /**
     * @var ObjectManager
     */
    private $objectManager = null;

    /**
     * @var Subject
     */
    private $originalSubject = null;

    /**
     * @var Subject
     */
    private $subject = null;

    /**
     * @var ScopeConfig
     */
    private $scopeConfig = null;

    /**
     * @var StoreManager
     */
    private $storeManager = null;

    /**
     * Setup
     */
    public function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->className = Subject::class;

        $arguments = $this->getConstructorArguments();

        $this->subject = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($arguments)
            ->setMethods(['getFlowApiToken'])
            ->getMock();

        $this->subject
            ->expects($this->any())
            ->method('getFlowApiToken')
            ->willReturn(self::SCOPE_CONFIG_VALUE_MAP[Auth::FLOW_API_TOKEN]);

        $this->originalSubject = $this
            ->objectManager
            ->getObject($this->className, $arguments);
    }

    /**
     * Constructor arguments
     * @return array
     */
    private function getConstructorArguments()
    {
        $arguments = $this->objectManager->getConstructArguments($this->className);

        $this->scopeConfig = $this
            ->getMockBuilder(ScopeConfig::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $arguments['scopeConfig'] = $this->scopeConfig;

        $this->storeManager = $this
            ->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $arguments['storeManager'] = $this->storeManager;

        return $arguments;
    }

    /**
     * Testing get header from auth
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetAuthHeader()
    {
        $expectedHeader = [
            self::SCOPE_CONFIG_VALUE_MAP[Auth::FLOW_API_TOKEN],
            ''
        ];

        $header = $this->subject->getAuthHeader(self::TEST_STORE_ID);
        $this->assertEquals($expectedHeader, $header);
    }
}


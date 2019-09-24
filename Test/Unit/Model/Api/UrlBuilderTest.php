<?php
namespace FlowCommerce\FlowConnector\Test\Integration\Model\Api;

use FlowCommerce\FlowConnector\Model\Api\UrlBuilder as Subject;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test class for AuthTest.
 */
class UrlBuilderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Scope Config
     */
    const SCOPE_CONFIG_VALUE_MAP = [
        Auth::FLOW_ORGANIZATION_ID => 'test-organization',
        Auth::FLOW_API_TOKEN => 'abcdefghijklmnopqrstuvwxyz'
    ];

    /**
     * Test URL stub
     */
    const URL_STUB = '/test_endpoint';

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
     * @var Auth
     */
    private $auth = null;

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

        $this->originalSubject = $this
            ->objectManager
            ->getObject($this->className, $arguments);
    }

    /**
     * Constructor Arguments
     * @return array
     */
    private function getConstructorArguments()
    {
        $arguments = $this->objectManager->getConstructArguments($this->className);

        $this->auth = $this
            ->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFlowOrganizationId','getFlowApiToken'])
            ->getMock();
        $arguments['auth'] = $this->auth;

        $this->auth
            ->expects($this->any())
            ->method('getFlowOrganizationId')
            ->willReturn(self::SCOPE_CONFIG_VALUE_MAP[Auth::FLOW_ORGANIZATION_ID]);

        $this->auth
            ->expects($this->any())
            ->method('getFlowApiToken')
            ->willReturn(self::SCOPE_CONFIG_VALUE_MAP[Auth::FLOW_API_TOKEN]);

        return $arguments;
    }

    /**
     * Test get flow api endpoint
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetFlowApiEndpoint()
    {
        $expectedApiEndpoint = UrlBuilder::FLOW_API_BASE_ENDPOINT .
            self::SCOPE_CONFIG_VALUE_MAP[Auth::FLOW_ORGANIZATION_ID].
            self::URL_STUB;

        $endpoint = $this->subject->getFlowApiEndpoint(self::URL_STUB);
        $this->assertEquals($expectedApiEndpoint, $endpoint);
    }
}


<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Controller\Webhooks;

use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController as ControllerTestCase;

/**
 * Class OrderPlacedTest
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class OrderPlacedTest extends ControllerTestCase
{
    public function testThrows404WithoutXFlowSignature()
    {
        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->dispatch('flowconnector/webhooks/orderplaced');
        $this->assertSame(404, $this->getResponse()->getHttpResponseCode());
    }
}

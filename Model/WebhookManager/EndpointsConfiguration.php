<?php

namespace FlowCommerce\FlowConnector\Model\WebhookManager;

use FlowCommerce\FlowConnector\Controller\Webhooks\AuthorizationDeletedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\CaptureUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\CardAuthorizationUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\FraudStatusChanged;
use FlowCommerce\FlowConnector\Controller\Webhooks\LabelUpserted;
use FlowCommerce\FlowConnector\Controller\Webhooks\OnlineAuthorizationUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\RefundCaptureUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\RefundUpsertedV2;
use FlowCommerce\FlowConnector\Controller\Webhooks\TrackingLabelEventUpserted;
use FlowCommerce\FlowConnector\Controller\Webhooks\OrderPlaced;

/**
 * Class EndpointsConfiguration
 * @package FlowCommerce\FlowConnector\Model\WebhookManager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EndpointsConfiguration
{

    /**
     * Returns configuration for all webhook event endpoints
     * @return array
     */
    public function getEndpointsConfiguration()
    {
        return [
            $this->getRouteStub(AuthorizationDeletedV2::class) => [AuthorizationDeletedV2::EVENT_TYPE],
            $this->getRouteStub(CaptureUpsertedV2::class) => [CaptureUpsertedV2::EVENT_TYPE],
            $this->getRouteStub(CardAuthorizationUpsertedV2::class) => [CardAuthorizationUpsertedV2::EVENT_TYPE],
            $this->getRouteStub(FraudStatusChanged::class) => [FraudStatusChanged::EVENT_TYPE],
            $this->getRouteStub(LabelUpserted::class) => [LabelUpserted::EVENT_TYPE],
            $this->getRouteStub(OnlineAuthorizationUpsertedV2::class) => [OnlineAuthorizationUpsertedV2::EVENT_TYPE],
            $this->getRouteStub(RefundCaptureUpsertedV2::class) => [RefundCaptureUpsertedV2::EVENT_TYPE],
            $this->getRouteStub(RefundUpsertedV2::class) => [RefundUpsertedV2::EVENT_TYPE],
            $this->getRouteStub(TrackingLabelEventUpserted::class) => [TrackingLabelEventUpserted::EVENT_TYPE],
            $this->getRouteStub(OrderPlaced::class) => [OrderPlaced::EVENT_TYPE],
        ];
    }

    /**
     * Given a class name, returns the respective stub
     * @param string $className
     * @return string
     */
    private function getRouteStub($className)
    {
        $explodedClassName = explode('\\', $className);
        $stub = array_pop($explodedClassName);
        return strtolower($stub);
    }
}

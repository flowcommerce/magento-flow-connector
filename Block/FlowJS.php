<?php
/**
 * FlowCommerce
 *
 * FlowCommerce_FlowConnector
 * @category    FlowCommerce
 * @package     FlowCommerce_FlowConnector
 * @author      FlowCommerce
 * @copyright   Copyright (c) 2018 FlowCommerce
 */
namespace FlowCommerce\FlowConnector\Block;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Configuration;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class FlowJS extends Template
{
    /** @var Configuration */
    private $configuration;

    /** @var Auth */
    private $auth;

    public function __construct(
        Context $context,
        Auth $auth,
        Configuration $configuration,
        array $data = []
    ) {
        $this->auth = $auth;
        $this->configuration = $configuration;
        parent::__construct($context, $data);
    }

    /**
     * Return flow organizationId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowOrganizationId()
    {
        return $this->auth->getFlowOrganizationId();
    }

    public function isFlowJsEnabled()
    {
        return $this->auth->getFlowOrganizationId();
    }
}

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
        return $this->auth->getFlowOrganizationId() && $this->configuration->getFlowJsVersion();
    }

    public function isFlowProduction()
    {
        return $this->auth->isFlowProductionOrganization();
    }

    /**
     * Return flowjs version
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowJsUrl()
    {
        return 'https://cdn.flow.io/flowjs/' . $this->configuration->getFlowJsVersion() . '/flow.min.js';
    }

    /**
     * Return flow cart localization toggle
     * @return boolean
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isFlowCartLocalize()
    {
        return $this->configuration->isCartLocalizationEnabled();
    }

    /**
     * Return max catalog hide in ms
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowMaxCatalogHideMs()
    {
        return $this->configuration->getMaxCatalogHideMs();
    }

    /**
     * Return flow catalog localization toggle
     * @return boolean
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isFlowCatalogLocalize()
    {
        return $this->configuration->isCatalogPriceLocalizationEnabled();
    }

    /**
     * Return max catalog hide in ms
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowMaxCartHideMs()
    {
        return $this->configuration->getMaxCartHideMs();
    }

    /**
     * Return flow support magento discounts
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isFlowSupportMagentoDiscounts()
    {
        return $this->configuration->isSupportMagentoDiscounts();
    }
}

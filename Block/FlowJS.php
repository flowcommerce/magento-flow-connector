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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class FlowJS extends Template
{
    /** @var Configuration */
    private $configuration;

    /** @var Auth */
    private $auth;

    /**
     * @param Context $context
     * @param Auth $auth
     * @param Configuration $configuration
     * @param array $data
     * @return void
     */
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

    public function isFlowEnabled()
    {
        return $this->configuration->isFlowEnabled();
    }

    public function isFlowProduction()
    {
        return $this->auth->isFlowProductionOrganization();
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

    /**
     * Check if country picker is enabled
     * @return boolean
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isCountryPickerEnabled()
    {
        return $this->configuration->isCountryPickerEnabled();
    }

    /**
     * Check if payment methods are enabled on pdp
     * @return boolean
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isPaymentMethodsPDPEnabled()
    {
        return $this->configuration->isPaymentMethodsPDPEnabled();
    }

    /**
     * Check if shipping window is enabled on pdp
     * @return boolean
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isShippingWindowPDPEnabled()
    {
        return $this->configuration->isShippingWindowPDPEnabled();
    }

    /**
     * Check if tax and duty messaging is enabled
     * @return boolean
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isTaxDutyMessagingEnabled()
    {
        return $this->configuration->isTaxDutyMessagingEnabled();
    }

    /**
     * @return string|null
     */
    public function getBaseCurrencyCode(): ?string
    {
        try {
             /** @var \Magento\Store\Model\Store $currentStore */
            $currentStore = $this->_storeManager->getStore();
            return $currentStore->getBaseCurrency()->getCode();
        } catch(NoSuchEntityException $e) {
            return null;
        }
    }
}

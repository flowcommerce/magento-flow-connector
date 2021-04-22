<?php
/**
 * FlowCommerce
 *
 * FlowCommerce_FlowConnector
 * @category    FlowCommerce
 * @package     FlowCommerce_FlowConnector
 * @author      FlowCommerce
 * @copyright   Copyright (c) 2020 FlowCommerce
 */
namespace FlowCommerce\FlowConnector\Block;

use FlowCommerce\FlowConnector\Model\Configuration;
use Magento\Framework\View\Element\Template;

class FlowProductInfo extends Template
{
    /** @var Configuration */
    private $configuration;

    /**
     * FlowProductInfo constructor.
     * @param Template\Context $context
     * @param Configuration $configuration
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Configuration $configuration,
        array $data = []
    ) {
        $this->configuration = $configuration;
        parent::__construct($context, $data);
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
}


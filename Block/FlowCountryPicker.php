<?php
/**
 * FlowCommerce
 *
 * FlowCommerce_FlowConnector
 * @category    FlowCommerce
 * @package     FlowCommerce_FlowConnector
 * @author      FlowCommerce
 * @copyright   Copyright (c) 2019 FlowCommerce
 */
namespace FlowCommerce\FlowConnector\Block;

use FlowCommerce\FlowConnector\Model\Configuration;
use Magento\Framework\View\Element\Template;

class FlowCountryPicker extends Template
{
    /** @var Configuration */
    private $configuration;

    /**
     * FlowCountryPicker constructor.
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
     * Check if country picker is enabled
     * @return boolean
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isCountryPickerEnabled()
    {
        return $this->configuration->isCountryPickerEnabled();
    }

}

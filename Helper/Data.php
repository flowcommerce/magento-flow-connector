<?php

namespace FlowCommerce\FlowConnector\Helper;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * @var Auth
     */
    private $auth;
    
    /**
     * Data constructor.
     * @param Context $context
     * @param Auth $auth
     */
    public function __construct(
        Context $context,
        Auth $auth
    ) {
        $this->auth = $auth;
        parent::__construct($context);
    }

    /**
     * Returns the Flow Organization Id set in the Admin Store Configuration.
     * @param storeId ID of store, if null defaults to current store.
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowOrganizationId($storeId = null)
    {
        return $this->auth->getFlowOrganizationId($storeId);
    }

}

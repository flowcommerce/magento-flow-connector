<?php

namespace FlowCommerce\FlowConnector\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \FlowCommerce\FlowConnector\Model\Util;

class Data extends AbstractHelper
{
    /**
     * @var Util
     */
    private $util;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        Util $util
    ) {
        $this->util = $util;
        parent::__construct($context);
    }

    /**
     * Returns the Flow Organization Id set in the Admin Store Configuration.
     * @param storeId ID of store, if null defaults to current store.
     */
    public function getFlowOrganizationId($storeId = null)
    {
        return $this->util->getFlowOrganizationId($storeId);
    }

}

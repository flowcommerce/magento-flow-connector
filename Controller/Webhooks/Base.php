<?php
// DUPLICATED CONTROLLERS TO ACCOMODATE BREAKING CHANGE IN M2.3
if (interface_exists("Magento\Framework\App\CsrfAwareActionInterface")) {
    include __DIR__ . "FlowCommerce\FlowConnector\Controller\Webhooks\Base.m230.php";
} else {
    include __DIR__ . "FlowCommerce\FlowConnector\Controller\Webhooks\Base.m220.php";
}

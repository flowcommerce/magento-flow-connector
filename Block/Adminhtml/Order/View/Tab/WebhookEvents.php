<?php

namespace FlowCommerce\Flowconnector\Block\Adminhtml\Order\View\Tab;

use Magento\Framework\View\Element\Text\ListText;
use Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * Class WebhookEvents
 * @package FlowCommerce\Flowconnector\Block\Adminhtml\Order\View\Tab
 */
class WebhookEvents extends ListText implements TabInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Webhook Events');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Flow Webhook Events');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}

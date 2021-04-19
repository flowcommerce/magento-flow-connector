/**
 * FlowCommerce
 *
 * FlowCommerce_FlowConnector
 * @category    FlowCommerce
 * @package     FlowCommerce_FlowConnector
 * @author      FlowCommerce
 * @copyright   Copyright (c) 2019 FlowCommerce
 */
var config = {
    paths: {
        'flowJs': '//cdn.flow.io/flowjs/latest/flow.min',
        'day': 'FlowCommerce_FlowConnector/js/day.min',
    },
    deps: [
        'flowJs',
        'day',
    ],
    shim: {
        'FlowCommerce_FlowConnector/js/flow-init': { deps: ['flowJs'] },
        'FlowCommerce_FlowConnector/js/configurable-mixin': { deps: ['flowInit'] },
        'FlowCommerce_FlowConnector/js/customer-data-mixin': { deps: ['flowInit'] },
        'FlowCommerce_FlowConnector/js/price-box-mixin': { deps: ['flowInit'] },
        'FlowCommerce_FlowConnector/js/swatch-renderer-mixin': { deps: ['flowInit'] },
        'FlowCommerce_FlowConnector/js/shopping-cart-mixin': { deps: ['flowInit'] },
        'FlowCommerce_FlowConnector/js/view/minicart-mixin': { deps: ['flowInit'] },
    },
    config: {
        mixins: {
            'Magento_ConfigurableProduct/js/configurable': {
                'FlowCommerce_FlowConnector/js/configurable-mixin': true
            },
            'Magento_Customer/js/customer-data': {
                'FlowCommerce_FlowConnector/js/customer-data-mixin': true
            },
            'Magento_Catalog/js/price-box': {
                'FlowCommerce_FlowConnector/js/price-box-mixin': true
            },
            'Magento_Swatches/js/swatch-renderer': {
                'FlowCommerce_FlowConnector/js/swatch-renderer-mixin': true
            },
            'Magento_Checkout/js/shopping-cart': {
                'FlowCommerce_FlowConnector/js/shopping-cart-mixin': true
            },
            'Magento_Checkout/js/view/minicart': {
                'FlowCommerce_FlowConnector/js/view/minicart-mixin': true
            },
        }
    }
};

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
        'flowCompanion': 'FlowCommerce_FlowConnector/js/flow-companion',
        'flow': '//cdn.flow.io/flowjs/latest/flow.min',
        'flowCountryPicker': '//cdn.flow.io/country-picker/js/v0/country-picker.min',
        'day': 'FlowCommerce_FlowConnector/js/day.min'
    },
    deps: [
        'flowCompanion',
        'flow',
        'flowCountryPicker',
        'day',
        'es6-collections'
    ],
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

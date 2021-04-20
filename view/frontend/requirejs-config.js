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
        'flowCountryPicker': '//cdn.flow.io/country-picker/js/v0/country-picker.min',
        'day': 'FlowCommerce_FlowConnector/js/day.min',
        'flowInit': 'FlowCommerce_FlowConnector/js/flow-init',
        'configMixin': 'FlowCommerce_FlowConnector/js/configurable-mixin',
        'customerMixin': 'FlowCommerce_FlowConnector/js/customer-data-mixin',
        'priceMixin': 'FlowCommerce_FlowConnector/js/price-box-mixin',
        'swatchMixin': 'FlowCommerce_FlowConnector/js/swatch-renderer-mixin',
        'cartMixin': 'FlowCommerce_FlowConnector/js/shopping-cart-mixin',
        'minicartMixin': 'FlowCommerce_FlowConnector/js/view/minicart-mixin',
    },
    deps: [
        'flowJs',
        'flowCountryPicker',
        'day',
    ],
    shim: {
        'flowInit': {
            deps: ['flowJs', 'flowCountryPicker'],
            exports: 'flow'
        },
        'configMixin': {
            deps: ['flowInit']
        },
        'customerMixin': {
            deps: ['flowInit']
        },
        'priceMixin': {
            deps: ['flowInit']
        },
        'swatchMixin': {
            deps: ['flowInit']
        },
        'cartMixin': {
            deps: ['flowInit']
        },
        'minicartMixin': {
            deps: ['flowInit']
        },
    },
    config: {
        mixins: {
            'Magento_ConfigurableProduct/js/configurable': {
                'configMixin': true
            },
            'Magento_Customer/js/customer-data': {
                'customerMixin': true
            },
            'Magento_Catalog/js/price-box': {
                'priceMixin': true
            },
            'Magento_Swatches/js/swatch-renderer': {
                'swatchMixin': true
            },
            'Magento_Checkout/js/shopping-cart': {
                'cartMixin': true
            },
            'Magento_Checkout/js/view/minicart': {
                'minicartMixin': true
            },
        }
    }
};

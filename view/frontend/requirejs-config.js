/**
 * FlowCommerce
 *
 * FlowCommerce_FlowConnector
 * @category    FlowCommerce
 * @package     FlowCommerce_FlowConnector
 * @author      FlowCommerce
 * @copyright   Copyright (c) 2021 FlowCommerce
 */
var config = {
    map: {
        '*': {
            'flowInit': 'FlowCommerce_FlowConnector/js/flow-init',
            'day': 'FlowCommerce_FlowConnector/js/day.min'
        }
    },
    paths: {
        'flowJsBundle': 'FlowCommerce_FlowConnector/js/flow.min',
        'flowCountryPickerBundle': 'FlowCommerce_FlowConnector/js/country-picker.min'
    },
    shim: {
        'flowJsBundle': {
            'exports': 'flow'
        },
        'flowCountryPickerBundle': {
            'deps': [
                'flowJsBundle'
            ]
        }
    },
    config: {
        mixins: {
            'Magento_ConfigurableProduct/js/configurable': {
                'FlowCommerce_FlowConnector/js/configurable-mixin': true
            },
            'Magento_Catalog/js/price-box': {
                'FlowCommerce_FlowConnector/js/price-box-mixin': true
            },
            'Magento_Swatches/js/swatch-renderer': {
                'FlowCommerce_FlowConnector/js/swatch-renderer-mixin': true
            },
            'Magento_Checkout/js/shopping-cart': {
                'FlowCommerce_FlowConnector/js/shopping-cart-mixin': true
            }
        }
    }
};

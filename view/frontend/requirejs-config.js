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
        'flowCountryPicker': '//cdn.flow.io/country-picker/js/v0/country-picker'
    },
    deps: [
        'flowCountryPicker'
    ],
    shim: {
        'flowCountryPicker': ['jquery', 'jquery/ui'],
    },
    config: {
        mixins: {
            'Magento_Catalog/js/price-box': {
                'FlowCommerce_FlowConnector/js/price-box-mixin': true
            },

            'Magento_Swatches/js/swatch-renderer': {
                'FlowCommerce_FlowConnector/js/swatch-renderer-mixin': true
            }
        }
    }
};

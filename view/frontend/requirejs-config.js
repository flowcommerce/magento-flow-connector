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
        'priceBoxMixin': 'FlowCommerce_FlowConnector/js/price-box-mixin',
        'swatchRendererMixin': 'FlowCommerce_FlowConnector/js/swatch-renderer-mixin',
        'shoppingCartMixin': 'FlowCommerce_FlowConnector/js/shopping-cart-mixin',
    },
    deps: [
        'flowJs',
        'flowCountryPicker',
        'day',
    ],
    shim: {
        'flowInit': ['flowJs', 'flowCountryPicker', 'day'],
        'configMixin': ['flowJs', 'flowInit'],
        'priceBoxMixin': ['flowJs', 'flowInit'],
        'swatchRendererMixin': ['flowJs', 'flowInit'],
        'shoppingCartMixin': ['flowJs', 'flowInit'],
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
            },
        }
    }
};

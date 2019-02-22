define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template',
    'jquery/ui'
], function ($, utils, _, mageTemplate) {
    'use strict';

    return function (widget) {
        var flow = window.flow || {},
            body;
            flow.magento2 = window.flow.magento2 || {};
            flow.cart = window.flow.cart || {};
            flow.session = window.flow.session || {};

        $.widget('mage.shoppingCart', widget, {
            _create: function () {
                var result = this._super();

                if (typeof(flow.session.getExperience()) == 'string') {
                    var cartContainer = $('.cart-container').first();
                    cartContainer.attr('data-flow-cart-container', '');
                }

                return result;
            },
        });
        return $.mage.shoppingCart;
    }
});

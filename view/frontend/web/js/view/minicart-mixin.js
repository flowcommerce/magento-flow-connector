define([
    'jquery',
], function ($) {
    'use strict';

    return function (Component) {
        var flow = window.flow || {},
            miniCart,
            body,
            flowMiniCartLocalize;
            flow.session = window.flow.session || {};
            flow.cart = window.flow.cart || {};
            flow.magento2 = window.flow.magento2 || {};

        if (typeof(flow.session.getExperience()) == 'string') {
            miniCart = $('[data-block=\'minicart\']');
            body = $('[data-container=\'body\']');
            flow.magento2.miniCartAvailable = false;
            flowMiniCartLocalize = function (flag) {
                flow.cart.localize();
                console.log('Flow localizing cart: ' + flag);
            };

            if (body.hasClass('checkout-cart-index')) {
                // Is Cart page
                body.addClass('flow-cart');
                miniCart.remove();
            } else {
                // Is not Cart page
                flow.magento2.miniCartAvailable = true;
                miniCart.attr('data-flow-cart-container', '');
                miniCart.on('dropdowndialogopen', function(){flowMiniCartLocalize('open')});
                miniCart.on('contentUpdated', function(){flowMiniCartLocalize('minicart.contentUpdated')});
            }
        }

        return Component.extend();
    }
});

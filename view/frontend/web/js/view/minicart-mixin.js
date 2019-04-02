define([
    'jquery',
    'flowCompanion'
], function ($) {
    'use strict';
    var flow = window.flow || {};

    return function (Component) {
        var miniCart,
            body,
            flowMiniCartLocalize;

        if (!flow.magento2.shouldLocalizeCart) {
            return false;
        }

        miniCart = $('[data-block=\'minicart\']');
        body = $('[data-container=\'body\']');
        flow.magento2.miniCartAvailable = false;
        flowMiniCartLocalize = function (source, waitTimeMs = 0) {
            flow.magento2.hideCart();
            flow.magento2.hideCartTotals();
            setTimeout(function(){
                flow.cart.localize()
                console.log('Flow localizing cart: ' + source + ', waited: ' + waitTimeMs + 'ms');
            }, waitTimeMs);
            return true;
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
            miniCart.on('contentUpdated', function(){flowMiniCartLocalize('minicart.contentUpdated', 500)});
        }

        return Component.extend();
    }
});

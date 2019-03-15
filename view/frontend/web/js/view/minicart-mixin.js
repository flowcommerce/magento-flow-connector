define([
    'jquery',
    'flow'
], function ($, flow) {
    'use strict';

    return function (Component) {
        var flow = window.flow,
            miniCart,
            body,
            flowMiniCartLocalize;

        if (typeof(flow.session.getExperience()) == 'string') {
            miniCart = $('[data-block=\'minicart\']');
            body = $('[data-container=\'body\']');
            flow.magento2.miniCartAvailable = false;
            flowMiniCartLocalize = function (source, waitTimeMs = 0) {
                setTimeout(function(){
                    flow.cart.localize()
                    console.log('Flow localizing cart: ' + source + ', waited: ' + waitTimeMs + 'ms');
                }, waitTimeMs);
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
                miniCart.on('contentUpdated', function(){flowMiniCartLocalize('minicart.contentUpdated', 50)});
            }
        }

        return Component.extend();
    }
});

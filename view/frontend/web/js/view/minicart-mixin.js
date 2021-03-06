define([
    'jquery',
    'flowCompanion'
], function ($) {
    'use strict';

    return function (Component) {
        return Component.extend({
            initialize: function () {
                var miniCart,
                    body,
                    flowMiniCartLocalize,
                    result = this._super();

                flow.cmd('on', 'ready', function() {
                    if (!window.flow.magento2.shouldLocalizeCart()) {
                        return;
                    }

                    miniCart = $('[data-block=\'minicart\']');
                    body = $('[data-container=\'body\']');
                    window.flow.magento2.miniCartAvailable = false;
                    flowMiniCartLocalize = function (source, waitTimeMs) {
                        window.flow.magento2.hideCart();
                        window.flow.magento2.hideCartTotals();
                        setTimeout(function(){
                            window.flow.cart.localize()
                        }, waitTimeMs);
                    };

                    if (body.hasClass('checkout-cart-index')) {
                        // Is Cart page
                        body.addClass('flow-cart');
                        miniCart.remove();
                    } else {
                        // Is not Cart page
                        window.flow.magento2.miniCartAvailable = true;
                        miniCart.attr('data-flow-cart-container', '');
                        miniCart.on('dropdowndialogopen', function(){flowMiniCartLocalize('open', 0)});
                        miniCart.on('contentUpdated', function(){flowMiniCartLocalize('minicart.contentUpdated', 500)});
                    }
                });
                return result;
            }
        });
    }
});

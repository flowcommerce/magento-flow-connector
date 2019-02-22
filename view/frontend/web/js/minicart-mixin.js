define([
    'jquery',
], function ($) {
    'use strict';

    return function (Component) {
        var flow = window.flow || {},
            miniCart;

        flow.cart = window.flow.cart || {};
        miniCart = $('[data-block=\'minicart\']');
        miniCart.attr('data-flow-cart-container', '');

        var flowLocalize = function (flag) {
            flow.cart.localize();
            console.log('Flow localizing cart: ' + flag);
        };

        miniCart.on('dropdowndialogopen', function(){flowLocalize('open')});
        miniCart.on('contentUpdated', function(){flowLocalize('minicart.contentUpdated')});

        return Component.extend({ 

            update: function (updatedCart) {
                var result = this._super(updatedCart);
                flowLocalize('update cart');
                return result;
            },

        });
    }
});

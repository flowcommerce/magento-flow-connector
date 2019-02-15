define([
    'jquery',
], function ($) {
    'use strict';

    return function (Component) {
        var flow = window.flow || {},
            miniCart;

        flow.cart = window.flow.cart || {};
        miniCart = $('[data-block=\'minicart\']');

        miniCart.on('dropdowndialogopen', function () {
            flow.cart.localize();
        });

        return Component.extend({ 

            update: function (updatedCart) {
                var result = this._super(updatedCart);
                flow.cart.localize();
                return result;
            },

        });
    }
});

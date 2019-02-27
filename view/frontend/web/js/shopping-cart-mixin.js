define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        var flow = window.flow || {},
            body;
            flow.magento2 = window.flow.magento2 || {};
            flow.cart = window.flow.cart || {};
            flow.session = window.flow.session || {};

        $.widget('mage.shoppingCart', widget, {
            _create: function () {
                var result = this._super(),
                    items, i, cartContainer;
                if (typeof(flow.session.getExperience()) == 'string') {
                    cartContainer = $('.cart-container').first();
                    cartContainer.attr('data-flow-cart-container', '');
                    if (checkoutConfig != undefined) {
                        if (checkoutConfig.totalsData != undefined) {

                            if (checkoutConfig.totalsData.base_currency_code &&
                                checkoutConfig.totalsData.base_discount_amount)
                            {
                                cartContainer.attr('data-flow-cart-discount-currency', checkoutConfig.totalsData.base_currency_code);
                                cartContainer.attr('data-flow-cart-discount-amount', checkoutConfig.totalsData.base_discount_amount);
                            }
                        }
                    }

                    items = cartContainer.find('[data-role="cart-item-qty"]');
                    for (i = 0; i < items.length; i++) {
                        var qty, number, itemContainer;
                        qty = $(items[i]).attr('value');
                        number = $(items[i]).data('cart-item-id');
                        itemContainer = $(items[i]).closest('.cart.item');
                        itemContainer.attr('data-flow-cart-item-number', number);
                        itemContainer.attr('data-flow-cart-item-quantity', qty);
                        itemContainer.find('.price .cart-price > span.price').first().attr('data-flow-localize','cart-item-price');
                        itemContainer.find('.subtotal .cart-price > span.price').first().attr('data-flow-localize','cart-item-line-total');
                    }

                    console.log('cart localize init');
                    flow.cart.localize();
                }

                return result;
            },
        });
        return $.mage.shoppingCart;
    }
});

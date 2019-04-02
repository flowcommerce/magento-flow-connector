define([
    'jquery',
    'flowCompanion'
], function ($) {
    'use strict';

    return function (widget) {
        var body;

        $.widget('mage.shoppingCart', widget, {
            _create: function () {
                var flow = window.flow || {};
                var result = this._super(),
                    items, i, cartContainer;
                var totalDiscount = 0.0;
                if (flow.magento2.shouldLocalizeCart) {
                    cartContainer = $('.cart-container').first();
                    cartContainer.attr('data-flow-cart-container', '');
                    if (checkoutConfig != undefined) {
                        if (checkoutConfig.quoteItemData != undefined) {
                            for (i = 0; i < checkoutConfig.quoteItemData.length; i++) {
                                totalDiscount += checkoutConfig.quoteItemData[i].base_discount_amount;
                            }

                            if (totalDiscount > 0) {
                                cartContainer.attr('data-flow-cart-discount-amount', totalDiscount);
                                cartContainer.attr('data-flow-cart-discount-currency', checkoutConfig.totalsData.base_currency_code);
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

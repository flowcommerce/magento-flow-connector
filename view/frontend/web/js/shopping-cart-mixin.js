define([
    'jquery',
    'flowCompanion'
], function ($) {
    'use strict';

    return function (widget) {
        var body;

        $.widget('mage.shoppingCart', widget, {
            _create: function () {
                var result = this._super(),
                    items, i, cartContainer, baseCurrency,
                    lineItemDiscount = {};
                if (!window.flow.magento2.shouldLocalizeCart) {
                    return result;
                }

                cartContainer = $('.cart-container').first();
                cartContainer.attr('data-flow-cart-container', '');
                try {
                    for (i = 0; i < checkoutConfig.quoteItemData.length; i++) {
                        lineItemDiscount[checkoutConfig.quoteItemData[i].sku] = {
                            percent: parseFloat(checkoutConfig.quoteItemData[i].base_discount_amount) / parseFloat(checkoutConfig.quoteItemData[i].base_row_total) * 100,
                        }
                    }
                } catch (e) {
                    // Can not calculate line item discount due to insufficient data at this time
                }

                items = cartContainer.find('[data-role="cart-item-qty"]');
                for (i = 0; i < items.length; i++) {
                    var qty, number, itemContainer;
                    qty = $(items[i]).attr('value');
                    number = $(items[i]).data('cart-item-id');
                    itemContainer = $(items[i]).closest('.cart.item');
                    itemContainer.attr('data-flow-cart-item-number', number);
                    itemContainer.attr('data-flow-cart-item-quantity', qty);
                    itemContainer.find('.price .cart-price > span.price').attr('data-flow-localize','cart-item-price');
                    itemContainer.find('.subtotal .cart-price > span.price').attr('data-flow-localize','cart-item-line-total');
                    try {
                        if (lineItemDiscount[number].percent > 0 && window.flow.magento2.support_discounts) {
                            itemContainer.attr('data-flow-cart-item-discount-percent', lineItemDiscount[number].percent);
                        }
                    } catch (e) {
                        // Can not calculate line item discount due to insufficient data at this time
                    }
                }

                window.flow.cart.localize();

                return result;
            },
        });
        return $.mage.shoppingCart;
    }
});

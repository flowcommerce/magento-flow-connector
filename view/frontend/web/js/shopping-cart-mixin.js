define([
    'jquery',
    'Magento_Checkout/js/model/totals'
], function ($, totalsService) {
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
                    items,
                    i,
                    cartContainer,
                    totals,
                    subtotal,
                    grandtotal,
                    discount;
                if (typeof(flow.session.getExperience()) == 'string') {
                    cartContainer = $('.cart-container').first();
                    cartContainer.attr('data-flow-cart-container', '');

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

                    totalsService.totals.subscribe(function () {
                        console.log('totals updated');

                        totals = cartContainer.find('#cart-totals');
                        subtotal = totals.find('[data-th=\'Subtotal\'] span.price').first();
                        grandtotal = totals.find('[data-th=\'Order Total\'] span.price').first();
                        discount = totals.find('[data-th=\'Discount\'] span.price').first();

                        subtotal.attr('data-flow-localize','cart-subtotal'); 
                        grandtotal.attr('data-flow-localize','cart-total'); 
                        discount.attr('data-flow-localize','cart-discount'); 

                        flow.cart.localize();
                        console.log('totals localized');
                    });

                    console.log('cart localize init');
                    flow.cart.localize();
                }

                return result;
            },
        });
        return $.mage.shoppingCart;
    }
});

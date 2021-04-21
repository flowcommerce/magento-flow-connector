define([
    'flowInit',
    'jquery',
    'mage/utils/wrapper'
], function (flow, $, wrapper) {
    'use strict';
    return function (customerData) {
        function bindTotalsObserver() {
            var targetTotals = document.getElementById('cart-totals');
            if (targetTotals == null) {
                return false;
            }

            var config = {childList: true, subtree: true, characterData: true };

            var callback = function(mutationsList, observer) {
                reloadFlowCart(observer);
            };

            var observer = new MutationObserver(callback);

            observer.observe(targetTotals, config);
        }

        function reloadFlowCart(observer = null) {
            if (observer) observer.disconnect();
            if (!flow.magento2.shouldLocalizeCart()) {
                flow.magento2.showCart();
                flow.magento2.showCartTotals();
                return false;
            }

            flow.magento2.hideCart();
            flow.magento2.hideCartTotals();

            var totals, subtotal, grandTotal, discount, flowFields, shippingEstimator, giftCard, localTax;
            totals = $('#cart-totals');
            shippingEstimator = $('#block-shipping');
            giftCard = $('#block-giftcard');
            localTax = totals.find('.totals-tax');
            subtotal = totals.find('.totals.sub');
            grandTotal = totals.find('.totals.grand');
            discount = totals.find('[data-th=\'Discount\']');

            subtotal.find('span.price').attr('data-flow-localize','cart-subtotal'); 
            grandTotal.find('span.price').attr('data-flow-localize','cart-total'); 
            if (discount) {
                if (flow.magento2.support_discounts) {
                    discount.find('span.price').attr('data-flow-localize','cart-discount'); 
                } else {
                    discount.hide();
                }
            }
            if (totals.find('[data-flow-localize="cart-tax"]').length <= 0 && !flow.magento2.miniCartAvailable) {
                flowFields = $(`<tr class="totals vat flow-localize">\
                    <th data-bind="i18n: title" class="mark" scope="row" data-flow-localize="cart-tax-name">Tax</th>\
                    <td class="amount">\
                    <span class="price" data-bind="text: getValue(), attr: {'data-th': title}" data-th="Flow Tax" data-flow-localize="cart-tax"></span>\
                    </td>\
                    </tr>\
                    <tr class="totals duty flow-localize">\
                    <th data-bind="i18n: title" class="mark" scope="row">Duty</th>\
                    <td class="amount">\
                    <span class="price" data-bind="text: getValue(), attr: {'data-th': title}" data-th="Flow Duty" data-flow-localize="cart-duty"></span>\
                    </td>\
                    </tr>\
                    <tr class="totals shipping flow-shipping flow-localize">\
                    <th data-bind="i18n: title" class="mark" scope="row">Estimated Shipping</th>\
                    <td class="amount">\
                    <span class="price" data-bind="text: getValue(), attr: {'data-th': title}" data-th="Flow Shipping" data-flow-localize="cart-shipping"></span>\
                    </td>\
                    </tr>\
                    `);
                if (subtotal) subtotal.after(flowFields);
                if (shippingEstimator) shippingEstimator.hide();
                if (giftCard) giftCard.hide();
                if (localTax) localTax.hide();
                flow.magento2.installedFlowTotalsFields = true;
            }

            flow.cmd('on', 'cartLocalized', bindTotalsObserver); 
            flow.cart.localize();
            return true;
        }

        flow.cmd('on', 'ready', function () {
            bindTotalsObserver();
            flow.cmd('on', 'cartError', function () {
                flow.magento2.showCart();
                flow.magento2.showCartTotals();

                var totals, subtotal, grandTotal, discount, flowFields, shippingEstimator, giftCard, localTax;
                totals = $('#cart-totals');
                shippingEstimator = $('#block-shipping');
                giftCard = $('#block-giftcard');
                localTax = totals.find('.totals-tax');
                subtotal = totals.find('.totals.sub');
                grandTotal = totals.find('.totals.grand');
                discount = totals.find('[data-th=\'Discount\']');

                if (discount) discount.show();
                if (shippingEstimator) shippingEstimator.show();
                if (giftCard) giftCard.show();
                if (localTax) localTax.show();
            }); 
        });
    };
});

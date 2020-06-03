define([
    'jquery',
    'mage/utils/wrapper',
    'flowCompanion'
], function ($, wrapper) {
    'use strict';

    return function (customerData) {
        function bindTotalsObserver() {
            var targetTotals = document.getElementById('cart-totals');
            if (targetTotals == null) {
                return false;
            }

            var config = {childList: true, subtree: true };

            var callback = function(mutationsList, observer) {
                var shouldUpdate = false;
                for (var i = 0; i < mutationsList.length; i++) {
                    if (mutationsList[i].type === 'childList') {
                        shouldUpdate = true;
                    }
                }
                if (shouldUpdate) {
                    reloadFlowCart(observer);
                }
            };

            var observer = new MutationObserver(callback);

            observer.observe(targetTotals, config);
        }

        function reloadFlowCart(observer = null) {
            if (observer) {
                observer.disconnect();
            }
            if (!window.flow.magento2.shouldLocalizeCart) {
                window.flow.magento2.showCart();
                window.flow.magento2.showCartTotals();
                return false;
            }

            window.flow.magento2.hideCart();
            window.flow.magento2.hideCartTotals();

            var totals, subtotal, grandtotal, discount, flowFields, shippingEstimator;
            totals = $('#cart-totals');
            shippingEstimator = $('#block-shipping');
            subtotal = totals.find('[data-th=\'Subtotal\']');
            grandtotal = totals.find('[data-th=\'Order Total\'] span.price');
            if (window.flow.magento2.support_discounts) {
                discount = totals.find('[data-th=\'Discount\'] span.price');
            }

            subtotal.attr('data-flow-localize','cart-subtotal'); 
            grandtotal.attr('data-flow-localize','cart-total'); 
            if (window.flow.magento2.support_discounts) {
                discount.attr('data-flow-localize','cart-discount'); 
            }
            if (totals.find('[data-th="Flow Tax"]').length <= 0 && !window.flow.magento2.miniCartAvailable) {
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
                    <tr class="totals shipping flow-localize">\
                    <th data-bind="i18n: title" class="mark" scope="row">Estimated Shipping</th>\
                    <td class="amount">\
                    <span class="price" data-bind="text: getValue(), attr: {'data-th': title}" data-th="Flow Shipping" data-flow-localize="cart-shipping"></span>\
                    </td>\
                    </tr>\
                    `);
                totals.find('.totals.sub').after(flowFields);
                shippingEstimator.hide();
                window.flow.magento2.installedFlowTotalsFields = true;
            }

            window.flow.events.on('cartLocalized', bindTotalsObserver);
            window.flow.cart.localize();
            return true;
        }

        customerData.init = wrapper.wrap(customerData.init, function (_super) {
            var result = _super();
            bindTotalsObserver();
            return result;
        });

        return customerData;
    };
});

define([
    'jquery',
    'flowJs',
    'flowCountryPicker'
], function ($) {
    return function (config) {
        const flow = window.flow || {};
        flow.cmd = flow.cmd || function () {
            (flow.q = flow.q || []).push(arguments);
        };
        flow.cmd('set', 'organization', config.organization_id);
        flow.cmd('set', 'optinContainerSelector', '#flow-optin');
        flow.cmd('init');
        flow.magento2 = flow.magento || {};
        flow.magento2.enabled = config.enabled;
        flow.magento2.production = config.production;
        flow.magento2.support_discounts = config.support_discounts;
        flow.magento2.cart_localize = config.cart_localize;
        flow.magento2.catalog_localize = config.catalog_localize;
        flow.magento2.pricing_timeout = config.pricing_timeout;
        flow.magento2.cart_timeout = config.cart_timeout;
        flow.magento2.payment_methods_enabled = config.payment_methods_enabled;
        flow.magento2.shipping_window_enabled = config.shipping_window_enabled;
        flow.magento2.tax_duty_enabled = config.tax_duty_enabled;
        flow.magento2.hasLocalizedCatalog = false;
        flow.magento2.hasLocalizedCart = false;
        flow.magento2.hasLocalizedCartTotals = false;
        flow.magento2.installedFlowTotalsFields = false;
        flow.magento2.hasExperience = function () {
            flow.session = window.flow.session || {}; 
            return typeof(flow.session.getExperience()) == "string";
        }

        flow.magento2.shouldLocalizeCatalog = function () {
            return flow.magento2.hasExperience() && flow.magento2.catalog_localize;
        }

        flow.magento2.shouldLocalizeCart = function () {
            return flow.magento2.hasExperience() && flow.magento2.cart_localize;
        }

        flow.magento2.showPrices = function () {
            if (!flow.magento2.hasLocalizedCatalog) {
                flow.magento2.hasLocalizedCatalog = true;
                document.getElementsByTagName('body')[0].classList.add('flow-catalog-localized');
            }
        }

        flow.magento2.hidePrices = function () {
            if (flow.magento2.hasLocalizedCatalog) {
                flow.magento2.hasLocalizedCatalog = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-catalog-localized');
            }
        }

        flow.magento2.showCart = function () {
            if (!flow.magento2.hasLocalizedCart) {
                flow.magento2.hasLocalizedCart = true;
                document.getElementsByTagName('body')[0].classList.add('flow-cart-localized');
            }
        }

        flow.magento2.hideCart = function () {
            if (flow.magento2.hasLocalizedCart) {
                flow.magento2.hasLocalizedCart = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-cart-localized');
            }
        }

        flow.magento2.showCartTotals = function () {
            if (!flow.magento2.hasLocalizedCartTotals) {
                flow.magento2.hasLocalizedCartTotals = true;
                document.getElementsByTagName('body')[0].classList.add('flow-cart-totals-localized');
            }
        }

        flow.magento2.hideCartTotals = function () {
            if (flow.magento2.hasLocalizedCartTotals) {
                flow.magento2.hasLocalizedCartTotals = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-cart-totals-localized');
            }
        }

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

        flow.cmd('on', 'ready', function() {
            if (flow.magento2.shipping_window_enabled) {
                flow.cmd('set', 'shippingWindow', {
                    formatters: {
                        all: function (minDate, maxDate) {
                            const minFormattedDate = day(minDate).format('MMM D');
                            const maxFormattedDate = day(maxDate).format('MMM D');

                            return `Estimated delivery: ${minFormattedDate} - ${maxFormattedDate}`;
                        }
                    }
                });
            }
            flow.cmd('localize');

            flow.cmd('on', 'catalogLocalized', function() {
                flow.magento2.showPrices();
            });

            flow.cmd('on', 'cartLocalized', function(data) {
                flow.magento2.showCart();
                if (flow.magento2.installedFlowTotalsFields) {
                    flow.magento2.showCartTotals();
                }
            });

            if (!flow.magento2.hasExperience()) {
                flow.magento2.showPrices();
                flow.magento2.showCart();
                flow.magento2.showCartTotals();
            }

            if (config.country_picker_enabled) {
                flow.magento2.countryPickerOptions = flow.magento2.countryPickerOptions || {};
                flow.magento2.countryPickerOptions.containerId = 'flow-country-picker';
                flow.magento2.countryPickerOptions.type = 'dropdown';
                flow.magento2.countryPickerOptions.logo = true;
                flow.magento2.countryPickerOptions.isDestination = true;
                flow.magento2.countryPickerOptions.onSessionUpdate = function (status, session) {
                    $.cookie('flow_mage_session_update', 1,  {domain: null});
                    location.reload();
                };

                flow.countryPicker.createCountryPicker(flow.magento2.countryPickerOptions);
            }

            $(document).on('ajax:addToCart', function (event, data) {
                var sku = data.sku,
                    qty = 1,
                    options,
                    productId;

                if (typeof flow.magento2.simpleProduct == 'string') {
                    productId = flow.magento2.simpleProduct;
                }

                if (typeof flow.magento2.optionsSelected == 'object' && typeof data.productIds == 'object') {
                    if (!_.contains(flow.magento2.optionsSelected[data.productIds[0]], false) &&
                        flow.magento2.optionsIndex[data.productIds[0]] != undefined
                    ) {
                        _.each(flow.magento2.optionsIndex[data.productIds[0]], function (optionData) {
                            if (_.difference(optionData.optionIds, flow.magento2.optionsSelected[data.productIds[0]]).length == 0) {
                                productId = optionData.productId;
                            }
                        });
                    } 
                } 

                if (flow.magento2.product_id_sku_map != undefined && productId) {
                    if (flow.magento2.product_id_sku_map[productId]) {
                        sku = flow.magento2.product_id_sku_map[productId];
                    }
                }

                if (sku && qty) {
                    const cartAddEvent = {
                        item_number: sku,
                        quantity: qty
                    };

                    flow.beacon.processEvent('cart_add', cartAddEvent);
                }
            });

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

        return flow;
    };
});

define([
    'jquery',
    'day',
    'Magento_Customer/js/customer-data',
    'underscore',
    'flowJsBundle',
    'flowCountryPickerBundle',
    'domReady!'
], function ($, day, customerData, _) {
    return function (config) {
        window.flow = window.flow || {};
        window.flow.cmd = window.flow.cmd || function () {
            (window.flow.q = window.flow.q || []).push(arguments);
        };
        window.flow.cmd('set', 'organization', config.organization_id);
        window.flow.cmd('set', 'optinContainerSelector', '#flow-optin');
        window.flow.cmd('init');
        window.flow.magento2 = window.flow.magento2 || {};
        window.flow.magento2.enabled = config.enabled;
        window.flow.magento2.production = config.production;
        window.flow.magento2.support_discounts = config.support_discounts;
        window.flow.magento2.cart_localize = config.cart_localize;
        window.flow.magento2.catalog_localize = config.catalog_localize;
        window.flow.magento2.pricing_timeout = config.pricing_timeout;
        window.flow.magento2.cart_timeout = config.cart_timeout;
        window.flow.magento2.payment_methods_enabled = config.payment_methods_enabled;
        window.flow.magento2.shipping_window_enabled = config.shipping_window_enabled;
        window.flow.magento2.tax_duty_enabled = config.tax_duty_enabled;
        window.flow.magento2.hasLocalizedCatalog = false;
        window.flow.magento2.hasLocalizedCart = false;
        window.flow.magento2.hasLocalizedCartTotals = false;
        window.flow.magento2.installedFlowTotalsFields = false;
        window.flow.magento2.hasExperience = function () {
            return typeof(window.flow.session.getExperience()) == "string";
        }

        window.flow.magento2.shouldLocalizeCatalog = function () {
            return window.flow.magento2.hasExperience() && window.flow.magento2.catalog_localize;
        }

        window.flow.magento2.shouldLocalizeCart = function () {
            return window.flow.magento2.hasExperience() && window.flow.magento2.cart_localize;
        }

        window.flow.magento2.showPrices = function () {
            if (!window.flow.magento2.hasLocalizedCatalog) {
                window.flow.magento2.hasLocalizedCatalog = true;
                document.getElementsByTagName('body')[0].classList.add('flow-catalog-localized');
            }
        }

        window.flow.magento2.hidePrices = function () {
            if (window.flow.magento2.hasLocalizedCatalog) {
                window.flow.magento2.hasLocalizedCatalog = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-catalog-localized');
            }
        }

        window.flow.magento2.showCart = function () {
            if (!window.flow.magento2.hasLocalizedCart) {
                window.flow.magento2.hasLocalizedCart = true;
                document.getElementsByTagName('body')[0].classList.add('flow-cart-localized');
            }
        }

        window.flow.magento2.hideCart = function () {
            if (window.flow.magento2.hasLocalizedCart) {
                window.flow.magento2.hasLocalizedCart = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-cart-localized');
            }
        }

        window.flow.magento2.showCartTotals = function () {
            if (!window.flow.magento2.hasLocalizedCartTotals) {
                window.flow.magento2.hasLocalizedCartTotals = true;
                document.getElementsByTagName('body')[0].classList.add('flow-cart-totals-localized');
            }
        }

        window.flow.magento2.hideCartTotals = function () {
            if (window.flow.magento2.hasLocalizedCartTotals) {
                window.flow.magento2.hasLocalizedCartTotals = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-cart-totals-localized');
            }
        }

        if(config.base_currency_code) {
            var customOptionPriceNoticeElements = $('.catalog-product-view span.price-notice');
            $(customOptionPriceNoticeElements).each(function() {
                var priceWrapper = $(this).find('.price-wrapper');
                var priceAmount = $(priceWrapper).data('price-amount');
                if(priceAmount) {
                    $(priceWrapper).attr('data-flow-price-conversion-amount', priceAmount);
                    $(priceWrapper).attr('data-flow-price-conversion-base-currency', config.base_currency_code);
                }
            });

            var customOptionSelectPriceElements = $('.catalog-product-view .product-custom-option [price]');
            $(customOptionSelectPriceElements).each(function() {
                var priceLabel = $(this).text();
                var priceLabelSplit = '+';
                var priceLabelArray = priceLabel.split(priceLabelSplit);
                if(priceLabelArray.length < 2) {
                    priceLabelSplit = '-';
                    var priceLabelArray = priceLabel.split(priceLabelSplit);
                }
                if(priceLabelArray.length == 2) {
                    var priceAmount = $(this).attr('price');
                    if(priceAmount) {
                        $(this).attr('data-flow-price-conversion-amount', Math.abs(priceAmount));
                        $(this).attr('data-flow-price-conversion-base-currency', config.base_currency_code);
                        $(this).attr('data-flow-item-prefix', priceLabelArray[0] + priceLabelSplit);
                    }
                }
            });
        }

        function bindTotalsObserver() {
            var targetTotals = document.getElementById('cart-totals');
            if (targetTotals == null) {
                return false;
            }

            var config = {childList: true, subtree: true, characterData: false };

            var callback = function(mutationsList, observer) {
                reloadFlowCart(observer);
            };

            var observer = new MutationObserver(debounce(callback, 1000));

            observer.observe(targetTotals, config);
        }

        function debounce(func, wait, immediate) {
            var timeout;

            return function() {
                var context = this,
                args = arguments;
                var callNow = immediate && !timeout;
                clearTimeout(timeout);

                timeout = setTimeout(function() {
                    timeout = null;
                    if (!immediate) {
                        func.apply(context, args);
                    }
                }, wait);

                if (callNow) func.apply(context, args);
            }
          }

        function reloadFlowCart(observer = null) {
            if (observer) observer.disconnect();
            if (!window.flow.magento2.shouldLocalizeCart()) {
                window.flow.magento2.showCart();
                window.flow.magento2.showCartTotals();
                return false;
            }

            window.flow.magento2.hideCart();
            window.flow.magento2.hideCartTotals();

            var totals, subtotal, grandTotal, discount, flowFields, shippingEstimator, giftCard, localTax;
            totals = $('#cart-totals');
            shippingEstimator = $('#block-shipping');
            giftCard = $('#block-giftcard');
            localTax = totals.find('.totals-tax');
            subtotal = totals.find('.totals.sub');
            grandTotal = totals.find('.totals.grand');
            shipping = totals.find('.totals.shipping');
            discount = totals.find('[data-th=\'Discount\']');

            subtotal.find('span.price').attr('data-flow-localize','cart-subtotal');
            grandTotal.find('span.price').attr('data-flow-localize','cart-total');
            if (discount) {
                if (window.flow.magento2.support_discounts) {
                    discount.find('span.price').attr('data-flow-localize','cart-discount');
                } else {
                    discount.hide();
                }
            }
            if (totals.find('[data-flow-localize="cart-tax"]').length <= 0 && !window.flow.magento2.miniCartAvailable) {
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
                if (shipping) shipping.hide();
                window.flow.magento2.installedFlowTotalsFields = true;
            }

            window.flow.cmd('on', 'cartLocalized', bindTotalsObserver);

            window.flow.cmd('on', 'ready', function() {
                window.flow.cart.localize();
            });
            return true;
        }

        window.flow.cmd('on', 'ready', function() {
            if (window.flow.magento2.shipping_window_enabled) {
                window.flow.cmd('set', 'shippingWindow', {
                    formatters: {
                        all: function (minDate, maxDate) {
                            const minFormattedDate = day(minDate).format('MMM D');
                            const maxFormattedDate = day(maxDate).format('MMM D');

                            return `Estimated delivery: ${minFormattedDate} - ${maxFormattedDate}`;
                        }
                    }
                });
            }
            window.flow.cmd('localize');

            window.flow.cmd('on', 'catalogLocalized', function() {
                window.flow.magento2.showPrices();
            });

            window.flow.cmd('on', 'cartLocalized', function(data) {
                window.flow.magento2.showCart();
                if (window.flow.magento2.installedFlowTotalsFields) {
                    window.flow.magento2.showCartTotals();
                }
            });

            if (!window.flow.magento2.hasExperience()) {
                window.flow.magento2.showPrices();
                window.flow.magento2.showCart();
                window.flow.magento2.showCartTotals();
            }

            if (config.country_picker_enabled) {
                window.flow.magento2.countryPickerOptions = window.flow.magento2.countryPickerOptions || {};
                window.flow.magento2.countryPickerOptions.containerId = 'flow-country-picker';
                window.flow.magento2.countryPickerOptions.type = 'dropdown';
                window.flow.magento2.countryPickerOptions.logo = true;
                window.flow.magento2.countryPickerOptions.isDestination = true;
                window.flow.magento2.countryPickerOptions.onSessionUpdate = function (status, session) {
                    $.cookie('flow_mage_session_update', 1,  {domain: null});
                    location.reload();
                };

                window.flow.countryPicker.createCountryPicker(window.flow.magento2.countryPickerOptions);
            }

            $(document).on('ajax:addToCart', function (event, data) {
                var sku = data.sku,
                    qty = 1,
                    options,
                    productId;

                if (typeof window.flow.magento2.simpleProduct == 'string') {
                    productId = window.flow.magento2.simpleProduct;
                }

                if (typeof window.flow.magento2.optionsSelected == 'object' && typeof data.productIds == 'object') {
                    if (!_.contains(window.flow.magento2.optionsSelected[data.productIds[0]], false) &&
                        window.flow.magento2.optionsIndex[data.productIds[0]] != undefined
                    ) {
                        _.each(window.flow.magento2.optionsIndex[data.productIds[0]], function (optionData) {
                            if (_.difference(optionData.optionIds, window.flow.magento2.optionsSelected[data.productIds[0]]).length == 0) {
                                productId = optionData.productId;
                            }
                        });
                    }
                }

                if (window.flow.magento2.product_id_sku_map != undefined && productId) {
                    if (window.flow.magento2.product_id_sku_map[productId]) {
                        sku = window.flow.magento2.product_id_sku_map[productId];
                    }
                }

                if (sku && qty) {
                    const cartAddEvent = {
                        item_number: sku,
                        quantity: qty
                    };

                    window.flow.cmd('on', 'ready', function() {
                        window.flow.beacon.processEvent('cart_add', cartAddEvent);
                    });
                }
            });

            bindTotalsObserver();
            customerData.reload(['cart'], false);


            window.flow.cmd('on', 'cartError', function () {
                window.flow.magento2.showCart();
                window.flow.magento2.showCartTotals();

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

            var miniCart,
                body,
                flowMiniCartLocalize;

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

        return window.flow;
    };
});

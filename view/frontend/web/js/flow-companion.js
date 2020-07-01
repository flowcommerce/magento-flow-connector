define([
'jquery',
'day',
'underscore',
'mage/cookies',
'flowCountryPicker',
'flowJs',
'es6-collections'
], function ($, day, _) {
    'use strict'; 

    flow.magento2 = flow.magento2 || {};
    flow.magento2.enabled = window.flow_enabled || false;
    flow.magento2.production = window.flow_production || false;
    flow.magento2.support_discounts = window.flow_support_magento_discounts || false;
    flow.magento2.cart_localize = window.flow_cart_localize || false;
    flow.magento2.catalog_localize = window.flow_catalog_localize || false;
    flow.magento2.pricing_timeout = window.flow_pricing_timeout || false;
    flow.magento2.payment_methods_enabled = window.flow_payment_methods_enabled || false;
    flow.magento2.shipping_window_enabled = window.flow_shipping_window_enabled || false;
    flow.magento2.tax_duty_enabled = window.flow_tax_duty_messaging_enabled || false;
    flow.magento2.hasExperience = function () {
         return typeof(flow.session.getExperience()) == "string";
    }
    flow.magento2.hasLocalizedCatalog = false;
    flow.magento2.hasLocalizedCart = false;
    flow.magento2.hasLocalizedCartTotals = false;
    flow.magento2.shouldLocalizeCatalog = function () {
        return flow.magento2.hasExperience() && flow.magento2.catalog_localize;
    }
    flow.magento2.shouldLocalizeCart = function () {
        return flow.magento2.hasExperience() && flow.magento2.cart_localize;
    }
    flow.magento2.installedFlowTotalsFields = false;

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

        if (!flow.magento2.hasExperience) {
            flow.magento2.showPrices();
            flow.magento2.showCart();
            flow.magento2.showCartTotals();
        }

        if (flow_country_picker_enabled) {
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

                flow.cmd('on', 'ready', function() {
                    flow.beacon.processEvent('cart_add', cartAddEvent);
                });
            }
        });
    });
});

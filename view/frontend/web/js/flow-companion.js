define([
    'jquery',
    'day',
    'underscore',
    'flowCountryPicker',
    'mage/cookies'
], function ($, day, _) {
    'use strict'; 

    !function (f, l, o, w, i, n, g) {
        f[i] = f[i] || {};f[i].cmd = f[i].cmd || function () {
            (f[i].q = f[i].q || []).push(arguments);};n = l.createElement(o);
        n.src = w;g = l.getElementsByTagName(o)[0];g.parentNode.insertBefore(n, g);
    }(window,document,'script',window.flow_flowjs_url,'flow');
    window.flow.session = window.flow.session || {};
    window.flow.cart = window.flow.cart || {};
    window.flow.magento2 = window.flow.magento2 || {};
    window.flow.magento2.enabled = window.flow_enabled;
    window.flow.magento2.production = window.flow_production;
    window.flow.magento2.support_discounts = window.flow_support_magento_discounts;
    window.flow.magento2.cart_localize = window.flow_cart_localize;
    window.flow.magento2.catalog_localize = window.flow_catalog_localize;
    window.flow.magento2.pricing_timeout = window.flow_pricing_timeout;
    window.flow.magento2.payment_methods_enabled = window.flow_payment_methods_enabled;
    window.flow.magento2.shipping_window_enabled = window.flow_shipping_window_enabled;
    window.flow.magento2.tax_duty_enabled = window.flow_tax_duty_messaging_enabled;
    window.flow.magento2.hasExperience = false;
    window.flow.magento2.hasLocalizedCatalog = false;
    window.flow.magento2.hasLocalizedCart = false;
    window.flow.magento2.hasLocalizedCartTotals = false;
    window.flow.magento2.shouldLocalizeCatalog = false;
    window.flow.magento2.shouldLocalizeCart = false;
    window.flow.magento2.installedFlowTotalsFields = false;
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

    window.flow.cmd('set', 'organization', window.flow_organization_id);
    window.flow.cmd('set', 'optinContainerSelector', '#flow-optin');
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
    window.flow.cmd('init');
    window.flow.cmd('localize');

    window.flow.cmd('on', 'ready', function() {
        window.flow.magento2.hasExperience = typeof(window.flow.session.getExperience()) == "string";
        window.flow.magento2.shouldLocalizeCatalog = window.flow.magento2.hasExperience && window.flow.magento2.catalog_localize;
        window.flow.magento2.shouldLocalizeCart = window.flow.magento2.hasExperience && window.flow.magento2.cart_localize;

        window.flow.events.on('catalogLocalized', function() {
            window.flow.magento2.showPrices();
        });

        window.flow.events.on('cartLocalized', function(data) {
            window.flow.magento2.showCart();
            if (window.flow.magento2.installedFlowTotalsFields) {
                window.flow.magento2.showCartTotals();
            }
        });

        if (!window.flow.magento2.hasExperience) {
            window.flow.magento2.showPrices();
            window.flow.magento2.showCart();
            window.flow.magento2.showCartTotals();
        }

        if (window.flow_country_picker_enabled) {
            window.flow.magento2.countryPickerOptions = window.flow.magento2.countryPickerOptions || {};
            window.flow.magento2.countryPickerOptions.containerId = 'flow-country-picker';
            window.flow.magento2.countryPickerOptions.type = 'dropdown';
            window.flow.magento2.countryPickerOptions.logo = true;
            window.flow.magento2.countryPickerOptions.isDestination = true;
            window.flow.magento2.countryPickerOptions.onSessionUpdate = function (status, session) {
                $.cookie('flow_mage_session_update', 1,  {domain: null});
                window.location.reload();
            };

            window.flow.countryPicker.createCountryPicker(window.flow.magento2.countryPickerOptions);
        }
    });

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

        if (productId &&
            window.flow.magento2.product_id_sku_map[productId] &&
            window.flow.magento2.product_id_sku_map != undefined
        ) {
            sku = window.flow.magento2.product_id_sku_map[productId];
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
});

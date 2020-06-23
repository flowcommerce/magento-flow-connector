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

    flow = flow || {};
    flow.cmd = flow.cmd || function () {
        (flow.q = flow.q || []).push(arguments);
    }
    flow.cmd('set', 'organization', flow_organization_id);
    flow.cmd('set', 'optinContainerSelector', '#flow-optin');
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
    flow.cmd('init');
    flow.cmd('localize');

    flow.cmd('on', 'ready', function() {
        flow.magento2.hasExperience = typeof(flow.session.getExperience()) == "string";
        flow.magento2.shouldLocalizeCatalog = flow.magento2.hasExperience && flow.magento2.catalog_localize;
        flow.magento2.shouldLocalizeCart = flow.magento2.hasExperience && flow.magento2.cart_localize;

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
    window.flow = flow;
});

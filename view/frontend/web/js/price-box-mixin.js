define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template',
    'jquery/ui'
], function ($, utils, _, mageTemplate) {
    'use strict';

    return function (widget) {
        var globalOptions = {
            productId: null,
            priceConfig: null,
            prices: {},
            priceTemplate: '<span class="price"><%- data.formatted %></span>'
        };

        $.widget('mage.priceBox', widget, {
            options: globalOptions,

            reloadPrice: function reDrawPrices() {
                var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                    priceTemplate = mageTemplate(this.options.priceTemplate);

                _.each(this.cache.displayPrices, function (price, priceCode) {
                    price.final = _.reduce(price.adjustments, function (memo, amount) {
                        return memo + amount;
                    }, price.amount);

                    price.formatted = utils.formatPrice(price.final, priceFormat);

                    var template = { data: price };
                    var BASEPRICE = 'base_price';
                    var REGULARPRICE = 'regular_price';
                    var FINALPRICE = 'final_price';
                    var experience = flow.session.getExperience();
                    var country = flow.session.getCountry();
                    var currency = flow.session.getCurrency();
                    var localizationKey = experience+country+currency;
                    var productId = this.options.productId;
                    var flowFormattedPrice = false;
                    var flowLocalizedPrices = this.options.priceConfig.flow_localized_prices;
                    if (typeof(experience) == "string" &&
                        flowLocalizedPrices != undefined
                    ) {
                        var price_code = REGULARPRICE;
                        switch (priceCode) {
                            case 'basePrice':
                                price_code = BASEPRICE; 
                                break;

                            case 'finalPrice':
                                price_code = FINALPRICE; 
                                break;
                        } 
                        if (flow.optionsIndex != undefined) {
                            var $form = $($('form#product_addtocart_form > input[value="'+productId+'"],[name="product"]')[0].form);
                            var atts = $form.find('input.super-attribute-select');
                            var att1 = atts[0].value;
                            var att2 = atts[1].value;
                            if (att1 > 0 || att2 > 0) {
                                _.each(flow.optionsIndex[productId], function(option, key) {
                                    if (_.contains(option, att1) && _.contains(option, att2)) {
                                        productId = key;
                                    }
                                });
                            }
                        }
                        if (flowLocalizedPrices[localizationKey][productId][FINALPRICE] != undefined) {
                            if (flowLocalizedPrices[localizationKey][productId][FINALPRICE].label != undefined) {
                                flowFormattedPrice = flowLocalizedPrices[localizationKey][productId][FINALPRICE].label;
                            }
                        }
                        if (flowLocalizedPrices[localizationKey][productId][price_code] != undefined) {
                            if (flowLocalizedPrices[localizationKey][productId][price_code].label != undefined) {
                                flowFormattedPrice = flowLocalizedPrices[localizationKey][productId][price_code].label;
                            }
                        }
                    } 
                    if (flowFormattedPrice) {
                        template = { data: { formatted: flowFormattedPrice } };
                    } 

                    $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate(template));
                }, this);
            },
        });
        return $.mage.priceBox;
    }
});

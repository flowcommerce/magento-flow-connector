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
                priceTemplate: '<span class="price"><%- data.formatted %></span>',
                flowPriceTemplateById: '<span data-flow-item-attribute-key="product_id" data-flow-item-attribute-value="<%- data.productId %>"><span data-flow-localize="item-price" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>',
                flowPriceTemplateBySkuPriceCode: '<span data-flow-item-number="<%- data.productSku %>"><span data-flow-localize="item-price-attribute" data-flow-item-price-attribute="<%- data.flowPriceCode %>" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>'
            },
            MAGENTOBASEPRICEKEY = 'basePrice',
            MAGENTOFINALPRICEKEY = 'finalPrice', 
            FLOWBASEPRICEKEY = 'base_price',
            FLOWREGULARPRICEKEY = 'regular_price',
            FLOWFINALPRICEKEY = 'final_price',
            FLOWEXPERIENCE = flow.session.getExperience(),
            FLOWCOUNTRY = flow.session.getCountry(),
            FLOWCURRENCY = flow.session.getCurrency(),
            FLOWLOCALIZATIONKEY = FLOWEXPERIENCE+FLOWCOUNTRY+FLOWCURRENCY;

        $.widget('mage.priceBox', widget, {
            options: globalOptions,

            reloadPrice: function reDrawPrices() {
                var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                    priceTemplate = mageTemplate(this.options.priceTemplate);
                this.hasFlowExperience = false;
                this.flowFormattedPrice = false;
                _.each(this.cache.displayPrices, function (price, priceCode) {
                    price.final = _.reduce(price.adjustments, function (memo, amount) {
                        return memo + amount;
                    }, price.amount);

                    price.formatted = utils.formatPrice(price.final, priceFormat);

                    var template = { data: price };
                    
                    if (typeof(FLOWEXPERIENCE) == "string") {
                        this.hasFlowExperience = true;
                        var flowLocalizedPrices = false,
                            flowPriceCode = FLOWREGULARPRICEKEY;
                        switch (priceCode) {
                            case MAGENTOBASEPRICEKEY:
                                flowPriceCode = FLOWBASEPRICEKEY; 
                                break;

                            case MAGENTOFINALPRICEKEY:
                                flowPriceCode = FLOWFINALPRICEKEY;
                                break;
                        }

                        if (this.options.priceConfig.flow_localized_prices != undefined) {
                            flowLocalizedPrices = this.options.priceConfig.flow_localized_prices;
                        } else if (this.options.priceConfig.prices.flow_localized_prices != undefined) {
                            flowLocalizedPrices = this.options.priceConfig.prices.flow_localized_prices;
                        }

                        template.data.productId = this.getCurrentProductId(this.options.productId);
                        template.data.productSku = this.getCurrentProductSku(template.data.productId, flowLocalizedPrices);
                        template.data.flowLocalized = false;
                        template.data.flowPriceCode = flowPriceCode;

                        template = this.localizeTemplate(template, flowLocalizedPrices);

                        if (!template.data.flowLocalized) {
                            priceTemplate = mageTemplate(this.options.flowPriceTemplateById);
                            if (template.data.productSku) {
                                priceTemplate = mageTemplate(this.options.flowPriceTemplateBySkuPriceCode);
                            }
                        }
                        console.log('localize FE init ' + template.data.flowPriceCode);
                    }

                    $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate(template));
                }, this);
                if (this.hasFlowExperience && !this.flowFormattedPrice) {
                    flow.cmd('localize');
                    console.log('localize FE trigger');
                }
            },

            getCurrentProductId: function (productId) {
                if (flow.optionsIndex != undefined) {
                    var $form = $($('form#product_addtocart_form > input[value="'+productId+'"],[name="product"]')[0].form);
                    var atts = $form.find('input.super-attribute-select');
                    var att1 = atts[0].value;
                    var att2 = atts[1].value;
                    if (atts != undefined) {
                    }
                    if (att1 > 0 || att2 > 0) {
                        _.each(flow.optionsIndex[productId], function(option, key) {
                            if (_.contains(option, att1) && _.contains(option, att2)) {
                                productId = key;
                            }
                        });
                    }
                }
                return productId;
            },

            getCurrentProductSku: function (productId, flowLocalizedPrices) {
                if (flowLocalizedPrices) {
                    if (flowLocalizedPrices[FLOWLOCALIZATIONKEY][productId]['sku'] != undefined) {
                        return flowLocalizedPrices[FLOWLOCALIZATIONKEY][productId]['sku'];
                    }
                }
                return false;
            },

            localizeTemplate: function (template, flowLocalizedPrices) {
                if (flowLocalizedPrices[FLOWLOCALIZATIONKEY][template.data.productId][FLOWFINALPRICEKEY] != undefined) {
                    if (flowLocalizedPrices[FLOWLOCALIZATIONKEY][template.data.productId][FLOWFINALPRICEKEY].label != undefined) {
                        this.flowFormattedPrice = flowLocalizedPrices[FLOWLOCALIZATIONKEY][template.data.productId][FLOWFINALPRICEKEY].label;
                    }
                }
                if (flowLocalizedPrices[FLOWLOCALIZATIONKEY][template.data.productId][template.data.flowPriceCode] != undefined) {
                    if (flowLocalizedPrices[FLOWLOCALIZATIONKEY][template.data.productId][template.data.flowPriceCode].label != undefined) {
                        this.flowFormattedPrice = flowLocalizedPrices[FLOWLOCALIZATIONKEY][template.data.productId][template.data.flowPriceCode].label;
                    }
                }
                if (this.flowFormattedPrice) {
                    template.data.formatted = this.flowFormattedPrice;
                    template.data.flowLocalized = true;
                    console.log('localize BE');
                }
                return template;
            },
        });
        return $.mage.priceBox;
    }
});

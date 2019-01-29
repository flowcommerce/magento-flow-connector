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
                priceTemplate: '<span class="price"><%- data.formatted %></span>',
                flowPriceTemplateById: '<span data-flow-item-attribute-key="product_id" data-flow-item-attribute-value="<%- data.productId %>"><span data-flow-localize="item-price" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>',
                flowPriceTemplateBySkuPriceCode: '<span data-flow-item-number="<%- data.productSku %>"><span data-flow-localize="item-price-attribute" data-flow-item-price-attribute="<%- data.flowPriceCode %>" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>'
            },
            flow = window.flow || {},
            MAGENTOBASEPRICEKEY = 'basePrice',
            MAGENTOFINALPRICEKEY = 'finalPrice', 
            FLOWBASEPRICEKEY = 'base_price',
            FLOWREGULARPRICEKEY = 'regular_price',
            FLOWFINALPRICEKEY = 'final_price';

        $.widget('mage.priceBox', widget, {
            options: globalOptions,

            reloadPrice: function reDrawPrices() {
                var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                    priceTemplate = mageTemplate(this.options.priceTemplate);
                this.flowFormattedPrice = false;
                _.each(this.cache.displayPrices, function (price, priceCode) {
                    price.final = _.reduce(price.adjustments, function (memo, amount) {
                        return memo + amount;
                    }, price.amount);

                    price.formatted = utils.formatPrice(price.final, priceFormat);

                    var template = { data: price };
                    
                    var flowLocalizationKey = this.getFlowExperience();
                    if (flowLocalizationKey) {
                        flow.magento2 = window.flow.magento2 || {};
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
                        template.data.productSku = this.getCurrentProductSku(template.data.productId, flowLocalizedPrices, flowLocalizationKey);
                        template.data.flowLocalized = false;
                        template.data.flowPriceCode = flowPriceCode;

                        template = this.localizeTemplate(template, flowLocalizedPrices, flowLocalizationKey);

                        if (!template.data.flowLocalized) {
                            priceTemplate = mageTemplate(this.options.flowPriceTemplateById);
                            if (template.data.productSku) {
                                priceTemplate = mageTemplate(this.options.flowPriceTemplateBySkuPriceCode);
                            }
                        }
                    }

                    $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate(template));
                }, this);
                if (flowLocalizationKey && !this.flowFormattedPrice) {
                    flow.cmd('localize');
                }
            },

            getFlowExperience: function () {
                var flowLocalizationKey = false;
                if (flow.session != undefined) {
                    var flowExperience = flow.session.getExperience(),
                        flowCountry = flow.session.getCountry(),
                        flowCurrency = flow.session.getCurrency();
                    if (typeof(flowExperience) == "string" &&
                        typeof(flowCountry) == "string" &&
                        typeof(flowCurrency) == "string"
                    ) {
                        flowLocalizationKey = flowExperience+flowCountry+flowCurrency;
                    }
                }
                return flowLocalizationKey;
            },

            getCurrentProductId: function (productId) {
                if (flow.magento2.optionsSelected != undefined) {
                    if (flow.magento2.optionsSelected[productId] != undefined) {
                        if (!_.contains(flow.magento2.optionsSelected[productId], false) && flow.magento2.optionsIndex[productId] != undefined) {
                            _.each(flow.magento2.optionsIndex[productId], function (optionData) {
                                if (_.difference(optionData.optionIds, flow.magento2.optionsSelected[productId]).length == 0) {
                                    productId = optionData.productId;
                                }
                            });
                        } 
                    }
                }
                return productId;
            },

            getCurrentProductSku: function (productId, flowLocalizedPrices, flowLocalizationKey) {
                if (flowLocalizedPrices) {
                    if (flowLocalizedPrices[flowLocalizationKey][productId]['sku'] != undefined) {
                        return flowLocalizedPrices[flowLocalizationKey][productId]['sku'];
                    }
                }
                return false;
            },

            localizeTemplate: function (template, flowLocalizedPrices, flowLocalizationKey) {
                if (flowLocalizedPrices[flowLocalizationKey][template.data.productId][FLOWFINALPRICEKEY] != undefined) {
                    if (flowLocalizedPrices[flowLocalizationKey][template.data.productId][FLOWFINALPRICEKEY].label != undefined) {
                        this.flowFormattedPrice = flowLocalizedPrices[flowLocalizationKey][template.data.productId][FLOWFINALPRICEKEY].label;
                    }
                }
                if (flowLocalizedPrices[flowLocalizationKey][template.data.productId][template.data.flowPriceCode] != undefined) {
                    if (flowLocalizedPrices[flowLocalizationKey][template.data.productId][template.data.flowPriceCode].label != undefined) {
                        this.flowFormattedPrice = flowLocalizedPrices[flowLocalizationKey][template.data.productId][template.data.flowPriceCode].label;
                    }
                }
                if (this.flowFormattedPrice) {
                    template.data.formatted = this.flowFormattedPrice;
                    template.data.flowLocalized = true;
                }
                return template;
            },
        });
        return $.mage.priceBox;
    }
});

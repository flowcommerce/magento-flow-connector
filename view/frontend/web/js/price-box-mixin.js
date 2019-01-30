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
                priceTemplate: '<span data-flow-localize="item-price" class="price"><%- data.formatted %></span>',
                flowPriceTemplateById: '<span data-flow-item-attribute-key="product_id" data-flow-item-attribute-value="<%- data.productId %>"><span data-flow-localize="item-price" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>',
                flowPriceTemplateBySku: '<span data-flow-item-number="<%- data.productSku %>"><span data-flow-localize="item-price" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>',
                flowPriceTemplateBySkuPriceCode: '<span data-flow-item-number="<%- data.productSku %>"><span data-flow-localize="item-price-attribute" data-flow-item-price-attribute="<%- data.flowPriceCode %>" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>'
            },
            flow = window.flow || {},
            MAGENTOREGULARPRICEKEY = 'regularPrice',
            MAGENTOBASEPRICEKEY = 'basePrice',
            MAGENTOFINALPRICEKEY = 'finalPrice', 
            FLOWBASEPRICEKEY = 'base_price',
            FLOWREGULARPRICEKEY = 'regular_price',
            FLOWFINALPRICEKEY = 'final_price';
        flow.magento2 = window.flow.magento2 || {};

        $.widget('mage.priceBox', widget, {
            options: globalOptions,

            reloadPrice: function reDrawPrices() {
                var flowLocalizationKey = this.getFlowExperience();
                if (this.options.priceConfig.prices != undefined) {
                    if (this.options.priceConfig.prices.flow_localization_enabled == false) {
                        return this._super();
                    }
                }
                if (!flowLocalizationKey ||
                    this.options.priceConfig.flow_localization_enabled == false
                ) {
                    return this._super();
                }

                var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                    priceTemplate = mageTemplate(this.options.priceTemplate),
                    flowLocalizedPrices = false; 
                this.flowFormattedPrice = false; 

                if (this.options.priceConfig.flow_localized_prices != undefined) {
                    flowLocalizedPrices = this.options.priceConfig.flow_localized_prices;
                } else if (this.options.priceConfig.prices != undefined) {
                    if (this.options.priceConfig.prices.flow_localized_prices != undefined) {
                        flowLocalizedPrices = this.options.priceConfig.prices.flow_localized_prices;
                    }
                }

                _.each(this.cache.displayPrices, function (price, priceCode) {
                    if (price.amount != undefined) {
                        price.final = _.reduce(price.adjustments, function (memo, amount) {
                            return memo + amount;
                        }, price.amount);

                        price.formatted = utils.formatPrice(price.final, priceFormat);

                        var template = { data: price };
                        
                        template.data.flowLocalized = false;
                        template.data.flowPriceCode = this.getFlowPriceCode(priceCode);
                        template.data.productId = this.getCurrentProductId(this.options.productId); 

                        if (flowLocalizedPrices) {
                            template.data.productSku = this.getCurrentProductSku(template.data.productId, flowLocalizedPrices, flowLocalizationKey);
                            template = this.localizeTemplate(template, flowLocalizedPrices, flowLocalizationKey);
                        } 

                        if (!template.data.flowLocalized) {
                            priceTemplate = mageTemplate(this.options.flowPriceTemplateById);
                            if (template.data.productSku) {
                                if (template.data.flowPriceCode == FLOWFINALPRICEKEY) {
                                    priceTemplate = mageTemplate(this.options.flowPriceTemplateBySku);
                                } else {
                                    priceTemplate = mageTemplate(this.options.flowPriceTemplateBySkuPriceCode);
                                }
                            }
                        }

                        $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate(template));
                    }
                }, this);
                if (!this.flowFormattedPrice) {
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

            getFlowPriceCode: function (priceCode) {
                var flowPriceCode = false;
                if (typeof(priceCode) == "string") {
                    switch (priceCode) {
                        case MAGENTOREGULARPRICEKEY:
                            flowPriceCode = FLOWREGULARPRICEKEY;
                            break;

                        case MAGENTOBASEPRICEKEY:
                            flowPriceCode = FLOWBASEPRICEKEY; 
                            break;

                        case MAGENTOFINALPRICEKEY:
                            flowPriceCode = FLOWFINALPRICEKEY;
                            break;
                    }
                }
                return flowPriceCode;
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
                if (_.toArray(flowLocalizedPrices[flowLocalizationKey][template.data.productId])[0] != undefined) {
                    if (_.toArray(flowLocalizedPrices[flowLocalizationKey][template.data.productId])[0].label != undefined) {
                        this.flowFormattedPrice = _.toArray(flowLocalizedPrices[flowLocalizationKey][template.data.productId])[0].label;
                    }
                }
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

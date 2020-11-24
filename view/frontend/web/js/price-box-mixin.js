define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template',
    'jquery/ui',
    'flowCompanion'
], function ($, utils, _, mageTemplate) {
    'use strict';

    return function (widget) {
        var globalOptions = {
                priceTemplate: '<span data-flow-localize="item-price" class="price"><%- data.formatted %></span>',
                flowPriceTemplateBySku: '<span data-flow-item-number="<%- data.productSku %>"><span data-flow-localize="item-price" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>',
                flowPriceTemplateBySkuPriceCode: '<span data-flow-item-number="<%- data.productSku %>"><span data-flow-localize="item-price-attribute" data-flow-item-price-attribute="<%- data.flowPriceCode %>" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span></span>'
            },
            MAGENTOMINPRICEKEY = 'minPrice',
            MAGENTOOLDPRICEKEY = 'oldPrice',
            MAGENTOREGULARPRICEKEY = 'regularPrice',
            MAGENTOBASEPRICEKEY = 'basePrice',
            MAGENTOFINALPRICEKEY = 'finalPrice', 
            FLOWMINPRICEKEY = 'minimal_price',
            FLOWREGULARPRICEKEY = 'regular_price',
            FLOWFINALPRICEKEY = 'final_price',
            FLOWACTUALPRICEKEY = 'localized_item_price';

        $.widget('mage.priceBox', widget, {
            options: globalOptions,

            reloadPrice: function reDrawPrices() {
                flow.magento2.product_id_sku_map = flow.magento2.product_id_sku_map || {};

                if (this.options.prices.flow_product_id_sku_map != undefined) { 
                    Object.assign(
                        flow.magento2.product_id_sku_map,
                        this.options.prices.flow_product_id_sku_map
                    );
                }

                if (this.options.priceConfig.flow_product_id_sku_map != undefined) { 
                    Object.assign(
                        flow.magento2.product_id_sku_map,
                        this.options.priceConfig.flow_product_id_sku_map
                    );
                }

                var flowLocalizationKey = this.getFlowLocalizationKey();

                if (!flowLocalizationKey || !flow.magento2.shouldLocalizeCatalog()) {
                    flow.magento2.showPrices();
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
                            if (template.data.productSku) {
                                if (template.data.flowPriceCode == FLOWACTUALPRICEKEY) {
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
                } else {
                    flow.magento2.showPrices();
                }
            },

            getFlowLocalizationKey: function () {
                var flowLocalizationKey = false;
                try {
                    var flowExperience = flow.session.getExperience(),
                        flowCountry = flow.session.getCountry(),
                        flowCurrency = flow.session.getCurrency();

                    if (flowExperience && flowCountry && flowCurrency) {
                        flowLocalizationKey = flowExperience+flowCountry+flowCurrency;
                    }
                } catch (e) {
                    // No viable flow experience was found at this time, acceptable
                }
                return flowLocalizationKey;
            },

            getFlowPriceCode: function (priceCode) {
                var flowPriceCode = false;
                if (typeof(priceCode) == "string") {
                    switch (priceCode) {
                        case MAGENTOMINPRICEKEY:
                            flowPriceCode = FLOWMINPRICEKEY;
                            break;

                        case MAGENTOBASEPRICEKEY:
                        case MAGENTOBASEPRICEKEY:
                        case MAGENTOOLDPRICEKEY:
                        case MAGENTOREGULARPRICEKEY:
                            flowPriceCode = FLOWREGULARPRICEKEY;
                            break;

                        case MAGENTOFINALPRICEKEY:
                            // Use localized_item_price instead of final_price from Flow, localized_item_price is always what the final price in checkout will be
                            flowPriceCode = FLOWACTUALPRICEKEY;
                            break;
                    }
                }
                return flowPriceCode;
            },

            getCurrentProductId: function (productId) {
                try {
                    if (flow.magento2.simpleProduct != undefined) {
                        productId = flow.magento2.simpleProduct;
                    } else if (!_.contains(flow.magento2.optionsSelected[productId], false) && flow.magento2.optionsIndex[productId] != undefined) {
                        _.each(flow.magento2.optionsIndex[productId], function (optionData) {
                            if (_.difference(optionData.optionIds, flow.magento2.optionsSelected[productId]).length == 0) {
                                productId = optionData.productId;
                            }
                        });
                    }
                } catch (e) {
                    // Options index either malformed or not available, use original product id instead as fallback
                }
                return productId;
            },

            getCurrentProductSku: function (productId, flowLocalizedPrices, flowLocalizationKey) {
                try {
                    return _.toArray(flowLocalizedPrices)[0][productId].sku;
                } catch (e) {
                    return false;
                }
            },

            localizeTemplate: function (template, flowLocalizedPrices, flowLocalizationKey) {
                try {
                    var localizedPricingKeyedOnProductId = flowLocalizedPrices[flowLocalizationKey];
                } catch (e) {
                    // BE localization is not available for this item, default to existing 
                    return template;
                }

                try {
                    this.flowFormattedPrice = _.toArray(localizedPricingKeyedOnProductId[template.data.productId])[0].label;
                } catch (e) {
                    // No price labels are localized for this item, do nothing
                }

                try {
                    this.flowFormattedPrice = localizedPricingKeyedOnProductId[template.data.productId][FLOWACTUALPRICEKEY].label;
                } catch (e) {
                    // Final price label is not localized for this item, do nothing
                }

                try {
                    this.flowFormattedPrice = localizedPricingKeyedOnProductId[template.data.productId][template.data.flowPriceCode].label;
                } catch (e) {
                    // Flow price code either could not be found or its respective label is not localized for this item, do nothing
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

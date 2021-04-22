define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template'
], function ($, utils, _, mageTemplate) {
    'use strict';
    window.flow = window.flow || {};
    window.flow.cmd = window.flow.cmd || function () {
        (window.flow.q = window.flow.q || []).push(arguments);
    };
    window.flow.magento2 = window.flow.magento2 || {};

    return function (widget) {
        var globalOptions = {
                priceTemplate: '<span data-flow-localize="item-price" class="price"><%- data.formatted %></span>',
                flowPriceTemplateByPriceCode: '<span data-flow-localize="item-price-attribute" data-flow-item-price-attribute="<%- data.flowPriceCode %>" class="price"><span style="width:3em; height:0.5em; display:inline-block;"></span></span>',
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
            FLOWACTUALPRICEKEY = 'localized_item_price';

        $.widget('mage.priceBox', widget, {
            options: globalOptions,

            reloadPrice: function reDrawPrices() {
                window.flow.magento2.product_id_sku_map = window.flow.magento2.product_id_sku_map || {};

                if (this.options.prices.flow_product_id_sku_map != undefined) { 
                    Object.assign(
                        window.flow.magento2.product_id_sku_map,
                        this.options.prices.flow_product_id_sku_map
                    );
                }

                if (this.options.priceConfig.flow_product_id_sku_map != undefined) { 
                    Object.assign(
                        window.flow.magento2.product_id_sku_map,
                        this.options.priceConfig.flow_product_id_sku_map
                    );
                }

                var flowLocalizationKey = this.getFlowLocalizationKey();

                if (!flowLocalizationKey || !window.flow.magento2.shouldLocalizeCatalog()) {
                    window.flow.magento2.showPrices();
                    return this._super();
                }

                var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
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

                        var priceTemplate = mageTemplate(this.options.priceTemplate);

                        if (!template.data.flowLocalized && template.data.flowPriceCode) {
                            if (template.data.productSku) {
                                priceTemplate = mageTemplate(this.options.flowPriceTemplateBySkuPriceCode);
                            } else {
                                priceTemplate = mageTemplate(this.options.flowPriceTemplateByPriceCode);
                            }
                        }

                        $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate(template));
                    }
                }, this);
                if (!this.flowFormattedPrice) {
                    window.flow.cmd('localize');
                } else {
                    window.flow.magento2.showPrices();
                }
            },

            getFlowLocalizationKey: function () {
                var flowLocalizationKey = false;
                try {
                    var flowExperience = window.flow.session.getExperience(),
                        flowCountry = window.flow.session.getCountry(),
                        flowCurrency = window.flow.session.getCurrency();

                    if (flowExperience && flowCountry && flowCurrency) {
                        flowLocalizationKey = flowExperience+flowCountry+flowCurrency;
                    }
                } catch (e) {
                    // No viable flow experience was found at this time, acceptable
                }
                return flowLocalizationKey;
            },

            getFlowPriceCode: function (priceCode) {
                switch (priceCode.toString()) {
                    case MAGENTOMINPRICEKEY:
                        return FLOWMINPRICEKEY;
                        break;

                    case MAGENTOBASEPRICEKEY:
                    case MAGENTOOLDPRICEKEY:
                    case MAGENTOREGULARPRICEKEY:
                        return FLOWREGULARPRICEKEY;
                        break;
                    default:
                        return false;
                }
            },

            getCurrentProductId: function (productId) {
                try {
                    if (window.flow.magento2.simpleProduct != undefined) {
                        productId = window.flow.magento2.simpleProduct;
                    } else if (!_.contains(window.flow.magento2.optionsSelected[productId], false) && window.flow.magento2.optionsIndex[productId] != undefined) {
                        _.each(window.flow.magento2.optionsIndex[productId], function (optionData) {
                            if (_.difference(optionData.optionIds, window.flow.magento2.optionsSelected[productId]).length == 0) {
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

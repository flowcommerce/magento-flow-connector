define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/translate',
    'priceUtils',
    'priceBox',
    'jquery/ui',
    'jquery/jquery.parsequery'
], function ($, _, mageTemplate, $t, priceUtils) {
    'use strict';

    return function (widget) {
        $.widget('mage.configurable', widget, {
            _initializeOptions: function () {
                if (window.flow.magento2.shouldLocalizeCatalog) {
                    if (window.flow.magento2.optionsIndex == undefined) {
                        window.flow.magento2.optionsIndex = {};
                    }
                    var productId = this.options.spConfig.productId;
                    var optionIndex = [];
                    _.each(this.options.spConfig.index, function (option, key) {
                        var optionData = {
                            productId: key,
                            optionIds: []
                        };
                        _.each(option, function(value) {
                            optionData.optionIds.push(value);
                        });
                        optionIndex.push(optionData);
                    });
                    window.flow.magento2.optionsIndex[productId] = optionIndex;
                }
                return this._super();
            },

            _configureElement: function (element) {
                if (window.flow.magento2.shouldLocalizeCatalog) {
                    var productId = $widget.options.jsonConfig.productId,
                        selectedOptionId = element.config.id;
                        optionsMap = _.toArray($widget.optionsMap);
                    if (window.flow.magento2.optionsSelected == undefined) {
                        window.flow.magento2.optionsSelected = [];
                    }
                    if (window.flow.magento2.optionsSelected[productId] == undefined) {
                        window.flow.magento2.optionsSelected[productId] = [];
                        _.each(_.toArray($widget.optionsMap), function(option, key) {
                            window.flow.magento2.optionsSelected[productId].push(false);
                        });
                    }
                    _.each(optionsMap, function(options, key) {
                        if (typeof(options[selectedOptionId]) == "object") {
                            if (window.flow.magento2.optionsSelected[productId][key] != selectedOptionId) {
                                window.flow.magento2.optionsSelected[productId][key] = selectedOptionId; 
                            } else {
                                window.flow.magento2.optionsSelected[productId][key] = false;
                            }
                        }
                    });
                }
                return this._super(element);
            }
        });
    }
});

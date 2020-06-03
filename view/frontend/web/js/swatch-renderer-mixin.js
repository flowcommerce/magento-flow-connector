define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/smart-keyboard-handler',
    'mage/translate',
    'priceUtils',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'mage/validation/validation',
    'flowCompanion'
], function ($, _, mageTemplate, keyboardHandler, $t, priceUtils) {
    'use strict';

    return function (widget) {
        $.widget('mage.SwatchRenderer', widget, {
            _RenderControls: function () {
                if (window.flow.magento2.optionsIndex == undefined) {
                    window.flow.magento2.optionsIndex = {};
                }
                var productId = this.options.jsonConfig.productId;
                var optionIndex = [];
                _.each(this.options.jsonConfig.index, function (option, key) {
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
                return this._super();
            },

            _OnClick: function ($this, $widget, eventName) {
                if (window.flow.magento2.shouldLocalizeCatalog) {
                    var productId = $widget.options.jsonConfig.productId,
                        selectedOptionId = $this["context"].attributes["option-id"].value,
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
                return this._super($this, $widget, eventName);
            },

            _OnChange: function ($this, $widget) {
                if (window.flow.magento2.shouldLocalizeCatalog) {
                    var productId = $widget.options.jsonConfig.productId,
                        selectedOptionId = $this["context"].value,
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
                return this._super($this, $widget);
            },

            _Rebuild: function ($this, $widget) {
                if (window.flow.magento2.shouldLocalizeCatalog) {
                    window.flow.magento2.hidePrices();
                }
                return this._super($this, $widget);
            },

            _UpdatePrice: function ($this, $widget) {
                if (window.flow.magento2.shouldLocalizeCatalog) {
                    window.flow.magento2.hidePrices();
                }
                return this._super($this, $widget);
            },

        });

        return $.mage.SwatchRenderer;
    }
});

define([
    'flowJs',
    'jquery',
    'underscore',
    'mage/template',
    'mage/smart-keyboard-handler',
    'mage/translate',
    'priceUtils',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'mage/validation/validation',
    'flowInit'
], function (flow, $, _, mageTemplate, keyboardHandler, $t, priceUtils) {
    'use strict';

    return function (widget) {
        $.widget('mage.SwatchRenderer', widget, {
            _RenderControls: function () {
                flow.magento2.optionsIndex = flow.magento2.optionsIndex || {};
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
                flow.magento2.optionsIndex[productId] = optionIndex;
                return this._super();
            },

            _OnClick: function ($this, $widget, eventName) {
                var productId = $widget.options.jsonConfig.productId,
                    selectedOptionId = $this["context"].attributes["option-id"].value,
                    optionsMap = _.toArray($widget.optionsMap);
                flow.magento2.optionsSelected = flow.magento2.optionsSelected || [];
                if (flow.magento2.optionsSelected[productId] == undefined) {
                    flow.magento2.optionsSelected[productId] = [];
                    _.each(_.toArray($widget.optionsMap), function(option, key) {
                        flow.magento2.optionsSelected[productId].push(false);
                    });
                }
                _.each(optionsMap, function(options, key) {
                    if (typeof(options[selectedOptionId]) == "object") {
                        if (flow.magento2.optionsSelected[productId][key] != selectedOptionId) {
                            flow.magento2.optionsSelected[productId][key] = selectedOptionId; 
                        } else {
                            flow.magento2.optionsSelected[productId][key] = false;
                        }
                    }
                });
                return this._super($this, $widget, eventName);
            },

            _OnChange: function ($this, $widget) {
                var productId = $widget.options.jsonConfig.productId,
                    selectedOptionId = $this["context"].value,
                    optionsMap = _.toArray($widget.optionsMap);
                flow.magento2.optionsSelected = flow.magento2.optionsSelected || [];
                if (flow.magento2.optionsSelected[productId] == undefined) {
                    flow.magento2.optionsSelected[productId] = [];
                    _.each(_.toArray($widget.optionsMap), function(option, key) {
                        flow.magento2.optionsSelected[productId].push(false);
                    });
                }
                _.each(optionsMap, function(options, key) {
                    if (typeof(options[selectedOptionId]) == "object") {
                        if (flow.magento2.optionsSelected[productId][key] != selectedOptionId) {
                            flow.magento2.optionsSelected[productId][key] = selectedOptionId; 
                        } else {
                            flow.magento2.optionsSelected[productId][key] = false;
                        }
                    }
                });
                return this._super($this, $widget);
            },

            _Rebuild: function ($this, $widget) {
                flow.cmd('on', 'ready', function() {
                    if (flow.magento2.shouldLocalizeCatalog()) {
                        flow.magento2.hidePrices();
                    }
                });
                return this._super($this, $widget);
            },

            _UpdatePrice: function ($this, $widget) {
                flow.cmd('on', 'ready', function() {
                    if (flow.magento2.shouldLocalizeCatalog()) {
                        flow.magento2.hidePrices();
                    }
                });
                return this._super($this, $widget);
            },

        });

        return $.mage.SwatchRenderer;
    }
});

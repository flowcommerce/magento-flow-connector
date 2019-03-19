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
    'flow'
], function ($, _, mageTemplate, keyboardHandler, $t, priceUtils) {
    'use strict';
    var flow = window.flow || {};

    return function (widget) {
        $.widget('mage.SwatchRenderer', widget, {
            _RenderControls: function () {
                if (this.shouldLocalize()) {
                    if (flow.magento2.optionsIndex == undefined) {
                        flow.magento2.optionsIndex = {};
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
                    flow.magento2.optionsIndex[productId] = optionIndex;
                }
                return this._super();
            },

            _OnClick: function ($this, $widget, eventName) {
                if (this.shouldLocalize()) {
                    var productId = $widget.options.jsonConfig.productId,
                        selectedOptionId = $this["context"].attributes["option-id"].value,
                        optionsMap = _.toArray($widget.optionsMap);
                    if (flow.magento2.optionsSelected == undefined) {
                        flow.magento2.optionsSelected = [];
                    }
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
                }
                return this._super($this, $widget, eventName);
            },

            shouldLocalize: function () {
                var result = false;
                if (flow.session != undefined && flow.magento2.catalog_localize) {
                    if (typeof(flow.session.getExperience()) == "string") {
                        result = true;
                    }
                }
                return result;
            },
        });

        return $.mage.SwatchRenderer;
    }
});

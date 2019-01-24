define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/smart-keyboard-handler',
    'mage/translate',
    'priceUtils',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'mage/validation/validation'
], function ($, _, mageTemplate, keyboardHandler, $t, priceUtils) {
    'use strict';

    return function (widget) {
        var flow = window.flow || {};

        $.widget('mage.SwatchRenderer', widget, {
            _RenderControls: function () {
                if (typeof(flow.session.getExperience()) == "string") {
                    flow.magento2 = window.flow.magento2 || {};
                    if (flow.magento2.optionsIndex == undefined) {
                        flow.magento2.optionsIndex = {};
                    }
                    var productId = this.options.jsonConfig.productId;
                    var newIndex = [];
                    _.each(this.options.jsonConfig.index, function (option, key) {
                        newIndex[key] = [];
                        _.each(option, function(value) {
                            newIndex[key].push(value);
                        });
                    });
                    flow.magento2.optionsIndex[productId] = newIndex;
                }
                return this._super();
            },

            _OnClick: function ($this, $widget, eventName) {
                if (typeof(flow.session.getExperience()) == "string") {
                    flow.magento2 = window.flow.magento2 || {};
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
        });

        return $.mage.SwatchRenderer;
    }
});

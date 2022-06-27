define([
    'jquery',
    'underscore',
    'flowInit'
], function ($, _) {
    'use strict';
    window.flow = window.flow || {};
    window.flow.cmd = window.flow.cmd || function () {
        (window.flow.q = window.flow.q || []).push(arguments);
    };
    window.flow.magento2 = window.flow.magento2 || {};

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', $.mage.SwatchRenderer, {
            _RenderControls: function () {
                window.flow.magento2.optionsIndex = window.flow.magento2.optionsIndex || {};
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

            _OnClick: function ($this, $widget) {
                var productId = $widget.options.jsonConfig.productId,
                    $parent = $this.parents('.' + $widget.options.classes.attributeClass),
                    selectedOptionId = $this.val() || $parent.attr('data-option-selected'),
                    optionsMap = _.toArray($widget.optionsMap);
                window.flow.magento2.optionsSelected = window.flow.magento2.optionsSelected || [];
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
                return this._super($this, $widget);
            },

            _OnChange: function ($this, $widget) {
                var productId = $widget.options.jsonConfig.productId,
                    selectedOptionId = $this.val() || $this.context.attributes["data-option-id"].value,
                    optionsMap = _.toArray($widget.optionsMap);
                window.flow.magento2.optionsSelected = window.flow.magento2.optionsSelected || [];
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
                return this._super($this, $widget);
            },

            _Rebuild: function () {
                window.flow.cmd('on', 'ready', function() {
                    if (window.flow.magento2.shouldLocalizeCatalog()) {
                        window.flow.magento2.hidePrices();
                    }
                });
                return this._super();
            },

            _UpdatePrice: function () {
                window.flow.cmd('on', 'ready', function() {
                    if (window.flow.magento2.shouldLocalizeCatalog()) {
                        window.flow.magento2.hidePrices();
                    }
                });
                return this._super();
            },

        });

        return $.mage.SwatchRenderer;
    }
});

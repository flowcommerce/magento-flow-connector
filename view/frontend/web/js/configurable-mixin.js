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
            }
        });
    }
});

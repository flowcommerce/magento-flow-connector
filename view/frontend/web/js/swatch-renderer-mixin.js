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
        $.widget('mage.SwatchRenderer', widget, {
            _RenderControls: function () {
                if (typeof(flow.session.getExperience()) == "string") {
                    if (flow.optionsIndex == undefined) {
                        flow.optionsIndex = {};
                    }
                    var productId = this.options.jsonConfig.productId;
                    var newIndex = [];
                    _.each(this.options.jsonConfig.index, function (option, key) {
                        newIndex[key] = [];
                        _.each(option, function(value) {
                            newIndex[key].push(value);
                        });
                    });
                    flow.optionsIndex[productId] = newIndex;
                }
                return this._super();
            }
        });

        return $.mage.SwatchRenderer;
    }
});

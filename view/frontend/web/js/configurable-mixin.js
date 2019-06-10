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
            _configureElement: function (element) {
                if (window.flow.magento2.shouldLocalizeCatalog) {
                    window.flow.magento2.simpleProduct = this._getSimpleProductId(element);
                }
                return this._super(element);
            }
        });
    }
});

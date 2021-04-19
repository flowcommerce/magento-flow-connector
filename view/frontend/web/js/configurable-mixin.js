define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/translate',
    'priceUtils',
    'priceBox',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'flowInit'
], function ($, _, mageTemplate, $t, priceUtils) {
    'use strict';
    window.flow = window.flow || {};

    return function (widget) {
        $.widget('mage.configurable', widget, {
            _configureElement: function (element) {
                var self = this;
                flow.cmd('on', 'ready', function () {
                    if (flow.magento2.shouldLocalizeCatalog()) {
                        flow.magento2.simpleProduct = self._getSimpleProductId(element);
                    }
                });
                return this._super(element);
            }
        });
    }
});

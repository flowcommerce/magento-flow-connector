define([
    'jquery'
], function ($) {
    'use strict';
    window.flow = window.flow || {};
    window.flow.cmd = window.flow.cmd || function () {
        (window.flow.q = window.flow.q || []).push(arguments);
    };
    window.flow.magento2 = window.flow.magento2 || {};

    return function (widget) {
        $.widget('mage.configurable', widget, {
            _configureElement: function (element) {
                var self = this;
                window.flow.cmd('on', 'ready', function () {
                    if (window.flow.magento2.shouldLocalizeCatalog()) {
                        window.flow.magento2.simpleProduct = self._getSimpleProductId(element);
                    }
                });
                return this._super(element);
            }
        });
    }
});

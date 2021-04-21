define([
    'flowInit',
    'jquery'
], function (flow, $) {
    'use strict';
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

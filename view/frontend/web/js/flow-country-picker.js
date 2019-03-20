require([
    'jquery',
    'flow',
    'flowCountryPicker',
    'mage/cookies'
], function ($) {
    var flow = window.flow || {};

    flow.magento2.countryPickerOptions = flow.magento2.countryPickerOptions || {};
    flow.magento2.countryPickerOptions.containerId = 'flow-country-picker';
    flow.magento2.countryPickerOptions.type = 'dropdown';
    flow.magento2.countryPickerOptions.logo = true;
    flow.magento2.countryPickerOptions.isDestination = true;
    flow.magento2.countryPickerOptions.onSessionUpdate = function (status, session) {
        $.cookie('flow_mage_session_update', 1,  {domain: null});
        window.location.reload();
    };

    flow.countryPicker.createCountryPicker(flow.magento2.countryPickerOptions);
});

require([
    'jquery',
    'flowCountryPicker',
    'mage/cookies'
], function ($) {
    var flow = window.flow || {};
        flow.magento2 = window.flow.magento2 || {};
        flow.magento2.countryPickerOptions = window.flow.magento2.countryPickerOptions || {};
        flow.magento2.countryPickerOptions.containerId = 'flow-country-picker';
        flow.magento2.countryPickerOptions.type = 'dropdown';
        flow.magento2.countryPickerOptions.logo = true;
        flow.magento2.countryPickerOptions.isDestination = true;
        flow.magento2.countryPickerOptions.onSessionUpdate = function (status, session) {
            $.cookie('flow_mage_session_update', 1,  {domain: null});
            window.location.reload();
        };

    window.flow.countryPicker.createCountryPicker(flow.magento2.countryPickerOptions);
});

require([
    'jquery',
    'flowCountryPicker',
    'mage/cookies'
], function ($) {
    window.flow.countryPicker.createCountryPicker({
        type: 'dropdown',
        logo: true,
        containerId: 'flow-country-picker',
        onSessionUpdate: function (status, session) {
            $.cookie('flow_mage_session_update', 1,  {domain: null});
            window.location.reload();
        },
        isDestination: true
    });
});

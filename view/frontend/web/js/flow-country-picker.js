require([
    'jquery',
    'flowCountryPicker',
    'mage/cookies'
], function ($) {
    var containerId = 'flow-country-picker';

    if ($('#'.containerId).length > 0) {
        window.flow.countryPicker.createCountryPicker({
            type: 'dropdown',
            logo: true,
            containerId: containerId,
            onSessionUpdate: function (status, session) {
                $.cookie('flow_mage_session_update', 1,  {domain: null});
                window.location.reload();
            },
            isDestination: true
        });
    }
});

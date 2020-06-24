define([], function () {
    window['flow'] = window['flow'] || {};
    window['flow'].cmd = window['flow'].cmd || function () {
        (window['flow'].q = window['flow'].q || []).push(arguments);
    };

    flow.cmd('set', 'organization', flow_organization_id);
    flow.cmd('set', 'optinContainerSelector', '#flow-optin');
    flow.cmd('init');
});

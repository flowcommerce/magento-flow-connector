define(['flow_organization_id'], function (flowOrganizationId) {
    window.flow = window.flow || {};
    window.flow.cmd = window.flow.cmd || function () {
        (window.flow.q = window.flow.q || []).push(arguments);
    };

    window.flow.cmd('set', 'organization', flowOrganizationId);
    window.flow.cmd('set', 'optinContainerSelector', '#flow-optin');
    window.flow.cmd('init');
});

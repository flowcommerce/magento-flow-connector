define([
], function () {
    if (window.flow_organization_id && window.flow_flowjs_url) {
        !function (f, l, o, w, i, n, g) {
            f[i] = f[i] || {};
            f[i].cmd = f[i].cmd || function () {
                (f[i].q = f[i].q || []).push(arguments);
            };
            n = l.createElement(o);
            n.src = w;
            g = l.getElementsByTagName(o)[0];
            g.parentNode.insertBefore(n, g);
        }(window, document, 'script', window.flow_flowjs_url, 'flow');

        flow.cmd('set', 'organization', window.flow_organization_id);
        flow.cmd('init');
        flow.cmd('localize');
        flow.session = flow.session || {};
        flow.cart = flow.cart || {};
        flow.magento2 = flow.magento2 || {};
        flow.magento2.cart_localize = window.flow_cart_localize;
        flow.magento2.catalog_localize = window.flow_catalog_localize;
    }
});

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
            n.async = false;
            g = l.getElementsByTagName(o)[0];
            g.parentNode.insertBefore(n, g);
        }(window, document, 'script', window.flow_flowjs_url, 'flow');
        flow.cmd('set', 'organization', window.flow_organization_id);
        flow.cmd('init');
        flow.cmd('localize');

        flow.session = flow.session || {};
        flow.cart = flow.cart || {};
        flow.magento2 = flow.magento2 || {};
        flow.magento2.enabled = window.flow_enabled;
        flow.magento2.cart_localize = window.flow_cart_localize;
        flow.magento2.catalog_localize = window.flow_catalog_localize;
        flow.magento2.pricing_timeout = window.flow_pricing_timeout;
        flow.magento2.hasExperience = false;
        flow.magento2.hasLocalizedCatalog = false;
        flow.magento2.hasLocalizedCart = false;
        flow.magento2.hasLocalizedCartTotals = false;
        flow.magento2.shouldLocalizeCatalog = false;
        flow.magento2.shouldLocalizeCart = false;
        flow.magento2.installedFlowTotalsFields = false;
        flow.magento2.showPrices = function () {
            if (!flow.magento2.hasLocalizedCatalog) {
                flow.magento2.hasLocalizedCatalog = true;
                document.getElementsByTagName('body')[0].classList.add('flow-catalog-localized');
            }
        }

        flow.magento2.hidePrices = function () {
            if (flow.magento2.hasLocalizedCatalog) {
                flow.magento2.hasLocalizedCatalog = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-catalog-localized');
            }
        }

        flow.magento2.showCart = function () {
            if (!flow.magento2.hasLocalizedCart) {
                flow.magento2.hasLocalizedCart = true;
                document.getElementsByTagName('body')[0].classList.add('flow-cart-localized');
            }
        }

        flow.magento2.hideCart = function () {
            if (flow.magento2.hasLocalizedCart) {
                flow.magento2.hasLocalizedCart = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-cart-localized');
            }
        }

        flow.magento2.showCartTotals = function () {
            if (!flow.magento2.hasLocalizedCartTotals) {
                flow.magento2.hasLocalizedCartTotals = true;
                document.getElementsByTagName('body')[0].classList.add('flow-cart-totals-localized');
            }
        }

        flow.magento2.hideCartTotals = function () {
            if (flow.magento2.hasLocalizedCartTotals) {
                flow.magento2.hasLocalizedCartTotals = false;
                document.getElementsByTagName('body')[0].classList.remove('flow-cart-totals-localized');
            }
        }

        flow.cmd('on', 'ready', function() {
            flow.magento2.hasExperience = typeof(flow.session.getExperience()) == "string";
            flow.magento2.shouldLocalizeCatalog = flow.magento2.hasExperience && flow.magento2.catalog_localize;
            flow.magento2.shouldLocalizeCart = flow.magento2.hasExperience && flow.magento2.cart_localize;

            flow.events.on('catalogLocalized', function() {
                flow.magento2.showPrices();
                console.log('Showing prices due to localization successful');
            });

            flow.events.on('cartLocalized', function() {
                flow.magento2.showCart();
                if (flow.magento2.installedFlowTotalsFields) {
                    flow.magento2.showCartTotals();
                }
                console.log('Showing cart due to localization successful');
            });

            if (!flow.magento2.hasExperience) {
                flow.magento2.showPrices();
                console.log('Showing prices due to no Flow experience found');
                flow.magento2.showCart();
                console.log('Showing cart due to no Flow experience found');
                flow.magento2.showCartTotals();
                console.log('Showing cart totals due to no Flow experience found');
            }
        });
    }
});

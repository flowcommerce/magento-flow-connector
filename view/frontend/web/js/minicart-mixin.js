define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery',
    'ko',
    'underscore',
    'sidebar',
    'mage/translate',
    'mage/dropdown'
], function (Component, customerData, $, ko, _) {
    'use strict';

    return Component.extend({

        /**
         * @override
         */
        initialize: function () {
            var self = this,
                cartData = customerData.get('cart');
            
            if (flow != undefined) {
                if (flow.session != undefined) {
                    if (flow.session.getExperience() != undefined) {
                        customerData.invalidate(['cart']);
                        customerData.reload(['cart'], true); 
                    }
                }
            }

            this.update(cartData());
            cartData.subscribe(function (updatedCart) {
                addToCartCalls--;
                this.isLoading(addToCartCalls > 0);
                sidebarInitialized = false;
                this.update(updatedCart);
                initSidebar();
            }, this);
            $('[data-block="minicart"]').on('contentLoading', function () {
                addToCartCalls++;
                self.isLoading(true);
            });

            if (cartData()['website_id'] !== window.checkout.websiteId) {
                customerData.reload(['cart'], false);
            }

            return this._super();
        },

        /**
         * Update mini shopping cart content.
         *
         * @param {Object} updatedCart
         * @returns void
         */
        update: function (updatedCart) {
            _.each(updatedCart, function (value, key) {
                if (!this.cart.hasOwnProperty(key)) {
                    this.cart[key] = ko.observable();
                }
                if ( updatedCart['subtotalFlow'] != undefined && flow.session.getExperience() != undefined) {
                    switch (key) {
                        case 'subtotal':
                        case 'subtotal_excl_tax':
                        case 'subtotal_incl_tax':
                            value = updatedCart['subtotalFlow'];
                            updatedCart[key] = value;
                            break;

                        case 'items':
                            _.each(value, function (item, index) {
                                if (typeof(item.product_price_flow) == 'string') {
                                    updatedCart[key][index].product_price = item.product_price_flow;
                                }
                            });
                            break;
                    }
                }
                this.cart[key](value);
            }, this);
        },
    });
});

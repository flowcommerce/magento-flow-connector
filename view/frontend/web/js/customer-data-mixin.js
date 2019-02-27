define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (customerData) {
        var flow = window.flow || {};
        flow.magento2 = window.flow.magento2 || {};
        flow.cart = window.flow.cart || {};
        flow.session = window.flow.session || {};

        function reloadFlowCart() {
            if (typeof(flow.session.getExperience()) == 'string') {
                var totals, subtotal, grandtotal, discount, flowFields;
                totals = $('#cart-totals');
                subtotal = totals.find('[data-th=\'Subtotal\']').first();
                grandtotal = totals.find('[data-th=\'Order Total\'] span.price').first();
                discount = totals.find('[data-th=\'Discount\'] span.price').first();

                subtotal.attr('data-flow-localize','cart-subtotal'); 
                grandtotal.attr('data-flow-localize','cart-total'); 
                discount.attr('data-flow-localize','cart-discount'); 
                if (totals.find('[data-th="Flow Tax"]').length <= 0) {
                    flowFields = $(`<tr class="totals vat">
                            <th data-bind="i18n: title" class="mark" scope="row" data-flow-localize="cart-tax-name">Tax</th>
                            <td class="amount">
                                <span class="price" data-bind="text: getValue(), attr: {'data-th': title}" data-th="Flow Tax" data-flow-localize="cart-tax"></span>
                            </td>
                        </tr>
                        <tr class="totals duty">
                            <th data-bind="i18n: title" class="mark" scope="row">Duty</th>
                            <td class="amount">
                                <span class="price" data-bind="text: getValue(), attr: {'data-th': title}" data-th="Flow Duty" data-flow-localize="cart-duty">CA$18.48</span>
                            </td>
                        </tr>
                        <tr class="totals shipping">
                            <th data-bind="i18n: title" class="mark" scope="row">Estimated Shipping</th>
                            <td class="amount">
                                <span class="price" data-bind="text: getValue(), attr: {'data-th': title}" data-th="Flow Shipping" data-flow-localize="cart-shipping"></span>
                            </td>
                        </tr>
                    `);
                    totals.find('.totals.sub').after(flowFields);
                    console.log('installed flow fields');
                }

                flow.cart.localize();
                console.log('totals localized');
            }
        }

        customerData.set = wrapper.wrap(customerData.set, function (_super, sectionName, sectionData) {
            console.log('setting totals');
            var result = _super(sectionName, sectionData);
            reloadFlowCart();
            return result;
        });

        customerData.reload = wrapper.wrap(customerData.reload, function (_super, sectionNames, updateSectionId) {
            console.log('reload totals');
            var result = _super(sectionNames, updateSectionId);
            reloadFlowCart();
            return result;
        });

        return customerData;
    };
});

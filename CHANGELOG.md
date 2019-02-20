# Flow Connector for Magento 2 Change Log

## 1.1.9
- Persist webhook ids if urls match
- No longer removes old webhooks automatically
- Webhooks default to use secure base urls

## 1.1.8
- Fix inventory sync error marking

## 1.1.7
- Replace M2 core es6-collections with core-js for full FlowJS compatability

## 1.1.6
- Fix issue where a customer's default address overrided their selected experience
- Improved error handling for catalog inventory syncing

## 1.1.5
- Refactor functionality then deprecate Util class.
- Add webhook verification feature (on by default).
- Add feature to redirect regular Magento checkout to Flow checkout (off by default).
- Add support for triggering core Magento test suite when merging to master using CLI in addition to Github UI.

## 1.1.4

- Fix Travis when building against Git tags.
- Switch to using order placed event for creating Magento order instead of order and allocation upserted.
- Bring back support for deprecated Helper class, and flag methods of this class as deprecated.
- Completely disable Magento quote validation for Flow orders.
- Implement fallback to shipping address if billing address is blank for Flow orders.
- Up Magento 2 version to 2.2.7 for Travis.

## 1.1.3

- Fix issue where multiple order confirmation emails can be dispatched under specific circumstances.
- Improve discount requests error handling.
- Improve integration tests for catalog sync functionality.
- Fix the most significant core Magento integration test suite failures appearing when Flow Connector is installed.
- Add integration tests for webhook processing feature.
- Add integration tests for catalog inventory sync feature.
- Split register webhooks command into 3 separate commands and add automated integration tests for all 3.
- Fix Travis composer require step when building against Git tags.

## 1.1.2

- Fix guest discount calculation in Checkout UI
- Fix out of stock exception during quote generation on order creation
- Fix store view scope for order creation
- Remove exception for empty configuration of disabled connector on store view

## 1.1.1

- Add configuration option to disable and enable sending invoice email after invoicing Flow order in Magento automatically.
- Add configuration option to disable and enable sending shipment email after shipping Flow order in Magento automatically.
- Add create shipment configuration option for disabling creation of shipments for Flow orders in Magento automatically.
- Fix regression where configuration options had to be re-entered after upgrading to 1.1.0.

## 1.1.0

- Magento invoices and shipments can now be created automatically.
- Magento now handles storing discounts better for all sales entities.

## 1.0.40

- Fix loading customers by email on orders
- Flow custom fields fix for fresh installations
- API extension for discount code requests from Checkout UI, only applies to codes which discount the order subtotal directly

## 1.0.39

- VAT, Duty, and Rounding Adjustments are now collected into item pricing, tax, and shipping totals as applicable to ensure totals calculation accuracy
- VAT, Duty, Rounding Adjustments, and Raw Item Prices are saved on sales order entities for record keeping but not used in total calculations directly
- Orders can now generate invoice entities with accurate total calculations
- Fix upgrade script for installations

## 1.0.38

- Fix issue where message fields in Flow Connector's database tables get trimmed to 200 characters.
- Fix inability to set region ID to billing and shipping addresses for configurations and experiences where region is not optional.
- Fix Uncaught Error: Wrong parameters for FlowCommerce\FlowConnector\Exception\WebhookException.

## 1.0.37

- Fix Undefined index: customer with PayPal orders issue.

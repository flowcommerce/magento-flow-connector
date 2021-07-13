# Flow Connector for Magento 2 Change Log

## 2.6.4
- Magento Marketplace specific changes

## 2.6.0
- Refactor FlowJS implementation, optimized to remove possible race conditions
- Upgrade test suite to comply with Magento 2.4.X
- Update Travis CI configuration to comply with Magento 2.4.X
- Add CSP allowlists for required domains

## 2.5.6
- Speed up webhook processing to every 2 minutes
- Address deprecated quote clear method

## 2.5.5
- Store view configuration errors addressed
- Accept order imports with line items priced at 0

## 2.5.4
- Address strikethrough display errors

## 2.5.3
- Address price localization configuration bug

## 2.5.2
- Add cron for order sync polling, every half hour
- Add console command for order sync polling, flow:connector:order-sync
- Add Order Sync dashboard view to retry or fail order imports with errors
- Remove deprecated webhook authorization_deleted_v2
- Remove deprecated webhook processing methods

## 2.5.1
- Cron task optimizations

## 2.5.0
- Add Sync Stream registration to intialization command
- Add Sync Stream Record registration for order import events
- Remove deprecated Helper/Data class
- Remove deprecated Model/Util class
- Remove deprecated Model/GuzzleHttp/Client class

## 2.4.2
- Add tracking numbers and URLs directly to orders on label webhook processing
- Clear carts via Continue Shopping redirect
- Address bug with regular pricing override

## 2.4.1
- Address race condition on FlowJS initialization
- Update pricing allocation on order import
- Add new Vat rate and Duty rate order and item attributes

## 2.4.0
- Cart localization bug addressed
- FlowJS events bug addressed
- FlowJS include optimization
- Removing optional FlowJS version configuration

## 2.3.7
- Address bug in beacon add to cart event for simple products

## 2.3.6
- Address bug in cart localization when viewing domestic cart

## 2.3.5
- Flow Beacon Add to Cart event recorded
- Address bug in checkout redirect with excluded items
- Address bug in cart localization when third parties update summary fields
- Address bug in catalog syncing when triggered by batch product imports

## 2.3.4
- Allow disabling of cache preloaded localized pricing
- Allow disabling of Magento discount support in Flow Checkout
- Toggle configuration of item price, regular or final
- Address bug in daily sync configuration
- Address bug in cart localization
- Use minified Flow country-picker.js

## 2.3.3
- Import latest label_id, commerical_invoice, pdf, zpl, and center to orders on label_upserted event

## 2.3.2
- Round pricing to 4 decimal places before syncing
- Update order_placed done requirements and logging
- Gracefully degrade to FlowJS localization when cache-miss cannot localize

## 2.3.1
- Shipping window configuration corrected
- Returning user address book now pulls contact info

## 2.3.0
- Localized default shipping window estimates on PDP, display toggle
- Localized payment method logos on PDP, display toggle
- Localized tax and duty breakouts under priceboxes, display toggle
- Returning user data in checkout
- Daily catalog sync toggle
- Added catalog sync attributes to identify item source in Flow
- Import and display customer's selected shipping method estimated delivery window in orders
- Checkout token redirect optimization
- Automatically cancel orders with Flow fraud status of "declined"
- Updated README links to the new Flow documentation website

## 2.2.5
- Updated logging for webhooks event messaging
- Support forced pricing display overrides (pricebook and item overlay)

## 2.2.4
- Fix for ext_order_id 32 char limit
- Better logging for webhooks event messaging

## 2.2.3
- Adding support for buy requests on order items which are required to properly track product custom options
- Update tests to reflect expanded support and modified model of expected options object

## 2.2.1
- Payment capture webhook events will be marked for reprocessing when the order is not yet eligable to be invoiced

## 2.2.0
- Update documentation for completeness and ease of use
- Removing Kubernetes deployment configuration from repo

## 2.1.4
- Support for vanity urls in Flow Checkout UI

## 2.1.2
- Fixing typo that helps optimize sku syncing
- Magento Marketplace linter suggestions

## 2.1.1
- Magento Marketplace linter suggestions

## 2.1.0
- Removing daily batch catalog syncing for sandbox environments
- Exposing methods to determine production status (boolean)
- Patching compatibility for IE11, removing dependencies on ES6
- Dropping support for Magento versions below 2.3 due to breaking changes and in anticipation of Magento deprecating version 2.2 as of December 2019
- Updating testing suite to include Magento version 2.3.2

## 2.0.4
- Removing recurring deployment initialization, preventing overload of webhook registrations for development environments

## 2.0.3
- Webhook events can be requeued even after they reach max retries or time limit
- Addresses new errors introduced in M2.3+, ensures up to M2.3.2 compatibility
- Payment webhook events use Flow Authorization ID and/or Flow Order ID as unique indentifier
- Updates demo store to use latest Magento version (M2.3.2)

## 2.0.2
- Support breaking change between M2.2 and M2.3 for custom controller POST calls
- Support configurable product variant selection for dropdown and swatch/dropdown cases
- Opt-in support with default css
- Renamed default css file to reflect entire extension, not only country picker

## 2.0.1
- Consolidate all checkout redirects to use the same method
- Checkout redirects now use secure checkout tokens and maintain session continuity
- Trivial magento marketplace validation adjustments
- Removing outdated/obsolete TODO comments

## 2.0.0
- FlowJS and Flow's Country Picker JS now included by default
- FlowJS version option in back office
- Optional country picker installation
- Optional catalog price localization installation
- Optional cart localization installation
- Optional price flicker removal
- Optional cart flicker removal
- Discounts handled and imported per order line item according to Magento 2's pricing rules
- Catalog Sync status back office view with manual requeue option
- Webhook Event status back office view with manual requeue option

## 1.1.10
- Upgrade core-js to version 2.6.5 for JS bundling compatibility

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

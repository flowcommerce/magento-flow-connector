# Flow Connector for Magento 2 Change Log

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

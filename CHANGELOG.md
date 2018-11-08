# Flow Connector for Magento 2 Change Log

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

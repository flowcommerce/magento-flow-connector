# Flow Connector Magento Module

This module provides the core components for integrating with Flow.

Flow is a comprehensive global e-commerce solution. For more information about Flow's capabilities please reference [Flow Integration Overview](https://docs.flow.io/integration-overview).

## Getting Started

### Installation

1. In the `require` section of your `composer.json`, require the `flowcommerce/flowconnector` module.

```
"require": {
    "flowcommerce/flowconnector": "^1.1.6"
}
```

2. Run following commands from your Magento 2 app root:

```
composer update
compose install
./bin/magento setup:upgrade
```

3. Follow the Simple Integration guide for installing Flow.js, [Flow.js Installation Guide](https://docs.flow.io/guides/flowjs/introduction)

### Initial Setup

Login to your Flow Console:
- Create your Organization's first viable Experience.
- If you do not have an Organization yet or need more information about your Flow Console, Organizations, and Experiences, please contact your Customer Success Manager.
- An Experience is viable for checkout when it is "Active" and has Shipping Tiers available. [Flow Logistics Setup](https://docs.flow.io/integration-overview#logistics-setup)
- From the left menu, select Organization Settings -> API Keys.
- This is where you can find and generate Flow API Keys for use in the Magento 2 Back Office setup
- You will also need your Flow Organization ID for Magento 2 Back Office setup. It can be found in your current browser address bar 'https://console.flow.io/YOUR-ORGANIZATION-ID-HERE/organization/api-keys'.

Login to your Magento 2 Back Office:
- From the left menu, click on Stores -> Configuration.
- Change the Scope to your store (or default store if you only have one).
- Select Flow -> Connector Settings, enable the connector and fill out your Flow Organization ID and Flow API Key.
- After saving the configuration, the module will connect to Flow and register webhooks. You can view webhooks from your Flow Console -> Organization Settings.

### Console Commands

Console commands provided by this module:

```
flow:flow-connector:catalog-sync-attributes-save  Saves product attributes needed for the integration in flow.io
flow:flow-connector:catalog-sync-process          Process sync skus queue and send to Flow.
flow:flow-connector:catalog-sync-queue-all        Queue all products for sync to Flow catalog.
flow:flow-connector:cron-cleanup                  Clean up Flow cron tasks.
flow:flow-connector:integration-initialize        Initializes integration with flow.io. This is a wrapper for webhooks registration, attributes creation, inventory center key fetching and creating secret for webhook payload verification.
flow:flow-connector:inventory-center-fetch-keys   Fetches inventory center keys for all store views where flowconnector is configured.
flow:flow-connector:inventory-sync-process        Process inventory sync queue and send to Flow. Warnings may be logged for items which are configured to not track inventory.
flow:flow-connector:inventory-sync-queue-all      Queue all products for sync to Flow inventory.
flow:flow-connector:webhook-event-process         Process Flow webhook events.
flow:flow-connector:webhook-register-webhooks     Register webhooks with Flow.
flow:flow-connector:webhook-update-settings       Create secret for webhook payload verification.

```

### Update version

To update the plugin to the latest version, run:

```
composer update
compose install
./bin/magento setup:upgrade
```

## Overview 

### Product Catalog

Documentation: [Flow Product Catalog](https://docs.flow.io/integration-overview#product-catalog)

This module will sync product information to Flow in two ways:

1. Product updates (and deletes) are queued with an observer. This queue is processed with a cron task every minute, with additional workers spawned every minute the queue is not empty.
2. There is a cron task that will sync the entire product catalog to Flow. By default, this will sync twice a day.

### Checkout UI (CUI)

Documentation: [Flow Checkout UI](https://docs.flow.io/checkout/checkout)

Once your Magento 2 Catalog is fully synced with Flow Product Catalog, customers can be sent to Flow Hosted Checkout. There is a redirect URL included in this module that will send the user's cart and information to Flow Hosted Checkout.

```
/flowconnector/checkout/redirecttoflow?country=FRA
```

To localize pricing data, please refer to [Flow.js Product Localization](https://docs.flow.io/shopify/flow-js).

### Discount Codes

Discount codes have limited functionality as of version 1.0.40. The conditions under which a discount code can be applied to a CUI order are as follows:
- Does not apply to the shipping amount
- Is not a “Buy X Get Y” discount
- Does not apply a discount to a specific item in the cart through the use of Magento 2 Sales Rule “Action” conditions
- Applies to all cart items evenly

To enable this feature for your Organization please contact your Customer Success Manager.

IMPORTANT: Due to how Magento 2 calculates Cart Sales Rules it is possible that other Magento 2 Cart Sales Rules may apply to an order when applying a valid discount code. If you need to isolate a Magento 2 Sales Rule, raise the priority of your discount code and enable the option "Discard subsequent rules". This will calculate that discount first and disregard the following Magento 2 Sales Rules.

### Webhook Processing

Documentation: [Flow Webhook](https://docs.flow.io/module/webhook)

Upon configuring this module with your Flow credentials, the module will configure a set of webhooks to receive event data from Flow. These webhook events are queued and processed with a cron task. For example, after a customer submits an order through Flow Checkout UI, a series of webhook events will be sent to Magento 2 with detailed order and payment information.

## Extending Functionality

This module will dispatch several events that observers can listen to:

- An event is dispatched when a webhook event is received from Flow.
  - List of event types can be found in the Model/WebhookManger/EndpointsConfiguration.php class.
  - The event name will be `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_PREFIX` + the event type.
- An event is dispatched after a webhook event is processed.
  - List of event types can be found in the Model/WebhookManger/EndpointsConfiguration.php class.
  - The event name will be `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_PREFIX` + the event type + `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_SUFFIX_AFTER`.
- An event is dispatched after a product has been synced to Flow.
  - The event name is defined in `Flow\FlowConnector\Model\Sync\CatalogSync::EVENT_FLOW_PRODUCT_SYNC_AFTER`.
- An event is dispatched if the customer changed their email address during Flow Hosted Checkout.
  - The event name is defined in `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_CHECKOUT_EMAIL_CHANGED`.

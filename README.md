# Flow Connector Magento Module

## Getting Started

### Installation

1. In the `require` section of Magento 2's `composer.json`, require the `flowcommerce/flowconnector` module.
```json
"require": {
    "flowcommerce/flowconnector": "^2.0.3"
}
```
2. Run following commands from your Magento 2 app root:
```plaintext
composer update
compose install
php bin/magento setup:upgrade
```

### Initial Setup

Login to your Flow Console:
1. Create your Organization's first viable Experience.
    - If you do not have an Organization yet or need more information about your Flow Console, Organizations, and Experiences, please contact your Customer Success Manager.
    - An Experience is viable for checkout when it is "Active" and has Shipping Tiers available. [Flow Logistics Setup](https://docs.flow.io/integration-overview#logistics-setup)
2. Generate a Flow API Key for use in the Magento 2 Back Office setup
    - From the left hand menu, select Organization Settings -> API Keys -> New API Key.
    - Input a meaningful name for this key that clearly represents where it is intended to be used, for example "magento-sandbox-key", and click Create API Key
3. Note your Flow Organization ID for Magento 2 Back Office setup
    - This can be found in your current browser address bar 'https://console.flow.io/YOUR-ORGANIZATION-ID-HERE/organization/api-keys'.

Login to your Magento 2 Back Office:
1. From the left hand menu, click on Stores -> Configuration.
2. Change the Scope to your store (or default store if you only have one).
3. Select Flow Commerce -> Connector Settings, enable the connector and fill out your Flow Organization ID and Flow API Key.
    - After saving the configuration, the module will be able to connect to Flow's API

Initialize Installation Settings
1. Run the following command on your web server from your Magento 2 app root directory. This will accomplish:
    - Webhook registration
    - Fetching inventory center keys
    - Generating price attributes in Flow
```plaintext
php bin/magento flow:flow-connector:integration-initialize
```

Syncronize your Magento 2 Catalog
1. Run the following commands on your web server from your Magento 2 app root directory
```plaintext
php bin/magento flow:flow-connector:catalog-sync-attributes-save
php bin/magento flow:flow-connector:catalog-sync-queue-all
php bin/magento flow:flow-connector:catalog-sync-process
```

## Overview 

### Product Catalog

Documentation: [Flow Product Catalog](https://docs.flow.io/integration-overview#product-catalog)

This module automatically syncs product information to Flow in two ways:

1. Product creations, updates, and deletes are queued with an observer. This queue is processed with a cron task every minute, with additional workers spawned every minute the queue is not empty.
2. There is a cron task that syncs the entire product catalog to Flow. By default, this will sync twice per day.

### Price Localization

Documentation: [Flow.js Product Localization](https://docs.flow.io/guides/flowjs/product-localization)

Localized prices generated via the Flow pricing engine are cached in the same JSON configurations that standard Magento 2 pricing information is stored. To enable this price localization caching as well as the applicable RequireJS mixins, select "Yes" on the "Enable Catalog Price Localization" field in your Magento 2 configuration for Flow Commerce.

Alternatively, you can implement your own customized price localization solution by refering to the documentation.

### Cart Localization

Documentation: [Flow.js Cart Localization](https://docs.flow.io/guides/flowjs/cart-localization)

Localized carts generated via the Flow order engine are rendered on the fly via FlowJS. To enable cart localization, select "Yes" on the "Enable Cart Localization" field in your Magento 2 configuration for Flow Commerce.

Alternatively, you can implement your own customized price localization solution by refering to the documentation.

### Country Picker

Documentation: [Flow Country Picker](https://docs.flow.io/guides/country-picker)

The Flow Country Picker can be automatically installed into the top left of your header. To enable this, select "Yes" on the "Enable Country Picker" field in your Magento 2 configuration for Flow Commerce.

Alternatively, you can leave "Enable Country Picker" set to "No" and Flow's Country Picker JS file is still be available for a custom integration.

### Checkout UI (CUI)

Documentation: [Flow Checkout UI](https://docs.flow.io/checkout/checkout)

Once you have Magento 2 Catalog items synced with your Flow Organization's Product Catalog customers can be sent to Flow Hosted Checkout. Redirects to Flow Checkout UI can be automated via our Magento 2 Checkout controller interceptor. To enable this interceptor, select "Yes" on the "Redirect to Flow Checkout" field in your Magento 2 configuration for Flow Commerce.

Alternatively, you can leave "Redirect to Flow Checkout" set to "No" and implement sending your customers to this redirect controller manually. For example, creating a link to this path: 
```plaintext
BASE_URL/flowconnector/checkout/redirecttoflow?country=FRA
```

For more information on customizing Flow's CUI please refer to [Customizing Checkout UI](https://docs.flow.io/checkout/customization).

### Discounts

Discounts are calculated and applied according to the rules of your Magento 2 store's base currency and applied as a percentage of the line item row total. Magento 2 discounts applied to shipping costs are not applied to Flow orders.

### Webhook Processing

Documentation: [Flow Webhook](https://docs.flow.io/module/webhook)

Upon configuring this module with your Flow credentials, the module configures a set of webhooks to receive event data from Flow. These webhook events are queued and processed with a cron task. For example, after a customer submits an order through Flow Checkout UI, a series of webhook events are be sent to Magento 2 with detailed order and payment information.

### Console Commands

Console commands provided by this module:

```plaintext
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

### Extending Functionality

This module dispatches several events that observers can listen to:

- An event is dispatched when a webhook event is received from Flow.
  - List of event types can be found in the Model/WebhookManger/EndpointsConfiguration.php class.
  - The event name is `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_PREFIX` + the event type.
- An event is dispatched after a webhook event is processed.
  - List of event types can be found in the Model/WebhookManger/EndpointsConfiguration.php class.
  - The event name is `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_PREFIX` + the event type + `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_SUFFIX_AFTER`.
- An event is dispatched after a product has been synced to Flow.
  - The event name is `Flow\FlowConnector\Model\Sync\CatalogSync::EVENT_FLOW_PRODUCT_SYNC_AFTER`.
- An event is dispatched if the customer changed their email address during Flow Hosted Checkout.
  - The event name is `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_CHECKOUT_EMAIL_CHANGED`.

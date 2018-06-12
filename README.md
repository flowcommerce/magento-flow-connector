# Flow Connector Magento Module

This module is used to integrate your Magento store with Flow.

Please make sure you are familiar with the [Flow Integration Overview](https://docs.flow.io/integration-overview).

## Flow Integration

This module provides all the major components for integrating with Flow.

### Product Catalog

Documentation: [Flow Product Catalog](https://docs.flow.io/integration-overview#product-catalog)

This module will sync product information to Flow in two ways:

1. Product updates (and deletes) are queued with an observer. This queue is processed with a cron task every minute, with additional workers spawned every minute the queue is not empty.
2. There is a cron task that will sync the entire product catalog to Flow. By default, this will sync twice a day.

### Flow.js

Documentation: [Flow.js](https://docs.flow.io/shopify/flow-js)

This module will add the Flow.js library and the required annotations to the DOM to enable localization. If you are using a custom theme, you will need to implement something similar to the default code provided.

### Hosted Checkout

Documentation: [Flow Hosted Checkout](https://docs.flow.io/checkout/checkout)

Once your Magento Catalog is fully synced with Flow, customers can be sent to Flow Hosted Checkout using javascript provided by Flow.js. This module will provide a default implementation of the Flow.js hosted checkout redirect. If you are using a custom theme, you will need to implement something similar as the default code provided.

For testing without Flow.js, a redirect is provided that will send cart and user information to the Flow Hosted Checkout:
```
/flowconnector/checkout/redirecttoflow?country=FRA
```

### Webhook Processing

Documentation: [Flow Webhook](https://docs.flow.io/module/webhook)

Upon configuring this module with your Flow credentials, the module will configure a set of webhooks to receive event data from Flow. These webhook events are queued and processed with a cron task. For example, after a customer completes the Flow Hosted Checkout process, a series of webhook events will be sent to Magento with detailed order and payment information.

## Extending Functionality

This module will dispatch several events that observers can listen to:

- An event is dispatched when a webhook event is received from Flow.
  - List of event types can be found in the Model/WebhookEventManger.php registerWebhooks() method.
  - The event name will be `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_PREFIX` + the event type.
- An event is dispatched after a webhook event is processed.
  - List of event types can be found in the Model/WebhookEventManger.php registerWebhooks() method.
  - The event name will be `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_PREFIX` + the event type + `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_SUFFIX_AFTER`.
- An event is dispatched after a product has been synced to Flow.
  - The event name is defined in `Flow\FlowConnector\Model\Sync\CatalogSync::EVENT_FLOW_PRODUCT_SYNC_AFTER`.
- An event is dispatched if the customer changed their email address during Flow Hosted Checkout.
  - The event name is defined in `Flow\FlowConnector\Model\WebhookEvent::EVENT_FLOW_CHECKOUT_EMAIL_CHANGED`.

## Administration

### Console commands

Console commands provided by this module:

```
flow:flowconnector:catalog-sync-queue-all Queues all products for sync to Flow catalog.
flow:flowconnector:catalog-sync-process   Process sync skus queue and send to Flow.
flow:flowconnector:webhook-event-process  Process Flow webhook events.
```

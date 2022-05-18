# Flow Connector for Magento 2

## Introduction
Magento 2 is a popular e-commerce platform that helps businesses build and manage their online storefront. The Flow Connector extension for Magento 2 lets you seamlessly manage all of your global sales challenges.

In this guide, you will:
- Install the Flow Connector extension
- Initialize the Flow-Magento integration
- Configure your integration options

Once this extension is installed, you can optionally: 
- Enable Flow Country Picker
- Localize Magento catalog prices
- Localize Magento cart prices and totals
- Redirect international customers to Flow Checkout UI

## Prerequisites
In order to successfully connect your Magento console to Flow, you'll need the following:

- A Flow account with:
    - An Active Experience with Shipping Tiers available
    - A Flow API key for use in the Magento Back Office setup
    - your Flow Organization ID, which can be found in your Flow Console URL. For example, https://console.flow.io/{ORGANIZATION_ID}/experiences
- A Magento installation with:
    - Version 2.4.2 or greater
    - Magento Open Source (CE), Magento Commerce using on-prem (EE), or Magento Commerce on Cloud (ECE)
    - Shell access and permission to run command line tools
    - Crons enabled

Now, lets get started with the install process.

## Step 1 — Install extension
First, require the latest version of `flowcommerce/flowconnector` in the `require` section of Magento's `composer.json`:
```json
"require": {
    "flowcommerce/flowconnector": "^{VERSION}"
}
```

Then, use composer to get the latest versions of dependencies and update your composer.lock file. Please note that all commands in this guide will be run using the Magento application root as the working directory:
```plaintext
$ composer update
```

Then, use composer to resolve dependencies and install the Flow Connector into your vendor directory:
```plaintext
$ composer install
```

Finally, use Magento's command line tool to complete the installation by updating your database schema and clearing your compiled code and cache:
```plaintext
$ php bin/magento setup:upgrade
```

Now, we are going to connect your Flow organization to your Magento store view.

## Step 2 — Connect Magento to Flow
First, login to your Magento Back Office. From the left hand menu, click on Stores -> Configuration. Change the Scope to your store (or default store if you only have one) and select Flow Commerce -> Connector Settings.

Then, enable the connector and fill out your Flow Organization ID, Flow API Key, and Save Config.

Then, we will use the settings we just configured to register webhooks for importing orders from Flow, register Sync Streams, and prepare Flow's catalog to sync Magento's catalog attributes:
```plaintext
$ php bin/magento flow:connector:initialize
```
A Sync Stream is a Flow service which tracks data syncing between two parties, in this case Flow and Magento 2.
For more detail about how Flow Webhooks work please refer to this guide: [Flow Webhook](https://docs.flow.io/docs/create-webhooks)

Then, enqueue your Magento catalog to be synced:
```plaintext
$ php bin/magento flow:connector:catalog-enqueue
```

Finally, synchronize your Magento catalog:
```plaintext
$ php bin/magento flow:connector:catalog-sync
```

This extension also automatically syncs product information to Flow in two ways:

1. Product creations, updates, and deletes are queued with an observer. This queue is processed with a cron task every minute, with additional workers spawned every minute the queue is not empty.
2. A cron task that syncs the entire product catalog to Flow. By default, this syncs twice per day for production Flow organizations only.

Now, we are going to configure the remaining integration options available in the Magento Back Office to suit your business and technical requirements.

## Step 3 — Configure the integration
Login to your Magento Back Office. From the left hand menu, click on Stores -> Configuration. Change the Scope to your store (or default store if you only have one) and select Flow Commerce -> Connector Settings. This is a list of the extension configuration options available:

### Enabled
This toggle controls the enabled status of the entire extension. When the Enabled option is set to "No", all functionality of the Flow Connector is disabled.

### Organization Id
This text field indicates which Flow organization should be integrated with the current store in Magento. This is a required field.

### API Token
This text field indicates the Flow organization secret API key which is used for authorization and communication with Flow's API. This is a required field.

### Checkout Base Url
This text field indicates the base url which is used for redirection to Flow Checkout UI. The value provided must have a CNAME DNS record mapped to https://checkout.flow.io/ which must be validated by Flow. By default, this field has no value and checkout redirection to Flow uses the standard https://checkout.flow.io/.

### Redirect to Flow Checkout
This toggle controls an automated redirection of international users to Flow Checkout UI via controller interception. At this point of redirect, Magento's cart is converted to a Flow order, including item discounts if enabled, and the user is sent Flow Checkout UI to complete their purchase. Following this purchase, webhooks are sent from Flow back to Magento to import the order data and empty the user's Magento cart. It is recommended that you select "Yes" for ease and consistency of the integration. This field defaults to "No".  

Alternatively, you can use the same functionality as the automated path to build a valid Flow order by sending users to the redirect controller manually `{BASE_URL}/flowconnector/checkout/redirecttoflow?country=FRA` or implement your own integration by following this guide: [Flow Checkout UI](https://docs.flow.io/docs/redirect-users-to-checkout-ui)

For more information on customizing Flow Checkout UI please refer to this guide: [Customizing Checkout UI](https://docs.flow.io/docs/customize-checkout-ui).  

### Support Magento Discounts
Discounts are calculated and applied according to the rules of your Magento store's base currency and applied as a percentage of the line item row total. Magento discounts applied to shipping costs are not applied to Flow orders. This field defaults to "Yes".

### Create Invoice
This dropdown indicates how invoices are imported in Magento from Flow:
- To create a Magento invoice at the point payment is captured in Flow, select "When Captured in Flow".
- To create a Magento invoice at the point a shipping label is generated in Flow, select "When Shipped in Flow".
- To never create a Magento invoice based on Flow order activity, select "Never".

### Send invoice email
This toggle controls how invoice emails are triggered by Flow order activity. Selecting "Yes" enables invoice emails to be sent automatically after they are created in Magento. This option is dependent on "Create Invoice" being enabled as well. This toggle defaults to "Yes".

### Create Shipment
This dropdown indicates how shipments are imported in Magento from Flow:
- To create a Magento shipment at the point a shipping label is generated in Flow, select "When Shipped in Flow".
- To never create a Magento shipment based on Flow order activity, select "Never".

### Send shipment email
This toggle controls how shipment emails are triggered by Flow order activity. Selecting "Yes" enables shipment emails to be sent automatically after they are created in Magento. This option is dependent on "Create Shipment" being enabled as well. This toggle defaults to "Yes".

### Enable Webhook Validation
This toggle controls the use of a secret key which is generated to protect Magento from receiving unauthorized webhooks. Selecting "No" is not recommended because it could allow other parties besides Flow to trigger Flow related functionality such as generating or altering order data. This toggle defaults to "Yes".

### Enable Country Picker
This toggle controls the initialization of the Flow Country Picker. By default, the Flow Country Picker is included via Magento layout xml to the header panel and receives default styling and options. This toggle defaults to "Yes".

Alternatively, you can customize the initialization options by following our custom integration guide: [Flow Country Picker](https://docs.flow.io/docs/enable-a-country-picker). 

### Enable Catalog Price Localization
This toggle controls the initialization of catalog price localization. This feature involves server-side API calls to Flow prior to page load as well as JavaScript mixins to complete the visual integration with catalog items. Selecting "Yes" will enable Flow API calls on Magento cache-miss events for product blocks to get all localized prices for this product dynamically. In this way, it loads the cache with every Flow experience price available to expedite page load time. When the browser page loads, JavaScript mixins are used to determine which prices should be shown to the user based on their geolocated IP address or Flow Country Picker selection. This toggle defaults to "Yes".

Alternatively, you can implement your own customized price localization solution by refering to our custom integration guide: [Flow.js Product Localization](https://docs.flow.io/docs/localize-product-prices)

### Maximum Time to Hide Catalog Prices
This text field controls the amount of time in milliseconds that catalog prices will remain hidden while waiting for the JavaScript mixins to determine which prices are to be displayed. This is a valuable tool to prevent the base currency price from displaying briefly before the local currency is displayed to the user. It is recommended to use a value of "5000" which means that regardless of the length of time it takes for the page to load, prices will never remain hidden by Flow for longer than 5 seconds. This field accepts integers and defaults to 0.

### Enable Cart Localization
This toggle controls the initialization of cart localization. This feature employs JavaScript mixins to observe international cart updates and localizes their amounts to match the local currency of the user. This localization occurs on both the core Magento minicart and dedicated cart page. This toggle defaults to "Yes".

Alternatively, you can implement your own customized price localization solution by refering to the documentation: [Flow.js Cart Localization](https://docs.flow.io/docs/localize-product-prices)

### Maximum Time to Hide Carts
This text field controls the amount of time in milliseconds that cart prices will remain hidden while waiting for the JavaScript mixins to localize the Magento cart to a valid Flow open order. This is a valuable tool to prevent the base currency cart totals from displaying briefly before the local currency is displayed to the user. It is recommended to use a value of "5000" which means that regardless of the length of time it takes for the page to load, cart totals will never remain hidden by Flow for longer than 5 seconds. This field accepts integers and defaults to 0.

### Enable Local Payment Methods on PDP
This toggle controls display of the local payment method logos which are available in Flow Checkout UI on the PDP. This is injected via the layout reference container "product.info.type" which appears on catalog product view pages (PDP). This toggle defaults to "Yes".

Alternatively, you can implement your own solution by refering to the documentation: [Display Payment Method Logos](https://docs.flow.io/docs/display-payment-method-logos)

### Enable Default Estimated Shipping Window on PDP
This toggle controls display of the estimated shipping window for the default shipping option on the PDP. This is injected via the layout reference container "product.info.type" which appears on catalog product view pages (PDP). This toggle defaults to "Yes".  

Alternatively, you can implement your own solution by refering to the documentation: [Display Delivery Window](https://docs.flow.io/docs/display-delivery-windows)

### Enable Tax and Duty Messaging
This toggle controls display of local tax and duties for all catalog item priceboxes. This toggle defaults to "Yes".

Alternatively, you can implement your own solution by refering to the documentation: [Display Tax and Duty](https://docs.flow.io/docs/display-tax-and-duty)

### Enable Daily Catalog Sync
This toggle controls the automatic enqueue of all skus daily at 1:15AM (timezone is dependent on your server configuration). Items will still be synced when their data has been updated and when using the flow:connector:catalog-sync shell command. This toggle defaults to "No".

### Enable Regular Pricing Override
This toggle controls whether to use final_price or regular_price when syncing item prices with Flow and for Flow Checkout UI. The connector will always sync all available Magento item prices as additional attributes. This toggle defaults to "No".

### Associate Magento with Flow Order Identifiers
This toggle controls whether Magento increment ID will be associated with Flow Order ID after Magento order was created in Magento system. This works asynchronously using the [Message Queues](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/message-queues/message-queues.html) Magento feature, for which at least MySQL based message queues configuration is required. MySQL based message queues are enabled by default in Magento Open Source using Magento built-in cron job, and no additional configuration is required. In Magento Commerce more powerful AMQP setup can be introduced after slight adjustments to the extension files as per [Change message queue from MySQL to AMQP](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/message-queues/message-queues.html#change-message-queue-from-mysql-to-amqp). This is usually necessary only for stores receiving a significantly large number of orders in a very short period of time. This toggle defaults to "No".

### Shell Commands Available
Save product attributes needed for catalog integration to Flow:
```plaintext
$ php bin/magento flow:connector:catalog-attributes-save
```

Sync queued items to Flow catalog:
```plaintext
$ php bin/magento flow:connector:catalog-sync
```

Sync missing orders from Flow to Magento:
```plaintext
$ php bin/magento flow:connector:order-sync
```

Enqueue all products for sync to Flow catalog:
```plaintext
$ php bin/magento flow:connector:catalog-enqueue
```

Remove Flow cron tasks older than 5 minutes and still marked as running:
```plaintext
$ php bin/magento flow:connector:cron-cleanup
```

Initialize integration with Flow. Includes webhook registration, sync stream registration, attributes creation, inventory center key fetching, and creating secret for webhook payload verification:
```plaintext
$ php bin/magento flow:connector:initialize
```

Fetch inventory center keys for all store views where flowconnector is configured:
```plaintext
$ php bin/magento flow:connector:center-fetch
```

Sync inventory queue to Flow. Warnings may be logged for items which are configured to not track inventory:
```plaintext
$ php bin/magento flow:connector:inventory-process
```

Enqueue all products for sync to Flow inventory:
```plaintext
$ php bin/magento flow:connector:inventory-sync
```

Process recieved Flow webhook events:
```plaintext
$ php bin/magento flow:connector:webhook-process
```

Register or update existing webhooks with Flow:
```plaintext
$ php bin/magento flow:connector:webhook-register
```

Create secret for webhook payload verification:
```plaintext
$ php bin/magento flow:connector:webhook-update
```

Queues Magento order increment ID M123456, and Flow order ID F123456 from store ID 1 for association using Flow API:
```plaintext
$ php bin/magento flow:connector:order-identifiers-queue --store_id 1 --magento_order_id M123456 --flow_order_id F123456
```

### Extending Functionality
This extension dispatches several events that observers can listen to:

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

## Limitations

### Refunds Support

Flow Connector supports refunds functionality, but note that partial refunds are supported only when refunding from Magento, whereas from Flow Console it is only possible to perform full refunds. In the event that partial refund was performed from Flow Console, it is necessary to create matching offline refund from Magento admin.

## Conclusion
Congratulations, your Magento 2 store is now integrated with Flow via the Flow Connector extension! Please contact a Flow representative to review your Flow organization settings and test the integration thoroughly prior to launching with live customers. When entering an international Flow experience you should see all product prices in the local currency and clicking checkout anywhere on the site should send you to Flow Checkout UI. Since Flow Checkout UI is hosted by Flow and is external to your Magento store, you may also want to host CSS and JS files which a Flow representative can help import to your Flow Checkout UI page load for branding and analytics consistency purposes.

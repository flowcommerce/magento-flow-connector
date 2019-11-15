# Integrating Magento 2 with Flow

## Introduction
Magento 2 is a popular e-commerce platform that helps businesses build and manage their online storefront. The Flow Connector extension for Magento 2 lets you seamlessly manage all of your global sales challenges.

In this guide, you will:
- Install the Flow Connector extension
- Initialize the Flow-Magento integration
- Configure your integration options

Once this extension is installed, you can optionally: 
- Enable Flow's Country Picker
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
    - Version 2.3.2 or greater
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

Next, run these composer shell commands with the Magento app root as the working directory:
```plaintext
$ composer update
$ composer install
$ php bin/magento setup:upgrade
```

Now, we are going to connect your Flow organization to your Magento store view.

## Step 2 — Connect Magento to Flow
First, login to your Magento Back Office. From the left hand menu, click on Stores -> Configuration. Change the Scope to your store (or default store if you only have one) and select Flow Commerce -> Connector Settings.

Next, enable the connector and fill out your Flow Organization ID, Flow API Key, and Save Config.

Then, we will use the settings we just configured to register webhooks for importing orders from Flow and prepare Flow's catalog to sync Magento's catalog attributes. That is done by running the following shell command with the Magento app root as the working directory:
```plaintext
$ php bin/magento flow:flow-connector:integration-initialize
```

Finally, syncronize your Magento catalog by running the following shell commands with the Magento app root as the working directory:
```plaintext
$ php bin/magento flow:flow-connector:catalog-sync-attributes-save
$ php bin/magento flow:flow-connector:catalog-sync-queue-all
$ php bin/magento flow:flow-connector:catalog-sync-process
```

Now, we are going to configure the remaining integration options available in the Magento Back Office to suit your business and technical requirements.

## Step 3 — Configure the integration
Login to your Magento Back Office. From the left hand menu, click on Stores -> Configuration. Change the Scope to your store (or default store if you only have one) and select Flow Commerce -> Connector Settings. This is a list of the extension configuration options available:

### Enabled
This toggle controls the enabled status of the entire extension. When the Enabled option is set to "No", all functionality of the Flow Connector is disabled.

### Organization Id
This text field indicates which Flow organization should be integrated with the current store in Magento. This is a required field.

### API Token
This text field indicates the Flow organization secret API key which is used for authorization and communication with Flow's API. This is a required field.

### Flow.js Version
This text field indicates which version of the Flow.js library is used for the front end portion of this integration. This field defaults to the value "latest" and it is recommended to continue to use this default value unless otherwise discussed with a representative from Flow.

### Checkout Base Url
This text field indicates the base url which is used for redirection to Flow Checkout UI. The value provided must have a CNAME DNS record mapped to https://checkout.flow.io/ which must be validated by Flow. By default, this field has no value and checkout redirection to Flow uses the standard https://checkout.flow.io/.

### Redirect to Flow Checkout
This toggle controls an automated redirection of international users to Flow Checkout UI via controller interception. At this point of redirect, Magento's cart is converted to a Flow order, including item discounts, and the user is sent Flow Checkout UI to complete their purchase. Following this purchase, webhooks are sent from Flow back to Magento to import the order data and clear the user's Magento cart. This field defaults to "No". To enable this automatic redirect, select "Yes". It is recommended that you select "Yes" for ease and consistency of the integration. Otherwise if you select "No", you can use the same functionality as the automated path to build a valid Flow order by sending users to the redirect controller manually `{BASE_URL}/flowconnector/checkout/redirecttoflow?country=FRA`.

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
This toggle controls the initialization of the Flow Country Picker. By default, the Flow Country Picker is included via Magento layout xml to the header panel and receives default styling and options. If you wish to customize the initialization options, select "No" on this toggle and follow our integration guide [Flow Country Picker](https://docs.flow.io/docs/flow-country-picker). This toggle defaults to "Yes".

### Enable Catalog Price Localization
This toggle controls the initialization of catalog price localization. This feature involves server-side API calls to Flow prior to page load as well as JavaScript mixins to complete the visual integration with catalog items. Selecting "Yes" will enable Flow API calls on Magento cache-miss events for product blocks to get all localized prices for this product dynamically. In this way, it loads the cache with every Flow experience price available to expedite page load time. When the browser page loads, JavaScript mixins are used to determine which prices should be shown to the user based on their geolocated IP address or Flow Country Picker selection. This toggle defaults to "Yes".

### Maximum Time to Hide Catalog Prices
This text field controls the amount of time in milliseconds that catalog prices will remain hidden while waiting for the JavaScript mixins to determine which prices are to be displayed. This is a valuable tool to prevent the base currency price from displaying briefly before the local currency is displayed to the user. It is recommended to use a value of "5000" which means that regardless of the length of time it takes for the page to load, prices will never remain hidden by Flow for longer than 5 seconds. This field accepts integers and defaults to 0.

### Enable Cart Localization
This toggle controls the initialization of cart localization. This feature employs JavaScript mixins to observe international cart updates and localizes their amounts to match the local currency of the user. This localization occurs on both the core Magento minicart and dedicated cart page. This toggle defaults to "Yes".

### Maximum Time to Hide Carts
This text field controls the amount of time in milliseconds that cart prices will remain hidden while waiting for the JavaScript mixins to localize the Magento cart to a valid Flow open order. This is a valuable tool to prevent the base currency cart totals from displaying briefly before the local currency is displayed to the user. It is recommended to use a value of "5000" which means that regardless of the length of time it takes for the page to load, cart totals will never remain hidden by Flow for longer than 5 seconds. This field accepts integers and defaults to 0.

# Feature Overview 

## Product Catalog
Documentation: [Flow Product Catalog](https://docs.flow.io/integration-overview#product-catalog)

This extension automatically syncs product information to Flow in two ways:

1. Product creations, updates, and deletes are queued with an observer. This queue is processed with a cron task every minute, with additional workers spawned every minute the queue is not empty.
2. There is a cron task that syncs the entire product catalog to Flow. By default, this syncs twice per day for production Flow organizations only.

## Price Localization
Documentation: [Flow.js Product Localization](https://docs.flow.io/guides/flowjs/product-localization)

Localized prices generated via the Flow pricing engine are cached in the same JSON configurations that standard Magento pricing information is stored. To enable this price localization caching as well as the applicable RequireJS mixins, select "Yes" on the "Enable Catalog Price Localization" field in your Magento configuration for Flow Commerce.

Alternatively, you can implement your own customized price localization solution by refering to the documentation.

## Cart Localization
Documentation: [Flow.js Cart Localization](https://docs.flow.io/guides/flowjs/cart-localization)

Localized carts generated via the Flow order engine are rendered on the fly via FlowJS. To enable cart localization, select "Yes" on the "Enable Cart Localization" field in your Magento configuration for Flow Commerce.

Alternatively, you can implement your own customized price localization solution by refering to the documentation.

## Country Picker
Documentation: [Flow Country Picker](https://docs.flow.io/guides/country-picker)

The Flow Country Picker can be automatically installed into the top left of your header. To enable this, select "Yes" on the "Enable Country Picker" field in your Magento configuration for Flow Commerce.

Alternatively, you can leave "Enable Country Picker" set to "No" and Flow's Country Picker JavaScript file is still be available for a custom integration.

## Checkout UI (CUI)
Documentation: [Flow Checkout UI](https://docs.flow.io/checkout/checkout)

Once you have Magento Catalog items synced with your Flow Organization's Product Catalog customers can be sent to Flow Hosted Checkout. Redirects to Flow Checkout UI can be automated via our Magento Checkout controller interceptor. To enable this interceptor, select "Yes" on the "Redirect to Flow Checkout" field in your Magento configuration for Flow Commerce.

Alternatively, you can leave "Redirect to Flow Checkout" set to "No" and implement sending your customers to this redirect controller manually. For example, creating a link to this path: 
```plaintext
BASE_URL/flowconnector/checkout/redirecttoflow?country=FRA
```

For more information on customizing Flow's CUI please refer to [Customizing Checkout UI](https://docs.flow.io/checkout/customization).

## Discounts
Discounts are calculated and applied according to the rules of your Magento store's base currency and applied as a percentage of the line item row total. Magento discounts applied to shipping costs are not applied to Flow orders.

## Webhook Processing
Documentation: [Flow Webhook](https://docs.flow.io/module/webhook)

Upon configuring this extension with your Flow credentials, the extension configures a set of webhooks to receive event data from Flow. These webhook events are queued and processed with a cron task. For example, after a customer submits an order through Flow Checkout UI, a series of webhook events are be sent to Magento with detailed order and payment information.

## Shell Commands
Shell commands provided by this extension:

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

## Extending Functionality
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

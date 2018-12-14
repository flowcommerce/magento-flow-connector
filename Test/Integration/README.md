# Integration Tests

## Webhook Event Creation

To help the *webhook event* tests some webhooks were created using sample payload files.

### Payload files
The payload files were generated placing an order on sandbox and copying payload content to the file.

***The payload must have unique order_number and event_id for order_placed event.***

```
{
  "event_id": "evt-8da004cf409e44b5a85b11c3d5b00c8b",
  "timestamp": "2018-12-12T20:23:49.873Z",
  "organization": "organization-id",
  "order_number": "ord-7ab8ac78b5a34eabb2bd6fa9eb9775a2"
}
```
### Create the webhook event

To create a webhook event for a specific payload file or a list of files, just pass as a param a *string[]* with the filenames.
```
$this->createWebhookEventsFixture->createOnlineAuthorizationUpsertedWebhooks(
['online_authorization_upserted_v2_paypal.json']
);
```

#### Available Methods
*createAllocationDeletedWebhooks*
*createAuthorizationDeletedWebhooks*
*createCaptureUpsertedWebhooks*
*createCardAuthorizationUpsertedWebhooks*
*createFraudStatusChangeWebhooks*
*createLabelUpsertedWebhooks*
*createOnlineAuthorizationUpsertedWebhooks*
*createOrderDeletedWebhooks*
*createOrderPlacedWebhooks*
*createRefundCaptureUpsertedWebhooks*
*createRefundUpsertedWebhooks*
*createTrackingLabelEventUpsertedWebhooks*





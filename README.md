# Ship a Postcard API examples

Print and mail real postcards to US addresses from your code. These are runnable examples of the [Ship a Postcard](https://shipapostcard.com) REST API in **cURL, Node.js, PHP and Python**, plus webhook signature verification in each language.

- **API reference:** https://shipapostcard.com/developer/docs
- **OpenAPI 3.1 spec:** https://shipapostcard.com/openapi.json (import it into Postman, Insomnia or your code generator)
- **Base URL:** `https://shipapostcard.com/api/v1`

Senders can be anywhere in the world; **recipients must have a US address**.

## 1. Get an API key

Create a free account at https://shipapostcard.com/register, then go to **Developer Hub → API Keys** and create a key. Each key belongs to a workspace: every postcard it creates is billed to that workspace and shows up in its API logs.

Add a payment method under **Billing**. API postcards are billed together every night at 23:59 UTC, with volume pricing, to the workspace owner's saved card.

```bash
export SHIPAPOSTCARD_API_KEY="your-api-key"
```

Send it as a Bearer token: `Authorization: Bearer $SHIPAPOSTCARD_API_KEY`.

## 2. Send a postcard

```bash
curl -X POST https://shipapostcard.com/api/v1/postcards \
  -H "Authorization: Bearer $SHIPAPOSTCARD_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Thanks for your order! Enjoy 20% off your next one with THANKS20.",
    "recipient_details": {
      "name": "Jane Doe",
      "address_line1": "185 Berry St",
      "city": "San Francisco",
      "state": "CA",
      "zip": "94107",
      "country": "US"
    },
    "design_details": {
      "image_url": "https://example.com/postcard-front.jpg",
      "size": "4x6",
      "orientation": "landscape"
    }
  }'
```

Response (`201 Created`):

```json
{
  "id": "sap_order_1234",
  "status": "pending_batch",
  "amount": 2.25,
  "message": "Postcard queued for daily batch processing."
}
```

| Field | Rules |
|---|---|
| `message` | Required, up to 350 characters (printed on the back). |
| `recipient_details.name` | Required, up to 40 characters. |
| `recipient_details.address_line1` | Required, up to 64 characters. |
| `recipient_details.city` | Required. |
| `recipient_details.state` | Required, 2-letter US state code. |
| `recipient_details.zip` | Required, `12345` or `12345-6789`. |
| `recipient_details.country` | Required, `US`. |
| `design_details.image_url` | Required, public URL of the front image (JPG or PNG). It's printed at 300 DPI, so use a large image (about 1875×1275 px for a 4x6). |
| `design_details.size` | `4x6` (default), `5x7` or `6x9`. |
| `design_details.orientation` | `landscape` (default) or `portrait`. |

The [OpenAPI spec](https://shipapostcard.com/openapi.json) is the source of truth for these rules.

## 3. Track or cancel it

| Request | What it does |
|---|---|
| `GET /postcards` | Your workspace's postcards, newest first, 10 per page (`?page=2`). |
| `GET /postcards/{id}` | One postcard. `{id}` is the `sap_order_…` id (the bare number also works). |
| `DELETE /postcards/{id}` | Cancels it while its status is `pending_batch` or `ready_for_batch`: `204`. Once billing or printing has started: `403`. |
| `GET /user` | Checks that your key works. |
| `GET /health` | API status, no key needed. |

### Statuses

`pending_batch` → `ready_for_batch` → `processing_payment` → `paid` → `sent_to_printer` → `in_transit` → `delivered`

It can also end as `failed`, `payment_failed` (card declined; it's retried once the workspace owner updates the card), `invalid_address`, `flagged_for_review` or `image_download_failed`.

## 4. Get notified with webhooks

Add an endpoint under **Developer Hub → Webhooks** and pick the events:

| Event | When | `data` |
|---|---|---|
| `postcard.created` | The API accepted a postcard. | `id`, `status`, `amount` |
| `postcard.sent_to_printer` | The nightly batch was paid and sent to the printer (one event per batch). | `batch_id`, `status`, `total_cards_processed`, `total_cards_failed`, `successful_order_ids` |
| `postcard.delivered` | USPS delivered it. | `id`, `status`, `lob_id`, `delivered_at` |
| `postcard.failed` | It can't be printed or mailed (e.g. undeliverable address, image that can't be downloaded). | `id`, `status`, `reason` (and `batch_id` when it failed at the printer) |
| `batch.payment_failed` | The nightly charge was declined; fulfillment is paused until the card is updated. | `batch_id`, `message`, `stripe_error`, `declined_amount`, `affected_cards`, `action_required` |

Each request is a `POST` with a JSON body:

```json
{
  "id": "evt_8sKq2...",
  "type": "postcard.delivered",
  "created_at": 1760600000,
  "data": {
    "id": "sap_order_1234",
    "status": "delivered",
    "lob_id": "psc_5c002b86ce47537a",
    "delivered_at": "2026-10-16T14:03:11+00:00"
  }
}
```

and two headers:

- `ShipAPostcard-Signature: t=1760600000,v1=5257a869e7…` is an HMAC-SHA256 of `"{t}.{raw body}"` with your endpoint's signing secret, in hex.
- `ShipAPostcard-Event-Id: evt_8sKq2...` stays the same across retries, so you can ignore duplicates.

**Verify the signature on the raw body before parsing it**, compare in constant time, and reject old timestamps (we use 5 minutes) to stop replays. Answer with a `2xx` within 10 seconds; otherwise the event is retried, up to 3 attempts a minute apart. Use a public HTTPS URL: URLs that point at private or internal addresses are refused.

## Examples

| | Send a postcard | List / get / cancel | Verify a webhook |
|---|---|---|---|
| cURL | [curl/create-postcard.sh](curl/create-postcard.sh) | [curl/manage-postcards.sh](curl/manage-postcards.sh) | n/a |
| Node.js 18+ | [node/create-postcard.mjs](node/create-postcard.mjs) | [node/manage-postcards.mjs](node/manage-postcards.mjs) | [node/verify-webhook.mjs](node/verify-webhook.mjs) |
| PHP 8.1+ | [php/create-postcard.php](php/create-postcard.php) | [php/manage-postcards.php](php/manage-postcards.php) | [php/verify-webhook.php](php/verify-webhook.php) |
| Python 3.9+ | [python/create_postcard.py](python/create_postcard.py) | [python/manage_postcards.py](python/manage_postcards.py) | [python/verify_webhook.py](python/verify_webhook.py) |

None of them need a package: they use each language's standard library.

## Errors

| Status | Meaning |
|---|---|
| `401` | Missing or revoked API key. |
| `403` | The postcard can no longer be cancelled. |
| `404` | Not found in your key's workspace. |
| `422` | Invalid fields: `{"error": "Validation Failed", "details": {"field": ["message"]}}`. |
| `429` | Over 600 requests per minute. Wait and retry. |

## Support

support@shipapostcard.com, or https://shipapostcard.com/support. Use of the API is subject to the [Developer Agreement](https://shipapostcard.com/developer-agreement).

## License

The example code in this repository is released under the [MIT License](LICENSE).

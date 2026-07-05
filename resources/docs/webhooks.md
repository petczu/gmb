---
title: Webhooks
description: Signed event notifications with retries
---

# Webhooks

Instead of polling the API, subscribe to events. Add endpoints in the app under
**Settings → Webhooks** (Pro plan): pick a URL and the events it should receive.

## Events

| Event | Fired when |
|---|---|
| `review.created` | A new review is synced from Google |
| `reply.published` | A reply goes live (manual, AI, automation or API) |
| `goal.reached` | A workspace hits its monthly review goal |
| `anomaly.detected` | Negative streak, volume spike, stalled location or rating drop |

## Delivery

Each event is a `POST` with a JSON body:

```json
{
  "event": "review.created",
  "workspace": { "id": "a73d9c73-…", "name": "Acme Agency" },
  "occurred_at": "2026-07-03T10:00:00+00:00",
  "data": {
    "id": 42,
    "location_id": 1,
    "location": "GAME OVER Escape Rooms Vienna",
    "author": "Laura Rozic",
    "rating": 5,
    "text": "Suly war sehr hilfsbereit und freundlich.",
    "reply": null,
    "reply_source": null,
    "replied_at": null,
    "created_at": "2026-07-03T09:58:00+00:00"
  }
}
```

Headers on every delivery:

| Header | Value |
|---|---|
| `X-Webhook-Event` | Event name, e.g. `review.created` |
| `X-Webhook-Id` | Unique delivery id |
| `X-Webhook-Signature` | `sha256=` + HMAC-SHA256 of the raw body |

Respond with any `2xx` within 10 seconds. Anything else (or a timeout) is
retried with backoff — up to 5 attempts over several hours. Every attempt is
visible in the app (**Settings → Webhooks → Deliveries**), where failed
deliveries can be re-sent manually.

## Verifying signatures

Each endpoint has its own secret (`whsec_…`, shown in the app). Compute
HMAC-SHA256 over the **raw request body** and compare:

```php
$expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

abort_unless(hash_equals($expected, $request->header('X-Webhook-Signature')), 401);
```

```javascript
import crypto from "node:crypto";

const expected =
  "sha256=" + crypto.createHmac("sha256", secret).update(rawBody).digest("hex");

const valid = crypto.timingSafeEqual(
  Buffer.from(expected),
  Buffer.from(req.headers["x-webhook-signature"] ?? ""),
);
```

```python
import hashlib, hmac

expected = "sha256=" + hmac.new(secret.encode(), raw_body, hashlib.sha256).hexdigest()
valid = hmac.compare_digest(expected, request.headers.get("X-Webhook-Signature", ""))
```

> Always compare with a constant-time function (`hash_equals`,
> `timingSafeEqual`, `compare_digest`) and always sign the **raw** body — a
> re-serialized JSON string will not match.

## Best practices

- Acknowledge fast: enqueue the payload and return `200` immediately.
- Deduplicate on `X-Webhook-Id` — retries deliver the same id again.
- Treat `data` as a snapshot; fetch `GET /reviews/{id}` for the current state.

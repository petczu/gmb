---
title: Introduction
description: Repunio REST API overview
---

# Repunio API

The Repunio API gives you programmatic access to your workspace's Google Business
Profile reviews: read locations, reviews and analytics, and publish replies.

> The API is available on the **Pro plan**. Keys are managed in the app under
> **Settings → API keys**.

## Base URL

```
https://YOUR-APP-DOMAIN/api/v1
```

All requests and responses are JSON. Send an `Accept: application/json` header.

## Quick start

1. In the app, open **Settings → API keys** and create a key with the scopes you
   need. Copy it right away — it is shown only once.
2. Call the API with the key as a Bearer token:

```curl
curl https://YOUR-APP-DOMAIN/api/v1/locations \
  -H "Authorization: Bearer ak_live_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" \
  -H "Accept: application/json"
```

```javascript
const res = await fetch("https://YOUR-APP-DOMAIN/api/v1/locations", {
  headers: {
    Authorization: "Bearer ak_live_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    Accept: "application/json",
  },
});
const { data } = await res.json();
```

```python
import requests

res = requests.get(
    "https://YOUR-APP-DOMAIN/api/v1/locations",
    headers={"Authorization": "Bearer ak_live_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"},
)
data = res.json()["data"]
```

## Endpoints at a glance

| Method | Path | Scope | Purpose |
|---|---|---|---|
| GET | `/locations` | `locations:read` | Connected locations with rating + goal |
| GET | `/reviews` | `reviews:read` | Reviews, filterable + paginated |
| GET | `/reviews/{id}` | `reviews:read` | Full review detail |
| POST | `/reviews/{id}/reply` | `reviews:reply` | Publish a public reply on Google |
| GET | `/stats` | `analytics:read` | Aggregate review analytics |

Prefer trying calls in the browser? Open the interactive
[API Reference](/docs/api-reference).

## Webhooks

Instead of polling, subscribe to signed webhooks for new reviews, published
replies, reached goals and detected anomalies — see
[Webhooks](/docs/webhooks).

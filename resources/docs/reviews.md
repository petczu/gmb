---
title: Reviews
description: List, read and reply to reviews
---

# Reviews

## List reviews

```
GET /api/v1/reviews
```

Scope: `reviews:read`. Newest first, paginated (Laravel-style `data` + `links` +
`meta`).

| Query param | Type | Meaning |
|---|---|---|
| `rating` | 1–5 | Only this star rating |
| `replied` | boolean | `true` = with a reply, `false` = without |
| `has_text` | boolean | `true` = written text, `false` = rating-only |
| `location_id` | integer | Single location |
| `from` / `to` | YYYY-MM-DD | Review date range |
| `per_page` | 1–100 | Page size (default 20) |
| `page` | integer | Page number |

```curl
curl "https://YOUR-APP-DOMAIN/api/v1/reviews?rating=5&from=2026-06-01&per_page=10" \
  -H "Authorization: Bearer ak_live_…" \
  -H "Accept: application/json"
```

<div class="response-tabs">
<div data-status="200" data-label="Success">

```json
{
  "data": [
    {
      "id": 42,
      "location_id": 1,
      "location": "GAME OVER Escape Rooms Vienna",
      "author": "Laura Rozic",
      "rating": 5,
      "text": "Suly war sehr hilfsbereit und freundlich.",
      "text_translated": null,
      "reply": "Hallo Laura, herzlichen Dank!",
      "reply_status": "published",
      "reply_source": "manual",
      "replied_at": "2026-06-14T09:12:00+00:00",
      "review_link": "https://search.google.com/local/reviews?...",
      "date": "2026-06-13T22:07:00+00:00"
    }
  ],
  "links": { "first": "…", "last": "…", "prev": null, "next": null },
  "meta": { "current_page": 1, "per_page": 10, "total": 1 }
}
```

</div>
</div>

## Get a review

```
GET /api/v1/reviews/{id}
```

Scope: `reviews:read`. Full detail, including the Google-translated text when
the review was written in another language. `404` when the id does not exist in
this workspace.

## Publish a reply

```
POST /api/v1/reviews/{id}/reply
```

Scope: `reviews:reply`. Publishes a **public, permanent** reply on Google and
stores it on the review (`reply_source: "api"`). Google allows one reply per
review — posting again overwrites the previous reply.

```curl
curl -X POST https://YOUR-APP-DOMAIN/api/v1/reviews/42/reply \
  -H "Authorization: Bearer ak_live_…" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"reply": "Thank you for the kind words!"}'
```

```javascript
await fetch("https://YOUR-APP-DOMAIN/api/v1/reviews/42/reply", {
  method: "POST",
  headers: {
    Authorization: "Bearer ak_live_…",
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify({ reply: "Thank you for the kind words!" }),
});
```

<div class="response-tabs">
<div data-status="200" data-label="Reply published">

```json
{
  "data": {
    "id": 42,
    "reply": "Thank you for the kind words!",
    "reply_status": "published",
    "reply_source": "api",
    "replied_at": "2026-07-03T10:00:00+00:00"
  }
}
```

</div>
<div data-status="422" data-label="Validation">

```json
{
  "message": "The reply field is required.",
  "errors": { "reply": ["The reply field is required."] }
}
```

</div>
</div>

> Publishing a reply also fires the `reply.published` [webhook](/docs/webhooks).

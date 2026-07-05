---
title: Stats
description: Aggregate review analytics
---

# Stats

## Get review stats

```
GET /api/v1/stats
```

Scope: `analytics:read`. Aggregates across the workspace, optionally filtered.

| Query param | Type | Meaning |
|---|---|---|
| `location_id` | integer | Single location |
| `from` / `to` | YYYY-MM-DD | Review date range |

```curl
curl "https://YOUR-APP-DOMAIN/api/v1/stats?from=2026-06-01&to=2026-06-30" \
  -H "Authorization: Bearer ak_live_…" \
  -H "Accept: application/json"
```

<div class="response-tabs">
<div data-status="200" data-label="Success">

```json
{
  "total": 18,
  "average_rating": 4.83,
  "distribution": { "1": 0, "2": 0, "3": 1, "4": 1, "5": 16 },
  "replied": 16,
  "reply_rate_percent": 89,
  "rating_only": 4,
  "new_this_month": 18
}
```

</div>
</div>

| Field | Meaning |
|---|---|
| `total` | Reviews matching the filters |
| `average_rating` | Average stars (null when no reviews) |
| `distribution` | Count per star rating, keys `1`–`5` |
| `replied` | Reviews that have a reply |
| `reply_rate_percent` | `replied / total`, rounded |
| `rating_only` | Reviews without written text |
| `new_this_month` | Reviews received since the 1st of the current month |

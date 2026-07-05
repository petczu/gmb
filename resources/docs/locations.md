---
title: Locations
description: List the workspace's connected business locations
---

# Locations

## List locations

```
GET /api/v1/locations
```

Scope: `locations:read`. Returns every connected Google Business Profile
location with its current rating, review count and monthly review goal.

```curl
curl https://YOUR-APP-DOMAIN/api/v1/locations \
  -H "Authorization: Bearer ak_live_…" \
  -H "Accept: application/json"
```

<div class="response-tabs">
<div data-status="200" data-label="Success">

```json
{
  "data": [
    {
      "id": 1,
      "name": "GAME OVER Escape Rooms Vienna",
      "address": "Operngasse 36, 1040 Wien",
      "rating": 4.9,
      "reviews_count": 312,
      "monthly_goal": 30
    }
  ]
}
```

</div>
<div data-status="403" data-label="Missing scope">

```json
{ "message": "This API key is missing the required scope: locations:read." }
```

</div>
</div>

| Field | Type | Notes |
|---|---|---|
| `id` | integer | Use as `location_id` in review/stat filters |
| `name` | string | Display name |
| `address` | string \| null | Formatted address |
| `rating` | number \| null | Current average rating on Google |
| `reviews_count` | integer | Total reviews on Google |
| `monthly_goal` | integer \| null | Review goal configured in Repunio |

---
title: Authentication
description: How to authenticate with the Repunio API
---

# Authentication

Every request needs a workspace API key in the `Authorization` header.

## Getting your key

Create a key in the app under **Settings → API keys** (Pro plan). Pick a name
and the scopes it should carry; optionally set an expiry (30 / 90 / 365 days).
The key looks like:

```
ak_live_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

The key is shown **once** when created — we only store a hash. If you lose it,
revoke it and create a new one.

## Using your key

```curl
curl https://YOUR-APP-DOMAIN/api/v1/stats \
  -H "Authorization: Bearer ak_live_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" \
  -H "Accept: application/json"
```

## Scopes

Each key carries an explicit set of scopes; a request outside them returns
`403`.

| Scope | Grants |
|---|---|
| `locations:read` | GET `/locations` |
| `reviews:read` | GET `/reviews`, GET `/reviews/{id}` |
| `reviews:reply` | POST `/reviews/{id}/reply` |
| `analytics:read` | GET `/stats` |

Scopes can be edited on an existing key at any time (the key itself does not
change). Revoking a key disables it immediately.

## Workspace scoping

A key belongs to exactly one workspace — every response is limited to that
workspace's data. For multiple workspaces, create a key in each.

## Failure modes

| Status | Meaning |
|---|---|
| `401` | Key missing, unknown, revoked or expired |
| `403` | Workspace is not on the Pro plan, or the key lacks the scope |

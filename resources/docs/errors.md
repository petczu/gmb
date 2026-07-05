---
title: Error Codes
description: HTTP statuses and error shapes
---

# Error Codes

Errors are JSON with a human-readable `message`; validation errors add an
`errors` map.

```json
{ "message": "Invalid or missing API key." }
```

```json
{
  "message": "The reply field is required.",
  "errors": { "reply": ["The reply field is required."] }
}
```

## Statuses

| Status | When | What to do |
|---|---|---|
| `401 Unauthorized` | Key missing, unknown, revoked or expired | Check the `Authorization: Bearer ak_live_…` header; create a new key if revoked/expired |
| `403 Forbidden` (plan) | Workspace is not on the Pro plan | Upgrade the workspace |
| `403 Forbidden` (scope) | Key lacks the required scope | Edit the key's scopes in Settings → API keys |
| `404 Not Found` | Resource id does not exist in this workspace | Ids are workspace-scoped; list endpoints to discover them |
| `422 Unprocessable Entity` | Invalid query params or body | Fix the fields listed in `errors` |
| `500 Internal Server Error` | Something failed on our side | Retry with backoff; contact support if persistent |

## Common 403 messages

```json
{ "message": "API access requires the Pro plan." }
```

```json
{ "message": "This API key is missing the required scope: reviews:reply." }
```

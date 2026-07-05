---
title: Changelog
description: Repunio API changes
---

# Changelog

## 2026-07-03

- Public API documentation launched at `/docs`, interactive reference at `/docs/api-reference`.

## 2026-07-01

- **API v1 released**: `GET /locations`, `GET /reviews`, `GET /reviews/{id}`, `POST /reviews/{id}/reply`, `GET /stats`. Scoped API keys (Settings → API keys, Pro plan).
- **Webhooks released**: `review.created`, `reply.published`, `goal.reached`, `anomaly.detected` — HMAC-SHA256 signed, retried with backoff, delivery log with manual resend.
- **MCP server released**: connect Claude or any MCP client with just the workspace endpoint URL (OAuth on first request; read-only by default, opt-in write).

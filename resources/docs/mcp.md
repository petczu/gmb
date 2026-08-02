---
title: MCP Server
description: Connect Claude or any MCP client to your workspace
---

# MCP Server

Repunio ships a built-in [Model Context Protocol](https://modelcontextprotocol.io)
server, so an AI assistant (Claude, or any MCP-capable client) can work with
your reviews directly: ask "how did we do this month?", "which reviews are
still unanswered?" or "summarize what guests say about the staff" — and it
answers from your real workspace data.

> MCP is available on the **Pro plan**. Your endpoint is shown in the app under
> **Settings → MCP**.

## Connecting

There is one endpoint for everyone:

```
https://YOUR-APP-DOMAIN/mcp
```

No token to copy, and no workspace id in the URL. Authentication is OAuth 2.1:
paste the URL into your client (e.g. claude.ai → Settings → Connectors →
**Add custom connector**, leave Client ID/Secret empty — the client registers
itself), and on first use you will be redirected to sign in and approve access.
Access is tied to your user account.

### Choosing a workspace

If you belong to more than one Pro workspace, the approval screen lets you pick
which workspace this connection should use; the choice is remembered for that
connector. With a single Pro workspace there is nothing to choose, it is used
automatically. To point an existing connector at a different workspace,
reconnect it (remove and re-add the connector, or sign out and back in) and pick
the other workspace when approving.

## Tools

| Tool | What it does |
|---|---|
| `list-locations-tool` | Connected locations with rating, review count and monthly goal |
| `list-reviews-tool` | Reviews, newest first, with filters (rating, replied, text, location, dates) |
| `get-review-tool` | Full detail of one review, incl. translated text |
| `review-stats-tool` | Totals, average rating, star distribution, reply rate |
| `reply-to-review-tool` | Publish a public reply on Google — **only when write access is enabled** |

## Read-only by default

Out of the box the server exposes read tools only. Publishing replies through
MCP is a per-workspace opt-in: **Settings → MCP → "Allow replying to reviews
over MCP"**. While it is off, the reply tool is not even advertised to the
client. Turn it on only if you are comfortable with a connected assistant
posting public replies on your behalf.

## Notes

- One endpoint for everyone; the server resolves the workspace from your
  signed-in account (the one you picked when approving, or your only Pro one).
- Revoking is instant: sign out the connector in your client, or remove the
  user's workspace membership.
- Requests are rate-limited (60/minute).

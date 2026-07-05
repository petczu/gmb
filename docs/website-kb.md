# Repunio — Website Knowledge Base

> Purpose: attach this file to your own prompt when generating the public marketing
> website (Lovable / v0 / Claude / any builder). Everything below is FACTUAL and
> derived from the shipped product. Items marked `TODO:` must be filled in by the
> owner before publishing. Do not let the site generator invent numbers, customer
> counts, testimonials or features that are not listed here.

---

## 1. Product in one paragraph

**Repunio** is a review-management platform for Google Business Profile. It pulls
all reviews from one or many locations into a single inbox, replies to them
manually or automatically with AI, tracks review-collection goals, detects
anomalies (negative streaks, suspicious spikes, stalled locations), and turns
everything into branded, AI-narrated performance reports that can be emailed on a
schedule. Built for local businesses, multi-location brands and the agencies that
manage them.

- Category: Local SEO / reputation management SaaS
- Platform: web app (responsive), English + German UI
- AI: reply generation and report narratives powered by Claude (Anthropic)
- Trial: **14-day free trial, no credit card required**
- Pricing model: **per location, per month**

## 2. Audience & pains

| Audience | Pain | Repunio answer |
|---|---|---|
| Single-location local business (restaurant, escape room, salon, clinic…) | No time to answer reviews; misses new ones; rating drifts down unnoticed | One inbox, AI replies in the owner's tone, alerts when something goes wrong |
| Multi-location brand | No overview across locations; inconsistent reply quality | All locations in one workspace, per-location goals, comparison reports |
| Marketing agency | Reporting eats hours; clients want proof of work | White-label PDF reports on a schedule, client access, per-workspace branding |

## 3. Feature inventory (marketing-ready)

### Reviews inbox
- Every Google review from every connected location in one place; automatic sync.
- Filter by rating, location, reply status, review text vs rating-only, date range; full-text search.
- Original + Google-translated text shown separately.
- One-click manual reply, or "Generate with AI" draft you can edit before publishing.

### AI replies & automations
- **AI agents**: describe your business once (or let AI draft the persona from your website), pick a tone — replies sound like you, not a bot.
- **Auto-reply rules**: instant publish or human-approval queue, filtered by rating; organic timing so replies don't look machine-gunned.
- Monthly AI-reply allowance per plan, topped up with pay-as-you-go credits (custom amount, volume discount, optional auto-recharge). Transparent **credits usage log** (every AI call with model + cost).

### Reports (the agency killer feature)
- Block-based report builder: pick a preset (Standard / Full / Staff bonus / Compliance) or toggle 12 blocks individually: KPIs at a glance, AI executive summary, what customers talk about, staff mentions & bonus table, collection cadence & spam-risk, themes & sentiment, response performance, rating distribution, volume over time, review highlights, AI recommendations, methodology.
- AI narrative in English or German; owner-defined AI instructions (e.g. staff roster with nicknames) applied to every report.
- **Staff mentions**: AI finds employees praised in reviews, merges name variants (Suly = Suleyman), computes each person's share — ready-made bonus table.
- Download as PDF, share via public link (optional password + expiry), or email automatically on a weekly/monthly schedule.
- **White label** (Pro): your logo + brand color instead of ours.

### Goals, coaching & alerts
- Monthly review goals per location, progress tracking.
- Weekly coaching emails with rotating, business-category-specific tips on collecting more reviews; celebration email when the goal is hit.
- Anomaly alerts: negative streak (3+ low ratings in 72h), volume spike (possible spam), stalled location (14+ days silent), rating drop.
- Notification routing: decide per email type who receives it (roles, individual members, notification-only guests).

### Team & agency
- Multi-workspace with one login and a workspace switcher; invite members with roles (Owner / Admin / Member), custom roles on Pro.
- Per-member location access restrictions.
- Guest contacts: receive reports/notifications without a login.
- GDPR: workspace deletion with 30-day grace period; 2FA (authenticator + email codes); Google sign-in.

### Developers (Pro)
- **REST API v1**: locations, reviews, stats, publish replies — scoped API keys (reviews:read, reviews:reply, locations:read, analytics:read), key expiry, last-used tracking.
- **Webhooks**: review.created, reply.published, goal.reached, anomaly.detected — HMAC-SHA256 signed, retried with backoff, full delivery log with resend.
- **MCP server**: connect Claude (or any MCP client) to your workspace with just a URL — OAuth on first request, read-only by default, opt-in write access. Ask your AI assistant "how did we do this month?" and it answers from your real reviews. *(Genuine differentiator — few competitors have this.)*

## 4. Plans & pricing (per location / month)

| | **Starter €12** | **Growth €24** | **Pro €49** |
|---|---|---|---|
| Reviews inbox, manual replies, basic reports | ✓ | ✓ | ✓ |
| AI replies / month | 20 | 250 | 1,500 |
| AI reports / month | 4 | 20 | 50 |
| Scheduled reports, period comparison | — | ✓ | ✓ |
| Full automations | — | ✓ | ✓ |
| White label, custom roles, client access | — | — | ✓ |
| REST API + webhooks + MCP | — | — | ✓ |

- 14-day free trial on every plan, no card required. Monthly or yearly billing (Stripe).
- AI overage: buy credits at a custom quantity; volume discount above a threshold; optional auto-recharge.
- `TODO:` yearly discount % (depends on configured Stripe prices).

## 5. Brand & design tokens

- Name: **Repunio** (never abbreviate; the brand name must NOT appear in technical identifiers, but on the site it's the product name).
- Primary color: `#2d19ec` (UI primary; buttons often rendered `#1800ff`); report accent `#455AF2`. Star/rating gold stays conventional.
- Logo: full wordmark + icon-only variants, light + dark (`public/logo/repunio-full-light.png` etc.). `TODO:` export web-ready SVG/PNG set for the site.
- Typography in app: Inter — safe default for the site.
- Look: clean, white, rounded-xl cards, generous whitespace (Filament-like). The site should feel like the app.

## 6. Voice & copy rules (IMPORTANT — feed to the generator verbatim)

- Write like a human: **no em dashes or en dashes** (use commas/periods), no AI cliches (vibrant, testament, underscore, pivotal, landscape, delve, leverage, robust, seamless, empower), no rule-of-three padding, no generic upbeat closers.
- Concrete over abstract: "reply to every review in your own tone" beats "revolutionize your reputation".
- Both languages: EN + DE versions of all copy (informal "du" in German — the app uses du-form).
- Never invent: customer counts, logos, testimonials, ratings, SLAs, integrations beyond Google Business Profile, or security certifications. If a section needs social proof, leave a clearly marked placeholder.

## 7. Site structure (suggested)

1. **Home** — hero (one-liner + product screenshot + "Start free trial"), pain/solution, 3 pillars (Inbox & AI replies / Reports / Goals & alerts), differentiator strip (MCP + API + white label + EU/GDPR + DE+EN), pricing teaser, FAQ, footer.
2. **Features** — one page or per-pillar pages using §3.
3. **Pricing** — table from §4, credits explained, trial CTA, plan FAQ.
4. **For agencies** — white label, scheduled client reports, client access, multi-workspace.
5. **Developers** — API, webhooks, MCP (code snippet: `Authorization: Bearer ak_live_…`; webhook signature `X-Webhook-Signature: sha256=HMAC_SHA256(body, secret)`; MCP endpoint `https://app…/mcp/{workspace}`).
6. **Legal** — the app already serves finished Terms / Privacy / Cookie pages at `/terms`, `/privacy`, `/cookies` (EN+DE); link to them or mirror the content.

## 8. CTAs & URLs

- Primary CTA everywhere: **Start 14-day free trial** → app registration (`TODO:` final app URL; currently `https://review.freizeitlab.team/register`).
- Secondary: **Sign in** → `/login`. Optional: "Book a demo" → `TODO:` calendar link.
- `TODO:` public site domain (repunio.com?), support email, imprint/legal entity (required in DACH!), social links.

## 9. FAQ raw material

- **How do reviews get in?** Connect your Google Business Profile in two clicks (OAuth). Reviews sync automatically, historical ones included.
- **Will AI replies sound robotic?** You define the persona and tone; the AI drafts, you can approve every reply before it goes out (or let rules publish instantly).
- **Do I need a credit card for the trial?** No. 14 days, full plan features, card only when you subscribe.
- **What happens when I hit my AI limit?** Nothing breaks — you can buy credits as you go or wait for the monthly reset.
- **Can my clients see the reports?** Yes: emailed PDFs on a schedule, or a share link (optionally password-protected and expiring), with your branding on Pro.
- **Where is my data?** `TODO:` hosting location/provider statement. GDPR deletion with 30-day grace is built in.
- **Which review platforms?** Google Business Profile today. `TODO:` roadmap statement if more are planned.

## 10. Screenshots to prepare (`TODO:`)

Reviews inbox with filters · AI reply generation modal · Report builder with block toggles · Finished PDF report (staff bonus table!) · Goals/coaching email · Webhooks/API keys settings · MCP settings page. Take at ≥1440px, light theme, demo workspace (never real client data).

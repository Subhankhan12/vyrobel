# Vyrobel — Master Plan (AI-Native Hospitality Operating System)
> **One platform to run hotels, aparthotels, serviced apartments, villas, and multi-unit rentals — sell everywhere, automate operations with AI agents, and grow direct bookings.**


This is the complete build plan. It covers every capability from your brief with nothing dropped, but it is organized around **what to build, in what order, and what to rent instead of build** — because the failure mode for a product this ambitious is not "missing a feature," it's "tried to build all of it at once and never shipped."

It is written for how you actually build: Laravel multi-tenant, phased Claude Code prompts with Phase-0 audit gates, extend-don't-duplicate, no faked data, stop-and-approve between phases.

---

## 0. The core strategic bet (read this first)

You cannot out-feature Mews or Cloudbeds head-on, and you don't need to.

- Mews (2026): ~$2.5B valuation, $410M raised, 12,500 properties, betting everything on autonomous agentic AI.
- Cloudbeds (2026): $250M raised, ~27,000 customers, "Cloudbeds Signals" foundation AI model for demand forecasting.
- Apaleo: smaller, API-first, strong with serviced apartments — the closest to your hybrid niche, which tells you the niche is real *and* not yet owned.

**Your winning wedge — the one thing to be undeniably best at:**

> **The hybrid operator.** One business running a *mix* of hotel rooms, serviced apartments, villas, and scattered multi-unit rentals — who also needs real owner accounting (payouts, commissions, statements).

This is a genuine seam in the market:
- Hotel PMSs (Mews, Cloudbeds, Opera) are weak at apartment/owner-payout accounting.
- Vacation-rental tools (Guesty, Lodgify, OwnerRez) are weak at hotel-grade front desk, folios, group bookings, and city-tax handling.
- The operator who has *both* is forced to run two systems. Be the one system that does both well.

**Your structural advantage — why a from-scratch build can win against funded incumbents:**

Everyone is racing toward agentic AI in 2026. The blocker, in the incumbents' own words, is the lack of a unified real-time data layer — agents can't act on data that's scattered across acquired, bolted-together systems. **You are building one clean schema from day one.** Design the data model so a single AI agent can see reservation + payment + housekeeping + messaging + owner context in one query, and your agents work natively where the incumbents have to do years of integration plumbing first. **This unified data model is the spine of the entire product. Protect it.**

**Your operating principle — rent the hard infrastructure, build the differentiated experience:**

| Brutally hard, low differentiation → **RENT** | High differentiation → **BUILD** |
|---|---|
| OTA channel connectivity (Channex) | The unified data model / "semantic layer" |
| Card processing & PCI (Stripe + Channex proxy) | The AI agent orchestration + guardrails |
| WhatsApp transport (Meta Business API / Twilio) | The hybrid hotel+apartment+owner data design |
| Email/SMS delivery (Postmark/SendGrid, Twilio) | The website builder + booking engine UX |
| Smart-lock hardware protocols (per-vendor APIs) | The guest PWA & staff PWA experience |
| Maps/geocoding, address validation | The automation builder (no-code rules) |

This is how a small team competes with companies that have hundreds of engineers: don't re-solve what's already a commodity.

---

## 1. Market reality (2026 research synthesis)

- **The market is real and growing.** Estimates vary by definition, but hotel/hospitality management software sits roughly in the **$5–9B** range and grows **~7–10% CAGR**. Cloud deployment is the fast-growing segment (~12% CAGR). Independent and SMB properties increasingly treat a cloud PMS as a necessity, and onboarding time has compressed from months to weeks — which is the buying behavior you can exploit.
- **Asia-Pacific and emerging markets are the fastest-growing regions.** North America is the largest but most saturated. A beachhead in a less-contested geography (e.g. Europe / MENA / Central Asia) is a smart wedge — the global incumbents are thinner there.
- **"Agentic AI" is the defining 2026 narrative.** Mews raised $300M specifically for it; Cloudbeds shipped Signals; Choice Hotels moved AI into production operations. The consensus: the first real wins are in *back-office automation* (housekeeping dispatch, messaging, pricing), not flashy guest chatbots.
- **AI guest messaging is now table stakes, not a differentiator.** A large field of bolt-on tools (Canary 20,000+ hotels, Duve, HiJiffy, Asksuite, Myma, Viqal, Conduit) already does this and integrates with every major PMS. Implication: you must have it, but it won't win you customers alone. The win is in *operational* agents wired into your unified data.
- **Voice (phone) AI is an emerging gap.** 40–60% of guest interactions are still phone calls; most tools are chat-only. A later differentiator, not an MVP item.
- **WhatsApp via the official Meta Business API is universal.** Every serious competitor supports it.

---

## 2. Positioning, ICP, and go-to-market

**Positioning line:**
> "The AI-powered operating system for hotels, aparthotels, serviced apartments, and multi-unit rentals — run everything, sell everywhere, and let AI handle the busywork."

**Ideal Customer Profile (beachhead):**
- Independent operators and small property managers, **10–80 units**, running a *mix* of room types and apartments.
- Currently juggling spreadsheets + a basic channel manager + a separate messaging tool, or stuck on legacy on-prem software.
- Price-sensitive but feeling acute pain around double bookings, owner statements, and manual guest messaging.
- In a geography where incumbents have weak local presence and weak local-OTA / local-payment / local-tax support.

**Expansion path (don't start here):**
- Small hotel chains and multi-property groups (Pro tier).
- White-label resellers / agencies (Enterprise tier).
- Larger serviced-apartment operators.

**Wedge-to-platform sequence:** Win the hybrid SMB operator with *PMS + booking engine + channel sync + owner accounting + AI messaging*, then expand outward to website builder, full agent suite, marketplace, and white-label.

---

## 3. Architecture

### 3.1 Multi-tenant SaaS foundation
- **Tenant model:** single application, tenant-scoped data. In Laravel, use a `tenant_id` (or `organization_id`) on every business table with a global scope that enforces isolation automatically, plus a hard-fail safeguard so a missing scope throws rather than leaks. (Single-DB-with-tenant-column is the pragmatic default; reserve schema-per-tenant or DB-per-tenant for large Enterprise customers who demand it.)
- **Hierarchy:** `Organization` → `Property` → `Building/Block/Floor` → `Unit/Room` → `RoomType/UnitGroup`. Support single-property, multi-property, chain, and property-manager modes from the same hierarchy.
- **Plan / billing layer:** subscriptions, trials, usage limits per plan (units, messages, AI calls, storage), add-on marketplace entitlements, dunning. Stripe Billing for tenant subscriptions.
- **Per-tenant config:** white-label branding, custom domain, locale, currency, country tax rules, API keys, webhooks, feature flags.
- **Platform operations:** super-admin panel, audit logs, activity logs, impersonation (with logging), data import/export, backup/restore, OTA credential vault (encrypted).

### 3.2 The unified data model (your moat — design carefully)
Everything an agent might need must be reachable from one consistent core. Minimum core entities:

`Reservation`, `Stay`, `Guest`, `GuestProfile`, `Folio/Invoice`, `Payment`, `Charge`, `Unit`, `RoomType`, `RatePlan`, `Availability`, `RateCalendar`, `Restriction`, `Channel`, `ChannelMapping`, `Message`, `Conversation`, `Task`, `HousekeepingStatus`, `MaintenanceTicket`, `Owner`, `OwnerStatement`, `Payout`, `Review`, `AutomationRule`, `AuditEvent`, `Document` (IDs/registration cards).

Two design rules that make the AI layer possible:
1. **Every state change emits a domain event** (`reservation.confirmed`, `payment.failed`, `unit.checked_out`, `review.received`, `ota.sync_failed`, …). These events drive the automation builder *and* feed the agents. This is your "semantic layer."
2. **One reservation object serves all channels.** A direct booking, an OTA import, and a walk-in are all `Reservation` rows differing only by `source` — so conflict detection, folios, and agents work uniformly. Never create parallel structures per channel (consistent with your extend-don't-duplicate principle).

### 3.3 Tech stack (aligned to how you build)
- **Backend:** Laravel (your stack). Livewire + Blade + Tailwind for the admin/CRM web app.
- **Async/queues:** Laravel Queues + Horizon (Redis) — essential for OTA sync, messaging, and agent jobs. The channel sync **must** be queue-based with retries and a dead-letter path.
- **Realtime:** Laravel Echo + WebSockets (Reverb/Pusher) for the unified inbox, calendar updates, and live dashboards.
- **PWAs:** Guest PWA and Staff PWAs as installable web apps (offline-capable for the housekeeping/maintenance flows; service workers + background sync). One codebase per audience, not native apps initially.
- **AI:** your existing Groq-based LLM client, reused across agents. Add a tool/function-calling layer and a strict guardrail/approval engine (Section 7).
- **Payments:** Stripe (+ Stripe Connect for owner payouts), Channex PCI proxy for OTA card tokens.
- **Channel manager:** Channex (white-label API) as the launch path.

### 3.4 Buy-vs-build summary (the decisions)
- **Channel manager → BUY (Channex).** Launch with Booking.com/Airbnb/Expedia/Vrbo + 50 OTAs in weeks. Build your internal ARI/mapping layer to talk to Channex, not to each OTA. Revisit direct connections only at real scale.
- **Payments → BUY (Stripe + Channex proxy).** Never store raw cards.
- **Messaging transport → BUY (Meta WhatsApp Business API + Twilio/SendGrid).** You build the unified inbox + AI on top.
- **Smart locks → BUY per-vendor APIs (Nuki, TTLock, Salto, August/Yale, etc.).** Abstract behind one internal `LockProvider` interface.
- **Website hosting/CDN, maps, email → BUY.**
- **Everything in the "BUILD" column of Section 0 → BUILD.**

---

## 4. The complete capability map

Everything from your brief, grouped by domain. Phase tags (`P1`–`P5`) map to the roadmap in Section 8 so you can see what's MVP vs later.

### A. Core PMS — Property & inventory `P1`
- Property types: hotel, aparthotel, serviced apartment, apartment building, individual apartment, villa, hostel, guest house, resort, multi-unit portfolio, and **mixed** (rooms + apartments + villas in one property).
- Property setup: name, brand, address, GPS, type, star rating, amenities, all policies (check-in/out, cancellation, child, pet, smoking, deposit, damage), city/tourism tax, fees (cleaning, service, extra-guest), breakfast/parking rules, min/max stay, long-stay discounts.
- Inventory: room types, individual rooms, apartment units, villas, dorm beds, connected rooms, multi-bedroom units, sellable unit groups, virtual room types, parent-child inventory, floor/building/block management, unit amenities/photos, unit status (clean/dirty/inspected, out-of-order, out-of-service, maintenance), owner assignment per unit.

### B. Reservations & calendar `P1`
- Booking creation: manual, walk-in, direct-website, OTA import, corporate, group, monthly/long-stay, day-use, hourly.
- Lifecycle: room assignment, room move, split booking, extend stay, early check-in, late checkout, no-show, cancellation, refund, partial refund, modification history.
- Reservation data: guest/internal notes, source tracking, deposit/payment status, folio/invoice, guest documents, preferences, blacklist/risk flag, VIP flag.
- Calendar views: daily timeline, weekly, monthly, **drag-and-drop room rack**, availability grid, channel availability grid, housekeeping board, revenue calendar, owner calendar, staff task calendar.
- UI must-haves: color-coded statuses, OTA source icons, payment badges, **overbooking / conflict / maintenance-block warnings**, smart search (guest, phone, OTA ID, email, booking ID).

### C. Distribution / channel manager `P2` (via Channex)
- Internal ARI engine (Availability, Rates, Inventory) that pushes/pulls through Channex.
- Rate-plan mapping, room mapping, OTA booking import/modify/cancel.
- **Overbooking prevention via locking + sync queue + retry + conflict resolver.**
- Mapping health dashboard, human-readable sync error log, emergency inventory lock.

### D. Booking engine `P2`
- Live availability & pricing, room/apartment filters, date & occupancy search, multi-room booking.
- Add-ons (breakfast, transfer, cleaning fee, early/late, extra bed, tours).
- Promo codes, gift vouchers, secure payment (pay now / pay later / deposit / partial), local tax breakdown, confirmation email, guest portal link.
- Abandoned-booking capture.

### E. White-label website builder `P3`
- Themes: luxury, boutique, aparthotel, apartment-rental, villa/resort, hostel, minimal, dark premium, family, business.
- Builder: drag-and-drop sections, homepage/room/property pages, gallery, amenities, offers/packages, blog, local guide, FAQ, policies, contact, map, reviews, WhatsApp button.
- SEO: meta, schema markup, sitemap, OG images, multi-language pages.
- Marketing pixels & consent: GA, GTM, Meta/TikTok pixels, cookie consent.
- Conversion: direct booking widget, promo codes, gift cards, abandoned-booking recovery.
- Custom domain + default subdomain, full white-label branding.
- *(Google Hotel Center / free booking links / metasearch: `P4`.)*

### F. Unified inbox & guest messaging `P3`
- One inbox across WhatsApp, email, SMS, and OTA threads (Booking.com, Airbnb, Expedia).
- Guest + reservation sidebar, internal staff notes, saved templates, smart tags, auto-translation.
- AI suggested replies, sentiment detection, SLA timers, escalation rules, AI→human handoff.
- Convert conversation → task / maintenance ticket / upsell.

### G. Guest PWA `P3`
- Book, manage reservation, upload passport/ID, digital check-in, sign registration card, pay deposit/balance, request invoice, chat with property.
- Stay tools: check-in instructions, smart-lock code, Wi-Fi details, request housekeeping/maintenance, order room service, book breakfast/transfer/tours, extend stay, request late checkout, local guide, report issue, review, join loyalty, push notifications.
- Apartment-specific: self-check-in guide, building entrance & parking instructions, house/trash rules, appliance guide, emergency contacts, damage report, checkout checklist.

### H. Staff PWAs `P2`–`P3`
- **Front desk** `P2`: arrivals/departures/in-house, check-in/out, payment collection, ID upload, room move, key issue, notes, incidental charges, refund request, no-show.
- **Housekeeping** `P2`: cleaning list, clean/dirty/inspected, priority rooms, stayover/departure/deep cleaning, linen tracking, photo proof, checklists, lost & found, maintenance reporting, supervisor inspection.
- **Maintenance** `P3`: tickets, urgency, unit affected, photos, parts, vendor assignment, cost tracking, completion proof, recurring/preventive maintenance.
- **Manager** `P3`: occupancy, revenue, ADR, RevPAR, arrivals/departures, payment issues, staff performance, complaints, OTA sync errors, AI alerts.

### I. Owner portal & accounting `P3` (your wedge — make it excellent)
- Owner view: their units only, calendar, occupancy, revenue, expenses, cleaning/maintenance charges, OTA commissions, management fees, net payout, monthly statement, documents, invoices, maintenance photos, owner block dates, performance comparison, AI monthly summary.
- Manager controls: assign units to owners, set commission %/fixed fee, payout rules, deduct expenses, approve statements, pay owners (Stripe Connect), export statements.
- **This is the feature most hotel PMSs do badly. Doing it well is a primary reason a hybrid operator switches to you.**

### J. Payments & finance `P1`–`P2`
- Online payment, card authorization, **security/incidental deposit holds**, split payment, multi-currency, partial payment, pay-at-property, refunds/partial refunds, chargeback tracking, payment links, **tokenized saved cards** (never raw).
- Invoicing: tax invoices, city/tourism tax, cleaning/damage fees.
- Owner payouts, commission tracking, OTA commission tracking, revenue by channel, reconciliation, daily cash report, shift closing, cash drawer, expense management, P&L, export to accounting software.
- Gateways: Stripe (primary), PayPal, Square, regional gateways (e.g. local bank transfer / manual), and a clean adapter to add local processors per market.

### K. CRM & loyalty `P4`
- Guest profiles, stay/spend history, preferences, nationality, birthday, tags, VIP, complaint history, favorite room, marketing consent.
- Campaigns (email/WhatsApp), segments, loyalty points, promo codes, corporate & travel-agent accounts, repeat-guest discounts, referral rewards.
- Segments: families, business, long-stay, couples, high-spend, OTA vs direct, cancelled, abandoned, good-reviewers, complainers.
- *(Use an immutable, append-only points-ledger for the loyalty engine — never mutate balances directly.)*

### L. Revenue management `P4`
- Rate plans, seasonal/weekend/occupancy/LOS pricing, last-minute & early-bird, refundable/non-refundable, corporate/promo/derived rates.
- Restrictions: stop-sell, min/max stay, CTA/CTD.
- Competitor rate tracking, demand & local-event calendar, **AI price suggestions with approval**, auto-pricing rules with guardrails.
- KPIs: occupancy, ADR, RevPAR, GOPPAR (later), booking window, cancellation/no-show rate, revenue by channel & room type, direct-vs-OTA, ALOS, conversion, abandonment.

### M. Reputation `P4`
- Pull OTA reviews where possible, manual imports, Google reviews, review dashboard, sentiment analysis, **AI reply drafts**, negative-review alerts, complaint-theme detection, staff insights, review-request automation, QR review cards, post-stay email, internal satisfaction survey.

### N. Automation builder (no-code) `P3`
- Trigger → condition → action engine driven by your domain events.
- Conditions: booking source, guest country, stay date, payment status, room type, property, occupancy level, channel, guest tag, message sentiment, review rating, task status.
- Actions: email/WhatsApp/SMS, create task, assign staff, change status, apply discount, request approval, trigger webhook, notify manager, generate report.
- Ship with a library of prebuilt recipes (welcome message, pre-arrival ID request, check-in guide, payment-link chase, review request, low-occupancy promo suggestion, VIP front-desk alert, OTA-sync-fail alert).

### O. AI agent layer `P3`–`P5` (Section 7 details the architecture)
The ten agents from your brief — Reservation, Guest Concierge, Revenue Manager, Housekeeping Coordinator, Maintenance, Review/Reputation, Marketing, Finance, Owner Report, Compliance — built progressively, all sharing one tool layer and one guardrail engine.

### P. Smart locks & self-check-in `P4`
- Digital check-in + ID verification + pre-arrival payment + deposit hold → stay-scoped lock code, auto-sent on approval; emergency/staff/cleaner codes; code audit logs; self-checkout checklist.

### Q. Marketplace & integrations `P5`
- Open API + webhooks for tenants; app marketplace.
- Integrations: Stripe, PayPal, Google Calendar/Analytics/GTM, Meta/TikTok pixels, WhatsApp Business, Twilio, Mailchimp/SendGrid, smart locks, key-card systems, government guest-reporting systems where applicable.

### R. Security & compliance `P1` (baked in, not bolted on)
- Multi-tenant data isolation, RBAC + field-level permissions, 2FA, staff device sessions, IP/session logs, audit logs, encrypted sensitive fields, secure file storage with signed URLs, data retention rules, GDPR export/delete tools, consent management, **PCI-safe design (tokenization only)**, webhook signature validation, rate limiting, API tokens, OTA credential vault, backup encryption.

### S. Analytics dashboards `P2`–`P4`
- **Manager:** occupancy, revenue, arrivals/departures, in-house, pending payments, housekeeping progress, maintenance, OTA sync errors, direct-conversion, AI alerts.
- **Revenue:** ADR, RevPAR, occupancy, revenue by channel, cancellation/no-show, booking pace, forecast, competitor comparison, promo performance.
- **Owner:** gross revenue, expenses, net payout, occupancy, ADR, maintenance/cleaning cost, statement.
- **SaaS super-admin:** active tenants, MRR/ARR, churn, trial conversion, failed payments, OTA/AI/storage/message usage, support tickets, feature adoption.

---

## 5. Roles & permissions matrix

RBAC with role inheritance + per-field overrides. Scope every permission to `Organization` and optionally `Property`/`Unit`.

| Role | Scope | Core access |
|---|---|---|
| **SaaS Super Admin** | Platform | Everything; tenant management, billing, impersonation (logged), feature flags |
| **Property Owner** (tenant admin) | Org | Full tenant config, all properties, billing, staff, integrations |
| **Group / Chain Admin** | Org (multi-property) | All properties, cross-property reports, staff & rates across portfolio |
| **Hotel Manager** | Property | Operations, reservations, rates, staff, reports for assigned property |
| **Revenue Manager** | Property/Org | Rates, restrictions, channel, revenue analytics; no guest PII edits |
| **Front Desk** | Property | Check-in/out, reservations, payments, folios, room moves; no rate strategy |
| **Reservation Agent** | Property | Create/modify bookings, guest comms; no financial settings |
| **Housekeeping** | Property | Cleaning board, unit status, linen, lost & found; no financials/PII beyond name/room |
| **Maintenance** | Property | Tickets, vendors, costs; no guest financials |
| **Accountant** | Property/Org | Folios, invoices, reconciliation, payouts, exports; read-only on operations |
| **Marketing Manager** | Org | Website, campaigns, CRM segments, reputation; no payments |
| **Guest Support Agent** | Property/Org | Unified inbox, tickets; limited PII; no rate/financial changes |
| **External Owner / Investor** | Own units only | Owner portal: their calendar, statements, payouts, documents — nothing else |
| **Guest** | Own reservation | Guest PWA: their booking, check-in, payments, requests |

Add **custom roles** (clone + edit permissions) for Pro/Enterprise tenants.

---

## 6. The PWA strategy

Three audiences, three installable PWAs sharing the API and design system:
1. **Guest PWA** — booking + digital check-in + in-stay services + lock code. Must work great on a phone with no install required, install optional.
2. **Staff PWA** — role-aware (front desk / housekeeping / maintenance / manager views switch by permission). **Offline-first** for housekeeping and maintenance (mark rooms, attach photos, sync when back online via service-worker background sync).
3. **Owner PWA** — lightweight read-mostly portal (statements, calendar, payouts).

Native iOS/Android apps are a later consideration; PWAs get you to market far faster and avoid app-store overhead. Push notifications work on modern PWAs.

---

## 7. The AI / agentic layer (architecture + safety)

This is where you either build a genuine moat or a liability. The whole industry's lesson in 2026: **agents are only as good as the unified data they sit on, and they must never act dangerously without guardrails.**

### 7.1 Architecture
- **One shared tool layer.** Define a registry of typed tools the agents can call: `get_availability`, `quote_price`, `get_reservation`, `create_task`, `draft_reply`, `propose_rate_change`, `summarize_owner_month`, `flag_payment_anomaly`, etc. Each tool is a thin, audited Laravel service method over the unified data model.
- **Reuse your Groq client** across all agents; agents differ by system prompt, allowed tools, and scope — not by separate infrastructure.
- **Context = the semantic layer.** Because your data is unified, an agent prompt can be assembled with exactly the reservation/payment/housekeeping/owner context it needs, in one place. This is the thing incumbents struggle to do.
- **Everything an agent does is an `AuditEvent`** with inputs, outputs, tokens, and (if applicable) the human who approved it.

### 7.2 The guardrail / approval engine (non-negotiable)
Classify every agent capability into three tiers:

| Tier | Examples | Rule |
|---|---|---|
| **Auto (safe)** | Draft a reply, answer Wi-Fi/policy questions, summarize, categorize a ticket, *suggest* a price | Agent acts directly |
| **Approve (sensitive)** | Send a guest message, apply a discount, change a rate, assign staff, create a refund *request* | Agent proposes; human one-click approves |
| **Forbidden by default** | Cancel a booking, issue a refund, move money, delete data, share PII externally | Hard-blocked unless explicitly enabled per-tenant with extra confirmation |

Agents propose; humans dispose, for anything that touches money, inventory cancellation, or PII. This is exactly the "AI must not act dangerously" requirement from your brief, made concrete.

### 7.3 The ten agents (built progressively)
- **A. Reservation Agent** `P3` — availability, quotes, date changes, upsell offers, cancellation explanations, payment reminders, confirmations, no-show follow-up. *(Approve tier for sending; Auto for drafting.)*
- **B. Guest Concierge Agent** `P3` — Wi-Fi, check-in, local recs, transfers, house rules, late-checkout requests, complaint routing.
- **C. Revenue Manager Agent** `P4` — dynamic pricing *suggestions*, low/high-occupancy alerts, competitor monitoring, event/weekend pricing, min-stay recs, OTA performance. *(Suggest-only by default; auto-apply only behind explicit opt-in + rules.)*
- **D. Housekeeping Coordinator Agent** `P4` — auto-assign cleaning, prioritize early arrivals, detect late-checkout conflicts, convert guest issues to tasks, remind/escalate, predict workload.
- **E. Maintenance Agent** `P4` — analyze issue photos, categorize, assign urgency, suggest repair checklist, track recurring problems, flag repeat out-of-order units.
- **F. Review / Reputation Agent** `P4` — monitor reviews, draft replies, sentiment, low-rating alerts, complaint-theme detection, staff coaching insights, auto-create improvement tasks.
- **G. Marketing Agent** `P5` — SEO suggestions, blog/offer/caption generation, email campaigns, abandoned-booking & returning-guest campaigns, low-season promos, Google Hotel campaign ideas.
- **H. Finance Agent** `P5` — missing-payment detection, OTA payout mismatch, refund anomaly, commission mismatch, owner-payout calc, revenue-leakage alerts, unpaid invoices, suspicious discounts.
- **I. Owner Report Agent** `P4` — monthly performance summary per owner with plain-language explanation of why performance was high/low. *(Pairs with your wedge.)*
- **J. Compliance Agent** `P5` — missing guest IDs/invoices, tax-report alerts, retention policy, GDPR deletion handling, suspicious-access and payment-risk flags.

### 7.4 Cost & quality control
- Cache and template common answers; only call the LLM when needed.
- Per-tenant AI usage metering tied to plan limits.
- Always ground agents in retrieved data (never let them invent availability, prices, or policies) — hold the same discipline throughout: reality-grounded, no fabrication.

---

## 8. Phased build roadmap (the part that imposes discipline)

The goal of phasing is a *sellable product at the end of every phase*, not a feature checklist. Each phase = a series of your standard Phase-0-audit → build → Playwright-verify Claude Code prompts.

### Phase 1 — The PMS core ("can a real property run on it?") — *the foundation*
SaaS multi-tenant foundation · property & inventory setup (incl. mixed properties) · drag-and-drop reservation calendar + room rack · manual/walk-in bookings · guest profiles · folios & invoices · Stripe payments + deposit holds · roles & RBAC · housekeeping status · basic manager reports · security baseline (2FA, audit logs, encryption, GDPR tools).
**Exit criterion:** a 20–40 unit hybrid property could replace its spreadsheet with this.

### Phase 2 — Distribution & get-paid ("sell everywhere safely")
Channel manager via **Channex** (Booking.com/Airbnb/Expedia/Vrbo + more) · internal ARI engine + **bulletproof sync queue, locking, conflict resolver, sync-error log** · booking engine (direct bookings on a subdomain) · front-desk & housekeeping staff PWA · revenue + manager dashboards.
**Exit criterion:** zero double bookings under load; a property can take OTA + direct bookings in one calendar.

### Phase 3 — The differentiators ("why switch to *you*")
White-label website builder · unified inbox (WhatsApp + email + OTA + SMS) · guest PWA (digital check-in, ID upload, lock code stub) · **owner portal + owner accounting (Stripe Connect payouts)** · automation builder (no-code) · **Reservation + Guest Concierge AI agents** + guardrail engine.
**Exit criterion:** the hybrid operator gets PMS + bookings + owner payouts + AI messaging in one product they can't get elsewhere at this price. **This is the version you sell hard.**

### Phase 4 — Intelligence & retention ("get smarter, keep them")
CRM & loyalty (reuse your ledger pattern) · revenue management + AI pricing suggestions · reputation management + AI replies · maintenance PWA + Maintenance/Housekeeping/Review/Owner-Report agents · smart locks & self-check-in · Google Hotel Center / metasearch.

### Phase 5 — The moat ("platform & ecosystem")
Marketing/Finance/Compliance agents · public API + webhooks + **app marketplace** · white-label reseller system · advanced multi-property analytics · voice AI (phone) · regional payment/OTA/tax expansion · (only now, if ever) evaluate direct OTA certifications.

**Rough framing, not a promise:** Phases 1–3 are the make-or-break ~12–18 months that produce a genuinely sellable, differentiated product. Resist pulling Phase 4–5 features forward; that's how the project dies.

---

## 9. Pricing & packaging

Refined from your tiers, aligned to the wedge:

- **Starter** — small apartments/guest houses/small hotels. PMS, calendar, direct booking website (basic), basic channel sync, guest messaging, payments, basic reports. *Priced to undercut incumbents per unit.*
- **Growth** — serious hotels & aparthotels. Everything in Starter + full channel manager, website builder, guest + staff PWAs, automation builder, revenue tools, **owner portal**, unified inbox, AI assistant. *Your flagship tier.*
- **Pro** — property managers / multi-property. Multi-property management, **advanced owner accounting**, advanced revenue manager, advanced reporting, custom roles, API access, webhooks, smart locks, advanced AI agents.
- **Enterprise / White Label** — groups, agencies, resellers. Full white label, custom domain, custom PWA branding, custom themes, dedicated-infra option, advanced permissions, custom integrations, SLA, priority support, migration support.

Pricing levers: per-unit base + usage add-ons (messages, AI calls, extra OTA channels via Channex), and a take-rate option on payments later. Keep a free trial with usage caps to exploit the fast-onboarding buying behavior.

---

## 10. Risks & mitigations (the honest list)

1. **Scope / founder burnout (your biggest risk).** Mitigation: the Phase 1–3 discipline above. Ship a sellable slice; do not build all 17 modules before launch.
2. **Double bookings destroy trust instantly.** Mitigation: queue + locking + conflict detection + retry + human-readable sync logs + emergency inventory lock, all in Phase 2. Test under concurrent load before any customer goes live.
3. **OTA connectivity is a multi-year slog if self-built.** Mitigation: Channex from day one; direct connections deferred to Phase 5 or never.
4. **Payments / PCI liability.** Mitigation: Stripe + Channex proxy, tokenization only, never store raw cards; keep PCI scope minimal by design.
5. **AI acting dangerously (refunds, cancellations, leaking PII).** Mitigation: the three-tier guardrail engine; agents propose, humans approve anything touching money/inventory/PII; grounded retrieval, no fabrication.
6. **Powerful but complex = unusable.** Mitigation: progressive disclosure in the UI — simple default views, advanced controls behind toggles. Staff will abandon complicated software. Treat UX simplicity as a feature with the same weight as any capability.
7. **Support & onboarding load at scale.** Mitigation: self-service setup wizards, prebuilt automation recipes, in-app guidance, and migration tooling (importers) from the start.
8. **Local market dependencies** (regional OTAs, payment methods, tax rules, government guest reporting). Mitigation: adapter interfaces for payments/tax/reporting so each new market is a plug-in, not a rewrite.

---

## 11. What "number one in the world" realistically means

Not "more features than Mews." That race is already lost on capital alone. Instead:

> **Be the #1 platform on Earth for the hybrid operator running hotels + apartments + villas with real owner accounting — and the first to make AI agents genuinely useful on top of one unified data model.**

Win that segment decisively, in a geography the incumbents under-serve, and you'll have a profitable, defensible, expandable business. *That* is a winnable version of "number one" — and the foundation from which you expand outward. Boiling the ocean is not.

---

### Immediate next steps
1. Lock the **unified data model** (Section 3.2) — this decision constrains everything; get it right before writing feature code.
2. Sign up for **Channex staging** and validate the OTA sync flow end-to-end as a spike, so Phase 2 has no surprises.
3. Stand up the **multi-tenant + RBAC + Stripe** skeleton (Phase 1 start) as your first Claude Code prompt series with the usual Phase-0 audit gate.
4. Decide the **beachhead geography & first 5 target customers** — concrete properties you can design Phase 3 around.

When you're ready, I can turn any phase into your paste-ready Claude Code master prompts (with the Phase-0 audit gate, explicit stop-and-approve points, extend-don't-duplicate constraints, and no-fake-data rules), or draft the unified schema first.

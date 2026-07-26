# Super Admin Panel — Completeness Review (2026-07-27)

Not "does what's built work?" (that audit is done — zero CRITICAL/P1, production-ready) but **"what does a
platform admin running this SaaS genuinely NEED that isn't there yet?"** A read-only review producing a
prioritized list for the founder to choose from. **No code was changed.**

**What exists today** (verified against the routes/controllers/views/models): the isolated `/admin` realm + hardened
auth + mandatory 2FA (Gate 0) · **Account** page (profile · email re-verify · password · 2FA recovery) · **Dashboard**
(real cross-org metrics) · **Customers** (list/detail/suspend/reactivate/assign-plan) · **Plans** (DB CRUD +
feature-gating + assign-to-customer) · **Security & Activity Log** (login capture + audit slice) · **Integrations**
(encrypted channel credentials + management + test-stub). Admins are created via the `admin:create` CLI.

---

## 1. Executive summary

**The panel is operationally sufficient to LAUNCH a free-trial / early-access product exactly as it is.** A founder
can already: see platform health, view + support customers (suspend/reactivate, assign plans), manage the plan
catalog + feature-gating, watch sign-in security, hold the channel credentials, and manage their own account. There
is **no launch-blocking hole in the panel itself.**

**The one real "before launch" question is billing model, not the panel:**
- Launching **free-trial / early-access** → the panel is **ready now**; everything below is *after-launch* work.
- Launching **paid (charging money)** → you need the **Stripe billing go-live** (already the documented, planned
  seam — Subhan's live-key flip), plus **suspension enforcement** so a non-payer can actually be cut off. Neither is
  new panel design; both are already-scoped deferrals.

**The honest shape:** the panel is *feature-complete for what a solo founder needs to launch and run a small early
book of customers*. The genuine gaps are **operational depth that becomes important once there are real paying
customers and a team** — billing/revenue tooling (needs live Stripe), the ability to actually enforce a suspension,
seeing the job queue, adding a second admin, GDPR data requests, and proactive alerting. **Do not over-build these
before launch.**

**Top genuinely-missing items by importance:**
1. **Suspension enforcement** (a suspended customer can still log in) — *deferred; becomes important the moment you
   need to cut off a non-payer.*
2. **Stripe billing sync + revenue/invoices/refunds** — *deferred (needs live Stripe); the whole paid-SaaS money
   layer.*
3. **Queue / failed-jobs visibility** — *the founder currently cannot reach Horizon in production (the gate is bound
   to an unassignable role); jobs run channel sync, emails, and payouts — you must be able to see failures.*
4. **Multi-admin / admin team** — *only the CLI creates admins today; a colleague/support hire needs server access.*
5. **GDPR data request (export/erase a customer)** — *a legal obligation with EU customers (founder is in Zürich).*
6. **Manual customer create + trial adjust/extend + cancel/offboard** — *day-two support ergonomics.*

---

## 2. IMPORTANT-BEFORE-LAUNCH (needed to operate from day one)

Deliberately short — over-building here delays launch. For a **free-trial / early-access** launch, this list is
effectively **empty** (the panel is ready). For a **paid** launch, only these apply, and both are already-scoped:

| # | Item | Why it matters | Effort | Buildable now? |
|---|---|---|---|---|
| B1 | **Stripe billing go-live** (activate the deferred billing sync) | You literally cannot charge without it. Already the documented go-live seam (test-mode-proven; live-key flip is Subhan's). | (flip + wiring) M | **Needs live Stripe** |
| B2 | **Suspension enforcement** | If you launch paid, a customer who stops paying must actually lose access — today "Suspend" only sets a flag. | S–M | Buildable now (touches tenant auth — careful) |

Everything else is genuinely **after-launch**. If launching free/early-access, even B1/B2 move to "soon".

---

## 3. IMPORTANT-SOON (needed once there are real paying customers / real usage)

The real operational backlog — build these as customers and usage arrive, not before.

### Billing & revenue (the money layer — mostly needs live Stripe)
| Item | Why | Importance | Effort | Needs |
|---|---|---|---|---|
| **Real revenue reporting** (MRR, churn, failed payments) | The dashboard shows *contracted plan value* honestly; real revenue needs collected-money data. | SOON | M | live Stripe |
| **Invoices / payment history per customer** | Support + finance need to see what a customer was charged. | SOON | M | live Stripe |
| **Refunds / credits / discounts / coupons** | Standard SaaS commercial ops (a refund, a promo). | SOON | M | live Stripe |
| **Dunning / failed-payment recovery** | Recover involuntary churn (a card expired). | SOON | M | live Stripe |

### Customer lifecycle & support
| Item | Why | Importance | Effort | Needs |
|---|---|---|---|---|
| **Adjust / extend a trial** | The single most common support ask ("give me a few more days"). | SOON | S | now |
| **Cancel / offboard a customer** | End a relationship cleanly (distinct from suspend). | SOON | S–M | now |
| **Create / comp a customer from the admin** | Sales-led onboarding + comp/pilot accounts (self-signup + assign-plan is the current workaround via `TenantProvisioningService`). | SOON | M | now |
| **Impersonation / "view as tenant" (read-only, heavy-audit)** | Real support debugging — see what the customer sees. Deferred (high-risk); build read-only + fully audited when support volume justifies it. | SOON | M | now (careful) |
| **Customer usage / adoption signal** | Spot churn risk / who's actually using the product (per-customer activity beyond counts). | SOON | M | now |

### Platform operations & monitoring
| Item | Why | Importance | Effort | Needs |
|---|---|---|---|---|
| **Queue / failed-jobs visibility** | Channel sync, emails, payouts, and analytics run on Horizon/Redis; the founder **cannot reach Horizon in production today** (its gate is bound to the unassignable global Super Admin role). Re-point that gate to the `admin` guard (small) and/or add an in-panel failed-jobs view. | SOON | S (re-point gate) → M (in-panel) | now |
| **Admin notifications / alerting** | Know when something happens — a customer churned, a payment failed, an error spiked, a big signup. | SOON | M | some need Stripe |
| **Error / system-health surface** | Spot an incident (often external — Sentry — but a basic health/heartbeat + queue view helps). | SOON | M (or external) | now |
| **Audit-log search / filter / export** | The activity view shows *recent*; incident review + compliance need search + export of the full trail. | SOON | S–M | now |

### Admin team & access
| Item | Why | Importance | Effort | Needs |
|---|---|---|---|---|
| **Multi-admin / admin team + roles** | Only `admin:create` (CLI) exists — a colleague or support hire needs server access. Add a UI to invite/list/deactivate admins + a read-only "support admin" vs full role. | SOON | M | now |

### Legal / compliance
| Item | Why | Importance | Effort | Needs |
|---|---|---|---|---|
| **GDPR data request (export / erase a customer's data) from the admin** | A legal obligation once you have EU customers (founder is in Zürich). The tenant side has `anonymize()`; there's no admin-triggered whole-customer export/erase. | SOON | M | now (care) |

### Integrations (already-deferred, priority when accounts land)
| Item | Why | Importance | Needs |
|---|---|---|---|
| **Real channel sync** (Channex/OTA) + **integration health/monitoring** + **customer-self-connect** | The management page + encrypted store + stub exist; the live two-way sync + per-customer self-connect need the real channel accounts. | SOON (when accounts land) | real accounts |

---

## 4. NICE-TO-HAVE (genuine improvements, not essential)

| Item | Why | Effort |
|---|---|---|
| **Admin session management** (see/revoke my sessions) | The owner realm has it; the admin doesn't. Small hardening nicety. | S |
| **Feature flags / maintenance mode** (platform-wide, beyond plan-gating) | A kill-switch for a broken feature + a maintenance banner. | M |
| **Platform settings / config in the DB** (trial length, default plan, support email) | Editable-without-a-deploy config (today in config/env). | S–M |
| **Data export / CSV** (customers, plans) | Handy for a spreadsheet / ad-hoc reporting. | S |
| **Customer communication / broadcast** (email a customer, an announcement) | In-panel comms (external email works at launch). | M |
| **Terms / consent acceptance tracking** | Record who accepted which ToS version. | S–M |

---

## 5. ALREADY-DEFERRED — confirmed correct (NOT new findings)

These are honest, correct deferrals. Restated with their *real* priority so the founder can sequence them:

| Deferral | Correctly deferred? | Real priority |
|---|---|---|
| **Stripe billing sync** | ✅ Yes (go-live seam; test-mode-proven, live-key flip is Subhan's) | **The** paid-launch dependency — do it when you launch paid. |
| **Suspension access-enforcement** | ✅ Yes (a status flag today; enforcement touches tenant auth) | **Higher than it looks** — a suspend that doesn't cut off is half-done; needed with paying customers. |
| **Impersonation / view-as-tenant** | ✅ Yes (high-risk; no route, asserted) | IMPORTANT-SOON for support; build read-only + heavily audited. |
| **Per-feature-limit enforcement** | ✅ Yes (limits editable, not creation-blocking) | Only matters if you *sell on limits* (property/seat caps). NICE-TO-HAVE otherwise. |
| **Real channel sync** | ✅ Yes (needs Channex/OTA accounts; test-stub is honest) | IMPORTANT-SOON once the accounts land. |
| **Customer-self-connect** | ✅ Yes (documented, later) | IMPORTANT-SOON with the channel accounts. |

---

## 6. Suggested roadmap (so you don't over-build before launch)

**PHASE 0 — LAUNCH (now).** The panel is ready for a **free-trial / early-access** launch as-is. If launching
**paid**, add only: **B1 Stripe billing go-live** + **B2 suspension enforcement**. Nothing else is blocking.

**PHASE 1 — FIRST PAYING CUSTOMERS (weeks after).** The money + control layer:
- Stripe billing sync → **real revenue reporting**, **invoices/payment history**, **refunds/coupons**, **dunning**.
- **Suspension enforcement** (if not done at launch).
- **Queue / failed-jobs visibility** (re-point the Horizon gate to the `admin` guard — small, high-value).
- **Adjust/extend trial** + **cancel/offboard** + **manual customer create** (support ergonomics).

**PHASE 2 — SCALE (a real book of customers + a team).**
- **Multi-admin / admin team + roles** (read-only support admin).
- **Admin notifications / alerting** (churn, payment-failed, error-spike).
- **GDPR data request** (export/erase a customer — legal).
- **Impersonation** (read-only, heavy-audit) + **customer usage/adoption analytics** + **audit-log search/export**.
- **Real channel sync + integration monitoring + customer-self-connect** (when the Channex/OTA accounts land).

**PHASE 3 — POLISH (as needed).** Admin session management · feature flags / maintenance mode · DB-editable
platform settings · CSV export · customer communication/broadcast · terms tracking.

**Bottom line:** the panel is **operationally sufficient to launch now** (free-trial) or **once the already-planned
Stripe go-live + suspension enforcement land** (paid). Everything else is honest *after-launch* depth — build it as
real customers and a real team make it matter, not before.

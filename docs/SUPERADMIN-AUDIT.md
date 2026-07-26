# Super Admin Panel — Deep End-to-End Audit (2026-07-27)

A full Playwright/MCP walk of every area, page, form, CRUD op, and action of the platform `/admin` panel —
audited as hard on **security/isolation** as on UI/UX/design. Read-only Phase A; findings + fix plan below.

> **✅ PHASE B COMPLETE (2026-07-27) — every genuine finding resolved or accepted-with-reason.** No CRITICAL/P1
> existed, so nothing was launch-blocking. **P2 fixed:** (P2-1) the customer list is now **paginated** (25/page,
> search/status carried into the links — test + browser proven); (P2-2) the suspend action is now **honest about
> enforcement** (a note + confirm copy: "a status flag … does not yet block their access" — enforcement stays a
> documented follow-up, NOT built); (P2-3) the Plans page now carries an **honest "About the catalog" note** (the
> tiers are fixed + editable; a brand-new tier is a follow-up tied to the marketing pricing page). **P3 fixed:** the
> admin state-changing forms now use the shared **double-submit guard**; the previously-dead **`plans.toggle` route
> is wired** to an Activate/Deactivate button (audited); an **admin favicon** was added. **P3 accepted (no change,
> documented):** `reactivate()` → `active` is the correct reactivation target (the real billing state derives from
> the subscription, not `organizations.status`); admin recovery-code **regeneration** is out-of-scope polish —
> recover via the `admin:create` command / DB. All fixes stayed additive, preserved the security model (audited
> `PlatformQuery`; no new bypass), disturbed nothing in the tenant realms, and respected every honest deferral.
> **The panel is audited, fixed, and production-ready** (the only open items are the pre-existing account-gated /
> documented deferrals).

**Method:** logged in as the platform admin (forced 2FA enrol → console), seeded 4 extra orgs with varied
statuses (active/trialing/suspended/cancelled) on varied plans so the list/dashboard/distribution were
representative, then drove every page + action (click/fill/submit/search/filter/suspend/assign/edit/rotate/test)
and cross-checked results against the DB (audit rows, encryption-at-rest, scope-restoration).

---

## Executive summary

**The panel is production-ready on security. Zero CRITICAL and zero P1 findings.** Every high-stakes property
was verified live: realm isolation, no cross-tenant leak, every admin action audited, no credential plaintext
leak, mandatory 2FA, no login enumeration, #28 (no guest PII on admin surfaces), and integer money (#17).

| Severity | Count | Items |
|---|---|---|
| **CRITICAL** (security/isolation/money/plaintext-leak) | **0** | — |
| **P1** (broken/wrong/mis-wired) | **0** | — |
| **P2** (UX / genuine gap / honesty) | **3** | customer-list pagination · suspension-presentation honesty · no plan-create affordance |
| **P3** (polish) | **5** | admin double-submit guard · dead `plans.toggle` route · no admin favicon · `reactivate()` always → active · no in-panel recovery-code regeneration |

**Honest deferrals — confirmed CORRECT, NOT findings** (do not "fix" by faking): Stripe billing sync
(`stripe_subscription_id` null), impersonation (no route — verified), suspension access-**enforcement**
(status marker; enforcement is a documented follow-up — but its *presentation* is P2-2 below),
per-feature-limit enforcement (limits editable, not creation-blocking), the real channel sync (test-connection
is an honest stub), customer-self-connect (documented in-UI, built later).

---

## SECURITY — the isolation re-verification (all CONFIRMED airtight)

Every security property was re-verified under real interaction, not just asserted by tests:

- **🔒 Realm isolation.** A tenant operator/owner/guest cannot reach `/admin` (redirected to the admin login,
  never the console) — re-confirmed by the 32 admin tests + prior browser proofs; unauthenticated `/admin`
  redirects to the admin login. A platform admin is a separate `platform_admins` identity, not a tenant user.
- **🔒 No cross-tenant leak.** After the admin performs a cross-tenant read (dashboard/customers), a subsequent
  **context-less tenant query THROWS** `TenantContextMissingException` — the `runAsSuperAdmin` scope is restored
  in its `finally`, so nothing leaks into a later tenant request. **Verified live.**
- **🔒 Every action audited.** `platform.login`, `platform.2fa_enrolled`, `platform.dashboard`,
  `platform.customers.list/view`, `platform.customer.suspend/reactivate/assign_plan`, `platform.plan.updated`,
  `platform.integration.updated/tested`, `platform.activity.audit_slice` — all recorded to
  `platform_admin_actions` with the admin id + IP. **Verified live** (rows appear on each action).
- **🔒 No credential plaintext leak.** A probe secret saved to Channex was **encrypted at rest** (raw DB column
  never contains the plaintext), **masked** (••••••••) everywhere (index/edit/test-response), and the **audit
  meta records the channel only, never the value**. **Verified live** + at the DB level.
- **🔒 Mandatory 2FA.** A correct password without 2FA lands on the enrolment screen and CANNOT reach the console
  (direct `/admin` bounces back to setup); a wrong 2FA code is rejected; recovery codes work at login.
- **🔒 No login enumeration.** A wrong password and a non-existent email return the SAME generic error
  ("These credentials do not match our records."). Failed admin attempts are captured (guard=admin + IP).
- **🔒 #28 — no guest PII.** The customer detail and the activity log expose operational facts only; no
  end-guest email/phone is rendered, and a guest's encrypted email is never persisted plaintext in the activity
  log. **Verified live** (probed the rendered HTML).
- **🔒 noindex + no public sign-up.** `/admin` responses carry `X-Robots-Tag: noindex, nofollow, noarchive`
  + a `<meta robots>`; `robots.txt` disallows `/admin`; there is no admin register/signup route (404).
- **🔒 Money #17.** Plan prices display correctly (€49.00/€99.00/€490.00/€990.00); a plan edit to €59.00 stored
  `5900` minor; validation rejects an empty name / negative price (the plans were unchanged by a bad submit).

---

## Per-area findings

### 1. Auth / realm — CLEAN (no findings)
Invalid login → generic no-enumeration error; wrong 2FA rejected; mandatory-2FA gate holds (password-without-2FA
never reaches the console); console blocked pre-enrolment; failed attempts captured; noindex present; no signup;
isolation + no-leak + audited all confirmed (see Security above).

### 2. Dashboard — CLEAN (no findings)
Every metric matches the seed exactly: Customers 5 (2 active · 1 trial), Active subscriptions 2 (1 trial),
Recurring plan value **€197.00/mo** (Σ active+trialing plan prices, yearly ÷12) labelled honestly as
"contracted, not yet billed", Properties 2 · 24 reservations, By-status 2/1/1/1, Plan distribution Essential×2.
The 6-month signups chart is real (Jul = 5, peak highlighted). Honest labels + zero states.

### 3. Customers
- **List / detail / actions all functional + correct.** Search (name/slug), status filter, and the honest
  empty state ("No customers match") work; the detail shows real facts + a per-customer audit slice (via the
  audited read); suspend/reactivate change `organizations.status` + are audited.
- **P2-1 · Customer list has NO pagination.** `PlatformCustomers::list()` returns ALL orgs; the view renders
  every row on one page. *Repro:* the list shows all 5 orgs with no pager; at hundreds/thousands of customers
  this is a real performance/UX problem. **Fix (buildable):** paginate the list (append the search/status query).
- **P2-2 · Suspension presentation is not honest about enforcement.** The red "Suspend customer" action + its
  confirm ("This flags the account as suspended on the platform") strongly imply the customer is locked out, but
  `organizations.status='suspended'` is **not enforced anywhere** — a suspended org's operators can still log in
  and use `/app`. *Repro:* suspend an org → status flips + audited, but nothing blocks that org's access.
  **Fix (presentation-honesty only — do NOT build enforcement):** clearly state suspension is a lifecycle flag
  and that access-enforcement is a documented follow-up.
- **P3 · `reactivate()` always sets `status='active'`** regardless of the prior status — a suspended *trialing*
  org becomes *active* on reactivate. Low impact (the real billing state is derived from the subscription, not
  `organizations.status`). **Fix (optional):** restore the prior status or note it.

### 4. Plans
- **List / edit / feature-gating all correct.** Prices display correctly (#17); editing name/price/features
  persists; **feature-gating proven end-to-end** — enabling `channels` on Essential flipped the demo org's
  `planAllows('channels')` false→TRUE (reverted). Validation rejects empty name / negative price.
- **P2-3 · No "Create plan" affordance.** Only the 4 config-seeded plans (Essential/Pro × monthly/yearly) are
  editable; there is no create/new-plan flow. *Repro:* the Plans index has no "Create/Add plan" button; only
  "Edit". This is partly by design (a new tier wouldn't surface on the config-anchored tenant Paywall), but the
  page presents "Plans management" without saying the catalog is fixed. **Fix:** an honest note that the catalog
  is the current editable tiers + that adding new tiers is a follow-up (preferred — avoids an orphaned-plan
  surprise), OR a real create flow with that caveat surfaced. Not a fake — a presentation/scoping decision.
- **P3 · `plans.toggle` route is DEAD.** The `POST /admin/plans/{plan}/toggle` route + `PlanController::toggle`
  exist but no UI button points at them (activation is via the edit form's `is_active` checkbox). **Fix:** wire
  a quick activate/deactivate button on the index, or remove the dead route+method.

### 5. Security & Activity Log — CLEAN (no findings)
Recent sign-ins render (login + the 2 real failed admin attempts, guard + IP); the 24h failure summary is real
(= 2); the cross-tenant account-events slice renders via the audited read (real `schedule.*`/`inbox.*`/
`maintenance_ticket.*` events); the privacy note is surfaced (security signal · 90-day retention); no guest
email plaintext. The 90-day prune command exists + is scheduled daily.

### 6. Integrations — CLEAN (no findings)
Saving a credential encrypts it at rest, masks it (••••••••), and never echoes it (index/edit/test-response
probed — no leak); rotate is write-only (blank keeps existing); the test-connection is an honest account-gated
stub; managing is audited (channel only, never the value); the customer-self-connect model is documented in-UI.

### 7. Cross-cutting
- **Design/nav consistent.** Every page uses the control-room shell; all 5 nav areas are live with correct
  active states; unknown `/admin/*` routes 404 gracefully (not 500); console is clean (the only errors are a
  stale `:8899` favicon from a prior prototype-server tab — not the app).
- **P3 · Admin state-changing forms lack the double-submit-disable** the tenant realm has (`data-guard-submit`
  + the busy-label). *Repro:* the suspend/assign-plan/plan-edit/integration-save forms have no submit guard —
  a double-click could file a duplicate audit row + gives no "submitting…" feedback. **Fix:** an admin
  submit-guard partial in the admin shell + `data-guard-submit` on the admin forms.
- **P3 · No favicon** on `/admin` pages (cosmetic).
- **P3 · No in-panel recovery-code regeneration / 2FA management** for admins. Recovery codes are shown once at
  enrolment + work at login, but an admin who exhausts/loses them has no UI to regenerate (recover via the
  `admin:create` command / DB). Minor operational gap.

---

## FIX PLAN (Phase B — genuine findings only, severity-ordered, gated)

- **CRITICAL / P1:** none — **the panel is airtight on security; no launch-blocking fixes.**
- **Batch B1 (P2 — UX / honesty):** (1) paginate the customer list (carry search/status); (2) suspension
  presentation-honesty note (flag, not access-lock — do NOT build enforcement); (3) plans-catalog honesty note
  (current editable tiers; new tiers a follow-up).
- **Batch B2 (P3 — polish):** (4) admin double-submit guard on the state-changing forms; (5) wire (or remove)
  the dead `plans.toggle` route; (6) admin favicon; (7) `reactivate()` prior-status nicety + a recovery-regen
  note (optional).

Every fix stays additive, preserves the security model (cross-tenant via the audited `PlatformQuery`; audited
actions; no new bypass), disturbs nothing in the tenant realms, respects the honest deferrals (fix presentation,
never build a deferred feature), and keeps the existing suite green.

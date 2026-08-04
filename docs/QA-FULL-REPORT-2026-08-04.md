# Vyrobel — Full End-to-End QA Report

**Date:** 2026-08-04 · **Base URL:** `http://127.0.0.1:8901` (`php artisan serve`) · **HEAD:** `926d60a`
**Method:** Real-browser automation via the **Playwright MCP** (navigate · click · type · submit · screenshot · read DOM/console), cross-checked against code + DB only to confirm findings.
**DB:** `migrate:fresh --seed` state (2 properties: `seaside-demo`, `vista-demo`; 1 org `vyrobel-demo`; 40 reservations).
**Screenshots:** `.playwright-mcp/qa/*.png` (gitignored).

> **⚠️ Read the scope note first.** This was a genuine, exhaustive-breadth pass: **every realm and effectively every page was loaded in a real browser** (200/redirect/403/404 recorded, console watched, screenshots/snapshots captured) — proving coverage and catching the critical "page-broken / money-wrong / privacy-leak" classes. **Deep per-control interaction** (every form with valid+invalid input, every filter/sort/drag/bulk action) was driven on a **curated high-value subset** — the storefront→booking funnel end-to-end (a real booking was created), the auth/2FA flows, search, and the money/privacy/isolation-critical surfaces — not on all ~120 pages. **Animation/motion was NOT frame-verified** (static screenshots don't capture transitions); it is called out as a coverage gap, not asserted as passing. Where a page was loaded but not deeply exercised, it is marked **LOADED**, not **PASS-deep**. Nothing was skipped silently.

---

## 0. Fix pass — ALL 7 FINDINGS RESOLVED (2026-08-04)

Every finding below was fixed, one per gate, each verified in a real browser (Playwright MCP) and covered by a test, invariants + honest refusals/deferrals preserved. On `main`.

| ID | Sev | Status | Commit | What fixed it |
|---|---|---|---|---|
| F-GUEST-1 | HIGH | ✅ RESOLVED | `1e564df` | `payment_option` is now a real submitted field; pay-on-arrival records **no** payment (balance stays DUE, reconciles #17), confirmation says "Pay on arrival"; card honored only when Stripe is wired. Removed the fabricating `recordPayment('offline')` (#39). Regression tests added; browser+DB verified (balance=total, 0 payments). |
| F-OP-1 | MEDIUM | ✅ RESOLVED | `e492537` | One shared `App\Support\Schedule\BoardDate` rule: Front Desk + Calendar default to **today** (current/upcoming activity), else most-recent-past arrival, never `max(check_in)`. Browser-verified both boards open on 3 Aug. |
| F-OP-2 | LOW | ✅ RESOLVED | `9b8828d` | Dashboard Channels card gated on `Organization::planAllows('channels')` — omitted on Essential (card gone; verified). |
| F-OP-3 | LOW | ✅ RESOLVED | `9b8828d` | `<x-operator.shell>` derives the topbar chip from the real authed user on every page — controller pages now show "Demo Admin / DA", not "Operator / O". |
| F-OP-5 | LOW | ✅ RESOLVED | `9b8828d` | The stale "Move room … deferred" note corrected to state the move persists immediately (the guarded route works). |
| F-OP-4 | TRIVIAL | ✅ RESOLVED | `1f47ee3` | Email previews prefer the latest booking with a real total — confirmation now shows €264.10, not €0.00. |
| F-DOC-1 | TRIVIAL | ✅ RESOLVED | `1f47ee3` | Docs corrected to `/book/seaside-demo` (property slug); the org slug `vyrobel-demo` 404s by design. |

**Suite after the pass:** 1616/1623 green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. The 7 non-green are pre-existing, unrelated flakes (6 `BookingImportTest` ordering flakes — pass in isolation; 1 `OwnerUnitDetailViewTest` random inventory_block collision) — none in any file this pass touched (diff scope: storefront-checkout / board-date / dashboard-channels / shell-chip / reservation-caption / email-preview / docs).

---

## 1. Executive summary

Vyrobel is in **strong shape**. Across five realms the product is honest, the money **reconciles to the cent**, the privacy perimeters hold, and cross-realm/tenant isolation is enforced. The overwhelming majority of surfaces are clean, and the "honest deferral" discipline (test-mode / account-gated / Soon) is applied consistently and correctly — those are **verified-correct, not findings**.

**One real bug of consequence was found** by driving the public booking funnel in a live browser: the storefront's **"Pay on arrival" option is not honored and fabricates a paid-in-full folio** (records a full "succeeded" payment when no money is collected). This is exactly the class of defect that static checks + green tests miss and a real browser catches.

### Coverage
| Realm | Pages loaded in browser | Deep-interaction | Result |
|---|---|---|---|
| Super-Admin (platform) | 11 / 13 routes | 2FA flow, plan/customer/integration forms viewed | **all PASS** |
| Operator (back office) | ~44 / 52 routes | wizard, front desk, guests, payout run, schedule PDF | PASS + 3 minor findings |
| Owner (portal) | ~27 / 46 routes | statement reconcile, bookings privacy, unit isolation | **all PASS** (exemplary) |
| Guest (portal) | ~20 / 27 routes | full funnel, arrival #33, receipt, isolation | PASS + **1 HIGH** |
| Public storefront | 5 / 6 routes | search→room→checkout→confirm (real booking) | **1 HIGH** (funnel) |
| Cross-cutting | 9/9 emails, 4/4 realm guards | isolation crafted attempts | **all PASS** |

### Findings by severity
| Severity | Count | IDs |
|---|---|---|
| CRITICAL | 0 | — |
| HIGH | 1 | F-GUEST-1 |
| MEDIUM | 1 | F-OP-1 |
| LOW | 3 | F-OP-2, F-OP-3, F-OP-5 |
| TRIVIAL | 2 | F-OP-4, F-DOC-1 |

### Findings by category
Data-honesty/logic (money): 1 HIGH (F-GUEST-1) · 1 LOW (F-OP-2) · 1 TRIVIAL (F-OP-4).
Functional/logic (dates): 1 MEDIUM (F-OP-1).
UI/UX consistency: 2 LOW (F-OP-3, F-OP-5).
Docs: 1 TRIVIAL (F-DOC-1).

### Biggest themes
1. **The storefront "pay on arrival" path is the one genuine money defect** — fix before any public-booking launch.
2. **Two operator scheduling screens (Front Desk + Calendar) open on the wrong day** (furthest-future booking, not today) — a daily-friction UX bug on the most-used screens.
3. **Everything money-related that should reconcile, reconciles exactly** — owner statements ↔ operator payouts ↔ guest receipts all tie to the cent at the real 18% commission. This is a genuine strength.
4. **Honesty discipline is excellent** — no fabricated sync/paid/verified states were found *except* F-GUEST-1; deferrals are clearly labelled everywhere else.

---

## 2. Page inventory + coverage matrix

Legend: **PASS** = loaded + content/behaviour verified · **LOADED** = rendered 200, not deeply exercised · **403✓/404✓** = correct gate/scope refusal · **F-x** = finding.

### AUTH + isolation
| Surface | Result |
|---|---|
| `/login` valid + invalid creds | PASS (error alert, pwd cleared, email kept) |
| `/guest/login`, `/admin/login` | PASS |
| Unauth `/app`→`/login`, `/owner`→`/login`, `/guest/*`→`/guest/login`, `/admin`→`/admin/login` | PASS (isolation) |
| Platform 2FA setup + recovery-codes flow | PASS |
| `/signup`, `/forgot-password` | LOADED (linked, not submitted) |

### SUPER-ADMIN (`admin@vyrobel-demo.test`)
| Page | Result |
|---|---|
| `/admin` overview | PASS (reconciles: 1 cust, 2 props, 40 res, €49/mo "not yet billed") |
| `/admin/customers` | PASS (search+filter) |
| `/admin/customers/{org}` | PASS (Stripe "not connected", Suspend = honest "flag only", audit feed) |
| `/admin/plans` | PASS (4 tiers, honest "new tier planned") |
| `/admin/plans/{plan}/edit` | PASS (name/price/active/feature gates) |
| `/admin/integrations` + `/{channel}` | PASS (all "Not configured", encrypted credential form) |
| `/admin/activity` | PASS (sign-in log + org event feed, 90-day retention) |
| `/admin/account` | PASS (profile/email/password/2FA) |
| `/admin/two-factor` (+ setup) | PASS |
| `/admin/account/email/verify/…` | not tested (signed link) |

### OPERATOR (`demo-staff` Hotel Manager, `demo-admin` Property Owner)
| Page | Result |
|---|---|
| `/app/dashboard` | PASS (real KPIs; date correct; F-OP-2 channels card) |
| `/app/front-desk` | LOADED + **F-OP-1** (opens on 13 Dec, not today) |
| `/app/calendar` | LOADED + **F-OP-1** (same); availability math reconciles |
| `/app/reservations` | PASS (40, KPIs, 7 filters) |
| `/app/reservations/{id}` | PASS (folio empty-state honest; **F-OP-5** move-room caption) |
| `/app/reservations/create` | PASS (7-step wizard, draft-restore, 300+69=369) |
| `/app/reservations/{id}/refund` | not deeply tested |
| `/app/guests` | PASS (full PII correct on CRM #28) |
| `/app/guests/{id}` | PASS (reconciles 3×369=1107) |
| `/app/owners` + `/{owner}` | PASS/LOADED |
| `/app/owners/invite/preview` | 403✓ (HM) → 200 (admin) |
| `/app/payouts/run` | PASS (reconciles EXACT €2804.40 paid + €885.60 pending; gold-standard honesty) |
| `/app/rates` | LOADED |
| `/app/reports` (+exports) | LOADED |
| `/app/insights`, `/portfolio`, `/search` | LOADED |
| `/app/group-booking` | LOADED |
| `/app/maintenance` (+ `/costs`,`/settings`,`/{ticket}`) | LOADED |
| `/app/housekeeping`, `/schedule` | LOADED |
| `/app/schedule/pdf` | PASS (valid 881KB PDF) |
| `/app/inbox` + `/{thread}` | LOADED |
| `/app/guest-requests`, `/reviews` | LOADED (+ **F-OP-3** user chip) |
| `/app/units`, `/unit-requests` | LOADED |
| `/app/security`, `/profile`, `/notifications`, `/notification-preferences` | LOADED |
| `/app/email-templates`, `/email-previews` | PASS (9 emails render; **F-OP-3**, **F-OP-4**) |
| `/app/roles` | 403✓ (HM) → 200 (admin) — real RBAC |
| `/app/settings` | 403✓ (HM) → 200 (admin) |
| `/app/billing` (+checkout) | 403✓ (HM) → 200 (admin) |
| `/app/channels` | 403✓ feature-gate ("Not in your plan", Essential) |

### OWNER (`demo-owner` External Owner)
| Page | Result |
|---|---|
| `/owner` overview | PASS (YTD €3,690 reconciles ↔ operator payouts; name-only; honest tax-hold) |
| `/owner/bookings` | PASS (name-only, **zero** contact/door PII #28) |
| `/owner/bookings/{id}` | LOADED (linked) |
| `/owner/statements/{id}` | PASS (every month = gross×0.82 exact; masked IBAN ···1000; no guest data) |
| `/owner/units` + `/{OWNED}` | PASS (no PII leak) |
| `/owner/units/{FOREIGN}` | 404✓ (OwnerScope isolation) |
| `/owner/payout-method`, `/balance`, `/expenses`, `/payouts` | LOADED |
| `/owner/calendar`, `/tax`, `/reports`, `/insights`, `/reviews`, `/documents` | LOADED |
| `/owner/security`, `/settings`, `/team`, `/messages`, `/notifications`, `/help`, `/agreement`, `/add-unit`, `/welcome`, `/archive`, `/notification-preferences` | LOADED |
| ISOLATION: `owner → /app/dashboard` | bounced → `/owner` (#26✓) |

### GUEST (`demo.traveller`) + PUBLIC storefront
| Page | Result |
|---|---|
| `/book/seaside-demo` | PASS (real rooms, honest "pick dates") |
| `/book/{p}/search` | PASS (real availability + prices) |
| `/book/{p}/rooms/{roomType}` | PASS (honest "coming soon", real price) |
| `/book/{p}/checkout` | PASS math (170+39.10+10+45 = 264.10) — but **F-GUEST-1** |
| `/book/{p}/confirmation` | **F-GUEST-1** ("Paid in full" on pay-on-arrival) |
| `/book/vyrobel-demo` | 404 — **F-DOC-1** (documented URL is wrong slug) |
| `/guest/trips` | PASS (reconciles €184.50 due; "check-in tomorrow" correct) |
| `/guest/reservation/{id}` + `/manage` + `/check-in` + `/extras` | LOADED |
| `/guest/reservation/{id}/arrival` | PASS (**#33** door code hidden far-out, revealed in-window) |
| `/guest/payments`, `/wallet`, `/messages`, `/saved`, `/account` | LOADED |
| `/guest/reservation/{id}/receipt` | PASS (300+69=369 paid-in-full) |
| `/guest/reservation/{id}/invoice.pdf` | PASS (valid PDF) |
| `/guest/reservation/{FOREIGN}` | 404✓ (cross-guest isolation) |
| `/guest/home`, `/review`, `/claim` flow | not tested |

### Cross-cutting
| Item | Result |
|---|---|
| 9 transactional emails (`/app/email-previews`) | all render in-browser (Booking-confirm, Cancellation, Refund, Payment-reminder, Pre-arrival, Review-request, Waitlist, Winback, Verify-email) |
| Realm guards (4) | all refuse the others (unauth + owner→/app) |

---

## 3. Findings by severity

### HIGH

**F-GUEST-1 — Storefront "Pay on arrival" is not honored; it fabricates a paid-in-full folio**
*Category: data-honesty / money-logic · Realm: Public storefront + Guest/Operator/Owner accounting*
- **Repro:** `/book/seaside-demo` → search 15–17 Aug → *Reserve* → *Book this room* → checkout shows **"PAY ON ARRIVAL · no card is collected here"** → fill name/email → *Confirm reservation*.
- **Actual:** Confirmation page reads **"Payment received · Paid in full"**. DB (reservation `R-II836XWY`, folio `01kz4yb6ev2jq382nv2rdygmk3`): `total_minor=26410, paid_minor=26410, balance_minor=0`, **1 payment €264.10 status=succeeded**. No money was collected.
- **Expected:** Pay-on-arrival must leave the balance **DUE** (record no payment); only a card path collects.
- **Root cause:** [BookingEngineController.php:429–434](../app/Http/Controllers/BookingEngineController.php#L429-L434) — when `config('services.payments.gateway') !== 'stripe'` (the current/demo config) it calls `recordPayment(fullBalance, 'offline')` for **every** booking, branching purely on gateway config and **ignoring the guest's payment choice**. The checkout form submits **no `payment_option` field** (only `first_name`/`last_name`/`email` + hidden context) — the "Pay on arrival / Pay now" toggle is decorative. In the Stripe-live config the same code instead force-redirects *every* booking (incl. pay-on-arrival) to Stripe card checkout (line 436) — so the option is unhonored in **both** configs.
- **Impact:** Operator/owner folio + accounting show revenue collected that wasn't; the front desk sees a €0 balance and never collects → revenue leakage + reconciliation error. Contradicts the on-screen promise to the guest.
- **Fix direction:** Submit + honor a real `payment_option`. Pay-on-arrival → create the folio with the balance due, record no payment, confirmation copy "Pay at the property". Card → Stripe (or the deferred honest state). Never record an `offline` payment the guest didn't make.
- **Evidence:** `.playwright-mcp/qa/gu-02-checkout.png`; DB query in report §7. *(Test booking `R-II836XWY` left in place as evidence — demo data, cleared by `migrate:fresh --seed`.)*

### MEDIUM

**F-OP-1 — Front Desk and Calendar open on the furthest-future booking date (13 Dec 2026), not today**
*Category: functional / date-logic · Realm: Operator*
- **Repro:** As any operator, open `/app/front-desk` (or `/app/calendar`) with no query param.
- **Actual:** Board loads on **Sunday 13 Dec 2026** (Front Desk) / **13–26 Dec window** (Calendar), 4 months ahead — even though the topbar pill and the "Today" button correctly point to 2026-08-03.
- **Expected:** The daily-ops board defaults to **today**.
- **Root cause:** [FrontDesk.php:248–251](../app/Livewire/FrontDesk.php#L248-L251) — `->max('check_in')` returns the **furthest-future** confirmed booking (Demo Booker arrives 13 Dec = the max). The comment says "most recent arrival date" but `max()` picks the furthest *future*, not the latest *past*. The Calendar shares the same default. (The **Dashboard** correctly defaults to today — so this is scheduling-screen-specific.)
- **Impact:** Every operator opening the two most-used screens each morning sees the wrong day; recoverable via the "Today" button but a real daily friction that defeats the board's purpose on load.
- **Fix direction:** Default to `today` (optionally fall back to the nearest activity **≤ today** for an empty demo, never the furthest future).

### LOW

**F-OP-2 — Dashboard "Channels · Booking.com (demo) Synced" card shown on an Essential plan that can't access Channels**
*Category: data-honesty · Realm: Operator.* `/app/channels` correctly 403s ("Not in your plan"), yet the dashboard renders a green **"Synced"** channels card. The status is real-data-derived (`channel_mapping.activated` seed) and demo-labelled with an honest "sync activates with your OTA connection" note, so it's borderline — but a green "Synced" on a plan without channels is mildly misleading. *Fix:* hide the channels dashboard card when `!planAllows('channels')`.

**F-OP-3 — Controller-rendered operator pages show a generic "Operator / O" user chip instead of the signed-in user**
*Category: ui-ux/consistency · Realm: Operator.* On `/app/email-previews`, `/email-templates`, `/guest-requests`, `/reviews` (the pages with their own `<title>` — retrofitted into the layout per `c0ce419`) the topbar identity reads **"Operator / O"** rather than the real "Demo Staff / DS" shown on every Livewire page. Cosmetic, but a placeholder that doesn't reflect real state. *Fix:* resolve `auth()->user()` name/initials in the shared controller-page topbar partial.

**F-OP-5 — Reservation-detail "Move room" button looks fully active but its caption says the action is "deferred"**
*Category: ui-ux/consistency · Realm: Operator.* `/app/reservations/{id}` renders an enabled **"Move room"** button + unit dropdown, under the caption *"Move room routes through the guarded AssignmentService — deferred until it persists safely from here."* Either the button is live (caption stale) or deferred (button should be visibly disabled). Not exercised to avoid mutating state; flagged to resolve the button-vs-caption contradiction. (The working move path is on the Front Desk per project notes.)

### TRIVIAL

**F-OP-4 — Email-preview "Booking confirmation" shows "Total €0.00"**
*Category: data-honesty (preview-only) · Realm: Operator.* `/app/email-previews` renders the org's latest booking (`R-YIIKBSWI`), whose folio is empty, so the confirmation preview shows **€0.00**. Preview-only (real confirmations carry a real total). *Fix:* preview a booking that has a folio.

**F-DOC-1 — Documented demo storefront URL 404s**
*Category: docs · Realm: Public.* CLAUDE.md "How to DEMO it" says `/book/vyrobel-demo`, but that is the **org** slug; the storefront route needs a **property** slug. `/book/vyrobel-demo` → **404**; the real URL is `/book/seaside-demo` (or `/book/vista-demo`). *Fix:* update the doc; optionally add an org-slug → default-property redirect.

---

## 4. Findings by realm

- **Super-Admin (platform):** **Zero findings.** Clean dark console, real reconciling stats, impeccable honesty (Stripe "not connected", Suspend = "status flag, doesn't lock out yet", integrations "not configured"). Access is 2FA-hardened (first login forces TOTP setup — intended, noted as demo friction not a bug).
- **Operator:** Core is solid and reconciles. Findings F-OP-1 (MEDIUM, scheduling default date), F-OP-2/3/5 (LOW), F-OP-4 (TRIVIAL). RBAC is real and correctly gates roles/settings/billing/owner-invite/channels.
- **Owner:** **Zero findings — exemplary.** Money reconciles to the cent at the real 18% commission across statements ↔ operator payouts ↔ YTD; name-only privacy holds with zero contact/door leakage; masked IBAN; foreign-unit → 404; owner→/app bounced (#26).
- **Guest:** One HIGH (F-GUEST-1, shared with storefront). Otherwise strong: #33 door-code gating works both ways, receipts reconcile, cross-guest access 404s, guest PDFs generate.
- **Public storefront:** Funnel works end-to-end and money math is exact — but **F-GUEST-1** makes the recorded payment state wrong. F-DOC-1 (doc slug).
- **Cross-cutting:** All 9 transactional emails render; all four realm guards refuse the others.

---

## 5. Role-journey results

- **GUEST full funnel (storefront → booking):** Browse → search (15–17 Aug) → room → checkout → **Confirm** → confirmation. **Completed end-to-end**; fields persisted (name/email/dates, no drop, no date-roll); money reconciled at every step (€264.10 itemised). **Broken hand-off:** the payment *state* — pay-on-arrival recorded as paid-in-full (**F-GUEST-1**).
- **GUEST authed journey:** Trips → reservation → manage → check-in → extras → arrival (door code) → payments → wallet → messages → receipt → invoice PDF. **All steps flowed**; folio consistent across Reservation/Receipt; €184.50-due and €369-paid figures consistent with the operator + guest-profile views.
- **OPERATOR:** Dashboard → reservations → detail → new-reservation wizard (draft-restore) → guests CRM → payout run → schedule PDF. **All reachable and consistent**; owner statements reconcile to the operator payout run to the cent. (Not driven to a live check-in/out mutation this pass.)
- **OWNER:** Overview → statements → units → bookings → isolation. **Completed**; owner sees name-only guest data and reconciling money; never full contact, never the door code.
- **SUPER-ADMIN:** Login (2FA) → overview → customers → customer detail → plans → integrations → activity → account. **Completed**, all honest.
- **CROSS-REALM isolation:** unauth → correct login per realm; owner → /app bounced to /owner (#26); guest → foreign guest reservation 404; owner → foreign unit 404; Hotel Manager → roles/settings/billing/owner-invite/channels 403, all reachable by the org-admin. **All refusals correct.**

---

## 6. Design / animation fidelity summary

**Design (static):** Each realm has a distinct, intentional, consistent visual system — operator/owner warm-neutral + orange gradient with glass cards and Sora headings; guest a lighter guest-facing variant; platform a dark console. Screenshots read as faithful, polished, on-system (`.playwright-mcp/qa/*.png`). No layout breakage, raw-Blade leakage, or 500s were seen on any page loaded. No per-page wireframe pixel-diff was performed this pass (the design-fidelity register already covers that); this QA confirms the rendered pages are coherent and unbroken.

**Animation / motion — COVERAGE GAP (not asserted passing):** motion (vyFadeUp, count-ups, drawer/popover/toast transitions, drag interactions, timeline rails) was **not frame-verified** — static screenshots don't capture it and no video/trace was recorded. The user-menu popover and the storefront search interaction fired correctly, but transitions/count-ups/`prefers-reduced-motion` were not systematically checked. **Recommend a dedicated motion pass** (Playwright video or manual) if animation sign-off is required.

---

## 7. Data-honesty + security summary

**Reconciliation (#17) — confirmed to the cent where checked:**
- Owner statements = gross × 0.82 (real **18%** agreement rate, not the wireframe's 15%): 540→442.80, 800→656.00, 570→467.40, 630→516.60, 880→721.60, 1080→885.60; YTD **€3,690** ties.
- Owner statements ↔ operator payout run: paid €442.80+656.00+467.40+516.60+721.60 = **€2,804.40**; pending #6 = **€885.60**. Exact.
- Guest checkout: 170 + 39.10 (VAT 23%) + 10 (city tax) + 45 (cleaning) = **€264.10**. Guest receipt: 300 + 69 VAT = **€369**, paid-in-full. Guest-profile lifetime: 3×369 = **€1,107**.

**Privacy perimeters — confirmed:**
- **#28** operator Guests CRM shows full email+phone (correct); owner Bookings/Units show **name-only, zero contact PII** (grep-clean of `@`/`+3519`/door/wifi).
- **#33** door code + WiFi (`5390` / `welcome2026`) revealed only in-window to the owning guest; hidden ("appear closer to arrival") for the far-future stay.
- Payout **IBAN masked** to `···1000`.

**Isolation — confirmed:** tenant guard throws unscoped; owner→/app bounces (#26); owner→foreign unit 404; guest→foreign reservation 404; RBAC 403s are real permission gates (Hotel Manager 403 → Property Owner 200 on roles/settings/billing/owner-invite); channels feature-gate 403 on Essential.

**Honest deferrals — VERIFIED-CORRECT (not findings):** Stripe test-mode / not-connected across payout run, customer detail, on-desk payment, storefront card; owner tax-hold "Paused"; Suspend "status flag only"; integrations "not configured"; "Soon" on Void/Modify/Export-CSV/passkeys etc. **No fabricated sync/verified/persona/position/saved-card** was found — the single exception where an honest deferral was **violated** is **F-GUEST-1** (pay-on-arrival recorded as paid).

---

## 8. Prioritized fix backlog

| # | Sev | ID | Page(s) | Fix direction |
|---|---|---|---|---|
| 1 | HIGH | F-GUEST-1 | Storefront checkout/confirm + `BookingEngineController::reserve` | Honor a submitted `payment_option`; pay-on-arrival → balance DUE + no payment recorded + "pay at property" copy; card → Stripe/honest-deferred. Never record an `offline` payment the guest didn't make. |
| 2 | MEDIUM | F-OP-1 | `/app/front-desk`, `/app/calendar` (`FrontDesk.php:248`) | Default the board to **today**; fall back to nearest activity ≤ today for empty demos, never `max(check_in)`. |
| 3 | LOW | F-OP-2 | `/app/dashboard` | Hide the Channels card when `!planAllows('channels')`. |
| 4 | LOW | F-OP-3 | `/app/email-previews`,`/email-templates`,`/guest-requests`,`/reviews` | Resolve real `auth()->user()` name/initials in the controller-page topbar partial. |
| 5 | LOW | F-OP-5 | `/app/reservations/{id}` | Reconcile the "Move room" button state with its "deferred" caption (disable the control, or update the caption if it's live). |
| 6 | TRIVIAL | F-OP-4 | `/app/email-previews` | Preview a booking that has a folio so the total isn't €0.00. |
| 7 | TRIVIAL | F-DOC-1 | CLAUDE.md demo docs | Use `/book/seaside-demo` (property slug); optionally add an org-slug redirect. |

---

## Appendix — what was NOT covered (honest gaps, for a follow-up pass)
- Deep per-control interaction on the LOADED-only operator/owner pages (every filter/sort/drag/bulk on maintenance, housekeeping lanes, schedule drag, rates grid edits, inbox reply/assign, group-booking 5-step, owner calendar block/release, expenses approve/query).
- Mutating operator ops (live check-in/out, walk-in, refund bounded #27, room-move persistence F-OP-5, add-charge).
- `/guest/home`, `/guest/review` (needs a completed stay), `/guest/claim` verify flow; several PDF endpoints (folio/credit-note/statement/arrival, owner remittance/tax/booking-summary) — only schedule PDF + guest invoice PDF were generated this pass.
- Second property (`vista-demo`) + the property switcher's multi-property behaviour; the "All properties" scope.
- Invalid-input validation on most forms (only login invalid-creds + the funnel happy-path were driven).
- **Animation/motion** (see §6).
- Owner sub-user roles (accountant / view-only) capability differences; owner-team invite flow.

These are coverage gaps, not known defects — nothing in them is asserted to pass or fail.

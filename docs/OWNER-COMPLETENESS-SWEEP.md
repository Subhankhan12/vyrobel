# Owner Pages — COMPLETENESS SWEEP (PHASE 0: authoritative gap audit)

**Date:** 2026-07-28 · **Status:** ✅ **PHASE 0 COMPLETE.** · **✅ WAVE A (shared backends) + ✅ WAVE B (per-page completeness) BUILT & SHIPPED (2026-07-29).**

## ✅ WAVE B — BUILT (2026-07-29), each page consumes the Wave-A backends + its 🟢 items
- **GATE 1 — My Units** (`cc48d53`): action-center consumes `owner_attention_states` (mark-read + BULK mark-all-read/snooze-all + status filter); weekend-uplift + min-stay editor (consume `setRateWeekendAware`) with live preview.
- **GATE 2 — Unit Detail** (`0d8f70f`): weekend-uplift editor · richer day-detail popover (guest name-only #28) · READ-ONLY cleaning schedule (real `HousekeepingTask`, never a fake cleaner).
- **GATE 3 — Rates page** (`9cd8b95`): nightly + weekend-uplift + min-stay in one manage view + Fri/Sat preview; **fixed** a latent min-stay read bug (room_type_id vs rate_plan_id) on Rates + Unit Detail.
- **GATE 4 — Security** (`9efd507`/`b47f75b`/`2274367`): **trusted devices** (skip-2FA, hash-only cookie, revocable) · **login approval** (opt-in, signed-link email, held-until-approved, single-use) · **passkeys** (WebAuthn on web-auth/webauthn-lib — real register + usernameless sign-in, crypto **browser-proven via a CDP virtual authenticator**). All mutation-proven.
- **GATE 5 — Team** (`7b4032f`): export **watermark + download audit-log** made TRUE — `ExportAudit::stamp()` records + stamps; 9 controllers wired; Team download-log surface.
- **GATE 6 — Settings + Onboarding** (`27c37e1`): **quiet hours** (real — held EMAIL, in-app kept, always-on exempt); **theme** kept honest (no dark palette, deferred not faked); **onboarding** `/owner/welcome` (IBAN-less thin flow, real units + rate + payout hand-off).

**Wave B honesty holds (documented, not faked):** per-unit attention filter (account-level signals only) · dark theme (no palette) · IBAN capture / micro-deposit (certified pipeline only). deps: web-auth/webauthn-lib ^5.3 (founder-approved).

---


## ✅ WAVE A — BUILT (2026-07-29), the shared backends every later wave reuses
- **GATE 1 — `owner_attention_states` store** (`b0f09cc`): the shared snooze/dismiss/mark-read/undo backbone behind My Units' action-center AND the Overview attention feed (one store, both surfaces). Owner-scoped (#28), controller-routed (#20) + audited, CAP_ACTIONS-gated, tamper-safe (`signalFor` resolves a posted key to a REAL current signal), mutation-proven (`OwnerAttentionStateTest`).
- **GATE 2 — REAL weekend-uplift pricing** (`a354a81`): additive `rate_plans.weekend_uplift_pct` + `RateAndRestrictionService::setRateWeekendAware` (writes the uplifted price onto Fri/Sat nights — NOT an inert flag, #39) + `OwnerRateService::apply(?float $weekendUpliftPct)` (backward-compatible) + `weekendUpliftFor()` reader + controller validation. Owner-scoped exclusive-owned only (same guard as the nightly rate); `OwnerRatesTest` +2. *(UI editors land in Wave B.)*
- **GATE 3 — modelling additions** (`bd9fabf`): owner registered **address** (settings form → `Owner.address` JSON → statement-PDF "Prepared for" letterhead block, only when set) · expense **vendor** (`owner_expenses.vendor` → page column + CSV, honest "—", demo-seeded suppliers). **Per-user currency/timezone = HONEST HOLD, not a fake column** (money has no FX so a personal currency misstates amounts #39; a personal timezone applied nowhere in this portal is an inert store) — both stay property-derived read-only.
- **GATE 4 — quick wins** (`this commit`): "**Email to me**" is REAL — a queued `OwnerReportMail` attaching the owner's latest finalized statement PDF to their OWN inbox (CAP_EXPORTS, self-only, MailSafetyGuard) · Overview earnings-chart **goal line** reusing `owner_monthly_target` (#56, shown only when set) · Insights **vs-last-year** occupancy delta (real per-month, shown only where prior-year data exists) + a bounded prior-year demo seed (apt occupancy only, **NOT** ledger-attributed, so demo Balance/statements are unchanged).

**Wave A honesty holds (documented refusals, NOT faked):** per-user display currency (no FX) · per-user timezone (inert here) · the Reports **PDF pack** (bundling deferred) — all left honest rather than stubbed.

---

**Original PHASE 0 status:** ✅ **COMPLETE — read-only. Awaiting your build decisions (🟠 items) before PHASE 1+.**
**Method:** every `docs/OWNER-*-AUDIT.md` (13 docs) extracted for DEFERRED / HONEST-OMITTED / REFUSED (#39) / SIMPLIFIED items, then **each candidate verified against the real repo** (tables/services/routes). Items already built in a later resolution pass are marked *CLOSED* (not gaps).

## Legend
- 🟢 **BUILDABLE NOW** — a real feature the backend lacks but we can build with **no external infra**. The new default: build it.
- 🟠 **PRODUCT DECISION** — buildable with no external infra, but it changes how the business works → **needs your call** before building (not a mechanical "fill-in").
- 🔴 **HARD-DEFER** — needs real external infrastructure (live payment rail, banking/SEPA, tax engine, OAuth server, SMS/push/OTA providers, real AI) → a separate real scope conversation, decided one at a time.
- ⚪ **PERMANENT-HONEST** — a data-reality / anti-fabrication stance, not a "feature" (e.g. no fabricated date/brand/history). Stays honest.

**Repo verification done this pass (confirms what's actually missing):** `owner_attention_states` ❌ · rate-proposal entity ❌ · `weekend_uplift` column ❌ (min_stay ✅ in `restrictions`) · `owner_expenses` has `deductible`/`flag` but **no approval state** ❌ · `HousekeepingTask` ✅ (cleaning modelled operator-side) · `messages`/`message_threads` have read-status + snooze + channel enums ✅ (reactions/attachments/starred ❌) · `units.is_active` ✅ but no owner write ❌ · passkey/webauthn ❌ · trusted_location ❌ · VAT engine ❌ · report Mailable ❌ · export watermark/download-audit ❌ · `owner_monthly_target` ✅ (#56) · CSV exports (balance/expenses/units/bookings) ✅ built · cover-thumbnail ✅ fixed (`roomType->coverPhoto()?->url`).

---

## MASTER GAP LIST — per page (only genuinely-open items; CLOSED/built items omitted)

### PHASE 1 — Overview (#54)
| Gap | Class | Build scope |
|---|---|---|
| Earnings-chart **goal line** (the "€9k goal") | 🟢 **stale-refusal** | reuse `owner_monthly_target` (#56 added it) — render the owner's REAL set goal as the chart line; honest "no target" when unset. **No new table.** |
| Occupancy delta / MoM pill / masked-IBAN / upcoming-stays | ✅ CLOSED (built #54) | — |
| Est. payout date · "Scheduled/On-hold" payout state | ⚪ / 🔴 | no scheduled-date field — belongs to the **payout engine** (🔴); stays honest until then. |

### PHASE 2 — My Units (#55)
| Gap | Class | Build scope |
|---|---|---|
| **Action-center snooze / dismiss / mark-read persistence** | 🟢 | NEW `owner_attention_states` store (owner+signal → snoozed_until / dismissed_at / read_at) + controller routes (#20, CAP_ACTIONS, audited) + Snooze options (Tomorrow/1wk/custom) + Undo + Snoozed panel. **The biggest My Units gap.** *(Shared: also powers the Overview attention feed.)* |
| **Richer calendar day-detail** | 🟢 | full day-detail popover (check-in/out, guest **name-only #28**, owner-net revenue) over the real heat-grid. |
| Action-center **approve/decline decisions** | 🟠 → see **Rate-proposal entity** | no owner "decision" entity; only real as part of the rate-proposal feature (below). |
| Cover thumbnail | ✅ CLOSED (fixed) | — |

### PHASE 3 — Add Unit (#55)
| Gap | Class | Build scope |
|---|---|---|
| Operator **approve→create-live-Unit** transition | 🟠 (operator-realm) | the owner side is real (`UnitRequest`); closing the loop is an **operator-realm** build (a `UnitRequest` → live `Unit` review/approve). Fits the operator-pages track, not this owner sweep. |

### PHASE 4 — Bookings + PDF (#56)
| Gap | Class | Build scope |
|---|---|---|
| OTA **brand** split (Booking.com vs Airbnb) | 🔴 / ⚪ | brand not modelled (`source` enum only) → needs real OTA/Channel accounts. Real `source` shown. |
| Letterhead street/city | 🟠 (small) | add `street`/`city` to `Organization` billing block (a small modelling addition). |
| *(Chart, goal ring, CSV, PDF occupancy/channel/name — all CLOSED/real)* | ✅ | — |

### PHASE 5 — Statement + PDF (#57)
| Gap | Class | Build scope |
|---|---|---|
| **"Payment & ops fees" bucket** | 🟠 | no attribution bucket for card/platform fees → a real fees line requires a new `owner_statement_lines` type + an attribution source. Product/modelling decision. |
| **Estimated payout date** | 🔴/⚪ | no scheduled-date field → payout engine. |
| Letterhead street/city | 🟠 (small) | same as Bookings (shared `Organization` fields). |

### PHASE 6 — Payouts + Payout Method (#58) ⚠️ REAL MONEY
| Gap | Class | Build scope |
|---|---|---|
| **Automated payout ENGINE** (scheduled runs · **micro-deposit verification** · Verified/Verifying state · live transfer rail · **SEPA trace** · **48h hold** · **instant withdrawal**) | 🔴 **MAJOR** | needs live Stripe/banking + PSD2/PCI/GDPR. `StripeConnectPayoutRail` exists (test-mode); the live-key flip is the founder's. **THE one genuinely-large deferred capability — a multi-phase fintech project, not a page-fix.** ⚠️ NO IBAN capture without the certified pipeline. |
| Upcoming-schedule **projection** | 🟠 | a derived cadence/forecast (from the real schedule) — buildable as an honest estimate, or stays "not yet available". |
| Registered **address** (remittance) | 🟠 (small) | add an `address` column to `Owner`. |
| *(payout-prefs store, bank CRUD, CSV, tax-hold, Connect test-mode — all CLOSED/real)* | ✅ | — |

### PHASE 7 — Balance + Expenses (#59)
| Gap | Class | Build scope |
|---|---|---|
| Balance **"Withdraw early"** | 🔴 | owner-initiated transfer rail = the payout engine. |
| **Expenses approval workflow** (Approve/Query, "auto-approved in 7 days") | 🟠 | `owner_expenses` has no approval state. Building a real owner-approval state machine **changes the money model** (the audit flagged "NOT recommended"). **Needs your call:** agreement-governed (current, honest) vs owner-approved. |
| Vendor field | 🟠 (small) | add a `vendor` string to `owner_expenses` if wanted. |
| *(Balance/Expenses CSV, request-maintenance relay, clearing model — CLOSED/real)* | ✅ | — |

### PHASE 8 — Calendar + Documents (#60)
| Gap | Class | Build scope |
|---|---|---|
| Multi-year Documents history / invented refs | ⚪ | real years only — stays honest. |
| *(real block + series expansion + real PDFs — CLOSED/real)* | ✅ | — |

### PHASE 9 — Reports + Insights (#61/#63)
| Gap | Class | Build scope |
|---|---|---|
| **"Email to me" report** | 🟢 | NEW report Mailable + queued send (owner-scoped, CAP_EXPORTS). Small. |
| **Tax & VAT summary + "Mark filed"** | 🔴 | needs a real **VAT computation engine** (rates/bases/per-quarter) + a filing-status store. An owner could file on a wrong figure → tax-correctness/compliance. Only if genuinely how the business works. |
| Insights **vs-last-year** series (occupancy/pace/rate Δ) | 🟢 (data) | seed prior-year demo history (like the Statement seed) → the LY series renders real; no engine needed. |
| Insights/My-Units **"rate proposals"** callout | 🟠 → **Rate-proposal entity** | see below. |

### PHASE 10 — Rates (MANAGE, not yet exact-matched) + shared pricing
| Gap | Class | Build scope |
|---|---|---|
| **Weekend uplift** (+ min-stay editor) | 🟢 | NEW `weekend_uplift_pct` on the rate model + `OwnerRateService::apply` extension + the editor (Rates + Unit Detail + My Units). min_stay is already real. *(Shared across 3 pages.)* |
| **Rate-PROPOSAL entity** | 🟠 **big** | operator→owner rate proposals the owner approves/declines → applied via `OwnerRateService`. **One real feature powering 3 surfaces** (My Units action-center decisions · Insights "review proposals" · a proposals inbox). **Needs your call:** does your ops model use operator→owner rate proposals? |

### PHASE 11 — Unit Detail (#65)
| Gap | Class | Build scope |
|---|---|---|
| **Weekend uplift** | 🟢 → PHASE 10 | shares the new column. |
| **Cleaning schedule** (read-only) | 🟢 | reuse `HousekeepingTask` (modelled operator-side, unit-scoped) → a real read-only cleaning view for the owner (name-free); "message about cleaning" = the real relay. **The data exists.** |
| Owner **unit-status** (Active/Snoozed) toggle | 🟠 | `units.is_active` exists; a real owner write is a product decision (owner pauses own units vs operator-controlled). |
| OTA sync / share | 🔴 | Channex/OTA accounts. |

### PHASE 12 — Team (#65)
| Gap | Class | Build scope |
|---|---|---|
| **Export watermark + download audit-log** | 🟢 | stamp financial exports (PDF/CSV) with the downloader identity + audit-log every download → makes the security note's original claim TRUE (it was softened). A nice real security build. |

### PHASE 13 — Tax Center (#64)
| Gap | Class | Build scope |
|---|---|---|
| **Withholding** (residency-driven rates applied to payouts) | 🔴 | a real tax engine + reconciliation; an owner could rely on it. Only if Vyrobel should genuinely withhold. Honest "None" is currently correct (Vyrobel doesn't withhold). |

### PHASE 14 — Security (#66)
| Gap | Class | Build scope |
|---|---|---|
| **Trusted locations** (skip-2FA-from-here) | 🟢 | NEW `trusted_devices`/`trusted_locations` store + remember-device / skip-2FA-from-trusted logic (with the security care that entails). |
| **Login approval** (approve new sign-ins) | 🟢 | NEW device-approval flow (email-approve a new device before it can complete sign-in). |
| **Passkeys** (WebAuthn) | 🟢 (larger) | real WebAuthn/passkey registration + assertion — a modern-auth build. |
| **Connected apps** (OAuth grants) | 🔴 | needs Vyrobel to be an OAuth authorization server. Large. |

### PHASE 15 — Messages (MANAGE, wireframe pending)
| Gap | Class | Build scope |
|---|---|---|
| Rich inbox (reactions / attachments / read-receipts / starred / pinned) | 🟢 (mostly) | read-status + snooze/close already exist; **reactions / attachments (secure upload) / starred / pinned** are NEW stores. Build when its wireframe arrives. |

### PHASE 16 — Agreement + Reviews (MANAGE, wireframes pending)
| Gap | Class | Build scope |
|---|---|---|
| Reviews **agency-reply store + response-rate** | 🟢 | a real per-review operator-response store + a derived response-rate — if the agency genuinely replies through Vyrobel. |
| Agreement page | — | decode its wireframe when sent. |

### PHASE 17 — Onboarding (deferred #66)
| Gap | Class | Build scope |
|---|---|---|
| Honest thin welcome flow (profile confirm + real 18% units + notification prefs + hand-off to Payout Method) | 🟢 | NEW `/owner/welcome` route + component + standalone shell. **Everything EXCEPT** IBAN capture / micro-deposits. |
| IBAN capture / micro-deposits | 🔴 | the payout-engine / certified pipeline (compliance). |

### Notifications-preferences additions (Settings #62)
| Gap | Class | Build scope |
|---|---|---|
| **Quiet hours** store · **Theme** store · per-user **currency/timezone** | 🟢 / 🟠 | Quiet-hours + theme = small new stores (🟢); per-user currency/timezone = a product decision (🟠 — vs property-derived). |
| SMS / Push channels · phone-verify · OTA "Connected" | 🔴 | SMS/push/OTA providers. |

---

## THE 🔴 HARD-DEFER LIST (needs real external infra / a real scope decision — one at a time)
1. **Automated PAYOUT ENGINE** (scheduled runs · micro-deposit verification · live transfer rail · SEPA trace · 48h hold · instant withdrawal · Verified state) — **the major fintech initiative** (live Stripe/banking + PSD2/PCI/GDPR). Powers: Payouts protected-changes, Balance "Withdraw early", Overview/Statement/Payouts est-date, Onboarding IBAN. **NO IBAN capture without the certified pipeline.**
2. **VAT computation + Withholding** (real tax engines) — correctness/compliance; only if genuinely how the business works.
3. **OAuth "connected apps"** — Vyrobel as an OAuth authorization server.
4. **Real OTA channel sync** (Airbnb/Booking via Channex) — real channel accounts. Also unblocks the Bookings OTA brand split.
5. **SMS + Push providers** — Settings SMS/push channels + phone-verify.
6. **Support AI** ("Ask Vyrobel") — a real support-AI (honest FAQ is what's built).

## THE 🟠 PRODUCT-DECISION LIST (buildable, but needs your call on whether it's how the business works)
1. **Rate-PROPOSAL entity** (operator→owner proposals, approve/decline → OwnerRateService) — one feature, 3 surfaces. *Recommend building if your ops model uses proposals.*
2. **Expenses APPROVAL** state machine — changes the money model (audit: "not recommended"). *Recommend keeping agreement-governed unless owners truly approve expenses.*
3. **Owner-controlled unit status** (pause own units) + **owner-controlled weekend-uplift/min-stay** — owner vs operator control.
4. **Statement "Payment & ops fees" bucket** + a projected payout date — modelling additions.
5. **Per-user currency/timezone** · **Owner address / letterhead street-city** · **expense vendor** — small modelling additions.

---

## PROPOSED PHASE ORDER (shared backends first, so later pages reuse them)
**Wave A — shared backends (build first):**
1. `owner_attention_states` store → My Units action-center **and** Overview attention feed.
2. `weekend_uplift_pct` column + `OwnerRateService` extension → Unit Detail + Rates + My Units.
3. Report Mailable ("Email to me") → Reports.
4. Overview goal-line (reuse `owner_monthly_target`) + Insights LY demo-seed — quick stale-refulls.

**Wave B — per-page 🟢 completeness (consume Wave A):**
5. My Units (snooze/dismiss/day-detail) · 6. Unit Detail (weekend-uplift + cleaning read-only) · 7. Security (trusted-locations + login-approval + passkeys) · 8. Team (export watermark) · 9. Settings (quiet-hours + theme) · 10. Onboarding (thin flow).

**Wave C — 🟠 product-decision features (only the ones you approve):**
11. Rate-proposal entity (My Units + Insights) · 12. any of: expenses-approval · owner unit-status · fees-bucket · small modelling additions.

**Wave D — 🔴 (separate scope conversations, one at a time):** payout engine · VAT/withholding · OAuth · OTA sync · SMS/push · support-AI.

Each Wave-A/B/C phase: audit-first → gated build → per-page commit → suite green + PHPStan L5 + Pint + browser-verify → reconcile (#17) → guards (#28/#20/#39). Shared backends get their own tests + are proven shared (anti-drift).

---

## ⏸️ AWAITING YOUR DECISIONS
1. **Wave A + B (all 🟢)** — proceed to build all? (The default per your mandate.)
2. **Wave C (🟠)** — which to build: rate-proposals? expenses-approval? owner unit-status? weekend-uplift owner-control? fees-bucket? the small modelling additions (address/street-city/vendor/per-user currency)?
3. **Wave D (🔴)** — which to schedule as real initiatives (esp. the **payout engine** — a real fintech scope conversation)?

# Doc batch 5 — the verification record (2026-08-25)

⚠️ **WHY THIS FILE EXISTS.** These findings were produced by a reading pass and reported in chat. A
verification that lives only in a report **gets done twice**, and the second reading may reach a different
answer — which is worse than not having done it at all (§N.32). Each row below carries the CODE FACT and
the file that establishes it, so the next pass reads rather than re-derives.

⚠️ **ONE FINDING WAS CORRECTED ON RE-READING** (R1) — proof the re-verification was not ceremonial.

## Scope, re-verified

37 audit-FALSE claims still present in the converted output, across 7 pages:
`guide-reports` 10 · `docs` 6 · `guide-housekeeping` 6 · `guide-settings` 6 · `guide-storefront` 5 ·
`guide-setup` 3 · `resources` 1.

⚠️ `/faq` (6) and `/glossary` (6) read **0 still-present and that means NOT LOOKED AT**, not corrected —
both render from a data structure the text extractor strips. See the note in `tools/marketing/convert.py`.
Their conversion fix is ruled and lands separately; **do not read their clean gate result as success.**

## What a customer is ACTUALLY charged — the pricing baseline

⚠️ **Read from the `plans` rows the engine bills from, NOT from `config/billing.php`.**

| plan | interval | pricing_type | price_minor | unit_rate_minor |
|---|---|---|---|---|
| `essential_monthly` | monthly | flat | 4900 | NULL |
| `essential_yearly` | yearly | flat | 49000 | NULL |
| `pro_monthly` | monthly | flat | 9900 | NULL |
| `pro_yearly` | yearly | flat | 99000 | NULL |

⚠️ **A customer pays a FLAT fee that does not move with unit count. There are TWO plans — Essential and
Pro. There is no Starter, Growth, Scale or Enterprise tier.**

⚠️⚠️ **A CONFIG KEY IS NOT A SHIPPED FACT.** `config/billing.php` carries `per_unit_minor` and a comment
reading "the subscription is billed PER active unit on the rack". **No seeded plan uses it**
(`pricing_type` is `flat` and `unit_rate_minor` is NULL on all four). That key has now misled one reading
in this arc. `App\Support\Marketing\MarketingPricing` says the same thing confirmed-not-assumed, and
derives the marketing suffix from the real shape so a page cannot claim per-unit while the engine bills flat.

## The verified findings

| # | page | claim (short) | verdict | CODE FACT |
|---|---|---|---|---|
| R1 | guide-reports | OOS units excluded from available nights, so a maintenance closure is not an occupancy failure | **FALSE** | ⚠️ **CORRECTED ON RE-READING.** Operator occupancy denominator is `Σ availability.total` (`PerformanceMetrics` BASIS §). `AvailabilityService:44-62` filters units by `is_active` only; a unit whose `condition_status` is `out_of_order`/`out_of_service` is counted into `availability.out_of_order` and **stays in `total`**. OOS is subtracted from *available*, never from the occupancy denominator. (The first reading cited the OWNER denominator `count(units) × days` — right verdict, wrong mechanism.) |
| R2 | guide-reports | "Reports default to everything you manage" | **TRUE** | `OperatorReports::render()` scopes by `currentReadIds`/`viewingAllProperties` — the Phase-6a context, whose pivot-or-all rule (#38) means an empty `property_user` pivot ⇒ the whole org. **Correct nothing.** |
| R3 | guide-reports | narrow to a property, a unit type or a single unit | **HALF** | The only control on `reports.blade.php` is a reporting-month/range `<select>` (line 16). Property scope comes from the global context switcher. **No unit-type and no single-unit filter exist.** Cut only that half. |
| R4 | guide-reports | month bars are clickable and filter the table below | **FALSE** | No `wire:click` anywhere in `reports.blade.php`; `monthBars()` returns display rows only. |
| R5 | guide-reports | the peak month is highlighted | **FALSE** | No peak/highlight logic in `OperatorReports` or its view. |
| R6 | guide-reports | set a revenue goal — goal line + progress ring | **FALSE** | No operator revenue-goal column, prop or view element. (`owner_monthly_target` is the OWNER's, #56 — a different surface.) |
| R7 | guide-reports | tax section totals net/VAT/gross split by accommodation, add-ons, city tax | **FALSE** | `reports.blade.php:87` renders ONE line, "Tax collected · pass-through". No net/VAT/gross split and no categories. (The real VAT summary is the OWNER's, via `OwnerVatService` #73.) |
| R8 | guide-reports | filing status — next return due, whether filed, who marked it | **FALSE** | No `filed_at`/`filed_by`/due-date column or writer anywhere. |
| R9 | guide-reports | "Download all statements" packages a period as a single PDF pack | **FALSE** | No operator statement-pack route or controller. (`Owner\OwnerArchiveZipController` is owner-side and a ZIP, not an operator PDF pack.) |
| R10 | guide-reports | CSV exports use ISO dates and unformatted numbers | **FALSE — and it is the opposite** | `OperatorReportsExportController:49-61` writes `MoneyFormatter::display($minor,$currency)` (formatted currency) and human month labels via `$m['label']`. ⚠️ See PRODUCT DEFECTS below. |
| S1 | guide-settings | owners choose their own payout currency; FX rate appears on the remittance advice | **HALF** | `owners.payout_currency` and `management_agreements.payout_currency` DO exist (`Owner.php:19,39`). ⚠️ No FX-rate capture and no rate field on the remittance PDF — that half is unbacked. |
| S2 | guide-settings | tax settings "feed the VAT summary in Reports" | **FALSE** | `OperatorReports` has no VAT summary at all. The VAT summary is the OWNER's and reads `tax_rates` (set by the `tax:set-rate` admin command), NOT property tax & fees. |
| S3 | guide-settings | commission defaults — the default management commission for new owners, and the base it applies to | **FALSE** | `OperatorSettings:42,216-218` shows the REAL per-owner `ManagementAgreement.commission_pct`. There is no org default-for-new-owners setting and no configurable base. |
| S4 | guide-settings | "Units are what you are billed on." | **FALSE (pricing)** | All four seeded plans are `flat` with `unit_rate_minor` NULL. Unit count does not affect the bill. |
| S5 | guide-settings | notification routing decides who is emailed about what | **FALSE** | No routing feature; Settings notification-routing is a "Soon" stub. |
| S6 | guide-settings | data export = full copy of reservations, guests, units, rates, statements as CSV **or JSON**, on demand | **FALSE** | Only two operator export controllers exist (`OperatorReportsExportController`, `MaintenanceCostExportController`). No full-account export and **no JSON writer anywhere**. (The owner has "Request my data"; the operator does not.) |
| T1 | guide-setup | the property timezone decides when the day rolls over on every board | **TRUE** | `properties.timezone` genuinely drives "today" — `ReservationStage:50`, `StorefrontOffer:83`, `RosterWeek:286`, `TryBookingSandbox:165`. **Correct nothing.** |
| T2 | guide-setup | "or we pull them for you on Growth and above" | **FALSE (pricing)** | ⚠️ **Names a tier that does not exist** — the plans are Essential and Pro. There is also no importer of any kind. |
| T3 | guide-setup | "you are billed per unit, and housekeeping, maintenance and availability all resolve at unit level" | **HALF (pricing)** | "billed per unit" is FALSE (flat plans). The unit-level resolution of housekeeping/maintenance/availability is TRUE. Cut only the false half. |
| H1 | guide-housekeeping | turn inspection off in Settings and Clean becomes the final state | **FALSE** | No inspection toggle in `OperatorSettings` or on `properties`. |
| H2 | guide-housekeeping | "Assign all" spreads rooms across the team on shift by count **and expected cleaning time** | **HALF** | `HousekeepingService::assignAll:151-173` picks the least-loaded housekeeper and increments load by **1 per task**. Balanced by count; **no duration input exists**. Cut only that half. |
| H3 | guide-housekeeping | "This is the only automatic reordering, and it is the one that matters." | **FALSE** | There is no automatic reordering in `HousekeepingService` at all. |
| H4 | guide-housekeeping | marking a room inspected releases it to the front desk in the same second | **HALF** | `HousekeepingService:57-84` syncs the unit's `housekeeping_status` to `inspected` in the same transaction, so the desk sees it immediately. ⚠️ But "releases" overstates: `HousekeepingService:17-19` — housekeeping status does **NOT** affect availability; only `condition_status` does. |
| H5 | guide-housekeeping | raise a maintenance ticket from the room card | **FALSE** | No maintenance action on `housekeeping-board.blade.php`. |
| H6 | guide-housekeeping | that block stops the unit selling everywhere immediately, **including on connected channels** | **HALF** | The block does stop local selling (`AvailabilityService` recompute). ⚠️ There is no live channel push — the channels page is STOPPED. Cut the channel half; **promise no future date.** |
| ST1 | guide-storefront | point your own domain at it, not a subdomain of ours | **FALSE** | No custom-domain column, route or resolver. Only `StorefrontLandingController` + `StorefrontSitemapController` exist. |
| ST2 | guide-storefront | connect payments — a recognised card form on your domain | **FALSE** | No storefront payment controller. (Guest card charge is hosted Stripe Checkout, SAQ A — never a card form on the operator's domain, #45.) |
| ST3 | guide-storefront | test with a real card and refund yourself | **FALSE** | Presupposes ST2. Live keys are refused outside production by `StripeSafetyGuard`; the build is test-mode only. |
| ST4 | guide-storefront | "Keep the channels running" | **FALSE** | Implies channels run. Channel sync is STOPPED and account-gated. |
| ST5 | guide-storefront | book on it yourself, on a phone, with a real card | **FALSE** | Same as ST3 — no real-card path exists. |
| D1 | docs | "Connecting Airbnb and Booking.com — what syncs each way, and reading connection health" | **FALSE** | No live sync and no connection-health surface. Channels STOPPED. |
| D2 | docs | "Inviting your team — which role to give a housekeeper, a receptionist and a bookkeeper" | **FALSE** | No such guide exists among the 16 `tools/marketing/core/guide-*.html` masters — the card points nowhere. |
| D3 | docs | "Two-factor authentication and sessions — **enforcing 2FA for the team**, revoking a session, reviewing connected apps" | **HALF** | Sessions and connected apps (OAuth #72) are real, and 2FA/TOTP is real per-user. ⚠️ There is **no org-wide 2FA enforcement** — no policy column, middleware or command. Cut only that half. |
| D4 | docs | "Currency, timezone and tax settings — **set once per tenant**; every figure in the product follows" | **FALSE** | `properties.timezone` and `properties.currency` are **per-property** (`create_properties_table:27,29`); the org carries only `default_currency`. Per-tenant is wrong, and multi-property is the product's core. |
| D5 | docs | "Importing from another system — the export format we accept, and how the reconciliation sheet works" | **FALSE** | No importer, no accepted format, no reconciliation sheet. |
| D6 | docs | header/footer link to "Changelog / see what changed recently" | **FALSE** | ⚠️ Already a RULED REFUSAL — `routes/web.php:633`: a changelog asserts a release history, and there are zero git tags across the repo's commits. The page still links to it. (Support is real — `settings.support` + the grounded "Ask Vyrobel" assistant.) |
| RES1 | resources | "One email a month, no drip sequence" + "Sign up for the list" | **FALSE** | No newsletter, list, subscriber table or signup route anywhere. |

## Direction two — claims OUR OWN changes invalidated

| page | claim | history |
|---|---|---|
| `article-pricing` | "those four nights cannot be sold at all, at any price, to anyone" | Enforcing `min_stay` (`RestrictionGuard`) made it TRUE; adding `reservations.restriction_override` made it FALSE again. Anyone who can take a booking can override with a keyed reason. |

⚠️ Re-swept after the override grant: this is the ONLY direction-two claim. The `guide-refunds` borne-by
and `guide-checkin` mid-stay hits are already this arc's own corrected text.

## PRODUCT DEFECTS — reported, NOT fixed

1. ⚠️ **`OperatorReportsExportController` writes formatted currency into CSV** (`MoneyFormatter::display`),
   so a spreadsheet receives `€1,234.00` as text rather than a number, and month cells carry human labels
   rather than ISO dates. The doc claim asserting the opposite is being corrected to match the code — but
   **the code is the side that is wrong here.** A CSV built for a spreadsheet should emit raw values.
2. ⚠️ **The `/docs` index links to a Changelog that was deliberately refused** (`routes/web.php:633`).
   A refused surface should not keep an entry point.

---

## Addendum (2026-08-25) — the two product defects, resolved

### 1. R10 — the CSV export. ⚠️ THE PAGE WAS RIGHT AND THE CODE WAS WRONG.

**FIXED.** `MoneyFormatter::csvAmount()` is now the ONE definition of a spreadsheet-safe amount: a bare
decimal in MAJOR units, `.` separator, no thousands grouping, no symbol. The ISO 4217 code travels in its
own column, because the amount alone is not the money. Months are ISO `Y-m` (the breakdown had carried the
ISO key alongside the human label all along — the exporter simply took the wrong one). Both the reports CSV
and the maintenance-costs CSV were fixed: **one export fixed and one left formatted is worse than two
broken, because the good one teaches an operator to trust a money column the other betrays.**

⚠️ **THE LESSON, AND IT IS THE REASON THIS ADDENDUM EXISTS.** An audit reports that a page and the product
disagree. **It cannot tell you which side is wrong.** Usually the page. Here the page was right, and the
previous pass corrected the page to describe the broken export — which made the defect look INTENTIONAL and
removed the only remaining signal that it existed. ⚠️ **A doc correction can bury a bug, in exactly the
place a reader would go to find it.** Every future batch asks which side is wrong before correcting; where
the code is the wrong side, say so and leave the sentence for the fix.

⚠️ **Re-read of the other 36 rows for the same shape** — rows where the CODE may be the wrong side:
- **S6** (`guide-settings`): the page promised a full-account export as CSV **or JSON**. No JSON writer
  exists and there is no full-account export. ⚠️ The page describes something a reasonable product would
  have; the correction records absence. **Worth a product decision, not a doc fix.**
- **H1** (`guide-housekeeping`): the page said inspection can be turned off in Settings. It cannot. ⚠️ An
  operator who does not inspect now has a permanently unused lane. **A real gap, recorded honestly.**
- **ST1/ST2** (`guide-storefront`): custom domain and an own-domain card form. Both are genuine product
  absences the page assumed. ⚠️ ST2 in particular is a REFUSAL (hosted checkout, SAQ A, #45), not a gap.
- The remaining 32 are page-side errors. **Nothing else was fixed; these are reported only.**

### 2. The `/docs` changelog entry — REMOVED

Both entries are gone and **nothing replaced them**, because a replacement would be a promise. The
changelog was refused on principle (`routes/web.php:633` — a changelog asserts a release history and there
are zero git tags), not deferred, and a marked-pending item still advertises something that is not coming.

## Addendum — the €885.60 vs €442.80 discrepancy: RESOLVED, and NO money was lost

⚠️ **The record was wrong, not the demo — and the demo was NOT edited.**

**Cause, established from the data:** September carries TWO owner-attributed stays of €540.00 gross each.
The statement claims only one, so its net is €442.80 (€540.00 − 18% commission). Both together would be
€885.60. Two independent things moved that figure, and neither is a loss:

1. ⚠️ **THE DEMO IS DATE-RELATIVE.** Which stays fall inside September moves with the real calendar. The
   collection-gate audit records that the pin had **already** drifted to €820.00 before anything else
   changed (`docs/audits/OWNER-COLLECTION-GATE-2026-08-23.md:111`).
2. ⚠️ **THE COLLECTION GATE** (`OwnerStatementService::claimable` → `OwnerCollection::split`) holds a
   booking's revenue out of a statement until its folio is collected — *"claiming an uncollected row would
   release money the business never received."* The held September stay is a walk-in that has paid €332.10
   of €540.00, so `reservationIsCollected` is false.

⚠️ **NOTHING IS LOST.** Held lines keep `statement_id = NULL` and are claimed by a later statement once the
guest pays, through the same straggler path the service already had. The owner ledger still says the owner
is OWED it; only PAYABILITY moved.

**What the number actually measures:** September's net payable = the **collected** September ledger lines
less the real management commission. ⚠️ **It is DERIVED over a moving window and was never a constant.**
The seeder comments and `CLAUDE.md` have been corrected to say so and to state no target. Historical dated
audit files still quote €885.60 and were **left alone** — they were true when written, and rewriting a
dated snapshot would falsify the record rather than correct it.

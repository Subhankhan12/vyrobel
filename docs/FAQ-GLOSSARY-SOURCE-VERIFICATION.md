# `/faq` + `/glossary` — source verification, BEFORE any restoration (2026-08-25)

⚠️ **NOTHING WAS RESTORED IN THIS PASS, AND THAT IS THE POINT.** The order is verify-at-source, correct at
source, and only then let the converter carry it. Switching the conversion on first would put every answer
on the page at once — several already known false — and a page that is briefly wrong is how a wrong
sentence gets copied somewhere else.

## What the masters actually hold — measured, and NOT what the brief reported

| | `/faq` | `/glossary` |
|---|---|---|
| visible content source | `DCLogic` `static QS` (JS array, 9,293 chars) | `DCLogic` `static T` (JS array) |
| entries | ⚠️ **25 questions** | ⚠️ **35 terms** |
| second copy | ⚠️ a hand-maintained `application/ld+json` block, **12 questions** | none |
| what the converter emits | **0 questions** — every `<script>` is stripped | **0 terms** |
| typed counter in the master | ⚠️ **"18 questions"** | "35 terms" |

⚠️⚠️ **THE COUNTER IS WRONG THREE WAYS, NOT TWO.** It claims **18**; the source holds **25**; the rendered
page shows **0**. (The brief said the source holds 12 — that is the JSON-LD subset, not the source.)

⚠️ **THE GLOSSARY'S "35 terms" IS CORRECT TODAY.** It is still a typed count beside a moving list — the
same shape as the "sixteen written guides" line that shipped false sitewide — so it must be derived, but
this is a case where confirm-first found the number accurate.

⚠️ **THE TWO FAQ COPIES HAVE NOT DIVERGED IN TEXT — YET.** All 12 JSON-LD answers are **byte-identical**
to their `QS` twins. But the JSON-LD is a **stale SUBSET: 13 of the 25 questions are missing from it.**
That is already a divergence in coverage, and it is invisible on the page. ⚠️ One source rendered twice is
the fix; the JSON-LD must be GENERATED from the corrected `QS`, never maintained beside it.

## The audit's counts — CONFIRMED

`/faq` 6 FALSE · `/glossary` 6 FALSE. ⚠️ **Both pages read 0-still-present today only because nothing
looks at them.** Verification below covers **all 25 + all 35**, not only the audit's 12.

## `/faq` — findings (all 25 verified; the 6 audit-flagged are marked ⚑)

### Pricing — held at the security bar. Two plans, Essential and Pro, **both flat**.
| # | claim | verdict | code fact |
|---|---|---|---|
| 1 ⚑ | "A fixed amount per unit per month — roughly €4 to €6 depending on portfolio size" | **FALSE** | All four seeded `plans` are `pricing_type='flat'`, `unit_rate_minor` NULL. The bill does not move with unit count, and neither figure exists anywhere. |
| 3 ⚑ | "we bill per unit in the portfolio rather than per night sold" | **FALSE** | Same. `config/billing.php`'s `per_unit_minor` is used by **no plan**. |
| 5 | "Adding a property or a staff account costs nothing" / "units are what you are billed on" | **HALF** | First half TRUE (one org, one subscription, many properties). Second half FALSE. |
| 4 | "Export every reservation, guest, unit, rate and statement as CSV **or JSON** at any time from Settings" | **FALSE** | Only `OperatorReportsExportController` + `MaintenanceCostExportController` exist. No full-account export, no JSON writer. ("No exit fee" is TRUE — none exists.) |
| 25 | "a free trial… a real tenant… We do not ask for a card to start" | **TRUE** | The trial is created inside `TenantProvisioningService` as a real org; signup takes no card. **Correct nothing.** |

### Migration — ⚠️ the whole topic describes a service that does not exist
| # | claim | verdict | code fact |
|---|---|---|---|
| 6 ⚑ | "under 30 units is typically live in two to three weeks" (+ the 50–200 and spreadsheet timings) | **FALSE** | No migration service, no importer, and no customers to have observed a timeline in. |
| 7 ⚑ | "every future reservation is imported and reconciled line by line" | **FALSE** | There is no importer of any kind. |
| 8 ⚑ | "Nothing on Growth and above… Starter customers self-serve with a CSV importer" | **FALSE** | ⚠️ Names **two tiers that do not exist**, for a service that does not exist, plus an importer that does not exist. |
| 10 | "Can I run both systems at once — yes, and we recommend it" | **HALF** | Nothing prevents running two systems; but the answer implies a supported migration path. |
| 9 | "What does not come across" (card tokens, message threads, reviews) | **TRUE** | These are true statements about the world and match our own refusals. **Correct nothing** beyond its migration framing. |

### Owners
| # | claim | verdict | code fact |
|---|---|---|---|
| 11 ⚑ | "They cannot see **guest names**, contact details or documents" | ⚠️⚠️ **FALSE ON THE NAMES HALF — and it is a privacy claim** | Owner surfaces render the guest name by design, name-only under #28: `OwnerUnitDetail:301` `GuestName::display(...)`, and the same on Owner Bookings and the Block Calendar. Contact details and documents are correctly never shown. |
| 11 | "enforced by scope on every query rather than by hiding parts of a page" | **TRUE** | `OwnerScope` + route capability gates. **Correct nothing.** |
| 12 | "Gross revenue comes from confirmed stays split by night… expenses from resolved maintenance tickets" | **HALF** | ⚠️ Gross is now the **collected** lines — `OwnerStatementService::claimable` → `OwnerCollection::split` holds uncollected bookings. Expenses also come from `owner_expenses` (with an approval state), not only maintenance tickets. |
| 13 | "An owner can add a co-owner or an accountant scoped to their own properties only" | **TRUE** | Owner team, three fixed roles, primary-owner-only invite. **Correct nothing.** |
| 14 | "the reason comes back from the bank and is shown verbatim on the owner's dashboard" | **TRUE** | `payouts.failure_reason` exists and `OwnerPayouts:183,208` renders it verbatim when present. |
| 14 ⚠️ | "The amount returns to their available balance and **the next run picks it up** once details are corrected" | **UNVERIFIED — must be settled before restoration** | Failed-payout retry (`DO3-3`) is an open item in the project record. This is a money claim and is NOT being restored on an assumption. |

### Guests
| # | claim | verdict | code fact |
|---|---|---|---|
| 15 ⚑ | "Airbnb and Booking.com connect directly: availability, rates and restrictions push out; reservations… come back" | **FALSE** | Channel sync is STOPPED and account-gated. ⚠️ No future date may be promised in its place. |
| 17 ⚑ | "Door codes, wifi and directions are released… typically the afternoon before arrival, and **only once check-in is complete and the balance is settled**" | **FALSE** | #33: `ArrivalPack::for()` releases on a **DATE** — `checkIn->subDays(LEAD_DAYS)` through check-out. Not on check-in, not on payment, not on an ID. ⚠️ And no hour may be typed. |
| 17 | "secure ID capture where your jurisdiction requires registration" | **HALF** | Capture is real; the document is stored `verification_status = 'pending'`. Nothing is verified — no OCR, no name match. |
| 16 ⚠️ | "Guest payments settle **directly to you** through your own payment provider. **We never hold client funds.**" | **UNVERIFIED — must be settled before restoration** | ⚠️ A client-money and regulatory claim. The stack uses Stripe (hosted Checkout + Connect for owner payouts) and the flow-of-funds must be read end to end before this sentence is published. Not restored on an assumption. |

### Security
| # | claim | verdict | code fact |
|---|---|---|---|
| 18 | "retained for **the statutory period you set**" | **FALSE** | The windows are fixed in code — `guest-documents:purge-expired --days=30`, the 14-day guest deletion, 90-day activity pruning. There is no operator-facing retention setting. |
| 18 | "personal fields are replaced rather than rows deleted, so your accounts and occupancy history survive" | **TRUE** | `anonymize()` strips PII and stamps `anonymized_at`; rows are kept. **Correct nothing.** |
| 18 ⚠️ | "Guests can export or request erasure of their own data without contacting you" | **UNVERIFIED — must be settled before restoration** | The operator has bulk erasure/export on the Guests CRM; whether a GUEST can self-serve both from the guest portal was not established. A GDPR claim is not restored on an assumption. |
| 19 | "Where is data hosted? **In the EU.**" | ⚠️ **UNSUPPORTABLE** | There is no production deployment. `docs/DEPLOYMENT.md` commits to no region. A hosting claim cannot be true of a system that is not hosted. |
| 20 ⚑ | "Yes, across all three portals, and **admins can require it account-wide**" | **FALSE on the enforcement half** | No org-wide 2FA policy column, middleware or command exists. ⚠️ "across all three portals" is also unconfirmed — no 2FA route was found under the guest realm. |

### Fit — positioning, not product claims
| # | claim | verdict |
|---|---|---|
| 21 ⚑ | "When should I choose something else" (shorter integration list, newer vendor) | **TRUE** — an honest self-assessment that matches reality. **Correct nothing.** |
| 22 | "Hotels, serviced apartments, villas… a mixed portfolio in one account" | **TRUE** — the product's stated purpose, and multi-property is real. |
| 23 | "If every unit is yours you are not using the thing we are best at" | **TRUE** — opinion, honestly framed. |
| 24 ⚑ | "**Starter** exists for operators who have outgrown that" | **FALSE** — no Starter tier. |

## `/glossary` — findings (all 35 verified)

⚠️ **Most terms are INDUSTRY definitions and are correct as vocabulary** (ADR, RevPAR, OTA, rate parity,
lead time, changeover, walk, walk-in, work order, folio, extranet, PMS, shoulder season, rack rate,
mapping, unit type, city tax, remittance advice, processor, anonymisation…). ⚠️ **The dangerous ones are
those that describe OUR behaviour**, because a glossary defines the words every other page uses:

| # | term | verdict | code fact |
|---|---|---|---|
| 3 ⚑ | **Occupancy** — "Units out of service are excluded from the denominator" | **FALSE** | ⚠️ The same defect as `R1`, and here it is wrong for every page at once. Operator occupancy = `Σ availability.total`; `AvailabilityService:57-62` counts an out-of-service unit into `availability.out_of_order` and **leaves it in `total`**. |
| 4 ⚑ | **Rate plan** — "base rate, seasons, weekend uplift and any per-night override, **resolved in that order**" | **FALSE** | ⚠️ There is no read-time resolution chain. Seasons and weekend uplift are **MATERIALIZED into `rate_calendar`** (`SeasonalRateService:15`, `RateAndRestrictionService::setRateWeekendAware`) — one price per date, last write wins. Only the per-unit override resolves at read time (`NightlyRate`, "A season never touches it"). |
| 4 ⚑ | **Rate plan, why it matters** — "An override always beats a season, and a season always beats the base. Work down that list when a price looks wrong." | **FALSE, and actively misleading** | A season does not "beat" a base — it **overwrites** it, and a later base write overwrites the season. The debugging advice sends an operator down a chain that does not exist. |
| 6 ⚑ | **Minimum stay** — "The shortest booking you will accept on a given date" | **TRUE** | Now genuinely enforced by `RestrictionGuard`. **Correct nothing** in the definition. |
| 6/7 ⚑ | "cannot be sold to anyone, at any price" / "so it cannot be sold at all" | **FALSE** | ⚠️ Direction two — our own `reservations.restriction_override` made these false again. Anyone who can take a booking can override with a recorded reason. |
| 23 ⚑ | **Unit** — "What you are billed on" | **FALSE** | Flat plans; unit count does not reach the bill. |
| 18 | **Out of service** — "so **no channel** can book it" | **HALF — NOT flagged by the audit** | The block is real locally (`AvailabilityService` recompute). There is no live channel push, so nothing reaches a marketplace listing. |
| 21 | **Guest-ready** — "cleaned, inspected and **released** to the front desk" | **HALF** | `housekeeping_status` reaches the desk immediately, but `HousekeepingService:17-19` — it does **not** affect availability; only `condition_status` does. |

## ⚠️ Claims where the CODE may be the wrong side — REPORTED, fixed NONE
- **#4 / #19** — a customer cannot export their own account, and we make no hosting-region commitment.
  Both are product decisions, not sentences.
- **#18 retention** — a fixed 30-day window presented as "the statutory period you set". ⚠️ Statutory
  registration-retention periods differ by jurisdiction; a fixed window is a real gap, not a wording error.
- **#20** — no org-wide 2FA enforcement, which a business buyer will expect to exist.

## ⚠️ Three questions may have no honest answer left
`#8 What does migration cost?`, `#6 How long does migration take?` and `#7 Will I lose bookings when I
migrate?` all describe a migration service that does not exist. ⚠️ **A question with no honest answer
should not be asked** — the recommendation is to REMOVE these three questions rather than answer them with
a denial, and to say so plainly. That decision is recorded here rather than taken unilaterally.

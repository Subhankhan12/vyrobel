# FRONT WEBSITE · GATE 5 — THE DOC-TRUTH AUDIT

**27 pages · 19 Docs + 8 Resources · audited against the actual product · 2026-08-22**

> ⚠️ **DOCUMENTATION IS A CLAIM ABOUT HOW THE PRODUCT BEHAVES.** A guide describing a feature that does not
> exist, is not enabled, or behaves differently is a false product claim — and unlike the content ledger's
> figures, **no existing CI gate can see it**. The ledger governs DATA; these pages are PROSE ABOUT BEHAVIOUR.
>
> ⚠️ **NOTHING IN THIS DOCUMENT CHANGES THE PRODUCT.** Every divergence is surfaced with three options and
> **none is picked**. A product change is its own gate, with its own tests. The two chrome corrections in §6
> are the sole exception, and they are already-shipped marketing copy, not product behaviour.
>
> **Method.** One agent per page extracted every behavioural claim and traced it to the code that decides it;
> a second, adversarial agent then tried to *refute* each non-TRUE verdict by opening the cited file. The
> sharp pages (Payouts · Security · GDPR · Channels · Arrival · Access · Refunds) and the FAQ were also
> audited independently, by hand, before any agent result was read.

---

## THE HEADLINE

**All 27 pages were audited. None can be published as written.**

The documentation set was authored as a description of the product the design imagined, not the product that
exists. That is not a small gap to be tidied: on the sharp pages it is the difference between a reader
believing their guests' door codes are withheld until check-in (they are not), that their statutory GDPR
retention is configured (there is no such setting), that they are billed per unit (billing is flat), and that
Vyrobel does not hold their money (guest payments land in the platform's own Stripe balance).

⚠️ **Seven findings are product findings, not documentation findings**, and are surfaced for their own
gate. Two are below; **P-4** to **P-7** are in **§5d**, and two of those are large.

| # | finding | why it matters |
|---|---|---|
| **P-1** | ⚠️ **No operator UI writes `units.door_code` / `wifi_ssid` / `wifi_password` / `arrival_notes`.** Every reference outside the model, the seeders and the tests is a READ. | The arrival-secrets feature (#33) is correctly gated on the guest side and **cannot be populated by a real operator**. It only ever holds data because `DemoSeeder` puts it there. |
| **P-2** | ⚠️ **The door code is released on a date, not on check-in.** `ArrivalPack` releases it exactly `LEAD_DAYS = 2` days before arrival, until checkout, for any non-cancelled reservation — and `SendPreArrivalEmails` emails it to every `confirmed` reservation on that day. Neither consults `guest_checked_in_at`, an ID on file, or the folio balance. | ⚠️ **This is a deliberate, coherent design, not a defect** — one shared resolver, no drift, and the operator can disable the template per-org (#86). It is simply **not what the documentation describes**: three pages tell the reader the code is withheld until check-in is complete and the balance settled. |

⚠️ **A third candidate finding was withdrawn before it reached this page, and the withdrawal is worth
recording.** The audit proposed *"a failed payout's amount appears in none of the Balance page's four
buckets"* — Available / On hold / In transit / Paid do all exclude `failed`, and the statement is already
stamped, so the reasoning looked sound. **It is wrong.** `OwnerBalance` has a **fifth** bucket, *Carried
forward*, defined in the code as *"stamped rows whose statement has NO active payout — a negative
(carried-forward) statement, **or one whose only payout failed**"*, and the identity it maintains is
`Available + On hold + In transit + Paid + Carried forward == Net`. The product had already handled exactly
this case. ⚠️ **Confirm-first cuts both ways (§N.27.11)** — five times in this session, and this one would have put a
false product defect in front of the founder.

⚠️ **And the chrome was already shipping two false claims on every page. Both are fixed** — see **§6**.

---

## §1 ⚠️ PAYOUTS — `/docs/payout-runs`

**Verdict: DEFER.** ⚠️ `STRIPE_CONNECT_WEBHOOK_SECRET` is **absent** from `.env` (`config/services.php:184`
reads it → null), `CHANNEX`-style test-mode is the payout engine's designed posture (`PayoutEngineMode`), and
the command is inert without `PAYOUT_ENGINE_ENABLED`. The guide describes a rail whose completion half cannot
verify a signature.

| the page claims | reality | verdict |
|---|---|---|
| "A payout is generated from a published statement. It cannot exist without one." | `OwnerPayoutService::createFromStatement` throws unless the statement `isLocked()`. | **TRUE** |
| The states: **Scheduled** → Processing → Paid → On hold → Failed | The real enum is `pending`, `processing`, `held`, `paid`, `failed` (+ `reversed`). There is no `scheduled`. | **PARTIAL** — vocabulary mismatch a reader will not find on screen |
| "**Processing** — Submitted to the bank." | Nothing reaches a bank. In test mode the fake client simulates; live, the Connect transfer's confirming webhook has **no signing secret configured**. | **FALSE (critical)** |
| "**Failed** — The bank rejected it. The reason comes back from the bank and is shown verbatim." | `payouts.failure_reason` exists and is displayed, but with no live rail no bank reason is ever produced. | **UNVERIFIABLE** |
| "A failed payout does not vanish. **The amount returns to the owner's available balance**." | The amount is not lost — but it does **not** return to *Available*. Available is Σ **unstamped** ledger rows; a failed payout's statement is stamped, so the money sits in the Balance page's fifth bucket, **Carried forward**. The doc names the wrong bucket, on the one page an owner reads to find their money. | **FALSE** |
| "**the next run picks it up** once the details are fixed. You do not need to recreate anything." | Defensible: `failed` is outside `Payout::LIVE_STATUSES`, so `createFromStatement` would mint a new payout on a subsequent run. ⚠️ But it contradicts the next paragraph. | **PARTIAL** |
| "**Retries are manual by design.**" | ⚠️ TRUE, and better than expected: `owner.payouts.retry` → `OwnerPayoutService::retry()` writes a new `pending` row under a row lock, idempotent, audited (`routes/web.php:1328`, `OwnerPayoutService.php:240-282`). ⚠️ **The register's open `DO3-3` was stale — this is built.** | **TRUE** |
| "Paused funds sit in their balance, visible and **withdrawable on demand**." | ⚠️ There is **no owner-initiated transfer rail**; "Withdraw early" is deliberately disabled and documented as such (`app/Livewire/Owner/OwnerBalance.php:55-58`). | **FALSE (critical)** |
| "The run happens on the payout day set for that owner, **offset from the statement date by the number of days in Settings**. Owners can choose their own payout day **within the range you allow**." | `owner_payout_preferences` holds `frequency`, `payout_day`, `minimum_minor`, `paused`. There is **no statement-date offset setting** and **no operator-configured allowed range**. | **FALSE** |
| "Every payout keeps … its **full state history** including any failure reasons." | Single-valued columns (`paid_at`, `failed_at`, `held_at`, `failure_reason`) plus `supersedes_id`. There is no per-payout state-history the owner can read. | **PARTIAL** |
| "Refunds issued after a statement was published appear as an adjustment on the following payout." | `AdjustOwnerLedgerOnRefund` (#100) posts the owner's net-ratio share to the next statement. | **TRUE** |

---

## §2 ⚠️ SECURITY — `/docs/account-security`

**Verdict: DEFER.** ⚠️ Four of this page's claims are **security properties that the product does not have**.
A security claim is either backed by code or it is false; it is never softened into vagueness.

| the page claims | reality | verdict |
|---|---|---|
| "Security, in your account menu." | `/app/security` exists (`app.security`), with 2FA enable/disable/regenerate, passkeys, IP rules and an export. | **TRUE** |
| "An authenticator app is the default…" | TOTP is real for operator, owner and platform-admin realms. | **TRUE** |
| "**SMS is offered** because some teams need it, and is meaningfully weaker." | ⚠️ **SMS does not exist anywhere in the product.** There is no provider; every SMS surface is honestly gated ("not set up yet"), and the code says so in six places. | **FALSE (critical)** |
| "Save your recovery codes — shown once." | `users.two_factor_recovery_codes`, `encrypted:array`, regenerable with the current password. | **TRUE** |
| "**Admins can require 2FA account-wide.** Existing users are prompted at their next login." | ⚠️ No tenant-level enforcement exists. 2FA is mandatory only for `PlatformAdmin` (`EnsurePlatformAdminTwoFactor`) — a different actor entirely. | **FALSE (critical)** |
| "The sessions list shows every signed-in device with its **approximate location** and last activity." | The shared `ActiveSessions` returns `device` (parsed UA), `ip`, `last_active`. ⚠️ **There is no geolocation anywhere in the application.** | **FALSE** |
| "Ending a session signs that device out immediately." | Real, self-only, on both owner and operator surfaces. | **TRUE** |
| "Any third-party application … appears here with the specific permissions it holds — **read reservations, write availability**, and so on." | ⚠️ `OAuthScopes` is **read-first and read-only**: `read:bookings`, `read:statements`, `read:units`. **There is no write scope of any kind.** Telling a reader an app can be granted write access to availability describes a capability the authorisation server refuses to issue. | **FALSE (critical)** |
| "a disconnected **channel manager** stops updating availability" | No channel manager authorises via OAuth; the OAuth server serves owner-scoped read APIs. | **FALSE** |
| "Changing a password requires the current one and **signs out other sessions**." | ⚠️ TRUE — `ChangePasswordController` verifies `current_password` and `changePasswordTo($password, $currentSessionId)` terminates every other session. (A first grep for `logoutOtherDevices` found nothing; **the needle could not match** — the file had to be opened.) | **TRUE** |
| "The strength meter … enforces a minimum, and **the generator** produces something you can store in a manager." | No password generator exists on any security surface. | **FALSE** |
| "We never email passwords, never ask for one in support, and no member of our team can see yours." | Hashed storage; no plaintext path. A policy statement the code supports. | **TRUE** |

---

## §3 ⚠️ GDPR — `/docs/gdpr-and-data`

**Verdict: DEFER.** ⚠️ The entire **Retention** section describes a settings screen that does not exist and a
statutory-anonymisation sweep that was never built. ⚠️ And the page's two "automatically" claims are, on a
host without the single `schedule:run` crontab line, **false on their face** — the page never mentions it.

| the page claims | reality | verdict |
|---|---|---|
| "Payment references — tokens held by your payment provider, **never raw card numbers**." | Hosted Stripe Checkout; SAQ A; no PAN is ever handled. | **TRUE** |
| "**Retention. Settings, Data and privacy. Set once per property.**" | ⚠️ The operator Settings screen has twelve sections — Property basics · Policies · Tax & fees · Invoice details · Your plan · Notification routing · Guest messages · Owner · Property status · Guest support · Taxes · Organization. **There is no "Data and privacy".** | **FALSE (critical)** |
| "**Set the statutory period** … then anonymised automatically. Set the actual legal figure for where you operate." | ⚠️ No statutory-retention setting exists as a column, config value or screen. ⚠️ **And no statutory anonymisation sweep exists at all**: `guests:process-deletions` executes *guest-requested* deletions after a 14-day grace. An operator following this instruction would believe their legal retention is configured when nothing is. | **FALSE (critical)** |
| "**Set ID document retention separately.** Images are deleted automatically at the end of it." | Real deletion exists — `guest-documents:purge-expired`, 30 days after checkout — but the window is a **command-flag default**, not a per-property setting. | **PARTIAL (critical)** |
| "**Leave anonymisation on**" (implying a toggle) | There is no toggle. `Guest::anonymize()` is the only erasure path and is always the behaviour. | **FALSE** |
| ⚠️ "anonymised **automatically**" / "deleted **automatically**" | Both commands run **only** under `* * * * * php artisan schedule:run`. On a host without it they never run, silently. ⚠️ The page never says so. | **PARTIAL (critical)** — true only with the cron |
| "A guest with an account can **export their own data** or request deletion from their account." | ⚠️ Deletion: real and self-service (`guest.account.delete`, reversible 14-day grace). **Export: there is no guest-realm export route.** The only "guest export" is the operator's bulk export — a different actor. | **PARTIAL (critical)** |
| "the operator side has an export and an erasure action against a guest record … records who actioned it and when." | `app.guests.anonymize`, `app.guests.bulk.anonymize`, `app.guests.bulk.export`, audited. | **TRUE** |
| "**You cannot erase what you are legally required to retain, and the product will tell you so** rather than silently doing nothing." | ⚠️ No statutory-retention block exists; nothing refuses an erasure on those grounds and nothing explains it. | **FALSE (critical)** |
| "our **data processing agreement** sets that out" | No DPA exists in the repository or as a published page. | **UNVERIFIABLE (critical)** — a legal-document claim; the founder's to confirm |
| "**Data export … in CSV or JSON**, without a support request … one click." | ⚠️ No account-wide export exists; exports are per-surface (guests bulk, maintenance, reports) in CSV/PDF/XLSX. **There is no JSON export anywhere in the product.** | **FALSE** |

---

## §4 ⚠️ CHANNELS — `/docs/connecting-channels`

**Verdict: DEFER — the sharpest end-to-end falsehood in the set,** and it names two third parties in its title.

The product's own channels screen states the truth in its docblock: *"the whole page is OTA sync, which is
ACCOUNT-GATED — live two-way sync + booking import are NOT wired"* (`OperatorChannels.php:22-27`), and rows
read **"sync activates with your Channex connection"**. `CHANNEX_FAKE=true`; `CHANNEX_API_KEY` and
`CHANNEX_WEBHOOK_SECRET` have **no value**; the real `ChannexClient` *"is config-gated and throws until Channex
credentials exist"* (`ChannelConnectionService.php:30-31`).

Against that, the guide walks the reader through **Authorise → Map units → Map rate plans → Choose what to
push → Run the first sync**, and asserts "Vyrobel is the source of truth once connected", "the channel page
shows the last successful sync per connection and any errors verbatim from the channel", and inbound "guest
messages where the channel exposes them" (OTA inbox channels are on the deferred list). ⚠️ There is also **no
channel-side authorisation flow** — nothing redirects to a channel to grant access.

**16 of 22 claims FALSE, 3 critical.** ⚠️ It also asserts things about **Airbnb** and **Booking.com** and so
falls under the claims ledger (`resources/marketing/claims.json`) as well as this audit.

---

## §5 THE PER-PAGE VERDICTS

⚠️ **Every page audited. 27 of 27 refuse publication as written** — 22 outright, 5 only after a doc fix
the founder must choose (and §5c blocks those too).

| page | verdict | claims | the sharpest false claim on it |
|---|---|---|---|
| `/docs` | **DEFER** | 7T · 9P · **6F** · 2U | "Connecting Airbnb and Booking.com — Mapping units to listings, what syncs each way, and reading c |
| `/faq` | **DEFER** | 5T · 12P · **6F** · 5U | "Airbnb and Booking.com connect directly: availability, rates and restrictions push out; reservati |
| `/glossary` | **DEFER** | 13T · 9P · **6F** · 1U | Occupancy — "Units out of service are excluded from the denominator, so a maintenance closure does |
| `/docs/first-property` | **DEFER** | 7T · 4P · **6F** · 1U | "The timezone decides when your day rolls over on every board in the product, so use the property' |
| `/docs/taking-a-reservation` | **DEFER** | 11T · 10P · **12F** · 1U | "Pick arrival and departure, then the party size. Availability is calculated from these before you |
| `/docs/check-in-and-folio` | **DEFER** | 9T · 7P · **14F** · 1U | "The panel shows the room's housekeeping state live." |
| `/docs/housekeeping-board` | **DEFER** | 3T · 6P · **7F** · 1U | "If you do not inspect rooms, turn inspection off in Settings and Clean becomes the final state." |
| `/docs/rate-plans` | **DEFER** | 1T · 3P · **10F** · 1U | "For any given night, Vyrobel resolves the price in four passes: the unit's base rate, then the se |
| `/docs/connecting-channels` | **DEFER** | 2T · 3P · **16F** · 1U | Title: "Connecting Airbnb, Booking.com and other channels" — the page is presented as an operating |
| `/docs/reports-and-exports` | **DEFER** | 4T · 5P · **10F** · 1U | "Units out of service are excluded from available nights, so a maintenance closure does not show u |
| `/docs/storefront` | **DEFER** | 1T · 6P · **7F** · 1U | "Getting it live — Settings, Storefront. Most of this is content rather than configuration." |
| `/docs/online-check-in` | **DEFER** | 2T · 7P · **21F** · 1U | "Setting it up — Settings, Guest experience. Configure once per property." |
| `/docs/refunds-and-changes` | **DEFER** | 3T · 8P · **11F** · 4U | "The policy on the rate plan decides the default." |
| `/docs/owner-statements` | **DEFER** | 5T · 9P · **8F** · 1U | "A stay spanning two months is split by night, not assigned whole to one of them." |
| `/docs/payout-runs` | **DEFER** | 7T · 7P · **12F** · 1U | "The run happens on the payout day set for that owner, offset from the statement date by the numbe |
| `/docs/invites-and-access` | **DEFER** | 15T · 9P · **7F** · 1U | Step 2 "Scope to properties — On a multi-property account, choose which properties they work in." |
| `/docs/settings` | **DEFER** | 2T · 12P · **11F** · 1U | "Owners can choose their own payout currency separately where you allow it; the conversion happens |
| `/docs/account-security` | **DEFER** | 9T · 5P · **8F** · 2U | "SMS is offered because some teams need it, and is meaningfully weaker — use it only where an app  |
| `/docs/gdpr-and-data` | **DEFER** | 5T · 5P · **10F** · 1U | "Retention — Settings, Data and privacy. Set once per property." |
| `/resources` | **DEFER** | 3T · 2P · **7F** · 2U | The page is published at /resources (canonical link rel="canonical" href="https://vyrobel.com/reso |
| `/resources/growing-direct-bookings` | **DEFER** | 5T · 9P · **3F** · 6U | "a branded storefront" |
| `/resources/booking-direct-trust` | **PUBLISHABLE WITH DOC FIX** | 2T · 10P · **1F** · 1U | Point 5: "A recognised card form, on your own domain, with the padlock… A bank transfer request fa |
| `/resources/pricing-levers` | **DEFER** | 3T · 3P · **6F** · 4U | "…in one grid, pushed to every channel." |
| `/resources/housekeeping-at-forty-units` | **PUBLISHABLE WITH DOC FIX** | 5T · 10P · **3F** · 0U | "Drag between lanes" — on the phone in their pocket |
| `/resources/out-of-service` | **PUBLISHABLE WITH DOC FIX** | 3T · 4P · **1F** · 0U | CTA card: "A ticket that takes the unit off sale … blocking every channel immediately" — reinforce |
| `/resources/owner-statement` | **PUBLISHABLE WITH DOC FIX** | 5T · 7P · **7F** · 0U | The portal shows "each expense with its work order attached". |
| `/resources/winning-owners` | **PUBLISHABLE WITH DOC FIX** | 5T · 7P · **4F** · 0U | "expenses linked to their work orders" (CTA) / "where an expense links to the work order behind it |

⚠️ **Adversarially re-checked — but not everywhere, and the gap is named.** Nineteen verification agents
re-opened the cited files for the sharp pages and every FALSE call, tasked with *refuting* them. **Zero
findings were overturned**, and their recurring note is that where an auditor was imprecise, the imprecision
made the finding **weaker** than reality, never wrong.

⚠️ **Eight verification agents did not run** — they died on a session limit, and they were the ones covering
**all seven `/resources` articles and `/docs/settings`**. Those eight pages' audits therefore stand
**un-refuted**, not verified. ⚠️ Recorded rather than glossed: on the articles the finding that matters is the
**210 unsourced figures**, which is a completeness claim an adversarial pass would have pressed hardest.
Re-running those eight is the first thing to do if any article is ever reconsidered.

**Totals across 27 audited pages: 591 claims — 142 TRUE · 188 PARTIAL · 220 FALSE · 41 UNVERIFIABLE, 59 of them critical.**

---

## §5b ⚠️ THE RESOURCES ARTICLES — the same discipline, and they fail it

⚠️ **They are not "just thought leadership."** Any figure, benchmark or outcome claim is subject to the
ledger and the claims gate, and any claim about a named third party needs `source_url` + `verified_on` +
`review_by`. On that test the seven articles fail comprehensively.

⚠️ **AND MY OWN FIRST SWEEP OF THEM WAS BLIND.** I searched the article bodies for `%`, `€` and the word
"Vyrobel", found almost nothing, and provisionally called five of them clean. They are not. The claims that
matter contain **no number and no brand**:

> *"Most operators answer no to at least two."* · *"Marketplaces treat operator-side cancellations badly, and
> repeated ones affect your ranking."* · *"Teams that add a fifth and a sixth find nobody updates any of
> them."* · *"Most OTA contracts in Europe no longer enforce strict price parity."* · *"Group chats work to
> about fifteen units."*

Each is a statistic, a claim about how a third party's ranking algorithm behaves, or a claim about the
industry — and none has an origin. **§N.27.1 again: a content sweep is blind to the absence it seeks.**
**210 unsourced figures / outcome claims** were extracted across the 27 pages — the full list is in
`FRONT-GATE5-CLAIMS-APPENDIX-2026-08-22.md`, alongside every non-TRUE claim with its citation.

Three specific findings:

| finding | detail |
|---|---|
| ⚠️ **`/resources/growing-direct-bookings` carries ledger entry B8** | *"keep the fifteen to eighteen percent"* is the SAME unsourced OTA-commission claim as **B8** in `resources/marketing/claims.json` — the claim for which `/compare` is currently HELD. ⚠️ Publishing this article would ship, through a different page, the exact claim the ledger is holding. Gate 3b's shape, a third time. |
| ⚠️ **The reading times are 2–3× overstated, sitewide** | `/resources` labels the housekeeping article "11 min" (≈797 words), the direct-bookings article "14 min" (≈994 words), the trust article "7 min" (≈875 words). Every guide card on `/docs` carries the same kind of figure. Small, but it is a fabricated number on 27 pages. |
| ⚠️ **`/resources` asserts operating experience the company does not have** | *"What we've learned running property operations"* and *"Roughly one a month"* (the archive is dated fortnightly, and stops on 22 Jul 2026). Same class as D1's invented customers. |

⚠️ **Two articles came back `PUBLISHABLE_WITH_DOC_FIX`** — `/resources/housekeeping-at-forty-units` and
`/resources/out-of-service`. **They are not built**, for two reasons: the "doc fix" is one of the three
options the founder must choose, not the session; and §5c below applies to them regardless.

---

## §5c ⚠️ WHY NO SUBSET CAN PUBLISH — THE SET IS SELF-REFERENTIAL

⚠️ **Found by reading the pages, not by any grep.** Every one of the 23 guide and article pages carries a
counter in its own footer:

> `Guide 1 of 16` … `Guide 16 of 16` · `Article 1 of 7` … `Article 7 of 7`

and every guide additionally renders **the complete sixteen-item sidebar** of its siblings. `/faq` announces
**"18 questions"**. So each page **asserts the whole set exists**.

⚠️ Publishing two articles would put *"Article 4 of 7"* and *"Article 2 of 7"* on a site with two articles —
the same false-count shape as the chrome's "sixteen written guides" in §6, on the very pages being published
to fix it. **Removing the counters is a doc fix, which is the founder's option to pick, not the session's.**

**Net: 0 of 27 pages built.** ⚠️ That is the finding, not a shortfall against it.

---

## §5d ⚠️ FURTHER PRODUCT FINDINGS — SURFACED, NOT FIXED

Four more surfaced by the audit and verified independently. ⚠️ **Both are inside the product, not the
marketing site, and neither is touched here.**

| # | finding | detail |
|---|---|---|
| **P-4** | ⚠️ **The operator Channels screen renders hardcoded third-party commission rates.** | `resources/views/livewire/operator/channels.blade.php:117` ships a static tile grid: **Booking.com 15% · Airbnb 3% · Expedia 15% · Vrbo 8%**. These are unsourced claims about four named third parties' pricing, rendered *inside the application*. ⚠️ They are the same class as ledger entry **B5** — *"an unsourced claim about COMPETITOR PRICING — the sharpest legal exposure"* — which audit B ruled **REMOVE**. The ledger governs the marketing site and cannot see this. |
| **P-5** | ⚠️ **The GDPR page names no sub-processor, and there are at least two.** | **Postmark** is the production email provider today (`docs/EMAIL-PRODUCTION.md`), and `config/services.php:190-203` declares an **Anthropic** LLM seam for the "Ask Vyrobel" assistant. ⚠️ The LLM seam is inert by default (`fake => true`, plus `LlmSafetyGuard` requiring a key AND production), so this is a **disclosure obligation before it is switched on**, not a present breach — but a page that says *"we are your processor … our data processing agreement sets that out"* while naming no sub-processor is incomplete either way. |

| **P-6** | ⚠️⚠️ **An operator cannot create a room type, a rate plan or a channel — and can only create a unit by approving an owner's request.** | `RoomType::create`, `RatePlan::create` and `Channel::create` appear **nowhere in `app/`**; they exist only in `DemoSeeder`. The one `Unit::create` in `app/` is `OwnerUnitRequestService.php:217`, reachable only when an external owner submits an add-unit request. `/app/units` is a read-only listing. ⚠️ A tenant provisioned through `/signup` therefore has a property with **no room types, no units and no rate plans, and no product surface to add any** — which is why several guides describe a first hour the product cannot deliver. |
| **P-7** | ⚠️⚠️ **Per-property staff scoping is unreachable in production.** | The `property_user` allow-list is pivot-or-all (#38). The **only** write to that table anywhere in the application is `PropertyController.php:73`, and it fires `if ($user->propertyAssignments()->exists())` — a condition only a pre-existing row could satisfy, and no other code path creates one (rows exist solely in `MultiPropertyContextTest`). ⚠️ So the allow-list can never be populated through the product, and **every back-office user resolves to the "all properties" branch**. The guides that explain "how to scope someone to one property" describe a control that has no way to be used. |

⚠️ **Also worth the founder's eye:** `AirbnbChannel` and `BookingComChannel` are `DeferredChannel` skeletons
that **throw** on both `send()` and `normalizeInbound()`, and `channels.provider` accepts only `channex`.
There is no Airbnb or Booking.com connector of any kind — which is what makes `/docs/connecting-channels`
false end to end rather than merely premature.

---

## §6 ⚠️ THE CHROME SWEEP — TWO FALSE CLAIMS ARE ALREADY LIVE

⚠️ The chrome has now carried an untrue claim **four** times (competitor names · "Three operators who
switched" · "A real booking, no signup" · and these). A ruling scoped to a page never reaches it.

| where | the claim | reality |
|---|---|---|
| `components/marketing/footer.blade.php:17` — the release banner, **on every page** | "Multi-currency pricing … **prices in EUR, GBP or USD**" | ⚠️ **FALSE.** `config/billing.php` has a single `default_currency => 'EUR'`, and all four plan rows are EUR. There is no GBP or USD price. |
| `footer.blade.php:17` + `nav.blade.php:57` (`desc="Sixteen written guides"`), **on every page** | "**sixteen written guides**" / "Documentation — Sixteen written guides" | ⚠️ **FALSE.** Zero guides are published, and this audit defers all sixteen. |

⚠️ **Both were already LIVE on every page, and both are FIXED in this gate** — not surfaced for a decision.
The three options above govern the *Gate-5 pages*, which are unpublished; these two sentences were shipped
and false, and a live false pricing claim is not something to leave standing pending a ruling. Gate 4 set the
precedent when the same sweep found the nav promising "A real booking, no signup".

* The **release strip is removed** rather than reworded: both halves of it were false, it announced a release
  that did not happen, and **KIND-2 (fabricated content) is REFUSED, not restyled**. Real release notes
  belong on the Changelog page, which is Gate 6's. Its CSS is left in the shell for that return.
* The **nav description** drops the count: `desc="Guides and reference — not published yet"`. The row already
  rendered as a marked non-link (the page is unrouted); only the description was still claiming content.

⚠️ Nothing else in the chrome was touched, and no Gate-5 page copy was rewritten.

---

## ⚠️ THE DIVERGENCES, SURFACED — THREE OPTIONS, NONE PICKED

For every page the same three options apply. **The session does not choose, and does not change the product
to make a doc true.**

| option | what it means here |
|---|---|
| **(a) FIX THE DOC** | Rewrite the page to describe what the product actually does. Cheapest, immediate, and for most pages a substantial rewrite rather than an edit — several sections describe screens that do not exist at all. |
| **(b) FIX THE PRODUCT** | Build the missing capability, then publish the doc unchanged. ⚠️ Its own gate, with its own tests. Applies most obviously to **P-1** (an operator UI for door codes), the GDPR retention settings, and the OAuth write scopes. |
| **(c) DEFER THE PAGE** | Leave it unrouted. Every link to it already renders as a **marked non-link** through `MarketingUrls::pending()`, which is the designed behaviour — never a silent 404. ⚠️ But see §6: the chrome still asserts the guides exist. |

⚠️ **Two decisions are the founder's alone and block everything else:**

1. ⚠️⚠️ **"Does Vyrobel hold my money?"** The FAQ answers *"No. Guest payments settle directly to you through
   your own payment provider. We never hold client funds."* **The code does not do that.**
   `createPaymentCheckout` creates the Checkout Session on the **platform's own Stripe account** — no
   `on_behalf_of`, no `transfer_data`, no connected-account option — and owners are paid by
   `createTransfer(..., destination: <connected account>)`, i.e. **a transfer out of the platform balance**.
   Whether that constitutes holding client funds is a question for the founder and their counsel; what is
   certain is that the sentence as written is contradicted by the code. **Nothing about this may publish
   until it is settled.**
2. ⚠️ **The DPA.** Two pages assert a data processing agreement exists. None was found. A processor claim
   without the agreement behind it is the class of claim that has to be correct.

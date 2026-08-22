# DOCS TRIAGE — 220 false claims → 10 decisions

**Read-only · 2026-08-22 · triage of `FRONT-GATE5-DOC-TRUTH-AUDIT-2026-08-22.md` at `ec8ce8f`**

> ⚠️ **NOTHING WAS BUILT, FIXED, PUBLISHED OR CHANGED.** No doc was edited, no product code touched. Money as
> seeded byte-identical (929 / 10,912,000 / 0 `unit_rate_overrides` / 41 / 16 / 0), verified before and after.
>
> ⚠️ **NO OPTION IS PICKED.** Two recommendations appear, and both are technical facts rather than
> preferences (F-1 SMS 2FA; A-1 the Channex account). Everything with a business trade-off stays the
> founder's.

---

## ⚠️ 0 · WHY THIS PASS EXISTS — the trap in "fix the docs AND build the missing features"

The instinct is right; the *literal* reading is dangerous. **These 27 pages were generated as illustrative
front-site content. Nobody authored them as a specification.** They describe an imagined product.

Building to them literally would ship, on the authority of a document nobody wrote:

| what the docs would make us build | why that is wrong, not merely costly |
|---|---|
| **SMS two-factor** | ⚠️ **Weaker than the TOTP already shipped.** SIM-swap and SS7 interception are live attack classes; NIST has deprecated SMS as an out-of-band authenticator. Building it is a **security downgrade adopted because a generated sentence mentioned it.** |
| **Geolocation of sessions** | The product does not merely lack it — `SecurityHeaders.php:99` **actively denies it** (`Permissions-Policy: geolocation=()`). Building it means adding an IP-geolocation processor, and a new sub-processor disclosure, to make a sentence true. |
| **A `write:availability` OAuth scope** | `OAuthScopes.php:19` refuses write scopes **by design**, deliberately documented. The docs describe a capability the authorisation server exists to withhold. |
| **Channels / OTA sync** | ⚠️ **Not buildable by decision at all** — it needs a Channex partner account the founder does not have. |

⚠️ **So the question is never "docs or product". It is, per cluster: is the imagined thing worth having?**

---

## ⚠️ 1 · RE-VERIFICATION — what changed since Gate 5, and what Gate 5 got wrong

### 1.1 ⚠️ How many FALSE claims are now TRUE: **zero. Two moved FALSE → PARTIAL.**

P-6 Gate 1 (`2edf171`) built the creation paths, and I verified them in the **UI**, not just the routes:
`units-list.blade.php:198` (Add room type), `:218` (Add unit), `rates-grid.blade.php:512` (Add rate plan),
all posting to `InventoryActionController`. The file even records the change: *"WAS 'Add unit · Soon'."*

⚠️ **But not one claim became fully TRUE, because the docs' field lists were wrong independently of the
blocker:**

| claim (`/docs/first-property`) | Gate 5 | now | why not TRUE |
|---|---|---|---|
| "Define unit types before units. A unit type carries **capacity, bed configuration, amenities and a base rate**." | FALSE | **PARTIAL** | Creation is real; capacity is real. ⚠️ **No bed-configuration column exists**, the create form sets no amenities (and there is **no amenity-management route at all**), and the base rate lives on the rate plan, not the room type. |
| "Add the units. **Number or name, floor**, and which type each belongs to." | FALSE | **PARTIAL** | Code, name and room type are real. ⚠️ `units.floor_id` exists but the form does not accept it and **there is no floor or building management route**. |

⚠️ **The honest headline is not "two claims improved" — it is that the CLUSTER's character changed.** The
reason several guides described "a first hour the product cannot deliver" is gone: **the first hour is now
deliverable.** What remains is a wrong field list, which is a doc fix. That is a materially different ruling
from the one Gate 5's snapshot implied.

Six other FALSE claims mention "Soon" or a missing route but are **unrelated** to P-6 and remain false
(notification routing · the statements PDF pack · group booking in the reservation wizard · booking import ·
the mailing list · what online check-in collects).

### 1.2 ⚠️ Gate 5 was RIGHT and the *handoff* is stale — `DO3-3` is already built

Gate 5 noted *"The register's open `DO3-3` was stale — this is built."* **I confirm it, and the correction
did not propagate:** `docs/SESSION-HANDOFF.md` and `PROJECT-STATE.md` still list *"① `DO3-3` — failed-payout
retry"* as **the next thing to do**.

```
POST owner/payouts/{payout}/retry   →  owner.payouts.retry
OwnerPayoutService::retry()         →  OwnerPayoutService.php:240-282
                                       row-locked, re-checked inside the lock, idempotent, audited
```

⚠️ **The founder's own next-action list opens with an item that is finished.** §N.31.8 exactly: a statement
written once and never re-read becomes indistinguishable from a fact — here in the *favourable* direction.

### 1.3 ⚠️ Gate 5 was WRONG in one direction — branding is not as broken as recorded

Gate 5's `/resources/growing-direct-bookings` entry says *"a branded storefront is false as shipped — the
public pages read none of the per-property brand columns."* The second half is true; the implication is not.

**`PropertyBrand` is real and consumed today** — guest transactional emails (`Mail/Concerns/BrandsGuestMail.php:24`),
the guest review page (`GuestReviewController.php:58`), the schedule PDF, and the Settings live preview. The
operator configures it in a **"Guest-facing brand"** Settings section that ⚠️ **Gate 5's own twelve-section
list omits** (the screen has fourteen; I read the headings).

⚠️ **So the accurate finding is narrower and much cheaper to fix:** branding works everywhere except the one
surface that matters most for this claim — the public booking storefront hardcodes
`shell.blade.php:249  <footer>Powered by Vyrobel</footer>` and ignores the columns the operator already
filled in. That moves cluster **H** from "a subsystem" to "wire an existing helper into an existing shell".

### 1.4 ⚠️ LOWER-CONFIDENCE clusters — un-refuted is not verified

Gate 5 states its own gap: 19 adversarial verifiers ran, **8 died on a session limit**, and they covered
**all seven `/resources` articles and `/docs/settings`**. Those eight pages stand **un-refuted, not
verified**.

⚠️ **Clusters resting materially on them — treat their claim counts as provisional:**

| cluster | exposure |
|---|---|
| **S1** (the 210 unsourced claims) | ⚠️ **Highest.** Its core finding is a *completeness* claim across exactly the un-verified articles — the thing an adversarial pass presses hardest. |
| **B** (owner money) | `/resources/owner-statement` (7F) + `/resources/winning-owners` (4F) + `/docs/settings` (11F) are all un-verified. |
| **D** (rates) | `/docs/settings` and `/resources/pricing-levers` contribute. |
| **K** (invented Settings sections) | `/docs/settings` is its largest single source. |

Everything else rests on hand-audited or adversarially re-checked pages.

---

## ⚠️ 2 · THE CLUSTERS — 10 feature decisions + 2 structural gates

All 220 FALSE claims are assigned to exactly one cluster (they sum to 220; 23 are genuine singletons listed
in §5). Boundaries are drawn so that **one ruling settles every claim inside**.

| # | cluster | claims | pages | buildable? | size |
|---|---|---|---|---|---|
| **A** | Channels / OTA sync | 35 | 15 | ⚠️ **NO — external** | — |
| **C** | Invented workflow affordances on real screens | 41 | 13 | yes | many small |
| **D** | Rate model, restrictions & the first hour | 41 | 14 | yes | mixed |
| **B** | Owner money: statements & payouts | 36 | 10 | partly | weeks |
| **E** | Arrival secrets & online check-in | 15 | 5 | yes | days |
| **G** | GDPR retention & data rights | 10 | 2 | yes | weeks |
| **F** | Security properties that do not exist | 8 | 3 | yes | ⚠️ mostly undesirable |
| **H** | Storefront branding & own domain | 5 | 3 | yes | days / weeks |
| **I** | The occupancy definition | 3 | 3 | yes | ⚠️ one line, large blast radius |
| **J** | Per-property staff scoping (P-7) | 1 | 1 | yes | a screen |
| — | singletons (§5) | 23 | 13 | — | — |
| **S1** | ⚠️ 210 unsourced industry claims | *structural* | 27 | n/a | founder-sourced |
| **S2** | ⚠️ "Guide N of 16" self-reference | *structural* | 23 | yes | a converter change |

---

## 3 · THE CLUSTERS IN DETAIL

### ⚠️ A · CHANNELS / OTA SYNC — 35 claims, 15 pages · ⚠️ NOT BUILDABLE BY DECISION

**Aggregate claim.** Vyrobel connects to Airbnb, Booking.com, Expedia and Vrbo; you authorise the channel, map
units and rate plans, choose what to push, and run a first sync. Thereafter Vyrobel is the source of truth,
rates and availability push out, reservations pull in, and the channel page shows last-sync and verbatim
channel errors.

**What exists.** A real channel-manager subsystem — `ChannexClient`, ARI push jobs, a webhook controller — and
an operator screen whose own docblock says *"the whole page is OTA sync, which is ACCOUNT-GATED"*
(`OperatorChannels.php:22-27`). `CHANNEX_FAKE=true`; `CHANNEX_API_KEY` and `CHANNEX_WEBHOOK_SECRET` empty;
`ChannelConnectionService.php:30-31` *"throws until Channex credentials exist"*. `AirbnbChannel` and
`BookingComChannel` are `DeferredChannel` skeletons that **throw** on `send()` and `normalizeInbound()`;
`channels.provider` accepts only `channex`. ⚠️ **And `Channel::create` still exists nowhere in `app/`** — I
re-verified; it is in `DemoSeeder` alone, so channels remain one of the seeder-only entities. There is no
channel-side authorisation flow anywhere.

**The gap.** A Channex partner account, credentials, a connection UI, unit/rate-plan mapping, and an
operator-reachable `Channel` creation path.

⚠️ **BUILDABLE? NO.** This is the one cluster **no ruling can unblock**. It requires a commercial
relationship the founder does not have. ⚠️ **RECOMMENDATION (a fact, not a preference): this cluster cannot
be scheduled until the Channex account exists.** Everything else about it is downstream of that.

**SIZE.** Post-account: a third-party integration plus a mapping UI — ⚠️ **weeks, not days**, and gated on a
partner onboarding of unknown length.

**DESIRABLE?** ⚠️ Yes in principle — it is a headline capability and a **Pro-tier feature gate already sold**
(`Organization::planAllows('channels')`). But note the ordering problem: **the tier gate is live while the
capability is not.**

**Dependencies.** The Channex account; then P-6's remaining leg (an operator path to create a `Channel`).

**⚠️ If FIXED instead, the doc would say:** *"Channel connections are not available yet. Vyrobel's channel
manager is built and tested against Channex, and will be enabled once our Channex connection is live. Until
then, availability and rates are managed in Vyrobel and entered on each channel's own extranet."*

⚠️ **Fixing the doc GUTS the page.** `/docs/connecting-channels` is 16 FALSE of 22 claims and its entire
subject is the unavailable thing. There is no honest version of it that is still a guide — only a notice.

---

### ⚠️ C · INVENTED WORKFLOW AFFORDANCES ON REAL SCREENS — 41 claims, 13 pages

⚠️ **This is the largest cluster and the most easily mis-ruled.** It is *not* a missing subsystem. Check-in,
the folio, the housekeeping board and the reservation wizard all **exist and work**. The docs describe a
richer version of them.

**Aggregate claim.** A collection of small affordances: the check-in panel shows housekeeping state *live*;
"mark guest as waiting" *pins the room to the top of the board*; charges *post automatically* as they happen;
completed online check-in means ID is *already verified against the booking name*; month bars in the revenue
chart are *clickable*; the peak month is *highlighted*; group booking is available *inside the reservation
wizard*; roles can be *adjusted per person*.

**What exists.** Each host screen is real and certified. What is absent is the specific affordance — e.g.
the reservation wizard books exactly one unit and cannot create a group at all (groups live on the separate
`/app/group-booking` screen); ID capture stores a document but performs no name-match check.

**The gap.** ~20 independent micro-features, each small, sharing no infrastructure.

**BUILDABLE?** Yes, all of it.

**SIZE.** Individually a control or a query each — **days**. Collectively **weeks**, and ⚠️ **this is where
building-to-the-doc does the most damage**: twenty features would enter the product because a generated
sentence described them, with no user having asked. Several are genuinely good ideas; the point is that the
document is not evidence that they are.

**DESIRABLE?** ⚠️ **Mixed, and it must be decided per item, not per cluster** — which is precisely why
"build the missing features" cannot be answered as one ruling here. A defensible split: harvest this cluster
as a **backlog to triage on merit later**, and fix the docs now.

**Dependencies.** None.

**⚠️ If FIXED instead, the doc would say** (example): *"Confirming check-in marks the reservation in-house.
The room's housekeeping state is managed on the Housekeeping board; the check-in panel does not display it."*

⚠️ Fixing does **not** gut these pages — they document real screens. `/docs/check-in-and-folio` (14F) loses
paragraphs, not its subject.

---

### ⚠️ D · RATE MODEL, RESTRICTIONS & THE FIRST HOUR — 41 claims, 14 pages · ⚠️ the cluster P-6 changed

**Aggregate claim.** Prices resolve in **four passes** (unit base rate → season → weekend uplift → per-night
override); seasons cannot overlap and the editor refuses; weekend uplift can be a percentage **or a fixed
amount**; you drag across the grid to set several nights; changes **push to channels on save**; owner-owned
units route through owner approval; minimum stays drop automatically to fill orphan gaps; lead-time
restrictions exist. Plus the first hour: define unit types, add units, import bookings, and *"you are billed
per unit"*.

**What exists.** ⚠️ **Two resolution levels, not four**, and this is a documented money rule (§N.28):
`unit_rate_overrides (plan, unit, date)` wins, else `rate_calendar (plan, date)`.
**Seasons and weekend uplift are real but materialise at AUTHORING time** through the certified writer —
`setRateWeekendAware()` writes the uplifted Fri/Sat price into `rate_calendar`. `weekend_uplift_pct` is
`decimal(5,2)` — **percentage only**. No overlap validation in `SeasonActionController`. No orphan-gap logic,
no lead-time restriction model. Rates are per **room type**, not per unit. ⚠️ **Room type, unit and rate-plan
creation are now real** (§1.1). Billing is **flat €49/€99** — `per_unit_minor` is a flagged placeholder no
plan uses, and there is no "Growth" tier. **No booking import exists** (re-verified: no route, no command).

**The gap.** Mostly none — the *product* is fine; the *description* is wrong. Genuinely absent: season-overlap
validation, fixed-amount uplift, drag-select, orphan-gap minimums, lead-time restrictions, booking import.

**BUILDABLE?** Yes. ⚠️ **But any new rate dimension must materialise through the certified writer, never
become a read-time input to `NightlyRate`** (§N.28 — learned by nearly shipping the opposite).

**SIZE.** Overlap validation: hours. Fixed-amount uplift: a day. Drag-select: days. **Booking import: weeks**
(file formats, mapping, reconciliation, idempotency). Orphan-gap and lead-time restrictions: a real pricing
subsystem, **weeks**.

**DESIRABLE?** ⚠️ Season-overlap validation is a genuine safety gap worth having regardless of the docs.
Orphan-gap and lead-time minimums are revenue-management features — real products have them, but nothing
except a generated sentence says this product needs them now.

**Dependencies.** "Push to channels on save" is **cluster A** and inherits its external blocker.

**⚠️ If FIXED instead, the doc would say:** *"A nightly price is whatever was last written to the rate
calendar for that plan and date, unless a per-unit override exists for that night. Seasons and weekend
uplifts are applied when you save them — they are written into the calendar, not recalculated at booking
time — so the grid always shows the price a guest will be charged."*

⚠️ **That fixed sentence is better than the original.** The four-pass story is not just false, it would
mislead an operator into expecting a price to change retroactively when a season is edited.

---

### ⚠️ B · OWNER MONEY: STATEMENTS & PAYOUTS — 36 claims, 10 pages · ⚠️ LOWER CONFIDENCE (§1.4)

**Aggregate claim.** Payouts run on a day set per owner, **offset from the statement date by a number of days
in Settings**, within a range the operator allows; the state vocabulary is Scheduled → Processing → Paid; a
failed payout's amount **returns to Available**; paused funds are **withdrawable on demand**; each payout
keeps a **full state history**; owners see **each expense with its work order attached**.

**What exists.** A real, certified rail. `createFromStatement` throws unless the statement is locked;
`AdjustOwnerLedgerOnRefund` posts refund shares to the next statement; ⚠️ **retry is built** (§1.2). The
Balance page maintains `Available + On hold + In transit + Paid + Carried forward == Net`.

**The gap.** No statement-date offset and no operator-allowed range in `owner_payout_preferences` (it holds
`frequency`, `payout_day`, `minimum_minor`, `paused` — I re-verified). The real enum is
`pending|processing|held|paid|failed|reversed` — **there is no `scheduled`**. A failed payout's money sits in
**Carried forward**, not Available — ⚠️ the doc names the wrong bucket **on the one page an owner reads to
find their money**. ⚠️ **"Withdrawable on demand" is false and dangerous**: there is no owner-initiated
transfer rail, and `OwnerBalance.php:55-58` deliberately disables it rather than fake it. Single-valued
timestamp columns, not a state history.

**BUILDABLE?** Partly. The offset/range settings and the vocabulary are straightforward. ⚠️ **An
owner-initiated withdrawal is a real-money rail** requiring live Connect, KYC, and the unanswered **Q10**
(whose Stripe account the configured secret belongs to).

**SIZE.** Settings + vocabulary: days. State history: a small table, days. ⚠️ **Withdrawal: weeks, and it is
regulated money movement.**

⚠️ **DEPENDENCIES — and this one has an ordering constraint that can lose real money.** The whole
statement→payout chain sits on **② + accrual**: the owner ledger accrues on **charges, not receipts**, and
nothing finalises a statement. ⚠️⚠️ **Fixing finalisation alone would pay owners against uncollected
revenue.** The pair must move together. Also depends on the live-key flip and Q10.

**DESIRABLE?** The offset/range settings: unclear that anyone needs them — they exist only because the doc
says so. ⚠️ **Correcting the failed-payout bucket and removing "withdrawable on demand" is not optional**
(see §4.3 — a money claim).

**⚠️ If FIXED instead, the doc would say:** *"A payout moves through Pending, Processing, Paid, or Held and
Failed. If a payout fails, the amount is not lost: it stays with its statement and appears on your Balance
page as Carried forward, and you or your manager can retry it. There is no self-service withdrawal — payouts
run on the schedule your manager sets."*

---

### ⚠️ E · ARRIVAL SECRETS & ONLINE CHECK-IN — 15 claims, 5 pages · ⚠️ contains P-1 and P-2

**Aggregate claim.** Door code, wifi and directions are held until **a moment you choose**, released **only
once check-in is complete and the balance is settled**; a guest who has not checked in gets a reminder
instead of the code; the front desk can override and release manually; the invitation goes out **on a
schedule you set**, repeats once, and can go **through the channel's messaging**; registration data is
retained for the statutory period and anonymised automatically.

**What exists.** `ArrivalPack` is a single shared resolver that releases the code **`LEAD_DAYS = 2` days
before arrival** until checkout, for any non-cancelled reservation; `SendPreArrivalEmails` mails it to every
`confirmed` reservation that day. ⚠️ **Neither consults `guest_checked_in_at`, an ID on file, or the folio
balance.** Online check-in is a fixed 4-step wizard with a fixed field set.

⚠️ **P-2 is a design, not a defect** — one resolver, no drift, disable-able per org. It simply is not what
three pages describe.

⚠️ **P-1 is the sharp one.** I re-verified every `door_code` reference in `app/`: `Mail/PreArrivalMail.php`
(read), `Models/Unit.php` (fillable + `encrypted` cast), `Support/Guest/ArrivalPack.php` (read). ⚠️ **No
operator UI writes `door_code`, `wifi_ssid`, `wifi_password` or `arrival_notes`.** The feature is correctly
gated on the guest side and **cannot be populated by a real operator** — it holds data only because
`DemoSeeder` puts it there. It is a **seeder-only capability**, one of the reachability-sweep class.

**The gap.** (i) An operator UI to write arrival secrets. (ii) Release conditioned on check-in/balance rather
than a date. (iii) A configurable release moment and invitation schedule.

**BUILDABLE?** Yes — except channel messaging, which is **cluster A**.

**SIZE.** ⚠️ **(i) is a form on the unit editor — days, and it is the cheapest high-value item in this
document.** (ii) is a change to a certified, guest-facing resolver — small in lines, but it governs whether a
guest can get into a building, so it needs care. (iii) days.

**DESIRABLE?** ⚠️ **(i) yes, unambiguously** — the alternative is a shipped feature no customer can use, and
⚠️ **a signposted missing feature is worse than an absent one** (§N.31.6). (ii) is a genuine product
question: date-based release is simpler and fails open (the guest gets in); condition-based is stricter and
can lock out a paid guest whose ID upload failed. ⚠️ **Do not resolve that by reference to the doc.**

**⚠️ If FIXED instead, the doc would say:** *"Arrival details are sent two days before check-in to every
confirmed booking, and stay available in the guest's portal until checkout. The release is on a schedule, not
on check-in or payment. You can turn the pre-arrival email off per property."* ⚠️ **And until (i) exists the
page cannot honestly tell an operator to enter a door code at all** — which is most of what it is for.

---

### ⚠️ G · GDPR RETENTION & DATA RIGHTS — 10 claims, 2 pages · ⚠️ see §4.2, the doc fix is UNCONDITIONAL

**Aggregate claim.** *"Retention. Settings, Data and privacy. Set once per property."* Set the **statutory
period**; data is then anonymised **automatically**; set ID retention separately; **leave anonymisation on**;
a guest can **export their own data**; **you cannot erase what you are legally required to retain, and the
product will tell you so**; export **in CSV or JSON**, one click.

**What exists.** Real and good: hosted Stripe (SAQ A, no PAN); `Guest::anonymize()`; a real guest-initiated
deletion with a 14-day grace; `guest-documents:purge-expired` at 30 days; audited operator erase and export.

**The gap — re-verified, all still absent.** There is **no "Data and privacy" section** (the screen has
fourteen sections; I read every heading). **No statutory-retention setting** exists as column, config or
screen — and **no statutory anonymisation sweep exists at all**; `guests:process-deletions` executes
*guest-requested* deletions only. No anonymisation toggle. ⚠️ **No guest-realm export route** — I enumerated
every `export` route; there are 15 and **none is in the guest realm**. No JSON export anywhere. Nothing
refuses an erasure on statutory grounds. ⚠️ And both "automatically" claims are **true only with the single
`schedule:run` crontab line**, which the page never mentions.

**BUILDABLE?** Yes.

**SIZE.** ⚠️ **A compliance mechanism, not a screen — weeks.** A per-property statutory period, a sweep that
anonymises on it, a retention-vs-erasure conflict rule with an explanation, and a guest self-export. The
sweep is the hard part: it must not collide with the existing erasure path or the append-only ledgers.

**DESIRABLE?** ⚠️ Note the shape: **the docs describe a compliance posture the product does not have, and the
product separately has GDPR gaps the docs never mention** (no Article 17 account deletion for any human
account; the phantom retention purge; backups undoing deletion). ⚠️ **The docs are not a good specification
for this work — the data inventory is.** `docs/legal/DATA-INVENTORY.md` records **96 of 142 entries with no
retention rule, no deletion path, or both**.

**Dependencies.** The deployment cron (§7.2 heartbeat) for any "automatically" claim to be true in production.

**⚠️ If FIXED instead, the doc would say:** *"Guests can request deletion from their account; the request is
reversible for 14 days and then anonymises their record while keeping the financial history. ID documents are
deleted 30 days after checkout. Vyrobel does not currently offer a configurable statutory retention period —
retention beyond these rules is managed by you."* ⚠️ **This guts the Retention section**, which is roughly
half the page.

---

### ⚠️ F · SECURITY PROPERTIES THAT DO NOT EXIST — 8 claims, 3 pages · ⚠️ see §4.1, UNCONDITIONAL

**Aggregate claim.** SMS is offered as a second factor; admins can **require 2FA account-wide**; the sessions
list shows **approximate location**; third-party apps hold scopes such as **write availability**; the setup
flow offers a **choice of methods**; there is a **password generator**.

**What exists (all re-verified).** TOTP across operator, owner and platform-admin realms; encrypted
regenerable recovery codes; real session termination; passkeys; IP rules; a self-scoped export; password
change that verifies the current password and terminates all other sessions.

**The gap.** ⚠️ **No SMS provider anywhere** (the only Twilio mention is a `WhatsAppChannel` docblock naming
it as an example of what *would* be needed). ⚠️ **No tenant-level 2FA enforcement** — the only enforcement is
`EnsurePlatformAdminTwoFactor`, a different actor entirely. ⚠️ **No geolocation** — `ActiveSessions` returns
device, IP and last-active, and `SecurityHeaders.php:99` **denies the browser API outright**. ⚠️ **No write
scope** — `OAuthScopes` is `read:bookings`, `read:statements`, `read:units`, with the file stating write
scopes are *"deliberately NOT here"*. No method chooser; no password generator.

**BUILDABLE?** Yes, technically, all of it.

**DESIRABLE?**

- ⚠️⚠️ **SMS 2FA — RECOMMENDATION: DO NOT BUILD. This is a technical fact, not a preference.** It is weaker
  than the TOTP already shipped (SIM-swap, SS7, carrier social engineering; NIST deprecated it as an
  out-of-band authenticator). Building it *because a generated document mentions it* would be adopting a
  security downgrade on the authority of fiction.
- **Account-wide 2FA enforcement — genuinely desirable**, and the only item here a real customer is likely to
  ask for. **Size: days** (a tenant flag plus a middleware modelled on the platform-admin one).
- ⚠️ **Geolocation — building it means adding an IP-geolocation sub-processor** and reversing an explicit
  Permissions-Policy denial, to make one sentence true. Weigh that against a GDPR posture that already owes
  counsel a sub-processor list.
- ⚠️ **Write OAuth scopes — the server withholds them by design.** A `write:availability` scope would let a
  third party change what a property sells. Not a doc-driven decision.

**⚠️ If FIXED instead, the doc would say:** *"Two-factor uses an authenticator app. Vyrobel does not offer SMS
codes — they are materially weaker, and we would rather not offer a weak option. The sessions list shows the
device and IP address for each session; we do not geolocate them. Connected applications can be granted
read-only access to bookings, statements and units; there are no write permissions."*

⚠️ **Note how much stronger that page is.** "We deliberately do not offer SMS" is a better security posture
than "SMS is offered and is weaker" — the truth is the better marketing.

---

### H · STOREFRONT BRANDING & OWN DOMAIN — 5 claims, 3 pages · ⚠️ Gate 5 overstated this (§1.3)

**Aggregate claim.** A **branded storefront** on **your own domain**, with a recognised card form on that
domain.

**What exists.** ⚠️ **More than Gate 5 recorded.** `PropertyBrand` is real and applied to guest emails, the
guest review page, the schedule PDF and the Settings preview, configured in a real **"Guest-facing brand"**
Settings section. ⚠️ **The public booking storefront is the one surface that ignores it** and hardcodes
`Powered by Vyrobel` (`shell.blade.php:249`). Separately, `organizations.custom_domain` is a fillable column
**written nowhere and read nowhere**, and card checkout redirects to Stripe-hosted Checkout on the **platform**
account (no connected-account routing for operators).

**The gap.** (i) Wire the existing `PropertyBrand` into the existing public shell. (ii) Custom-domain serving.
(iii) Per-operator merchant accounts.

**BUILDABLE?** Yes.

**SIZE.** ⚠️ **(i) is days** — the helper and the data already exist; this is plumbing, and it is the second
cheapest high-value item here. (ii) is **weeks** (DNS, TLS issuance, routing, tenant resolution by host).
(iii) is **weeks** and is regulated (Connect onboarding per operator, KYC).

**DESIRABLE?** (i) clearly. (ii)/(iii) are strategic — they change what Vyrobel *is* for an operator, and
⚠️ (iii) intersects the **unresolved "never hold client funds" question awaiting a payments lawyer**.

**⚠️ If FIXED instead:** *"Your booking page carries your property's name and your brand colour, served from
Vyrobel. Custom domains are not available yet."* — honest, and true the moment (i) ships.

---

### ⚠️ I · THE OCCUPANCY DEFINITION — 3 claims, 3 pages · ⚠️ small, sharp, money-adjacent

**Aggregate claim** (`/glossary`, `/docs/reports-and-exports`, `/resources/out-of-service`): *"Units out of
service are excluded from the denominator, so a maintenance closure does not show up as an occupancy dip."*

**What exists — I traced this end to end and Gate 5 is correct.** `AvailabilityService` sets
`availability.total = $units->count()` over **`is_active`** units; `condition_status` of `out_of_order` /
`out_of_service` is recorded in a **separate `out_of_order` column** and **does not reduce `total`**.
`PerformanceMetrics` then uses `$spine->sum('total')` as `availableRoomNights` — the occupancy denominator.

⚠️ **So out-of-service units ARE in the denominator, and a maintenance closure DOES depress occupancy —
exactly the opposite of what three pages promise.**

**The gap.** Use `total - out_of_order` as the denominator.

**BUILDABLE?** Yes. ⚠️ **SIZE: one line — and that is the trap.** `RevPAR = RR ÷ AN` and the certified
invariant **`RevPAR = ADR × occupancy` exactly** both ride on that denominator. Changing it **restates every
historical occupancy and RevPAR figure** across operator Insights, owner Reports, the portfolio comparison and
every PDF — and the D3-4 coverage guard is calibrated against it. ⚠️ **Cheap to type, expensive to be wrong
about.**

**DESIRABLE?** ⚠️ **A real question, not a doc question. Both definitions are legitimate and both are used in
hospitality.** Including OOS in the denominator measures *"how much of my building earned"*; excluding it
measures *"how well did I sell what I could sell"*. ⚠️ **The docs are not evidence for either.** Whichever is
chosen, the glossary should state it explicitly — the current failure is that the product is silent and the
docs assert the opposite of the truth.

**⚠️ If FIXED instead:** *"Occupancy is occupied nights divided by all active unit-nights in the period. Units
out of service remain in the denominator, so a maintenance closure lowers occupancy — that is deliberate: it
measures the building, not just the sellable part of it."*

---

### J · PER-PROPERTY STAFF SCOPING (P-7) — 1 claim, 1 page

**Aggregate claim.** *"Scope to properties — on a multi-property account, choose which properties they work in."*

**What exists.** The `property_user` allow-list is real and honoured on read *and* write paths
(`ReservationCreateController.php:103`). ⚠️ **But the only writer is `PropertyController.php:72`, guarded by
`if ($user->propertyAssignments()->exists())`** — a condition only a pre-existing row could satisfy, and
nothing else creates one. Re-verified: unchanged. **So every back-office user resolves to "all properties",
and the control has no way to be used.**

**BUILDABLE?** Yes. **SIZE: a screen** — a property multi-select on the invite/edit user form. Days.

**DESIRABLE?** ⚠️ Note this is a **live claim liability**: `/docs/invites-and-access` is one of the five
"publishable with doc fix" pages, and this is its blocker. It is also a **security-adjacent** gap — the
mechanism exists to restrict access and silently restricts nobody.

**⚠️ If FIXED instead:** *"Everyone you invite can see every property on the account. Per-property scoping is
not available yet."* ⚠️ **On a multi-property product that sentence may be commercially unacceptable**, which
is an argument for building rather than fixing — the founder's call.

---

## ⚠️ 4 · SECURITY & GDPR — THE UNCONDITIONAL CORRECTIONS

⚠️⚠️ **These are not one of four options. Whether a feature is built is a roadmap question; whether a page may
CLAIM it today is not.** Every item below must not be published in any form, whatever is decided about
building — and ⚠️ **the correction is a correction, never a softening.** Replacing a false specific with
*"we take security seriously"* produces a worse page: unfalsifiable, and it destroys the reader's ability to
check.

### 4.1 Security — must not publish (cluster F)

| the claim | the required correction |
|---|---|
| "SMS is offered … and is meaningfully weaker" | ⚠️ **Delete.** State positively: authenticator app only, **and say why** — a deliberate refusal to ship a weaker factor. |
| "Admins can require 2FA account-wide" | ⚠️ **Delete.** Only platform administrators are required to hold 2FA; tenants cannot enforce it. |
| "sessions … with its **approximate location**" | ⚠️ **Correct to device and IP address.** Do not write "location". |
| "read reservations, **write availability**, and so on" | ⚠️ **Correct to read-only**, naming the three real scopes. A reader must not believe a third party can be granted write access. |
| "a disconnected **channel manager** stops updating availability" | ⚠️ **Delete.** No channel manager authorises via OAuth. |
| "the **generator** produces something you can store in a manager" | ⚠️ **Delete.** No generator exists. |

### 4.2 GDPR — must not publish (cluster G)

| the claim | the required correction |
|---|---|
| "Retention. **Settings, Data and privacy.**" | ⚠️ **Delete the section.** The screen does not exist. |
| "**Set the statutory period** … anonymised automatically" | ⚠️ **Delete.** ⚠️ **The sharpest item in this document:** an operator following it would believe their legal retention is configured **when nothing is**. |
| "**You cannot erase what you are legally required to retain, and the product will tell you so**" | ⚠️ **Delete.** Nothing refuses an erasure on those grounds. |
| "A guest can **export their own data**" | ⚠️ **Correct.** Deletion is real and self-service; **export is not** — no guest-realm export route exists. |
| "Data export in **CSV or JSON** … one click" | ⚠️ **Correct.** Per-surface exports in CSV/PDF/XLSX; **no JSON anywhere**, no account-wide export. |
| "**Leave anonymisation on**" | ⚠️ **Delete.** There is no toggle; anonymisation is always the behaviour. |
| "anonymised / deleted **automatically**" | ⚠️ **Qualify.** True **only** where the `schedule:run` cron is installed. On a host without it these run silently never. |
| "our **data processing agreement** sets that out" | ⚠️ **Delete until the DPA exists.** Already a recorded go-live blocker; the page must not assert it first. |

### 4.3 ⚠️ One money claim, same standing (cluster B)

| the claim | the required correction |
|---|---|
| "Paused funds … **withdrawable on demand**" | ⚠️ **Delete.** There is no owner-initiated transfer rail. Telling an owner their money is withdrawable on demand is a false statement about **access to their own money.** |
| "a failed payout … **returns to the owner's available balance**" | ⚠️ **Correct to Carried forward** — the doc names the wrong bucket on the one page an owner reads to find missing money. |

---

## 5 · SINGLETONS — 23 claims not worth a cluster

Notable: **the reading times are fabricated on every page** (a ~994-word article labelled "14 min"; ~2–3×
overstated sitewide) · `/resources` claims **"What we've learned running property operations"** and a
**mailing list with no backend** · **"Importing from another system"** and a **reconciliation sheet** that do
not exist · **"Roles are a starting point rather than a cage: individual permissions can be adjusted per
person"** (roles are fixed catalogues — ⚠️ note this one is *authorization*-adjacent) · clickable month bars ·
a highlighted peak month · VAT filing status · **"Reports default to everything you manage"**.

⚠️ Each is a one-line doc fix. None justifies a build decision.

---

## ⚠️ 6 · THE PUBLISHABLE SET

### 6.1 ⚠️ S2 — the numbering gates everything

Every guide carries `Guide N of 16`, every article `Article N of 7`, `/faq` announces "18 questions", and each
guide renders **the full sixteen-item sibling sidebar**. ⚠️ **Each page asserts the whole set exists**, so
publishing any subset publishes a false count.

⚠️ **What it would take, precisely:** the counters live **only in the unbuilt design masters** under
`docs/design/front/` — I confirmed no built marketing view contains one. ⚠️ **So the fix belongs IN THE
CONVERTER** (§N.31.4: a fix applied after generation is undone by the next generation, and a fix living only
in generated output makes the generator a destructor). The converter must drop or recompute the counter and
render the sidebar from the **published** set, not the master's list. **Size: a converter change plus a
zero-match-must-fail-loudly guard — days.**

⚠️ **Until S2 is done, the publishable set is ZERO under every policy.** It is the cheapest item in this
document and it gates all others.

### 6.2 The set under each policy

⚠️ "Fix the doc" can in principle make any page publishable — the honest question is **at what cost to the
page**. Three outcomes:

| outcome | meaning | pages |
|---|---|---|
| **LIGHT EDIT** | ≤4 FALSE claims; page keeps its shape | 5 |
| **REWRITE** | 6–14 FALSE; page survives, sections die | 18 |
| ⚠️ **GUTTED** | the page's subject does not exist | 2 |
| ⚠️ **BLOCKED** | blocked by something other than claim count | 2 |

**Ordered by pages-unblocked-per-unit-of-work:**

| step | work | pages publishable | cumulative |
|---|---|---|---|
| **0** | ⚠️ **S2 — the converter counter fix** (days) | *gates everything* | **0 → 0** |
| **1** | **Light edits on 5 pages** — `booking-direct-trust` (1F), `out-of-service` (1F), `housekeeping-at-forty-units` (3F), `winning-owners` (4F), `growing-direct-bookings` (3F) | ⚠️ but see below | **+3 = 3** |
| **2** | **§4 unconditional Security + GDPR corrections** (required anyway, hours) | `account-security`, `gdpr-and-data` | **+2 = 5** |
| **3** | **Rewrite the 18** (weeks of writing, no code) | the docs body | **+16 = 21** |
| **4** | + **cluster J** (a screen, days) | unblocks `invites-and-access` honestly | 21 |
| **5** | + **cluster E(i)** (a form, days) | makes `online-check-in` a real guide | **+1 = 22** |
| **6** | ⚠️ **cluster A** — needs the Channex account | `connecting-channels` | **+1 = 23** |

⚠️ **Step 1 yields 3, not 5.** Two of the five "publishable with doc fix" pages are blocked by something a
doc fix cannot reach:

- ⚠️ **`/resources/growing-direct-bookings` carries ledger entry B8** — *"keep the fifteen to eighteen
  percent"* is the same unsourced OTA-commission claim for which `/compare` is currently **HELD**. Publishing
  it ships, through another page, the exact claim the ledger is holding.
- ⚠️ **`/resources/owner-statement` (7F)** is not a light edit despite its label.

### 6.3 ⚠️ Unpublishable under any policy

- **`Changelog`** — refused as fabricated: three entries **predate the repository's first commit**, two are
  dated **in the future**, and zero releases have happened. ⚠️ Not fixable by editing; only by having real
  releases.
- **`Careers`** — its subject is a company that does not exist.
- **`Alternatives` / `Compare`** — ⚠️ **ruled DROP, and the drop is STILL OUTSTANDING.** I re-verified:
  `MarketingUrls.php:35-36` still maps both, and `nav.blade.php:109` + `:166` still render **Compare** and
  **Vs alternatives** rows on every page. The Gate-3 fix removed the four **competitor names** from the nav
  description (confirmed gone), but the rows themselves remain as marked non-links. ⚠️ **Executing the DROP is
  an unfinished item, not a decided-and-done one.**

### 6.4 ⚠️ S1 — the 210 unsourced claims, across all 27 pages

Not a feature cluster and **not fixable by building anything**. Every figure, benchmark or outcome claim needs
`source_url` + `verified_on` + `review_by` under CI Gate 6, and the ones that matter **contain no number and
no brand** — *"Most operators answer no to at least two"*, *"Group chats work to about fifteen units"*,
*"Most OTA contracts in Europe no longer enforce strict price parity"*. ⚠️ **A grep proves the absence of its
needles** (§N.31.2): the first sweep of these articles searched `%`, `€` and "Vyrobel", found almost nothing,
and called five articles clean. They were not.

⚠️ **This is the founder's to source or to cut**, and it is the ⚠️ **lowest-confidence area in this
document** (§1.4) — the eight verifiers that died covered exactly these pages.

---

## ⚠️ 7 · THE FOUR OPTIONS — none picked

Per cluster: **BUILD** the capability · **FIX THE DOC** to describe what is true · **DEFER THE PAGE** ·
⚠️ **REMOVE THE FEATURE AND ITS DOCS.**

⚠️ **REMOVE is a real fourth option, not a polite one.** The reachability sweep found **11 seeder-only
entities**; a half-built capability nobody can reach is both a maintenance burden and a standing claim
liability. It applies most directly to **A** (channels: a subsystem, a Pro-tier gate and a nav entry for
something unreachable) and to **E**'s arrival secrets if no operator UI is ever built.

| cluster | BUILD | FIX THE DOC | DEFER | REMOVE |
|---|---|---|---|---|
| **A** channels | ⚠️ blocked on Channex | guts the page | ✓ | ⚠️ live: also drop the Pro gate |
| **C** workflow | weeks; triage on merit | ✓ natural fit | ✓ | n/a |
| **D** rates | per-item | ✓ **and the fixed text is better** | ✓ | n/a |
| **B** owner money | ⚠️ ②+accrual ordering | ⚠️ §4.3 mandatory regardless | ✓ | n/a |
| **E** arrival | ⚠️ (i) cheapest high value | honest but hollow | ✓ | ⚠️ real: the feature is unreachable |
| **G** GDPR | weeks, compliance | ⚠️ §4.2 mandatory regardless | ✓ | n/a |
| **F** security | ⚠️ **not SMS** | ⚠️ §4.1 mandatory regardless | ✓ | n/a |
| **H** storefront | (i) days | ✓ | ✓ | n/a |
| **I** occupancy | one line, wide blast | ✓ | ✓ | n/a |
| **J** scoping | a screen | ⚠️ may be commercially unacceptable | ✓ | ⚠️ or delete `property_user` |

⚠️ **Only two recommendations, both technical facts:**
1. ⚠️ **Do not build SMS 2FA** — it is weaker than the TOTP that ships.
2. ⚠️ **Cluster A cannot be scheduled** until a Channex partner account exists.

⚠️ **Everything else is the founder's.**

---

## 8 · VERIFICATION

Read-only throughout. **Nothing built, fixed, published or changed.** No product file, no doc page and no
test was modified; this file is the only artefact. Money as seeded **byte-identical** (929 / 10,912,000 / 0
`unit_rate_overrides` / 41 / 16 / 0). The suite, PHPStan L5, Pint and the eight CI gates were **not run and
not touched** — nothing in this pass could affect them.

**Re-verified against current code** (not Gate 5's snapshot): the inventory creation paths and their UI forms
· `Channel::create` · `NightlyRate` two-level resolution and §N.28 · `weekend_uplift_pct` · season-overlap
validation · SMS/Twilio · 2FA enforcement middlewares · geolocation and `SecurityHeaders` · `OAuthScopes` ·
the 14 Settings section headings · every `export` route · `door_code` references · `property_user`'s only
writer · `owner.payouts.retry` and `OwnerPayoutService::retry()` · `owner_payout_preferences` columns ·
`AvailabilityService` totals and `PerformanceMetrics` denominator · `PropertyBrand` consumers ·
`MarketingUrls` and the nav rows · the location of the "Guide N of 16" counters.

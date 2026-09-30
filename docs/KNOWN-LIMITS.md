# Vyrobel — KNOWN LIMITS, and where each one is told

**Verified 2026-08-31 against the code, not inherited from a report.** Every statement below carries the
code fact that makes it true, so a future reader can re-check it rather than trust it. ⚠️ **A limits
document that goes stale is worse than none.**

> ⚠️ **"Tell customers" is not "write it down somewhere."** A limit discovered *after* signing up is a
> support ticket and a refund request; the same limit stated where someone would meet it is a design. Each
> row below names **where** the telling belongs, and whether it is there yet.

> ⚠️ **No future is promised anywhere in this file.** Every limit is stated as a present fact, in the
> present tense, with no implied intent to change it — because an implied roadmap is a promise a reader
> will hold you to, and a limit that is genuinely temporary is still not one you may print.

---

## L1 · One payment account per organisation, and its country is fixed at creation

**What is true.** An organisation has exactly one `organizations.stripe_connect_account_id`
(`OperatorConnectService::startOnboarding` is idempotent on it — a second call re-links the *same*
account and never creates a second one). **Stripe fixes the account's country during its own hosted
onboarding and it cannot be changed afterwards.**

⚠️ **Whose constraint it is, precisely.** Stripe's, not ours — but note what our code does and does not do:
`createConnectAccount(string $email, array $metadata)` **takes no country parameter at all**
(`StripeClientInterface:105`). We never choose it; the account holder does, inside Stripe's flow. So this
is not a path we declined to build — there is no country for us to set.

⚠️ **The schema cannot express the alternative.** `organizations.country` is `char(2)` **nullable**, and
`properties` has **no country column at all** (`docs/CONNECT-TARGET-DESIGN.md` §302). A multi-country
operator would need one account per country — a one-to-many the schema has no room for.

**An operator who is already wrong.** There is **no in-product path**: no route clears
`stripe_connect_account_id`, so an organisation cannot be re-onboarded to a different country from any
screen. Changing it requires a new Stripe account *and* a console action to clear the stored id — and,
because the limit is one account per organisation, a genuinely multi-country operator needs a **second
organisation**, with its own properties, owners and books. That is a re-setup, not a migration.

**Where it is told — ✅ in product, at the point of the irreversible choice.**
`resources/views/livewire/operator/billing.blade.php` renders, **only while the account does not yet
exist** (`@if (! $connect['connected'])`), a card headed *"Before you start: the country cannot be changed
later"*. That is the right place: before creation, not in a document read afterwards.

⚠️ **What is NOT told: pre-sale.** Nothing on the marketing site raises country or the one-account limit,
so an operator with properties in two countries can reach onboarding before meeting it. **Reported — the
copy is a founder decision, not a correction.**

---

## L2 · No OTA channel can be connected

**What is true.** The pipeline exists and is tested — routes `app.channels`, `webhooks.channex`,
`admin.integrations.*` are registered — but `FakeChannexClient` is the bound default
(`config/services.php:47`), so **nothing reaches a real channel**.

⚠️ **"Stopped" is the wrong word: it never started.** No live sync was ever switched off; the real client
has never been bound outside a test. The `/integrations` page already states this plainly and carries the
disclosure markers the founder ruled must stay.

**Where it is told — ✅ pre-sale, on `/integrations`.** That is the correct place: it is a buying
criterion, and someone comparing Vyrobel to a channel manager must meet it before signing up, not after.

---

## L3 · A guest cannot export their own data; they *can* erase it

**What is true, and the halves differ.**

- **Erasure — EXISTS.** `POST guest/account/delete` plus `guest.account.delete.undo` (a real grace period).
- **Export — DOES NOT EXIST.** No guest export route is registered. A guest can download per-booking PDFs
  (folio receipt, VAT invoice), which is not a subject-access export.

⚠️ **This transfers an obligation, and that is the part worth saying.** The operator is the data
controller, so **the operator services every Article 15 request by hand.** What the product can give them:
the Guests CRM profile, the bulk guest CSV (`app.guests.bulk.export` — name, email, phone, city,
nationality, stays, VIP, marketing consent), and the per-booking PDFs. What it cannot: a single
guest-initiated bundle.

**Where it is told — the legal record and the DPA conversation**, not marketing copy. It is already
recorded: `docs/legal/DATA-INVENTORY.md` §170 ("subject-access and portability are **partial**") and §316
("no Art.15 export for guests at all"). `docs/DATA-RESIDENCY-INVENTORY.md` records the same, and was
corrected once already for wrongly denying the erasure half.

⚠️⚠️ **AND THE LEGAL RECORD CONTRADICTS ITSELF — still open, reported again here.** §299 states *"there is
no /app export route"* on the strength of an enumeration of `routes/web.php:836-897`, while
`/app/security/export` sits at **`routes/web.php:983`** — outside the range read — and §170 of the same
file records that export correctly. **The conclusion may still be right** (that export emits only the
acting user's own account fields, so Art.15 portability is absent) **but its stated reason is false.**
⚠️ Not edited here: a legal document is corrected by its author and a lawyer, not by a build pass.

---

## L4 · Retention windows are fixed in code and cannot vary by jurisdiction

**What is true.** `App\Support\Retention\RetentionPolicy::WINDOWS` sets one window per table for every
tenant — `failed_jobs` 7 days, `sessions` 30, `notifications` / `webhook_events` / `export_downloads` /
`llm_usage` 90 — pruned daily by `retention:prune` (`routes/console.php:51`). The class says so itself:
*"THE WINDOWS ARE FIXED IN CODE AND CANNOT VARY BY JURISDICTION."* `audit_events` and `domain_events` are
deliberately **never** pruned (`RetentionPolicy::NEVER_PRUNED`).

⚠️ **This also transfers an obligation.** An operator whose jurisdiction requires a *shorter* window
cannot comply through the product; one requiring a *longer* one cannot either. What they must do: raise it
in the DPA and, where a shorter window is mandatory, treat it as a manual control outside Vyrobel.

**Where it is told — the legal record and the DPA**, and it already is
(`docs/DATA-RESIDENCY-INVENTORY.md` §3, plus the class's own docblock, which is where an engineer looks
for a knob).

---

## L5 · An organisation cannot require two-factor of its staff

⚠️ **The limit is the MANDATE, not the feature — a different answer to a security-conscious buyer.**

**What exists.** Full TOTP two-factor with recovery codes, trusted devices and a login challenge
(`TwoFactorController`, `two-factor.challenge`). **It is mandatory for platform administrators** —
`EnsurePlatformAdminTwoFactor` enforces it, and `PlatformAdmin` documents it. So the product both
implements 2FA and enforces it *somewhere*.

**What does not exist.** No organisation-level setting requires it of operator staff: nothing in `app/`,
`config/` or the migrations carries a `two_factor_required` or equivalent. Enrolment is per person, opt-in.

⚠️ **And what an operator can do today is: nothing — they cannot even see it.** `two_factor_confirmed_at`
is read by **no operator view or Livewire component**, so an administrator cannot list who has enrolled,
let alone require it. Saying "we don't enforce it" without saying "and you can't check" would understate it.

**Where it is told — pre-sale, in the security answer a buyer asks for.** `/security` already carries
disclosure markers of this kind. ⚠️ Whether to state it there is a founder decision; **reported, not
written**, because the honest sentence names a gap a buyer weighs.

---

## L7 · The back office is desktop-only

⚠️⚠️ **RULED 2026-09-19 (F-33): this is a POSITION, not a defect awaiting a fix.** 🔬 The operator shell folds
at 375px and the navigation becomes unreachable; the shell carries twelve media queries and **none of them
touches the nav** — there is no drawer, no toggle, no hamburger. **54 screens sit inside that shell.**

**The customer-facing sentence:** *"The operator back office is designed for a desktop or laptop screen. The
guest and owner portals work on a phone."*

⚠️ **FIX 11 TRIED THE OBVIOUS FIX AND IT WAS WORSE** — collapsing the nav pushed all 22 links off-screen,
caught by one further measurement. A mobile navigation for this shell is a real project, not a media query.

⚠️⚠️ **THE ARGUMENT AGAINST THIS POSITION, RECORDED SO IT CAN BE RE-OPENED HONESTLY: housekeeping staff in a
real hotel work from a phone.** They are the role least likely to have a laptop and the most likely to need
the product mid-shift — and `Housekeeping` is one of the eleven back-office roles, inside this shell. ⚠️ A
position recorded bare looks like nobody thought about it; this one was weighed, and the counter-argument is
the thing a future reader should weigh again.

⚠️ **NO PRODUCT SURFACE STATES THIS TODAY.** 🔬 Nothing in `resources/views/` carries a supported-browser or
system-requirements statement, and **none was invented here** — the ruling was a position, not a feature.
Somewhere for it to live (a line on `/security`, in onboarding, or in the marketing footer) is a small piece
of work and **a founder decision, reported not taken**.

---

## L8 · ⚠️ AN INTERNAL LIMIT, NOT A CUSTOMER-FACING ONE — the money gate and the writable tenant exclude each other

⚠️⚠️ **NOTHING HERE IS TOLD TO A CUSTOMER, AND NO CUSTOMER-FACING SENTENCE EXISTS FOR IT.** This is a limit on
what this repository can *verify about itself*, recorded here because it is where a future pass will look —
and because, before FIX 48, **nothing anywhere warned one**. It is **F-61** in `docs/AUDIT-SUMMARY.md` §2b.

**The collision, 🔬 measured 2026-09-19.** `ops/money-as-seeded.sh` reports `mode=FULL` — the only mode that
asserts all six pinned figures — **only** when the database was seeded at its reference date
`VY_SEED_DATE=2026-09-01`. Tenant two (`northgate-demo-city-rooms`) is seeded with a **14-day trial**, which
therefore expires **2026-09-15**. At any real present date that trial is genuinely lapsed, so
`EnsureSubscriptionActive` holds **every one of its `/app/*` routes at `/app/billing`**.

⚠️⚠️ **BOTH GATES ARE CORRECT. That is what makes this a limit rather than a defect** — the paywall is
invariant #41 doing exactly its job, and the reference date is what makes the money pin mean anything. But
**tenant one may never be written to and tenant two's back office may not be reached**, so the browser tier is
now structurally narrower than it was: any journey needing a tenant-two OPERATOR surface cannot be walked
without disabling one of the two instruments.

⚠️⚠️ **WHAT NOT TO DO, AND IT IS THE FIRST THING THAT WILL OCCUR TO A HELPFUL PASS: DO NOT EXTEND THE TRIAL,
MOVE THE CLOCK, OR RE-SEED AT A LATER REFERENCE DATE.** That is re-baselining a fixture to make a check pass —
the oldest standing prohibition in this project wearing new clothes — and it would invalidate `mode=FULL` and
every figure that depends on it.

⚠️ **What IS still reachable:** tenant two's **owner portal**, which is not subscription-gated, and which is
where its GBP currency behaviour was proven.

**The two options on the record, neither of them a session's to choose:** a **disposable third tenant** whose
subscription state is set for walking, or **accepting the remainder as establish-only** and saying so in every
report that touches it.

---
## L9 · ⚠️ AN INTERNAL LIMIT — the third tenant is DISPOSABLE, and no pinned figure may reference it

⚠️⚠️ **NOT CUSTOMER-FACING. NO SENTENCE IS WRITTEN FOR A CUSTOMER HERE.** Like **L8**, this is a limit on how
this repository verifies itself, recorded where a future pass will look. It is **F-61** in `AUDIT-SUMMARY.md`.

**Why it exists.** 🔬 Tenant one is the untouchable money baseline — browsable, never writable. Tenant two is
correctly paywalled (L8). Between them, **no human could drive a write through the back office in any
organisation**, which is the core of the product. `harbourpoint-demo-walkthrough` exists so that is possible
again.

⚠️ **THE TWO ARE NOT REDUNDANT, AND NEITHER REPLACES THE OTHER:**
- **Tenant two exists to PROVE THE BILLING REFUSAL and must stay paywalled.**
- **Tenant three exists to BE WALKED and must stay entitled.**

⚠️⚠️ **THE RULE: NO PINNED FIGURE, ANYWHERE, MAY REFERENCE TENANT THREE.** The moment a test asserts one of
its counts it stops being disposable and becomes a fixture somebody has to preserve — and it was built to be
**destroyed by every `migrate:fresh --seed`**. Walk it, write to it, break it, and let the next reseed take it
away. ⚠️ Wanting to pin one of its numbers is the signal that it is being used as a baseline: use tenant one,
or measure by hand. The same sentence is in the seeder's docblock and the F-61 row, because a rule written in
one place goes stale in the other two.

⚠️ **It is entitled by `subscriptions.status = 'active'`, and that is measured, not chosen by habit.** 🔬
`hasFullAccess()` is `onTrial() || active()`; `onTrial()` is date-dependent and is the exact fuse that
paywalled tenant two, while `active()` **checks no date at all** and `current_period_end` gates nothing
anywhere in `app/`. So this tenant is structurally incapable of expiring, where any trial length would just
be a later fuse. ⚠️ **Zero Stripe** — `stripe_subscription_id` and `stripe_customer_id` are NULL, the same
shape `vyrobel-demo` already carries.

---
## L10 · ⚠️ A GUEST ON A TENTATIVE HOLD IS TOLD NOTHING AT ALL

⚠️⚠️ **CUSTOMER-FACING, AND IT IS THE DELIBERATE RESIDUE OF FIXING F-64.** Until 2026-09-20 a guest whose room
was held on a tentative group hold received a *"booking confirmed"* email — a false statement, since a hold
carries a pickup deadline and promises nothing. That send now happens at CONFIRM instead, which is the first
moment the sentence is true.

⚠️ **The honest consequence: between the hold being taken and somebody confirming it, the guest receives no
message from the product whatsoever.** For a direct booking this window does not exist; for a group hold it
can be days.

⚠️⚠️ **WHAT WAS DELIBERATELY NOT BUILT, AND WHY IT IS NOT A BUG TO BE QUIETLY PATCHED:** a *hold*
notification — *"we are holding a room for you until Friday"* — is a **new guest-facing message**. It needs
its own copy, its own per-org template toggle (#86), its own guest-preference question, and a decision about
whether an expiring hold chases the guest again. That is a product decision with a founder in it, not a
repair, and inventing one inside a defect fix is how a fix silently becomes a feature nobody ruled.

⚠️ **The operator is NOT in the dark** — 🔬 `DispatchNotificationsForDomainEvent` still fires the new-booking
notification at hold time, and the ARI push still tells the channels the room is gone. Only the guest-facing
sentence was withheld, and only because it was untrue.

## L11 · ⚠️⚠️ THIS PROJECT CANNOT STATE A COVERAGE PERCENTAGE FOR MONEY-WRITE ROUTES

⚠️⚠️ **IF YOU ARE ANSWERING A CUSTOMER'S SECURITY QUESTIONNAIRE, AN INSURER, OR A DUE-DILIGENCE REQUEST, THIS
IS THE ROW YOU NEED — AND THE ANSWER IS THAT THERE IS NO HONEST NUMBER TO GIVE. DO NOT INVENT ONE.**

The question *"what proportion of your money-write routes have been exercised?"* has no derivable answer here,
and that is a measured conclusion rather than a gap in the work.

⚠️ **THE MEMBERSHIP RULE IS WRITABLE:** *a money-write route is one whose handler can, on a successful
request, cause an insert or update to a row carrying a `*_minor` column.* **Applying it is not.**

🔬 **MEASURED 2026-09-20 (F-59):** 513 route entries · 296 POST/PUT/PATCH/DELETE · 14 unresolvable to a
controller method. ⚠️⚠️ **Deciding membership means following the call graph, and the same rule yields
3 / 47 / 163 / 190 at traversal depths 0–3 — a 63× swing on an arbitrary parameter.** Three candidate rules
(writes a money table · writes a `*_minor` figure · reaches a payment processor) **stop distinguishing each
other at depth ≥ 2**, because the closure saturates. Depth 0 misses every controller that delegates to a
service — which is this product's entire architecture — and depth 3 pulls in most of the application.

⚠️ **"Mechanical with a list of exceptions" is not an escape**: at depth 2 that list runs to ~160 entries,
which is a human judgement rule wearing a script's clothes.

⚠️⚠️ **AND BOTH HALVES ARE UNENUMERABLE, NOT ONLY THE DENOMINATOR.** The numerator — routes actually driven
with the row they wrote recorded — is **five individually named** plus **two clusters of unstated size**.

### ⚠️ What CAN be said honestly

- **Which specific money routes have been driven, and by which pass** — that list exists and is exact
  (`docs/AUDIT-SUMMARY.md` §5).
- **That every money write goes through one guarded service path**, with the bounds that enforces
  (invariants #17, #20, #27, #29, #30, #45).
- ⚠️ **What has NOT been executed is named rather than counted** — §5 states the rule, not a fraction.

⚠️ **A percentage would be a number nobody could reconstruct, which is worse than no number at all.**

## L12 · ⚠️⚠️ THE PRODUCTION HOST IS NOT THE VERIFIED ARCHITECTURE — three substitutions, measured

⚠️⚠️ **`vyrobel.com` runs on Hostinger SHARED hosting. Three things differ from the stack every one of this
programme's 3,652 tests was certified against, and each is recorded here because none of them can be fixed by
configuration.** 🔬 Measured 2026-09-20 (DEPLOY 02).

| verified architecture | what production actually runs |
|---|---|
| MySQL 8 (InnoDB) | **MariaDB 11.8.9** |
| Redis + Horizon, supervised worker | **database queue**, `queue:work --stop-when-empty` from a per-minute cron |
| Redis cache + sessions | **file** cache + **file** sessions |

⚠️ **THE SUITE WAS RE-RUN AGAINST MariaDB 11.8.9 AND LARGELY HOLDS** — 🔬 **3,648 of 3,652 passed** at
production's isolation level, against the MySQL 8 control's 3,651. Every difference is accounted for below.

### ✅ L12a — the one-live-payout DATABASE constraint could not exist on MariaDB — ⚠️ RESTORED 2026-09-20 by triggers

🔬 `CREATE UNIQUE INDEX payouts_one_live_per_statement` fails with **MariaDB error 1901**: the
`CASE WHEN status IN (…) THEN owner_statement_id ELSE NULL END` generated column cannot be indexed. Tested as
`VIRTUAL`, as `PERSISTENT`, and rewritten as `IF()` — **MariaDB refuses all three.**

⚠️ **The migration's column IS created and its index is NOT**, and the migration's own `hasColumn` guard makes
a re-run **skip silently and report "Ran"** — so the guard looks installed and is not.

✅ **RESTORED (DEPLOY 04) — BY A PAIR OF TRIGGERS, ON BOTH ENGINES.** 🔬 Measured first: MariaDB refuses a
generated column in *any* `CASE` form (1901, tested VIRTUAL, PERSISTENT and `IF()`) **and** a functional index
(1064). A real column with a plain unique index works on both — but a raw INSERT that omits it lands NULL and
collides with nothing, which is the exact bypass the rule exists to stop. ⚠️ **A trigger is the only mechanism
left**, and the objection to triggers (added by hand in a GUI, invisible, untested) does not apply to one
created in a migration with a test proving it refuses.

🔬 **`CREATE TRIGGER` was ATTEMPTED on the live host before anything was built** — not read from grants — and
succeeded, refusing with `SQLSTATE 45000 / 1644` and cleaning up. ⚠️ **This host therefore permits
`CREATE TRIGGER` but NOT `CREATE DATABASE` (1044)** — record both together; that is the shape of what it allows.

⚠️ **Fires on INSERT *and* UPDATE.** A payout can be *moved into* a live status without ever being inserted as
one; 🔬 an insert-only guard would leave that path open while reading as covered. ⚠️ It **refuses**, it never
silently corrects. 🔬 Proven on both engines by `OwnerCollectionGateTest` writing raw SQL around the service,
and 🔬 proven to survive both `migrate:fresh --seed` **and a backup restore** — the dump carries the triggers
and the restored trigger still refuses.

⚠️ 🔬 **A defect caught in the trigger itself:** `SIGNAL … MESSAGE_TEXT` is capped at **128 characters**, and a
longer message does not truncate — the engine raises **1648 "Data too long for condition item"** instead, so
the row is still refused but the operator sees an internal error. Found by driving a real duplicate, not by
reading the manual.

⚠️ **The other layers are unchanged and still load-bearing:** the service's row-lock and re-check, and the
Stripe rail's idempotency key. ⚠️ **And the product still cannot un-pay an owner** — `reverse()` is quarantined
by ruling.

### ✅ L12b — the no-double-booking guarantee DEPENDED on a host setting nobody pinned — ⚠️ FIXED 2026-09-20

🔬 **On MariaDB at REPEATABLE READ the concurrency tests FAIL** — `SELECT … FOR UPDATE` raises
**error 1020 "Record has changed since last read"** instead of blocking and cleanly rejecting, because
MariaDB 11.6+ ships `innodb_snapshot_isolation=ON`. 🔬 **At READ COMMITTED all 17 concurrency tests PASS.**

⚠️ **This WAS true until 2026-09-20: production was READ COMMITTED only because Hostinger's GLOBAL default
happened to be, and the application set it nowhere.** A provider could have changed it in a maintenance window
with no deploy, no commit and no alarm.

✅ **FIXED (DEPLOY 03).** The application now states its own isolation level explicitly, via
`Mysql::ATTR_INIT_COMMAND` in `config/database.php` on both connections. ⚠️ PDO applies it at **connect** time,
so it reaches a web request, an `artisan` command, a **queued job** and a **scheduled task** alike — and
re-applies on reconnect, which matters because 🔬 this host drops idle connections after 20 seconds. ⚠️ A
middleware would have left the queue worker, where webhooks fulfil and payouts are written, on the host default.

⚠️⚠️ **READ COMMITTED was chosen only after running the cell nobody had: 🔬 MySQL 8.4.10 @ READ COMMITTED →
3,651 pass, identical to the REPEATABLE READ control.** Choosing the level that makes MariaDB pass without
checking MySQL at that level would have been the same mistake that created this, pointed the other way.
🔬 It is the only level correct on both engines. ⚠️ Pinned by `DatabaseIsolationLevelTest`, mutation-proved,
and recorded as **F-65**. ⚠️ `DB_TRANSACTION_ISOLATION` can override it, but the pin will go red if it does.

### ✅ L12e — the `mariadb` DRIVER IS THE WRONG ANSWER — ⚠️ QUESTION CLOSED, DO NOT SWITCH

⚠️ Production runs `DB_CONNECTION=mysql` (`MySqlGrammar`) against MariaDB, and it looked like using Laravel's
dedicated `mariadb` driver would be more correct. 🔬 **Measured 2026-09-20 — it is not.** Full suite on
MariaDB 11.8.9 at READ COMMITTED, both drivers, same commit and seed:

| driver | result |
|---|---|
| `mysql` (what production uses) | **3,648 pass** (3 failures, separately explained) |
| `mariadb` | **3,641 pass + 2 NEW ERRORS** |

⚠️⚠️ **The two new errors are real and would break production.** The `mariadb` grammar creates
`notifications.id` as MariaDB's **native `UUID` column type**, and this application writes **ULIDs**:

```
1292 Incorrect uuid value: '01M2ZK50D8XW4E674H9XH7SZDY' for column notifications.id
```

⚠️ **Switching the driver would stop notifications from being written at all.** The question is closed, not
carried: **stay on `mysql`.**

### ✅ L12f — the queue's worst-case latency is ~60 SECONDS — ⚠️ and UNBOUNDED while maintenance is on

⚠️ There is no supervisor and no Horizon. 🔬 The lifecycle, measured rather than read from the crontab: a
per-minute cron starts `queue:work --stop-when-empty --max-time=55`, which **boots, drains and exits in
~3.3 seconds** (3 runs: 3325 / 3286 / 3297 ms). 🔬 The cron interval is **exactly 60s** (heartbeats observed
at 09:52:02 → 09:53:02).

⚠️⚠️ **SO A JOB DISPATCHED JUST AFTER THE WORKER EXITS WAITS FOR THE NEXT TICK — WORST CASE ≈ 60 SECONDS.**
⚠️ Acceptable for a hotel booking; it must be a deliberate acceptance, not a surprise.

⚠️⚠️ **AND WHILE MAINTENANCE MODE IS ON, THAT LATENCY IS UNBOUNDED.** 🔬 Re-measured 2026-09-20 by running
the *same* worker command either side of the switch: **DOWN → the worker boots, runs 3s and exits having
processed nothing** (`jobs` stays 1, the heartbeat stays NULL); **UP → the same command logs
`QueueHeartbeatJob … DONE`, `jobs` goes to 0 and the heartbeat stamps.** ⚠️ The job is **not lost** — the
`database` driver holds the row and it drains on the lift. It is only *deferred*, for as long as the park
lasts.

> ### ⚠️⚠️ CORRECTION (DEPLOY 06) — THIS ROW NAMED THE WRONG VICTIM, AND THE ERROR MATTERED
>
> ⚠️⚠️ This row used to say the 60 seconds was *"the latency a **payment webhook** could experience between
> Stripe's callback and the booking actually completing"*, and told the operator to consider **adding
> `--force`** to the worker cron. 🔬 **Both are wrong, and the second is dangerous.**
>
> 🔬 **THE STRIPE WEBHOOK NEVER QUEUES ANYTHING.** `StripeWebhookController` persists the `webhook_events`
> row and then calls `$apply()` **synchronously, inside the request** (`WebhookEventClaim::once()`). No job is
> created, so no amount of queue latency — and no `--force` — touches the payment path. The only receiver
> that queues is `ChannexWebhookController`, and Channex is account-gated and not live.
> ⚠️ `--force` would instead make the worker run jobs **against a half-migrated schema mid-deploy**, which is
> the failure the maintenance switch exists to prevent. **Do not add it.**
>
> ⚠️ The 60-second figure is still correct **for the queue**. It was simply never the payment path's number.
> The payment path's real exposure is L12g.

### ⚠️⚠️ L12g — THE MAINTENANCE HOLE: a Stripe event that arrives while the site is parked LEAVES NO TRACE

⚠️⚠️ **THIS IS THE ROW TO READ BEFORE TURNING PAYMENTS ON.** Measured end to end 2026-09-20 (DEPLOY 06) as
four links, because the answer at link 1 changes what the other three mean.

| # | the link | 🔬 what was measured |
|---|---|---|
| **a** | does the handler **persist before it queues**? | **It persists and never queues.** `WebhookEventClaim::once()` writes the `webhook_events` row (`createOrFirst`, `status=processing`) and then runs `$apply()` **inline, in the request**. The Stripe path has no job at all. Only `ChannexWebhookController` queues — and Channex is account-gated. |
| **b** | what does the route return **under maintenance**? | 🖥 **503, on the live host.** `PreventRequestsDuringMaintenance` sits in the framework's **global** stack and nothing excludes the webhooks. Fetched against production while parked: `POST /webhooks/stripe` → **503**, `POST /webhooks/stripe/connect` → **503**, `/` → 503, `/up` → 200 (the health route is auto-excepted). |
| **c** | does a job queued during maintenance **survive and drain**? | **Yes** — but it never applies to Stripe. See L12f's re-measurement. |
| **d** | how long is `ops/deploy.sh`'s window? | **There is no scripted window — the script cannot run on this host.** See L12h. |

⚠️⚠️ **THE CONSEQUENCE, AND IT IS NOT THE ONE THE 60-SECOND ROW IMPLIED.** The 503 is produced **before the
controller**, so there is **no `webhook_events` row, no job, no log line — nothing inside the product records
that an event was ever attempted.** After a park, **the product cannot enumerate what it missed**; only the
Stripe Dashboard can. That is the same blind spot `WebhookEventClaim` already names — *"nothing in this
product reconciles against Stripe"* — reached from the other direction.

📄 **STRIPE'S OWN RETRY HORIZON** (read from `docs.stripe.com/webhooks` 2026-09-20 — 📄 RECORDED, not 🔬: the
vendor's documentation, never exercised against a live endpoint by this project):

- *"Stripe attempts to deliver events to your destination for **up to three days** with an exponential back
  off in **live mode**."*
- *"We retry event deliveries created in a **sandbox** three times over the course of **a few hours**."*
  ⚠️ **This deployment is in exactly that mode today** (`STRIPE_FAKE=true`, `PAYMENTS_GATEWAY=manual`).
- A `5xx` is a delivery **failure**, so a 503 **is** retried.
- Manual recovery: Dashboard **Resend ≤ 15 days**, `stripe events resend` **≤ 30 days**.

⚠️⚠️ **SO THE REVISED WORST CASE, WITH THE TWO DURATIONS KEPT APART — THEY ARE NOT THE SAME RISK:**

- **THE DEPLOY WINDOW — small, and not the problem.** 🔬 The command half of a real deploy, timed on the live
  host **with every exit code captured** (the first timing run sent output to `/dev/null` and reported `git
  fetch` and `composer install` as "0s" when they were **rc=128** and **rc=2** — a 0-second step must prove it
  *succeeded*): `composer install` **5,630ms** · `view:cache` **1,059ms** · `route:cache` **394ms** ·
  `migrate --force` **317ms** · `config:cache` **305ms** · `queue:restart` **294ms** · `event:cache`
  **289ms** — **all rc=0, total 8.29 s.** Wall-clock is dominated by file upload, not by commands. Stripe's
  backoff absorbs this completely. **Nothing needs fixing here.**
- **THE STANDING PARK — unbounded, and the whole risk.** 🔬 Production has sat in maintenance since
  **09:53 UTC** on 2026-09-20; measured at 15:22 that is **5h29m and still counting**. Nothing in the product
  ends a park, warns about one, or bounds one.
- **⚠️⚠️ THE NUMBER: a park is safe for ~3 days in live mode and only ~a few hours in the mode this
  deployment is actually in.** Past that, an event is gone from automatic delivery and recoverable **only by
  hand, from the Dashboard, within 15 days** — by someone who first has to work out that it happened.

⚠️ **WHAT WAS DELIBERATELY *NOT* DONE.** Excluding the webhook route from maintenance was considered and
**refused**: it would let the handler run and write to the folio **against whatever schema the half-finished
deploy had left**, which is strictly worse than a 503 Stripe will retry. The maintenance switch is doing its
job; the gap is that **nobody is told the park is still on**.

### ⚠️⚠️ L12h — `ops/deploy.sh` CANNOT RUN ON THE HOST THIS PRODUCT IS DEPLOYED TO, AND ITS RECOVERY TRAP IS INERT

⚠️⚠️ 🔬 Measured on the live host 2026-09-20, step by step. The script is 12 steps; **four of them cannot
execute here**, and the first failure is at step 4:

| step | command | 🔬 result on this host |
|---|---|---|
| **4/12** | `git fetch --all --tags --prune` | **rc=128** — *"fatal: not a git repository"*. ⚠️ **There is no `.git` on production at all** — the app was placed by **file upload**, never by git. `git pull` / `--rollback-to` / `PREV_SHA` all rest on this. |
| **5/12** | `composer install --no-dev …` | **rc=2** under the default `php` — *"Root composer.json requires php ^8.4.1 but your php version (8.3.33) does not satisfy that requirement."* ⚠️ The host's default CLI PHP is **8.3.33**; the app needs **`/opt/alt/php84/usr/bin/php` (8.4.19)**, under which the same command is **rc=0 in 5.63s**. |
| **6/12** | `npm ci` · `npm run build` | ⚠️ **`node` and `npm` are NOT INSTALLED.** `node_modules` is absent; `public/build` is present because assets are **built elsewhere and uploaded**. |
| **10/12** | `(crontab -l …) \| crontab -` | ⚠️ **`crontab: command not found` (rc=127).** The two crons are **Hostinger-panel-managed**, and they are real and correct — 🔬 proven by a live toggle (see L12i). |

⚠️⚠️ **AND THE TRAP THAT PROMISES TO CLEAN UP HAS NEVER RUN AND CANNOT.** Line 127 installs
`trap '… php artisan up …' ERR` under the comment *"From here on, any failure must still bring the app back
up."* 🔬 The script sets `set -euo pipefail` — **without `-E`** — and every command is wrapped in the `run()`
**function**. Bash does not inherit an `ERR` trap into functions unless `errtrace` is set, so **a failure
inside `run()` aborts the script and the trap never fires.** 🔬 Mutation-proved: an exact replica of the
script's control flow exits **127 with no trap output**; changing the single character `set -euo` → `set -Eeuo`
makes the same replica print the trap's lines.

⚠️ **THE FAILURE MODE THIS PRODUCES IS THE SAFE ONE, WHICH IS WHY NOBODY NOTICED:** a failed deploy leaves the
app **DOWN** rather than publishing a half-migrated site. ⚠️⚠️ **But the file documents a recovery behaviour
it does not have** — the same shape as the generated-column index and the stray `_e` table: *a guarantee that
reads as installed and is not.* The rollback hint the operator is meant to see is never printed either.

⚠️ **NOT FIXED — DELIBERATELY, TWICE.** Whether `ops/deploy.sh` should be made to work here or retired in
favour of a release-directory procedure is a **founder decision about how this product is released**, not a
bug to patch mid-audit. ⚠️⚠️ **Until it is taken, `ops/deploy.sh` must not be run against this host** — which
is why the script now **refuses to run** rather than being quietly "fixed" into something that looks runnable.
⚠️ **A script documenting a recovery it does not have is worse than no script**, and that is precisely what
F-67 is.

⚠️⚠️ **AND DO NOT "FIX" THE TRAP BY ADDING `-E` ALONE — IT MAKES THINGS WORSE.** 🔬 Without `errtrace` the
trap never fires and a failed deploy leaves the app **down**, which is the safe outcome. Adding `-E` would
make the trap fire and do exactly what its comment promises: `php artisan up`, **publishing a site whose code
is new and whose schema and caches are not.** ⚠️ The comment is the thing that is wrong, not the flag.

⚠️ **THE "HOW" IS ANSWERED SEPARATELY AND IT IS MEASURED — see L12k.** A release directory plus a symlink swap
costs **zero failed requests** on this host and needs **no git remote**.

> ### ⚠️⚠️ AND THE PART THAT IS STILL TRUE ON THE SERVER: THE REFUSAL IS IN THE REPOSITORY, NOT ON PRODUCTION
>
> 🔬 Measured 2026-09-20 (DEPLOY 08): production's `ops/deploy.sh` is **dated 2026-08-30, 14,418 bytes, and
> contains the F-67 refusal ZERO times** — the repository's is 18,164 bytes and contains it. `ops/proc-check.sh`
> and `ops/secret-scan.sh` **are not on the server at all**. ⚠️ **So a person who SSHes in and runs
> `ops/deploy.sh` there still gets the original, dangerous script**, which goes down, places code and dies
> partway.
>
> ⚠️⚠️ **THE WIDER FACT, AND IT IS THE ONE WORTH CARRYING: PRODUCTION IS NOT A KNOWN VERSION OF THIS
> REPOSITORY.** It is a **hand-patched tree** — files edited directly during the deployment passes are current
> (🔬 `routes/console.php` 09-20 14:13, `config/database.php` 09-20 12:56) while everything untouched still
> dates from the last upload (🔬 `ops/deploy.sh` 08-30 23:12). ✅ Every load-bearing guard IS present —
> the F-65 isolation pin (×2), the `failed_jobs` alarm, `pingOnSuccessIf`, the trigger migration, F-64's hold
> gate, and every migration `Ran` — but that was **verified item by item, which is not the same as knowing
> what is deployed.**
>
> ✅ **RESOLVED 2026-09-20 (DEPLOY 09) — PRODUCTION IS NOW A COMMIT HASH.** The tree was diffed against HEAD
> file by file before anything moved: 🔬 **1,279 identical · 2 differing (`ops/deploy.sh`,
> `ops/runtime-contract.sh` — the repo was newer) · 0 production-only files · 3 repo-only
> (`ops/proc-check.sh`, `ops/secret-scan.sh`, `bootstrap/cache/.gitignore`)**. `composer.lock` md5 identical,
> `public/build` the same 11 content-hashed files.
>
> ⚠️⚠️ **THE HARD-STOP QUESTION WAS "IS THERE HAND-PATCHED WORK THAT EXISTS NOWHERE ELSE?" AND THE ANSWER WAS
> ZERO** — every difference accounted for by name. The five `ops/` files were then placed into a new release
> and swapped in, so the live tree carries a `.release` marker naming its commit. ⚠️ **"Every load-bearing
> guard is present" was true, but it is not the same claim as "I know what is running", and only the second
> one is checkable.**

### ⚠️⚠️ L12j — THE ASYMMETRY: STRIPE'S PATIENCE IS BOUNDED, A PARK IS NOT. THIS IS THE RISK STATEMENT.

⚠️⚠️ **IF ONE ROW FROM THIS FILE EVER REACHES A FOUNDER, MAKE IT THIS ONE.**

> 📖 **Stripe's automatic retry is BOUNDED — *"up to three days"* in live mode, and only *"three times over
> the course of a few hours"* in a sandbox. A maintenance park is UNBOUNDED: nothing in this product ends one,
> warns about one, or caps one. ⚠️⚠️ A park that outlasts the retry window loses those events PERMANENTLY from
> automatic delivery — recoverable only by MANUAL resend, within 15 days from the Dashboard or 30 days from
> the CLI, by someone who first has to work out that it happened at all.**

⚠️ **📖 NOT 🔬 — deliberately.** Those durations are Stripe's own documentation (`docs.stripe.com/webhooks`,
read 2026-09-20). Nothing in this project has ever watched a real delivery exhaust its retries. ⚠️ Treat the
three days as a *documented intention of a third party*, not as a measurement this project owns.

⚠️⚠️ **AND THE MODE MATTERS MORE THAN THE NUMBER:** `STRIPE_FAKE=true` and `PAYMENTS_GATEWAY=manual` — this
deployment is in **exactly the mode with the SHORT window**. The reassuring figure is the one that does not
currently apply.

⚠️ **WHY IT BITES HERE SPECIFICALLY:** the handler is **inline** (L12g), so a parked site loses the event
*before any record exists*. The queue's ≈60-second worst case (L12f) is a real figure about a real thing and
**is not this** — see the scope note there. ⚠️ **Every figure carries its scope; a corrected figure teaches
more than a vanished one, which is why L12f was re-scoped rather than deleted.**

### ✅ L12k — THE RELEASE LAYOUT — ⚠️⚠️ **ADOPTED 2026-09-20, AND LIVE ON PRODUCTION NOW**

⚠️⚠️ **THE TARGET WAS NEVER "A DEPLOY SCRIPT". IT IS A NUMBER: how long is `/webhooks/stripe` unreachable
during an ordinary code change?** Because the Stripe handler runs **inline** (L12g), that window *is* the
Stripe risk. ⚠️ For a code-only change the correct answer is zero, and only atomicity buys it.

⚠️ **THE FOUNDER'S RULING SHAPED IT:** `public_html` **stays a real directory the hosting panel owns**. The
swap happens one level below, on a symlink the panel knows nothing about.

```
~/domains/vyrobel.com/
  public_html/          ← REAL directory, panel-owned. Holds only four things:
      .htaccess           rewrites EVERYTHING through current/public
      .env                (600) — one copy, never per-release
      storage/            one copy — so the maintenance flag and logs survive a swap
      artisan             ⚠️ a SHIM (see below), not the application's
      current  ─────────→ ../releases/rel_<id>      ⚠️ THE ONE POINTER
  releases/rel_<id>/    ← a whole app: app/ bootstrap/ config/ public/ vendor/ …
      .env    → ../../public_html/.env
      storage → ../../public_html/storage
```

🔬 **MEASURED ON THE LIVE HOST, SITE PARKED THROUGHOUT, EVERY PROBE CACHE-BUSTED (there is a CDN — L12m):**

| | 🔬 |
|---|---|
| build the next release (`cp -al`, hardlinks) | **861 ms** |
| ⚠️⚠️ **the swap — ONE `mv -T` of one symlink** | **3 ms** |
| ⚠️⚠️ **requests failed across the swap** | **0 non-200 of 220** |
| new release first served | **t+32 ms** |
| prune the previous release **immediately** | ⚠️⚠️ **60/60 → HTTP 500** |
| prune the previous release **at +180 s** | ✅ **0/60 failures** |

⚠️⚠️ **ATOMICITY MEANS ONE SWAP, NOT TWO — AND THAT IS WHY THE ASSETS RULE MATTERS.** The `.htaccess` rewrites
the front controller **and every build asset** through `current/public`. If assets sat *beside* the pointer
they would need a second move, and the gap between two moves is the original hazard wearing a better name.

⚠️⚠️ **WHOLLY INTO ONE RELEASE, NEVER PARTLY INTO TWO.** Each release is a separate directory tree, so a
request resolves entirely into one of them: the marker read coherently `REL_A` or `REL_B` and never anything
between. ⚠️ **A late deploy, not a mixed one** — which is exactly the property a file-by-file upload over a
live app cannot offer, since there a single request really can read half-old, half-new code on the money path.
⚠️ Stated precisely: this is a structural argument (one realpath tree per release) plus a consistent
measurement — **a single marker file evidences per-request coherence rather than proving it exhaustively.**

### ⚠️⚠️ THE RETENTION FLOOR IS A MEASURED NUMBER, NOT A COUNT OF RELEASES

⚠️⚠️ **THE SWAP IS FREE; PRUNING IS WHAT BREAKS THE SITE.** Workers stay bound to the release they resolved,
so deleting it pulls the files out from under them. ⚠️ The binding constraint is **not** process lifetime —
🔬 the oldest `lsphp` aged 180 s → 360 s across the experiment **without ever being recycled**, so waiting for
processes to die would wait forever. What actually expires is the **`realpath_cache_ttl` of 120 s**, after
which a process re-resolves the path.

> ⚠️ **PRUNE NO SOONER THAN 180 SECONDS AFTER A SWAP** — above the 120 s realpath TTL and above the 60 s
> worker-cron cycle. 🔬 Proven safe by running it: **0 failures of 60**, against **60/60 HTTP 500** when done
> immediately. ⚠️ Keep at least the previous release on disk until then.

⚠️⚠️ **AND THE FLOOR SURVIVED A CHALLENGE IT COULD HAVE FAILED.** The edge caches static assets for **7 days**
(L12m), which would raise the floor to `max(realpath TTL, edge TTL)` — **seven days, not three minutes** — *if*
a cached HTML page could outlive a release and then request its assets. 🔬 It cannot: **HTML is never cached**
(`no-cache, private` + `Set-Cookie` → `DYNAMIC`), so a page always comes from the current release and always
references assets that exist in it. ⚠️ **The floor stays 180 s — but it is 180 s *because* HTML is uncacheable,
which is a property to protect, not a coincidence to rely on.**

### ✅ THE EDGE FIGURE, CLOSED BY SAMPLE SIZE (DEPLOY 10) — ⚠️ and the framing corrected

⚠️ DEPLOY 09 measured **199/200** at the edge across one swap and refused to call the single failure either
way. ⚠️ **200 requests cannot distinguish 0.5% from 0%**, so the sample was raised on **both** arms:

| arm | probes | connection failures (`000`) | rate |
|---|---|---|---|
| control — **no swap** | 600 | **0** | 0% |
| **3 real swaps** | 600 | **1** | 0.17% |
| *combined with DEPLOY 09* | swap 800 / control 700 | swap **2**, control **0** | 0.25% / 0% |

⚠️⚠️ **VERDICT: NOT DISTINGUISHABLE, AND CLOSED ON THAT BASIS.** Two events against zero, with 95% intervals
that overlap (≈0.07–0.91% versus 0–0.43%); a Fisher exact test on 2/800 vs 0/700 is nowhere near significant.
⚠️ **Any swap-induced excess is bounded under ~1% and the data are equally consistent with zero.**

⚠️ **AND THE APPLICATION LAYER SHOWED NO VARIATION AT ALL:** every non-`000` probe in both arms was a clean
503 — **600/600 and 599/600**. A `000` is a **connection** failure, not an HTTP one, so it points at the
network between the client and the edge, **not at the swap and not at the application**.

⚠️⚠️ **AND THE FRAMING THAT PRODUCED THE "GAP" WAS ITSELF WRONG — MINE INCLUDED.** The 0/220 was **`/up`,
cache-busted, at the ORIGIN**; the 199/200 was **`/login`, uncache-busted, at the EDGE**. ⚠️ **Two different
paths through two different layers is not a gap, it is two measurements** — and *"every figure carries its
scope"* applies to the figures this project writes about itself.

### ⚠️ THREE THINGS THAT HAD TO BE RIGHT, AND ALL THREE WERE FOUND BY MEASUREMENT, NOT BY READING

1. ⚠️ **`Options +FollowSymLinks` IS REQUIRED.** A symlinked *docroot* is followed; a symlink **inside** the
   served tree is not, without it. 🔬 Attempt 1 returned **404 for every application route**.
2. ⚠️⚠️ **LARAVEL'S COMPILED CACHES BAKE ABSOLUTE PATHS.** 🔬 `bootstrap/cache/config.php` named the old root
   **21 times**; with the app moved, the framework **500'd** while `/login` still correctly served its 503
   (because maintenance short-circuits before the framework boots — so the failure hid behind a page that
   looked right). ⚠️ **Every release must have its caches rebuilt in place after it is positioned.**
3. ⚠️⚠️ **THE PANEL CRON IS HARDCODED TO `public_html/artisan`, AND THE MIGRATION KILLED IT SILENTLY.**
   🔬 The scheduler logged `Could not open input file: …/public_html/artisan` every minute while the website
   looked perfectly healthy — and that cron drives the **14-day GDPR erasure** and the **30-day ID purge**.
   ⚠️ The panel owns those entries and a session cannot read or edit them, which is why the fix is a **shim**
   at `public_html/artisan` that does `require __DIR__.'/current/artisan';` — resolving through the *same*
   pointer, so a deploy stays one operation. 🔬 Proven by the log turning back into
   `INFO No scheduled commands are ready to run.` — a positive artefact, not an absent error.

### ⚠️ HOW TO DEPLOY INTO THIS LAYOUT

1. `cp -al releases/<current> releases/rel_<new>` — hardlinks, ~0.9 s.
2. Upload changed files into `releases/rel_<new>` (and `public/build`, which must still be built off-host —
   **node and npm are not installed here**, L12h).
   ⚠️⚠️ **NEVER `scp` STRAIGHT ONTO A FILE IN THE NEW RELEASE.** After `cp -al` every unchanged file is a
   **hardlink shared with the live release**, and `scp` opens the destination and writes into it — through
   the shared inode — so the edit lands in the release currently serving traffic, mid-upload. That is the
   F-67 half-old-code hazard, re-created by the very layout that fixed it. ⚠️ Upload to a **staging
   directory** instead, then `rm -f <target> && cp <staged> <target>` — the `rm` drops the directory entry
   without touching the other release's inode. 🔬 Prove it landed that way: the two releases' inodes for a
   changed file must DIFFER, and the live release must still contain its OLD content.
3. Re-point the shared symlinks and rebuild that release's caches **in place** (item 2 above).
   ⚠️⚠️ **`config:cache` AND `route:cache` ARE MANDATORY ON EVERY DEPLOY, INCLUDING ONE WHERE NO CONFIG
   VALUE CHANGED — AND SKIPPING THEM FAILS *SILENTLY*, SERVING THE PREVIOUS RELEASE.** 🔬 Measured
   2026-09-24 on a views-only deploy (rel_K → rel_L). `cp -al` hardlinks the previous release's
   `bootstrap/cache/config.php`, and that file is not just *values* — it is **20 baked ABSOLUTE PATHS**,
   `view.paths` among them. So rel_L booted, ran rel_L's PHP, and rendered **rel_K's Blade files**. The
   symlink swap succeeded in 3 ms, `/admin/login` returned **200**, and the response was **byte-identical
   to before the deploy** — 11,351 bytes both times. Nothing was red. ⚠️ The reasoning that caused it is
   the trap worth remembering: *"no config value changed, so the config cache is still valid."* True of the
   values, false of the paths. `routes-v7.php` carries 3 such paths too; `packages.php` and `services.php`
   carry none. ⚠️⚠️ **THE LESSON GENERALISES PAST THIS FILE: A CACHE KEYED ON ABSOLUTE PATHS CANNOT BE
   CARRIED ACROSS A RELEASE DIRECTORY, EVER.** ⚠️ It also explains why the shared `storage/framework/views`
   is NOT a hazard: compiled views are keyed by the *source* path, so each release gets its own entries
   (🔬 all 277 cached entries referenced `rel_K` alone) — the config cache was the only path carrier.
   ⚠️ **AND THE ONLY INSTRUMENT THAT CATCHES IT IS A CONTENT ASSERTION, NOT A STATUS CODE.** A 200, a
   green availability sweep and a correct `readlink` were all true while the deploy had not landed. Diff a
   marker that only the new code emits — ⚠️ and take the BEFORE reading *before* the swap, or you cannot
   tell "landed" from "was already there".
   ⚠️⚠️ **THEN `chmod 600 bootstrap/cache/config.php`.** 🔬 That file is a compiled copy of `.env` and contains
   the **database password** (13 occurrences); `config:cache` writes it **644** on a shared host. 🔬 Verified
   2026-09-20 that **600 still serves** — `/up` returns 200 through a full framework boot and `artisan` still
   runs for the cron — so there is no reason to leave it readable. ⚠️ It is a **per-release** step: a fresh
   `config:cache` recreates it at 644 every time.
4. `ln -s ../releases/rel_<new> public_html/current.tmp && mv -T public_html/current.tmp public_html/current`
   — ⚠️ the whole deploy, and the only atomic step.
5. ⚠️ **Wait ≥180 s, then** prune older releases.
6. ⚠️ **A MIGRATION IS NOT THIS.** See the scope note below.
7. ⚠️ **AN `.env`-ONLY CHANGE IS A RELEASE TOO** (L14 — a cached config never reads `.env`). `ops/release.sh
   recache rel_<new>` builds a release of the SAME commit with config and routes rebuilt from `.env` as it is now
   (`stage` refuses a commit that is already live); then `swap`. The live release is never touched — the new one
   gets its own config-cache inode, checked — so a rollback restores the previous config exactly.

⚠️ **TO REVERT THE LAYOUT ENTIRELY:** move the release's contents back into `public_html`, delete `current`
and the `artisan` shim, restore the previous `.htaccess`, and rebuild the caches in place.

### ⚠️⚠️ SCOPE IT HONESTLY — THIS IS NARROWER THAN IT SOUNDS

⚠️⚠️ **L12k MAKES A CODE-ONLY DEPLOY ZERO SECONDS. A SCHEMA CHANGE STILL PARKS THE SITE — AND THAT IS NOW
WHERE THE STRIPE EXPOSURE LIVES.** L12j does not go away; it **narrows to migration deploys**.

⚠️ The direction that would close it is **expand-then-contract migrations** — every step backward-compatible,
so each deploy is code-only and the schema change never needs a park. ⚠️ **Named here, deliberately NOT built:
it is a change to how every future migration is written, and that is a decision, not a task.**

### ⚠️⚠️ L12m — THERE IS A CDN IN FRONT OF PRODUCTION, AND NOTHING IN THIS REPOSITORY KNEW

⚠️⚠️ **THIS IS THE LAYER NOBODY OWNED.** Production is fronted by Hostinger's CDN — `server: hcdn` ·
`platform: hostinger` · `x-hcdn-cache-status` · `x-hcdn-request-id: …-dus-edge1`. ⚠️ It appears in **no**
deployment document, runbook or configuration this project controls, and it was found only because it
misbehaved.

⚠️ **HOW IT ANNOUNCED ITSELF.** A botched layout change made `/` emit a **301** for a few seconds. The CDN
cached it — 301s are cacheable by definition — and **replayed it for over two hours** (🔬 `age: 7004` and still
climbing at the last reading) while the origin served a correct 503 throughout. 🔬 `/` returned 301 on 22 of 24
probes; `/?cb=<unique>` returned **503 every time**. ⚠️ Killing the PHP workers did nothing; rewriting
`.htaccess` did nothing; **because the origin was never wrong.** ⚠️ The origin can no longer emit that 301 —
the site root now maps straight to the front controller — but the cached entry outlives the fix, which is the
whole lesson.

### ⚠️⚠️ WHAT THE EDGE DOES AND DOES NOT DO — 🔬 measured, not read from a setting

| | 🔬 measured |
|---|---|
| Laravel **HTML pages** (`/login`, the 503, the homepage) | `cache-control: no-cache, private` + `Set-Cookie` → **`DYNAMIC`**, `x-hcdn-upstream-rt` present. **Never cached.** |
| **`POST` of any kind** | **`DYNAMIC`**, always upstream. 🔬 38 probe POSTs and 25 webhook POSTs — **every one reached the origin**. |
| **static assets** (`/build/assets/*`, `favicon.ico`) | `cache-control: public, max-age=604800` → **HIT/MISS, 7 days.** |
| **redirects and error codes** (301, 500, 503) | ⚠️ carry **no** `cache-status`, **no** `age`, **no** `upstream-rt` — ⚠️⚠️ **the edge does not annotate non-2xx at all**, so headers alone cannot tell you who answered. A 301 *is* cached; a 503 was not observed to be. |

⚠️ **THE DISCRIMINATOR, VALIDATED BEFORE IT WAS USED:** `x-hcdn-upstream-rt` appears on responses the edge
fetched from the origin and is **absent on a cache HIT** — 🔬 confirmed both ways against a known HIT
(`favicon.ico`, `age` present, no `upstream-rt`) and a known DYNAMIC (`/up`). ⚠️ It is **absent on every
non-2xx**, which is why the parked 503 needed a different instrument entirely.

### ⚠️⚠️ THE QUESTION THAT DECIDES GATE 3: CAN THE EDGE ANSWER FOR THE WEBHOOK?

⚠️⚠️ **IT CANNOT — AND THIS IS THE MOST IMPORTANT MEASUREMENT IN THIS FILE.** An edge that cached, blocked,
rate-limited or challenged `POST /webhooks/stripe` would consume Stripe's retry budget **invisibly**, with
nothing in any origin log — the exact silent-failure signature this project keeps meeting.

🔬 **MEASURED LIVE (site briefly unparked, 2026-09-20), with a body only the origin can produce:** a POST with
a junk signature returns **`400 {"error":"invalid_signature"}`**, which is `StripeWebhookController` refusing
at the origin. **5 individual POSTs → all 400. 20 rapid identical POSTs → all 400. The Connect endpoint →
400.** ⚠️ **No `age` header on any of them. No rate-limit, no challenge, no cached answer.**

🔬 **AND INDEPENDENTLY, PARKED**, via a standalone origin witness that logged every arrival: **38 requests sent,
38 recorded by the origin** — 25 in a rapid burst, 4 identical repeats, plus deliberate 200/500/503 responses.
**Zero dropped, zero cached, zero throttled.**

⚠️ **THE BOUND ON THAT CLAIM:** 25 rapid requests from one IP is not a flood. A DDoS-scale burst may meet
protections that were not exercised here, and this says nothing about what the panel might enable later.
⚠️ **L12j's retry-window argument stands on this measurement — if the edge's behaviour on that route ever
changes, L12j is void and must be re-measured.**

### ⚠️⚠️ WHAT THIS DOES TO EVERY DEPLOYMENT NUMBER THIS PROJECT HAS PUBLISHED

⚠️⚠️ **THE 0/520 AND 0/220 WERE TAKEN THROUGH `/up`, WHICH IS `no-cache, private` — THE ONE ROUTE EXEMPT FROM
THE LAYER UNDER TEST.** ⚠️ **A probe that is exempt from the thing under test measures nothing about it.**
Those figures are **true about the origin and silent about the customer**, and the probe was chosen for
convenience eight passes before anyone knew the layer existed.

🔬 **RE-MEASURED AT THE EDGE, UNCACHE-BUSTED, ON A REAL PAGE (`/login`) ACROSS A REAL RELEASE SWAP:**
**199 of 200 returned 200; one returned `000` (a connection failure, not an HTTP error).** 🔬 A no-swap control
on the same path returned **0 of 100** such failures. ⚠️ **One failure in 200 is not attributed to the swap and
not dismissed as ambient** — the control makes "ambient" unlikely, and the sample is too small to call it
either way. ⚠️ **The honest statement is: 0/220 at the origin, 199/200 at the edge, and the gap is unexplained.**

### ⚠️ HTML/ASSET SKEW — why it cannot happen here, and the condition that keeps it that way

⚠️ At the origin a release is atomic because everything resolves through one pointer. ⚠️⚠️ **At the edge, HTML
and hashed assets are separate objects with separate lifetimes** — so in principle the edge could serve a page
from release N+1 referencing assets it still holds from N, or a cached page from N asking for assets that only
exist in N+1. **That is the edge equivalent of "partly into two releases", and it is a broken page.**

✅ **IT CANNOT HAPPEN HERE, AND THE REASON IS MEASURABLE: HTML IS NEVER CACHED.** 🔬 Every Laravel response
carries `no-cache, private` and a `Set-Cookie` (the session middleware), so the edge marks it `DYNAMIC` and
fetches it fresh. A page therefore always comes from the **current** release and always references assets that
exist in it. Old hashed assets may linger at the edge for 7 days, but nothing references them — a new release
produces **new filenames**, which are a MISS and fetched from origin.

⚠️⚠️ **THE CONDITION, AND IT IS FRAGILE: the moment any page is made publicly cacheable** — a marketing page
with `public, max-age=…`, a full-page cache, an edge rule added in the panel — **skew becomes possible
immediately.** ⚠️ Treat "HTML is uncacheable" as a load-bearing property, not an accident.

⚠️ **AND THE RETENTION FLOOR IS THEREFORE UNCHANGED AT 180 s (L12k), NOT `max(realpath TTL, edge TTL)`.** The
7-day asset TTL would raise it only if a cached HTML page could outlive a release and request its assets —
which requires cacheable HTML. ⚠️ **If that condition ever changes, the prune floor becomes 7 days.**

### ⚠️ WHAT IS NOT KNOWN, AND WHAT THE FOUNDER SHOULD DECIDE

- ⚠️ **The edge TTL for a cached 301 is ≥ 2 hours** — 🔬 observed `age` climbing past 7004 s and never expiring
  within the pass. The upper bound is **not** known.
- ⚠️ **There is no origin access log this account can read** (`~/.logs/` holds only cron output, an error log
  and `mail.log`), which is why the origin witness had to be built rather than consulted.
- ⛔ **A stale 301 on `/` is still being served from the edge.** It needs an **hPanel CDN purge**, or it expires.
  ⚠️ This session cannot purge an edge cache.
- ⚠️ **Whether the CDN can be disabled, and whether its rules can be edited, was not established** — it is a
  panel setting and the decision is the founder's. ⚠️ **Not disabled unilaterally.**

### ✅ L12n — DOES THE ALARM BOUND THE PARK? — ⚠️ THE ARGUMENT WAS TESTED, AND IT HOLDS

⚠️⚠️ **THE CLAIM UNDER TEST:** a schema change still parks the site, and a park is "unbounded" only because
nothing bounds it — but the heartbeat is maintenance-gated **by ruling** (L12i), so a park stops the beat, the
grace lapses, and the alarm rings. ⚠️ **If that holds, the alarm converts an unbounded park into one bounded
by grace plus human response — and expand-then-contract migrations are NOT a prerequisite for card payments.**

⚠️ It was treated as an argument to **break**, not to adopt. The chain, link by link:

| link | verdict |
|---|---|
| a park stops the **ping** | ✅ 🔬 **VERIFIED FROM SOURCE.** `ScheduleRunCommand:263-265` does `continue` **before** `runEvent()`, and `pingOnSuccessIf` attaches the ping as an **`onSuccess`** callback — a skipped event has no success, so it cannot ping. Nothing anywhere declares `evenInMaintenanceMode` (pinned: `HeartbeatIsMaintenanceGatedTest`). Corroborated live: DEPLOY 06's toggle froze both heartbeats through a park and moved them within 160 s of the lift. |
| no ping → the check alerts | 📖 **Healthchecks' documented behaviour.** ⚠️ **Not measurable here** — `OPS_HEARTBEAT_PING_URL` is 🔬 measured **unset on production**, so nothing external is watching yet. |
| alert → human → lift | ⚠️ **Not measurable.** It depends on a person, and no amount of code changes that. |

### ⚠️ THE THREE ATTEMPTS TO BREAK IT

1. ⚠️⚠️ **"THE ALARM WILL HAVE RUNG FOR EVERY ROUTINE DEPLOY, SO IT WILL BE IGNORED." — 🔬 THIS PREMISE IS NOW
   FALSE, AND ITS FALSENESS STRENGTHENS THE ARGUMENT.** L12k made a code-only deploy **parkless**; a deploy
   that never parks cannot stop the beat, so it cannot ring. **Only a schema park longer than the grace
   rings** — rare, and exactly when someone should look. The fatigue objection largely dissolves.
2. ⚠️ **"A PARK SHORTER THAN THE GRACE NEVER RINGS."** ✅ True, and **not a hole**. Detection is bounded by
   the cron period (60 s) plus the grace G; at the recommended G = 5 min that is **≤ 6 minutes**. A park
   shorter than that is invisible — and a 6-minute park is **four orders of magnitude inside** Stripe's live
   retry window. ⚠️ **The un-rung parks are precisely the harmless ones.**
3. ⚠️ **"THE ALARM CONFLATES CAUSES."** ✅ True, and it is by design: the same silence means *parked*, *cron
   dead*, *host dead*, or *`failed_jobs` non-empty* (that task **deliberately throws**, suppressing the ping —
   L12c). ⚠️ **So the alarm says "something is wrong", never "what".** The responder must look; that is what a
   dead-man's switch is.

### ⚠️⚠️ THE NUMBER, AND THE CORRECTION THAT MATTERS MORE THAN THE VERDICT

> **Detection ≤ 60 s + grace.** At G = 5 min → **≤ 6 minutes**. The park itself is bounded by detection plus
> human response. Against Stripe's **3-day** live retry window (4,320 minutes) that is a margin of roughly
> **720×**; against the sandbox window (~hours) it is still comfortable.

⚠️⚠️ **BUT THE ALARM IS NOT WHAT MAKES A PARK SAFE — STRIPE'S RETRY WINDOW IS.** Any park shorter than three
days loses **nothing** in live mode, alarm or no alarm. ⚠️ The alarm's real job is narrower and still worth
having: **it stops a park from silently EXCEEDING that window.** There are two independent protections, and
the weaker case — no alarm at all — is already covered for any park a human would plausibly cause.

✅ **VERDICT: EXPAND-THEN-CONTRACT IS NOT A GATE 3 PREREQUISITE.** It would become one only if a migration
park could plausibly outlast Stripe's retry horizon, which would mean a three-day migration. ⚠️ **The last
substantial code item before card payments is therefore removed — and nothing was built to remove it.**

### ⚠️⚠️ AND A SECOND DETECTION PATH NOBODY HAD COUNTED — FOUND BY READING, STRONGER THAN THE FIRST

🔬 `routes/web.php:1746` registers **`/up/liveness`**, which returns `Heartbeat::report()` as JSON and
**answers 503 when either heartbeat is stale**. ⚠️ Its own comment already says the quiet part: *"⛔ FOUNDER
STEP: point an external monitor at this URL. Unpolled, it is evidence nobody is reading."*

🔬 **Measured on production, parked: `/up/liveness` returns 503** (the maintenance page — it is **not** in the
`down` file's `except` list; only Laravel's own `/up` is auto-excepted).

⚠️⚠️ **THAT MAKES IT THE BETTER PARK DETECTOR OF THE TWO:**

| | push — the Healthchecks ping | pull — a monitor on `/up/liveness` |
|---|---|---|
| detects a park | after **period + grace** (≤ ~6 min at G = 5 min) | **on the very next poll** — a park 503s immediately |
| depends on the scheduler running | ⚠️ **yes** — it rides the scheduled task | ✅ **no** — it is an HTTP request |
| also catches | cron dead · host dead · `failed_jobs` non-empty | cron dead (stale heartbeat) · host dead · park |

⚠️ **They are complementary, not redundant**: the pull check cannot see a `failed_jobs` backlog on a healthy
site the way the push check can (the scheduler throws and withholds the ping), and the push check cannot
detect a park faster than its grace. ⚠️ **Configure both — it is the same sitting and the same monitor, two
URLs.**

⚠️⚠️ **THE RESIDUAL RISK, STATED PLAINLY: TODAY NEITHER IS CONFIGURED.** 🔬 `OPS_HEARTBEAT_PING_URL` is unset
on production, and nothing polls `/up/liveness`. So the only live protection is Stripe's retry window — and
this deployment sits in the mode where that window is **hours, not days** (L12j). ⚠️ **A forgotten park today
does lose events, within hours, with nothing announcing it.** That is Gate 3 condition (1), and it is one
founder sitting away.
### ⚠️ L12i — THE ALARM SHARES THE MAINTENANCE SWITCH WITH THE WORK IT WATCHES — ruled NOISY, on purpose

🔬 **MEASURED BY A REAL down→up→down TOGGLE ON PRODUCTION (2026-09-20), which is a positive artefact and not
an absent beat.** Before the lift both heartbeats were frozen — scheduler `09:53:02`, queue `14:12:35`.
Maintenance was lifted at **15:20:04**; **160 seconds later scheduler read `15:22:01` and queue `15:21:03`**,
then maintenance was restored byte-for-byte. ⚠️ That single artefact establishes three things at once: the
panel crons **are** alive and correctly configured, the silence **was** caused by maintenance, and a worker
drains within ~60s of a lift.

⚠️ The mechanism: `ScheduleRunCommand` skips every event for which `runsInMaintenanceMode()` is false, and
**nothing in this application declares `evenInMaintenanceMode()`** — so under maintenance the scheduler
heartbeat never beats, and the queue heartbeat is never even *dispatched* (it is a `Schedule::job`).

⚠️⚠️ **THE RULING — WE LIVE WITH *NOISY*, NEVER WITH *LYING*.** Making the heartbeats beat under maintenance
would silence the alarm during a park — but the scheduler is skipping **every** daily task while parked: the
**14-day GDPR erasure**, the **30-day ID purge**, `billing:reconcile`, `payouts:run`, the reminder emails. An
alarm reporting "healthy" through that asserts the opposite of the truth, **and it errs toward inaction**.
⚠️ Maintenance is also **not a silent failure** — it is the loudest state the product has, since every visitor
gets a 503; the alarm is the only signal that still reaches someone **not looking at the site**, which is
exactly the person who forgets a park. ⚠️ **So the heartbeats stay maintenance-gated, and a deploy or a park
WILL page. That cost is accepted, not overlooked** — pinned by `HeartbeatIsMaintenanceGatedTest` so it is
never "fixed" into a lie.

⚠️ **THE ONE THING THAT MAKES NOISY LIVABLE:** Healthchecks' **grace period must exceed the real deploy
window**. 🔬 That window's command half is under 15 seconds (L12g), so **any grace ≥ 5 minutes absorbs a
routine deploy while still catching a forgotten park.** ⚠️ Set it when the ping URL is set — see the founder's
sitting in `GO-LIVE-REQUIREMENTS.md`.

> ### ✅ CORRECTION (DEPLOY 10) — "A DEPLOY WILL PAGE" IS NO LONGER TRUE, AND THE NOISE OBJECTION LARGELY GOES
>
> ⚠️ The ruling above was written when **every** deploy parked the site. 🔬 **L12k removed that**: a code-only
> deploy is one 3 ms symlink swap with **no park at all** — and a deploy that never parks cannot stop the
> heartbeat, so it **cannot ring**.
>
> ⚠️⚠️ **SO THE ALARM IS LOW-NOISE, NOT HIGH-NOISE.** Only a **schema** deploy parks, and only one that
> outlasts the grace period rings. ⚠️ That matters beyond tidiness: the standing objection to a noisy alarm is
> that it trains people to ignore it, and **that objection is now much weaker than when the ruling was made.**
> ⚠️ The ruling itself is unchanged and still correct — noisy over lying — it is simply cheaper than it was.

### ✅ L12c — a failed job WAS invisible — ⚠️ now raises the alarm (2026-09-20)

⚠️ **Fixed by routing it through the channel that already rings, rather than a screen nobody opens.** The
per-minute scheduler heartbeat now **throws when `failed_jobs` is non-empty**, which suppresses the
success-ping, which lets the external check's grace period lapse, which sends the alert.

⚠️ **THRESHOLD: ANY failed job.** With zero customers every failure is worth waking for; raising it later is a
decision with a number, not a default.

🔬 **Proven both directions in one run:** empty → heartbeat beats · one failed row → **beat suppressed AND the
exception logged** (proven by a new log entry, not merely by an absent beat) · cleared → beats again.

⚠️⚠️ **THE ALERT TRAVELS THROUGH THE EXTERNAL CHECK'S OWN MAIL, NOT POSTMARK — AND THAT IS THE POINT, NOT AN
OVERSIGHT.** An alarm delivered through the product's own mail would go silent exactly when the mail went
silent, which is when it is needed most. ⚠️ **AN ALARM THAT SHARES A FAILURE MODE WITH THE THING IT WATCHES IS
NOT AN ALARM. Do not "tidy" this onto Postmark.**

⛔ **STILL INERT: `OPS_HEARTBEAT_PING_URL` is unset**, so no ping is attempted and nothing pretends one was.
⚠️ **The alarm has never been heard to ring.** Until it has, this is a configuration setting, not a safeguard.

### ⚠️ L12c-old — the original finding, for the record

🔬 Failed jobs land in `failed_jobs` (so they are not lost), but **Horizon cannot run** (`horizon:status` →
*Connection refused*, no Redis), **no route surfaces them**, and 🔬 `OPS_HEARTBEAT_PING_URL` is **unset**, so
there is no external dead-man's switch either. ⚠️ **A failed payment webhook would sit in a table nobody looks
at.** The in-app heartbeats exist (queue stale after 900s) but nothing watches them from outside.

### ⚠️ L12d — the reboot test is UNPROVABLE on this host

⚠️ Not passed, not skipped — **impossible**. Shared hosting, no root, no systemd. Whether the app returns
cleanly after an unplanned restart **cannot be exercised here** and stays unknown.

### ⚠️ Other measured host constraints

🔬 The database user **cannot `CREATE DATABASE`** (error 1044) — it can DROP/CREATE its own tables, so
`migrate:fresh` works inside the one database, but a second scratch database needs hPanel. 🔬
`max_user_connections 75` · `MAX_STATEMENT_TIME 120s` · ⚠️ **`wait_timeout 20s`**. 🔬 `ext-sodium` had to be
enabled by hand before `composer install` would run at all.

⚠️ **What did NOT differ, and was measured rather than assumed:** the collation (🔬 `config/database.php` pins
`utf8mb4_unicode_ci`, which both engines implement identically — same sort order, same case/accent equality) ·
`sql_mode` (🔬 Laravel sets `STRICT_TRANS_TABLES` per connection, so a bad amount is **rejected, not
truncated**) · JSON (🔬 43 CHECK constraints enforce validity; every JSON path test passed) · and the money
gate's digest (🔬 it sorts in **PHP**, not SQL, so collation cannot move it).

## L6 · ⚠️ NOT A LIMIT — the role toggles that gate nothing

⚠️⚠️ **This does not belong on this page and no customer-facing sentence is written for it.** *"Some
permission toggles do not take effect"* is a sentence no product should ship: an administrator who
unchecks *"manage taxes"* believes access was removed. That is the same class as a dead control or a false
claim — and this project **fixed** both of those rather than documenting them.

**⚠️⚠️ SUPERSEDED — 17 of 51, corrected FIX 42. This read "12 of 46 … not 14 of 51".** 🔬 Both of its numbers were wrong, for two separate reasons, and the census that produced them has been DELETED in favour of one source ({@see `vyUnreadPermissions()`}, pinned by name in `PhantomPermissionRegisterIsPinnedTest` and partitioned against the catalogue in `PermissionEnforcementContractTest`): **the MATCHER** was a bare `str_contains` with no comment stripping, so `guest_portal.view`, `housekeeping.view` and `owner_portal.view` — named in the codebase ONLY inside docblocks and a Blade comment — counted as enforced; and **the DENOMINATOR** counted permissions granted to a role while skipping `'*'`, so the five held only through a wildcard (`api.manage`, `billing.manage`, `organizations.manage`, `roles.manage`, `settings.manage`) were structurally invisible. ⚠️ See `docs/PHANTOM-PERMISSIONS.md` for the seventeen and their three shapes.
⚠️ The earlier count was reached with a pattern narrower than the codebase's idioms — and so were two of
my own attempts at it: a shell census undercounted by one because the permission list had no trailing
newline (`while read` silently drops the last line), and a `grep` for `availability.view` matched prose
because the `.` was an unescaped regex wildcard. The figure below comes from a literal `str_contains`
per permission over every `.php` file in `app/`, `routes/` and `resources/` — 966 files — and is pinned by
`KnownLimitsAreStillTrueTest` so it cannot drift again.

⚠️⚠️ **In no case is a write left unprotected.** ⚠️ **The table below lists TWELVE and this sentence said "The 11" — the typo the deleted test's comment faithfully copied. Corrected FIX 42: the twelve below are a SUBSET of the seventeen, and the authoritative three-shape breakdown is in `docs/PHANTOM-PERMISSIONS.md`.** These fall into three different shapes:

| Shape | Permissions | What is actually true |
|---|---|---|
| **Coarser gate** — the write exists and is protected, by a *broader* permission | `taxes.manage`, `fees.manage`, `invoices.issue` | `/settings/tax-fees` and `/settings/fees/add` are `can:properties.manage`; `/settings/invoice` is `can:settings.manage` |
| **Wrong realm** — an operator-catalogue entry for a non-operator capability | `invoices.view`, `payments.view` | the matching routes are guest-realm (`/reservation/{r}/invoice.pdf`, `/payments`) |
| **No distinct action** — nothing in the product performs this as a separate operator write | `amenities.manage`, `availability.manage`, `availability.view`, `restrictions.manage`, `guest_documents.manage`, `corporate_accounts.manage`, `addons.manage` | no route matches the capability at all; `availability.view` is named nowhere in the codebase |

**The options, reported — none implemented, because this is a product decision.**

1. **Remove them from the catalogue.** Truthful immediately, and the code favours it: the catalogue is
   config (`config/authorization.php`) and `OrganizationRoleProvisioner` re-provisions from it, so removal
   is a data change, not a migration. ⚠️ Cost: any org that customised a role loses that row's history.
2. **Wire them** — make each the real gate for its write. Correct, and the largest: it means a finer gate
   *replacing* a coarser one on live routes, which is a security change needing its own mutation proof.
3. **Label them** in the matrix as governed by a broader permission. Cheapest, and the weakest: it leaves
   a switch that does nothing and explains why, which is the shape this project keeps removing.

⚠️ **The surrounding code favours (1) for the seven with no action at all** — a permission for a capability
the product does not expose is not granularity, it is a phantom — **and (2) for the three with a coarser
gate**, where the finer permission is the one an administrator reasonably expects to bind.

---

### ⚠️⚠️ L12p — THE DOCUMENT ROOT **IS** THE APPLICATION ROOT, SO `.env` IS PROTECTED BY REWRITE RULES AND NOTHING ELSE

🔬 Measured 2026-09-21 (ACCESS 02) on the live host.

⚠️⚠️ **THE SHAPE OF IT.** `~/domains/vyrobel.com/public_html` is simultaneously the Apache document root and
the directory holding `.env` (0600), the `artisan` shim, `storage/` (logs **and** `storage/framework/down`,
which carries the maintenance bypass secret) and the `current` pointer. There is no `public/`-only docroot.
⚠️ **Every secret this system owns therefore sits inside the served tree**, including `APP_KEY` — 📄 the only
copy of which exists here and in `.env` alone, and without which every encrypted guest record is permanently
unreadable.

⚠️⚠️ **WHAT ACTUALLY PROTECTS THEM — AND IT IS NOT WHAT THE FILE LOOKS LIKE IT IS.** The load-bearing line is
the rewrite, not the deny-list:

```apache
RewriteCond %{REQUEST_URI} !^/current/public
RewriteRule ^(.*)$ /current/public/$1 [L]
```

Every request is re-rooted under `current/public/`, where none of those files live, so `/​.env` resolves to
`current/public/.env` — which does not exist — and falls through to Laravel. ⚠️ The four `RedirectMatch`
lines are **defence in depth on top of that**, not the wall itself.

⚠️⚠️ **AND ONE RULE IN THAT FILE IS INERT, WHICH NOBODY WOULD GUESS BY READING IT.** `<FilesMatch "^\.">
Require all denied` does **nothing**: mod_rewrite (fixup) re-maps the request to `index.php` before the
file-level section is evaluated against a filename. 🔬 Proven by differential — `/current/.gitignore` and
`/current/.gitattributes` return **503 from Laravel**, not 403. ⚠️ A reader would reasonably believe dotfiles
are denied by this site's own configuration. They are not.

⚠️ **`.env` AND `.git` ARE REFUSED BY A *HOST-LEVEL* RULE THIS PROJECT DOES NOT OWN.** 🔬 `/.env`,
`/current/.env`, `/.git/config` all return **403 with a generic edge error page** (787 bytes), distinct from
both Laravel's 503 and the site's own 404. That refusal is Hostinger's, configured outside the repository,
and it can change without anything here going red.

⚠️⚠️ **TWO NAMESPACE COLLISIONS CAME OUT OF THE SAME LINE.** `RedirectMatch 404 ^/(app|…|storage|…)` was
written to block Laravel's *source directories*, but two of those names are also the product's own *URL*
namespaces:
- **`/app`** — the entire operator back office. 🔬 It hard-404'd all 55+ routes at Apache, before PHP.
  ✅ **FIXED 2026-09-21**: `app|` removed from the top-level rule only. `public_html/app` does not exist, and
  the release's real `app/` stays blocked by the `^/current/(app|…)` rule below it. 🔬 Battery unchanged in
  51 of 53 cells; the two that moved went from *Apache refuses* to *Laravel refuses*.
- **`/storage`** — the public disk's URL was Laravel's default `APP_URL.'/storage'`, so **all public media
  404'd**. ⚠️ `public_html/storage` is a real directory holding the logs and the bypass secret, so the path was
  not — and still is not — unblocked. ⚠️ **It failed exactly as predicted, SILENTLY:** 🔬 2026-09-30 the founder
  reported "when I add photo, the preview is not available"; five room photos sat on disk while each address
  answered 404 (measured from outside, cache-busted). ✅ **WORKED AROUND 2026-09-30 ON THE APP SIDE, the server
  rule untouched:** the public disk now answers at **`/media`** (`config/filesystems.php`), served by
  `PublicMediaController` — registered outside the `web` group (no session, no cookie), images only (extension
  AND the file's own bytes), only from the directories the app writes photos, logos and avatars into. Message
  attachments are deliberately not public at all: by his ruling (DEC-m, 2026-09-30) they live on the private disk and
  open only for the people in that conversation, signed in (`MessageAttachmentController`). `PublicMediaTest` fails if a new
  upload directory is on neither list. ⚠️ The docroot move below is still the real fix for the REST of this
  class (the inert `FilesMatch`, the host-rule dependence).

⛔ **FOUNDER STEP, AND IT IS THE REAL FIX:** establish whether hPanel can point this domain's document root at
a **subdirectory** (`public_html/current/public`). ⚠️ **This could not be determined from the server** — the
vhost is not readable and all eight domains on the account follow the fixed `domains/<d>/public_html`
convention. If it can be moved, the whole class disappears: both collisions, the inert `FilesMatch`, and the
dependence on a host rule, all at once. If it cannot, **this limit is permanent** and everyone who touches
this host must know it.

✅ **THE STANDING DETECTOR IS `ops/exposure-battery.sh`** — 53 probes across every URL shape that could reach
a release's source, judged on **status AND body content**, exit 1 on exposure and **exit 2 on a probe it
could not measure**. ⚠️ 🔬 That third state was bought the same day: a transient `curl` failure made an
earlier version report an exposure while the wall was intact, and *a battery that cries wolf on a blip gets
re-run until it goes green* — the exact habit it exists to prevent. ⚠️ Prove it still fires with
`--prove-red` before trusting a green.

---

## L13 — ✅ CLOSED 2026-09-23 · every `/api/owner/*` returned 500 on a mode-775 key file

🔬 Measured 2026-09-23 (PUBLIC 01), the first time these routes were reachable from the public internet:

```
GET https://vyrobel.com/api/owner/bookings   -> 500  {"message":"Server Error"}
GET https://vyrobel.com/api/owner/statements -> 500
GET https://vyrobel.com/api/owner/units      -> 500
```

**The cause, from `storage/logs/laravel-2026-09-23.log`:**

```
production.ERROR: Key file ".../releases/rel_F/storage/oauth-public.key" permissions are not
correct, recommend changing to 600 or 660 instead of 775
```

League's `CryptKey` refuses a key file that is group- or world-writable, and `PassportServiceProvider::makeGuard()`
throws before the `auth:api` guard can even reject the caller.

⚠️ **IT FAILS CLOSED AND LEAKS NOTHING.** The body is the generic `{"message":"Server Error"}` — `APP_DEBUG`
is false, no path, no stack. No token is issued, no data is returned, and the OAuth surface has no registered
third-party clients yet, so nothing that works today is broken by it.

### ✅ CLOSED — the fix ran, and was proven by the RESULT

🔬 2026-09-23, SHADOW 01. `chmod 600 storage/oauth-public.key`. **Proven by 500 → 401
`{"message":"Unauthenticated."}` on all three routes** — `auth:api` now runs and refuses. The key's
content digest was unchanged, so nothing but the mode moved.

⚠️ **The prerequisite was measured FIRST, and it mattered:** the web process runs as `u545270279`
(proven from the ownership of session, log and cache files written by live requests), so `600` is
readable by the app. ⚠️⚠️ **Had it run as anything else, 600 would have produced the IDENTICAL 500 —
a "fix" into the same failure, indistinguishable from the bug.**

⚠️ **A DEPLOY CANNOT UNDO IT.** 🔬 `storage/` is a SYMLINK to `public_html/storage`, shared by all five
releases — one copy, not one per release. No pipeline change is needed.

⚠️ Neither key is tracked in git, has ever been in history, or is missed by `.gitignore` — measured.

---

**(the original entry follows, kept as written)**

**THE FIX WAS ONE COMMAND**, and at the time of writing it had NOT been run — a permission change to a cryptographic key on live
production was outside the pass that found it:

```
chmod 600 ~/domains/vyrobel.com/public_html/current/storage/oauth-private.key
chmod 600 ~/domains/vyrobel.com/public_html/current/storage/oauth-public.key
```

⚠️ `oauth-private.key` is already `600` and safe. It is the **public** key at `775` that trips the check —
🔬 note the irony: a public key being world-readable is harmless, and the guard is refusing it anyway.

⚠️⚠️ **AND THE REAL LESSON IS WHERE THIS WAS FOUND.** These routes existed for weeks behind maintenance and
then a lock, and **every instrument reported them healthy** because a layer in front answered first. It took
removing both layers to see a 500 that had presumably been there since the keys were generated on 2026-07-29.
⚠️ A route nothing can reach is a route nothing has tested.


---

## ✅ L14 — FIXED 2026-09-24 (ADMIN 01 · Phase 1) · setting `OPS_HEARTBEAT_PING_URL` in `.env` did not arm the alarm

### ✅ THE FIX, AND THE PROOF — BY BEHAVIOUR ON A CACHED CONFIG, NOT BY THE CODE READING RIGHT

The key moved into `config/ops.php` (`'heartbeat_ping_url' => env('OPS_HEARTBEAT_PING_URL')`) and
`routes/console.php` now reads `config('ops.heartbeat_ping_url')`. 🔬 **Proven A/B on 2026-09-24**, locally, with a
throwaway HTTP listener that was first shown to record a hit (the instrument had to be able to see one):

| run | code | value came from | config | the listener saw |
|---|---|---|---|---|
| A | the fix — `config()` | a line added to `.env` | **cached** (`config:cache`) | `GET /ping-AFTER-fix` ✅ |
| B · control | the OLD line — `env()` | the same `.env` line | **cached** | **nothing** — the defect, reproduced |

⚠️ The value was never supplied through the shell environment (checked: the shell had no such variable) — that is
the exact route that produced a false "it works" once before. Everything was restored afterwards: the fix in place,
the original `.env`, no cached config, the listener stopped.
⚠️ The config inventory's calibration FLIPPED with the fix: it used to assert the heartbeat URL was *not* a config
key; it now asserts it *is* one, and the tokenised scan finds **0** stray `env()` sites.
⚠️⚠️ **STILL THE FOUNDER'S STEP:** production has no ping URL configured, so the switch is still unarmed — now for
the right reason. The health board says so, amber. Setting it needs the value in `.env` **and** a config rebuild
(every deploy does one).

### The original finding (2026-09-23), kept as written

🔬 Measured 2026-09-23 (SHADOW 01), by the new `ops/config-inventory.php`, whose calibration **failed
first and was right to** — it asserted the heartbeat URL was a config key, and it is not.

`routes/console.php:116` reads the value with a raw `env()`:

```php
->pingOnSuccessIf((bool) env('OPS_HEARTBEAT_PING_URL'), (string) env('OPS_HEARTBEAT_PING_URL', ''));
```

⚠️⚠️ **PRODUCTION CACHES ITS CONFIG, AND LARAVEL DOES NOT LOAD `.env` WHEN THE CONFIG IS CACHED.**
🔬 Proven on the host: `configurationIsCached() = YES`, and `env('APP_NAME')` resolves to **NULL** even
though `APP_NAME` is a line in `.env`.

**So the ping is never armed, whatever the founder puts in `.env`.** The dead-man's-switch that exists to
notice silence stays silent, and nothing anywhere indicates it.

⚠️ **An earlier measurement of mine appeared to disprove this and was wrong:** it set the variable in the
*shell* environment, where `getenv()` sees it regardless of whether `.env` was ever read. The cron has no
such shell variable. ⚠️ **A test that supplies the value by a route the real caller does not use proves
nothing about the real caller.**

**BLAST RADIUS IS EXACTLY ONE FEATURE.** 🔬 The tokenised scan finds **2 stray `env()` sites, both on that
single line**. Every other key is read through `config()` and is unaffected.

⚠️ **THE FIX IS APPLICATION CODE** — move the key into a `config/` file and read `config(...)` — so it was
reported and deliberately not done: SHADOW 01 was scoped to configuration and permissions only.

---

## ✅ L15 — CLOSED 2026-09-25 (TONIGHT 01) · NOT A DEFECT: the "777" was each release's SYMLINK, not a file

🔬 **Measured 2026-09-25.** Every `releases/rel_*/.env` (25 of 25) is a **symbolic link** to the ONE shared file —
`.env → ../../public_html/.env` — and **no release holds a copy** (0 regular `.env` files in any release). `stat -c %a`
on a symlink reports the LINK's own mode, which is **always 777 and ignored by Linux**; what a reader reaches is the
target: `stat -L` → **600**, owner `u545270279`. The oldest link (rel_B) was created 2026-09-20 18:44 UTC — before this
entry was measured — so the "777" below was the link then too. ⚠️ The `chmod 600 releases/*/.env` it proposed would
have followed the links to a file that was already 600: a no-op.
⚠️ **The web process runs as that owner** — 🔬 the three cache files a live sign-up request wrote (19:17 UTC) are
owned by `u545270279` — so 600 is readable by the app (the L13 prerequisite, re-measured, not assumed).
⚠️ **And a deploy now RE-DOES it:** `ops/release.sh stage` and `recache` refuse a release whose `.env` is not the
link, and re-apply 600 to the shared file on every deploy — because a hand edit in hPanel's file editor can rewrite
it with a wider mode. 🔬 Proven on `rel_Z`: `.env → ../../public_html/.env · shared file mode 600`.
⚠️ **The real finding sat BESIDE it:** 9 old releases (rel_B, D–K) kept `bootstrap/cache/config.php` — a compiled
copy of `.env` — at **644**, from before `stage` set 600. All 24 are 600 now, digests unchanged.

### The original entry (2026-09-23), kept as written

🔬 Measured 2026-09-23: all five `releases/*/.env` are **777** — world-readable *and* world-writable.
(`public_html/.env` is correctly 600.)

⚠️ **AND THE ALARM IS SMALLER THAN IT LOOKS, BECAUSE THE TRAVERSAL CHAIN CONTAINS IT.**
🔬 `/home/u545270279` is mode **710**, owner `u545270279:apache`. The world has **no execute bit**, so no
other account on this shared host can traverse into the home directory at all. The group is `apache`,
which is how PHP reaches it.

**So this is sloppiness, not exposure** — but it is one panel action or support change away from being
exposure, and it costs one command to remove:

```
chmod 600 ~/domains/vyrobel.com/releases/*/.env
```

⚠️ **NOT DONE.** It is not *proven broken* by measurement — the app works — and SHADOW 01's rule was to fix
only what measurement proved broken and measurement could prove fixed. It is the founder's call.

⚠️ Also 775 under `storage/`: several `.gitignore` files and one old log. Harmless, same containment.

---

## ✅ L16 — CLOSED 2026-09-25 (SESSIONS 01, `rel_W @ 3cd72c26`) — WAS: ON PRODUCTION, "SIGN OUT OTHER SESSIONS" AND "A PASSWORD RESET ENDS OTHER SESSIONS" DID NOTHING — AND THE AUDIT ROW SAID THEY DID

🔬 **CLOSED BY MEASUREMENT, LIVE, BOTH WAYS.** Before the fix (rel_V, `file` driver), two real sessions of one probe
account: after A changed the password, B still got **200**; after A pressed "sign out other sessions", B still got
**200**; `sessions` held **0** rows. After (rel_W, `database` driver + the fixes below), the same script: B → **302
/login** in both, A stays signed in (200), the probe's rows **2 → 1** each time; a password RESET (token from the app's
own broker, on the host, for the probe only) signed B out too. The driver was proven by BEHAVIOUR — a fresh sign-up
after the swap created its `sessions` row — never by reading the file.

What changed (DECISIONS.md, "SESSIONS 01"): `SESSION_DRIVER=database` on production (config cache rebuilt at staging —
L14) · Laravel's AuthenticateSession on every web route (`EndSessionsOnPasswordChange`) so a password change or reset
is fatal to every OTHER session on every guard WHATEVER THE DRIVER · one shared "sign out others"
(`ActiveSessions::signOutOthers`: rows + `logoutOtherDevices` re-hash + remember token) · guest password change/reset
now ends other sessions (it ended none, on any driver) · admin force sign-out on yourself ends only your OTHER
sessions · a break-glass session is filed under the admin, never the viewed hotel user.
⚠️ Rollback (`ops/release.sh rollback rel_V`) returns sessions to FILES and signs everyone out a second time.
⚠️ Owner API tokens are NOT revoked by a password reset — reported to the founder, his ruling.

---

**THE RECORD AS IT STOOD (2026-09-24), kept as history:**

🔬 **Measured 2026-09-24 (ADMIN 01 · Phase 1), on the host.** Production's `session.driver` is **`file`**:
**163 session files** on disk (3 touched in the last two hours) and **0 rows** in the `sessions` table.

Every tenant-side session feature reads or deletes rows in that table, through ONE shared helper,
`App\Support\Security\ActiveSessions`:

| surface | what it does on production |
|---|---|
| owner Security page — "active sessions" list (`OwnerSecurity`) | always empty |
| operator Profile / Security — session list | always empty |
| owner "Sign out all other sessions" (`OwnerSecurityController`) | deletes nothing, then tells the owner **"You have no other active sessions."** — a false statement, not just an empty list |
| ⚠️⚠️ `User::changePasswordTo()` — **password change AND password reset** | `ActiveSessions::terminateFor()` deletes nothing, so a session an attacker already holds **survives the reset** — and the `user.password_changed` audit row records **`other_sessions_terminated: true`** |

⚠️⚠️ The last row is the serious one. The code comment on that line says the termination exists because "a password
reset did not actually lock them out, which is the entire reason someone resets a compromised password" — and on this
host that defect is live again, with an audit trail that says otherwise. The remember-me token IS rotated, so
"remember me" cookies do die; live sessions do not.

⚠️ **RECORDED, DELIBERATELY NOT FIXED** — the brief ruled "measure it, record it, do not fix it here". Two directions,
for the founder:
  · switch production to `SESSION_DRIVER=database` (the table and its 30-day retention window already exist) — a
    config change plus a config rebuild; every current session is signed out once, at the switch;
  · or give tenant sessions the same app-level tracking the platform console now has (below).
⚠️ **The platform console is NOT affected.** Admin sessions are tracked by the application itself
(`platform_admin_sessions`, keyed by a random value in the session data), so force sign-out and revocation are
enforced on the next request whatever the session driver.

---

## ⚠️ L17 — THE ADMIN AUDIT LEDGER IS APPEND-ONLY IN THE APPLICATION, NOT YET IN THE DATABASE

Since ADMIN 01 · Phase 1, `platform_admin_actions` refuses UPDATE / DELETE / TRUNCATE / REPLACE twice: model events,
and a check on every statement this application sends (`PlatformWriteGuard`, via the connection's `beforeExecuting`).
⚠️ Neither can see a client that is not this application — the MySQL/MariaDB CLI, phpMyAdmin in hPanel, another app.

⚠️ **A tighter database GRANT is not practical here**, and saying so is the point: MySQL table-level `REVOKE` cannot
subtract from a database-level grant, and the application's user holds database-level privileges (hPanel manages
it). Restricting it would mean replacing that with per-table grants for ~150 tables, redone at every migration.
**PROPOSED, NOT DONE:** a `BEFORE UPDATE` and a `BEFORE DELETE` trigger on `platform_admin_actions` that `SIGNAL` an
error. That refuses every client, including the CLI; someone with the privilege could still drop the trigger, so it
raises the bar rather than removing it. It changes the database, so it is the founder's call.

---

## ⚠️ L18 — THE PUBLISHED `sales@vyrobel.com` HAS NO MAILBOX: MAIL SENT TO IT IS REJECTED — PRIVACY REQUESTS INCLUDED

🔬 **Measured 2026-09-25 (TONIGHT 01).** The public site prints `sales@vyrobel.com` 12 times in 8 templates — as the
**privacy page's contact for "any privacy question or to exercise a right"**, the **terms' support line** ("Support is
by email at sales@vyrobel.com"), the contact page and the contact form's fallback, pricing, and three customer
stories. The domain has ONE mailbox, `contact@vyrobel.com`, and no alias, forwarder or catch-all. hPanel's delivery
log (Emails → Email Logs → Delivery, inbound) shows mail to `sales@` and to `info@` **Rejected** — 9 since
2026-08-30, the latest on 2026-09-25. Most look like spam; ⚠️ **but any real enquiry or data-subject request sent
to the published address is refused, and the sender is told so by their own provider, not by us.**

✅ **RULED 2026-09-26 (the founder):** `sales@vyrobel.com` STAYS on the privacy page and the terms; everywhere else — the
contact page and form, pricing, the three stories — now prints **`contact@vyrobel.com`**, the mailbox that exists (7
lines in 6 templates). ⚠️ **So the privacy page's rights contact and the terms' support line still point at an address
that REJECTS mail until the alias below exists.**

⚠️ **THE FOUNDER'S STEP — one minute, no code:** hPanel → Emails → **Email Alias** → `sales@vyrobel.com` delivering to
`contact@vyrobel.com`. (The other direction — printing `contact@` instead — is a copy change across 8 templates and
the legal pages; the alias keeps every published address working.) ⚠️ Not done by the session: it changes his mail
setup, and nothing briefed it.

## ✅ L19 — CLOSED BY ADMIN 06 (2026-09-27): a deletion now sees an open Stripe Checkout page, and refuses

✅ **CLOSED.** Every Checkout page the app opens (a subscription, or a guest's card payment) is now RECORDED in
`stripe_checkout_sessions` with Stripe's own `expires_at` and `livemode`. It is closed by `checkout.session.completed` or
`.expired` on either webhook. `OrganizationDeletion::refusal()` refuses while one is open. A page whose expiry Stripe did
not report counts as open for Stripe's longest lifetime, 24 hours (📖 Stripe's docs, read 2026-09-27: 30 minutes to 24
hours, 24 by default). Proved by `Admin06DeleteMoneyAndCountTest` › *B19 — …* and mutations `A06-M7`…`M11`. The pin that
said "a checkout writes nothing" turned red as built, and was retired. **One of the three items "beside it" is closed
too:** (1) a replaced brand logo is now removed once the new one is saved (`SettingsActionController`, test *a REPLACED
brand logo…*; production held 0 logos when measured); (2) and (3) are unchanged, recorded below. *(Corrected
2026-09-29 by ADMIN 06's second review, D-R2-12: this line first said "two of the three", and only one is.)*
**Two gaps the second review found are closed too (2026-09-29):** a **LIVE** page past its expiry that Stripe has not
reported on still refuses — it may have been paid in its last minute, with the report still in Stripe's retries
(test *R2-2*, mutations `A06-M57`/`M58`); and the **guest's** half — a card-payment page recorded, refusing, and closed by
its Connect event — now has its own test and mutations (test *R2-4*, `A06-M67`…`M69`). The text that follows is the
record as found.

### (as found, 2026-09-26) L19 — DELETING AN ORGANISATION CANNOT SEE AN OPEN STRIPE CHECKOUT PAGE — DORMANT TODAY, A PREREQUISITE OF THE STRIPE FLIP

🔬 **Found 2026-09-26 (ADMIN 05, by the data-safety review; verified by reading `BillingService::startCheckout()`).**
Starting a subscription checkout writes NOTHING to the database — the Checkout Session lives only at Stripe (up to 24
hours). So `OrganizationDeletion::refusal()` cannot see one. The case: a founder opens the payment page; the organisation
is then suspended and deleted; the founder completes the payment → Stripe holds a LIVE subscription for an organisation
that no longer exists, and `checkout.session.completed` resolves to nothing (recorded unresolved, 202) — Stripe keeps
charging until someone cancels it by hand. (The refusal's `incomplete` clause is dead code: nothing writes that status.)

🔬 **DORMANT on production — measured 2026-09-26, booleans only:** `APP_ENV=production` with the **fake Stripe client
ON**, **no Stripe key**, and all four plans on **placeholder** prices (`price_fake_…`). No real Checkout Session can
exist, so there is nothing for a deletion to miss today.

⛔ **IT MUST BE CLOSED BEFORE THE STRIPE FLIP (GO-LIVE B19).** The shape: persist the open session's id and expiry when
checkout starts, and refuse deletion while one is unexpired (or expire it through the API at deletion). ⚠️ Not built
now: it needs a schema change and a new Stripe call on the money path, neither briefed in this pass. 🔒 Pinned by
`Admin05DeleteOrganisationTest` › *L19 — starting a subscription checkout writes NOTHING*: whoever closes this turns it red.

**Beside it, recorded, not fixed:** (1) a REPLACED brand logo's old file is never deleted (`SettingsActionController`
stores the new one and forgets the old path — pre-existing), so a deletion cannot find it; ✅ nothing personal — logos are
public brand images. (2) Queued work for a deleted organisation is not removed: a job that loads its records fails
harmlessly; an owner's already-queued "email me my statement" still arrives — their own data, to their own inbox.
(3) A deleted organisation's slug and custom domain become free for a new organisation — an old guest link would then
reach the new one. The console ledger keeps the old slug.

## ⛔ L20 — A GUEST'S CARD PAYMENT PAGE CANNOT OPEN WITH REAL STRIPE — PRE-EXISTING, FOUND BY THE ADMIN 06 REVIEW, DORMANT TODAY

📖 **Found 2026-09-27 by the ADMIN 06 adversarial review. Read from Stripe's documentation and the code; not run
against real Stripe.** The SDK (stripe-php v20.3.0) pins API version `2026-06-24.dahlia`, and the app sets no other.
Since API version 2022-08-01, a Checkout Session in `payment` mode is created **without** a PaymentIntent: it is made
when the guest confirms. 📖 Stripe's own example session object reads `"mode": "payment"`, `"status": "open"`,
`"payment_intent": null`. Both guest card paths require one at creation:
- `BookingEngineController::reserve()` sends the guest to the confirmation page without ever opening the payment page.
- `GuestFolioPaymentController::pay()` answers "Stripe Checkout returned no PaymentIntent".

Settlement is keyed on the PaymentIntent (`payments.gateway_ref = pi_…`) too, so even a page that did open would never
settle. **Why every test passes:** `FakeStripeClient::createPaymentCheckout` returns a PaymentIntent at creation, which
real Stripe no longer does.

🔬 **DORMANT on production:** the fake Stripe client is on and there is no Stripe key (measured 2026-09-27).
⛔ **Must be settled before the Stripe flip (GO-LIVE B20).** First, confirm it with **one test-mode session against real
Stripe**. Then build the pending payment on the session id, attach the PaymentIntent and settle on
`checkout.session.completed` (Connect plane), and make the fake return `null` like Stripe does. Not built in ADMIN 06: it
is a money-path change the brief did not cover. ⚠️ **Whoever fixes it must keep recording the page**
(`CheckoutSessions::record()`), or B19 re-opens for guest payments. ADMIN 06's deletion is not exposed meanwhile, because
a guest page that never opens cannot be paid.

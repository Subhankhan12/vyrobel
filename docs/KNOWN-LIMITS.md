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

⚠️ **NOT FIXED IN THIS PASS — DELIBERATELY.** Whether `ops/deploy.sh` should be made to work here (a git
checkout on production? a prebuilt-asset step? the 8.4 binary pinned?) or retired in favour of the documented
manual procedure is a **founder decision about how this product is released**, not a bug to patch mid-audit.
⚠️⚠️ **Until it is taken, `ops/deploy.sh` must not be run against this host.**

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

# STATE — where Vyrobel stands right now

**Snapshot: 2026-09-29, live state read 13:41–13:55 Baku; updated by each pass since — last by the NEW RESERVATION SECOND pass (2026-10-04)** (first written 2026-09-27 by MEMORY 01).
Labels: **V** = Verified (how and when) · **R** = Reported (from an earlier record, not re-checked; source given) ·
**F** = Founder said. ⚠️ Memory is a claim, not a fact. Re-read anything you are about to rely on. Sources at the bottom.

## Live
- **Live release `rel_AU` → commit `7819226d`** (the Calendar New reservation pass: the dialog rebuilt — on top of the New
  reservation pass, the owners module pass, the payouts pass, the Owners rebuild, the Rates rebuild with DEC-l and its two live
  fixes, DEC-m, photos at `/media`, DEC-k, the Units, Maintenance and Guests rebuilds, Portfolio Open + fields, the Portfolio
  remove and ADMIN 06). **V:** `ops/release.sh swap` output (`.release = 7819226d…`), 2026-10-04 18:45 Baku. Rollback targets:
  `rel_AT @ 282109e4` (New reservation), `rel_AS @ 4751d2c9` (the owners module), `rel_AR @ d90f40ca` (payouts), `rel_AQ @ 5d391dbe` (Owners), `rel_AP @ 9b9b80c8` (Rates with its two fixes), `rel_AO @ cac9e677` (Rates, before the two fixes), `rel_AN @ 761cf2dd` (DEC-m),
  `rel_AM @ 9e9b1193` (photos), `rel_AL @ 8cda8c7f` (DEC-k), `rel_AK @ a4e2b6e5` (Units), `rel_AJ @ a3604b9c` (Maintenance), `rel_AI @ 9f704a3a` (Guests), `rel_AH @ 3b012e93` (Portfolio Open +
  fields), `rel_AG @ e4117aeb` (Portfolio remove), `rel_AF @ 4c17edd2` (ADMIN 06) — `ops/release.sh rollback rel_<X>`.
  ⚠️ A rollback to `rel_AL` or earlier puts photo addresses back on `/storage` — every photo 404s again.
- **GitHub `origin/main`** carries `7819226d` and the docs commit that records this deploy (**V** `git ls-remote origin`).
- **Live code vs git, byte for byte: 1,441 of 1,441 files in the live release match git at `7819226d`, 0 differ** (the other
  1,218 git files — tests, docs, tools, screenshots — are never deployed). **V** 2026-10-04, after the 18:45 Baku swap, sha256
  manifest checked inside the live release.
- **`PAYOUT_ENGINE_ENABLED` is OFF on production** (**V** 2026-10-03, the read-only preflight from `rel_AS`: `false`;
  production holds 0 owners, 0 management agreements, 0 unit requests, 0 payouts). D-PAYRUN-TENANT is fixed live since `rel_AR`;
  D-PAYRUN-BUTTON since `rel_AS`.
- **ADMIN 06's refusals are LIVE** — the first of the three conditions before any live Stripe key (`OPEN.md` F5).
- **The memory is not on the host:** `docs/` and `CLAUDE.md` are not deployed.

## Numbers
| What | Value | Source |
|---|---|---|
| Suite (the NEW RESERVATION SECOND commit) | **4,300/4,300 on MySQL 8.4.10** (tree `b0fdbb9d224d3508` = the code plus these docs, before these figures were written; 2026-10-04 21:09–21:28 Baku) · **4,300/4,300 on MariaDB 11.8.9** (the same tree, 21:51–22:08 Baku) · 31,593 assertions · 0 failed, 0 errors, 0 skipped (read from each run's JUnit log). ⚠️ An earlier run on the tree before the last fix (20:50–21:08) had 1 failure — `steps()` public on a Livewire component, caught by the boundary guard — fixed, then the whole suite re-run. The CALENDAR commit before it (`7819226d`): 4,280/4,280 on both engines | **V** run outputs and JUnit logs 2026-10-04. |
| Mutations | **699/699 landed and caught across every set**, 0 not caught, 0 did not land; baseline 398/398 green (`admin-06` = 87, `admin-05` = 49, `rates-page` = 49, `owner-module` = 48, `owners-page` = 41, `new-reservation` = 34, `units-list` = 33, `calendar-new-reservation` = 33, `admin-p1` = 31, `new-reservation-bookable` = 29, `payout-run-scope` = 26, `maintenance-board` = 26, `guests-list` = 24, `message-attachments` = 7, `public-media` = 6, and 12 older sets). ⚠️ `new-reservation-bookable` caught 28/29 at first after the review's additions (NB-M11 — a test whose "no false claim" check ran only where the sentence could not appear; sharpened); four `new-reservation` needles repointed | **V** harness output 2026-10-04 21:29–21:50 Baku, tree `b0fdbb9d224d3508` |
| Money gate FULL | `929 / 10928000 / 0 / 42 / 16 / 0` · digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297` · `mode=FULL` ✅ | **V** `ops/money-as-seeded.sh` output 2026-10-04 22:08 Baku (the NEW RESERVATION SECOND pass; reseeded 22:08). Tenant one only; local only. |
| PHPStan L5 · Pint | 0 errors · clean (both on the whole codebase) | **V** 2026-10-04 (the NEW RESERVATION SECOND pass, before the suite runs) |
| Public checks / exposure battery | **12 PASS · 0 FAIL · 0 could not measure** (`SERVED=0`) on `rel_AU`, 2026-10-04, run straight after the 18:45 Baku swap (and on `rel_AT` at 04:41 that day, on `rel_AS` at 21:32 on 2026-10-03, on `rel_AR` at 17:26 that day, on `rel_AQ` on 2026-10-01 at 19:08, on `rel_AP` at 03:21 that day, on `rel_AO` at 02:10, on `rel_AN` at 21:59 on 2026-09-30, `rel_AM` at 20:42, `rel_AL` at 19:13, `rel_AK` at 17:29 and `rel_AJ` at 12:22 the same day; on `rel_AI`, `rel_AH`, `rel_AG`, `rel_AF` on 2026-09-29); drift check passes, self-test 3/3 (V 2026-10-04, after the 18:45 Baku swap) | **V** `ops/public-checks.sh` 2026-10-01 |
| Customers | **0.** 15 organisations, all internal | **V** production read, 2026-09-29 |

## Switches on production (V: read-only production read from `rel_AF`, 2026-09-29 13:5x Baku; yes/no only, never a value)
- **Mail:** runtime mailer **`log`**, so every email is discarded. `MAIL_PASSWORD` set: **no**. Mail host is Hostinger:
  yes. Postmark token set: no.
- **Stripe:** **fake client ON**. `STRIPE_SECRET` set: no. Both webhook secrets set: no. Live: **no**.
- **Google and X sign-in:** client id and secret set, for both. All five switches **ON**: `signup`, `signup.google`,
  `signup.twitter`, `signin.google`, `signin.twitter`.
- **Heartbeat alarm:** `OPS_HEARTBEAT_PING_URL` set: **no**, so it is unarmed.
- **Break-glass:** enabled, 30 minutes. 0 sessions have ever been opened on production (V 2026-09-27).
- **Other settings:** maintenance mode off · config cached · queue `database` · sessions `database` · cache `file`.
- **Loose ends (V 2026-09-29, after the `rel_AF` deploy; errors re-read after `rel_AU`: 0 logged since its swap, V 2026-10-04, read after the 18:45 Baku swap):** 0 failed jobs · 0 errors logged since the swap · 0 suspended organisations · 0 break-glass sessions open · 1 admin, 0 invites
  pending · 0 announcements · 0 `/status` incidents.

## Organisations on production (V: all 15 `active`, all internal)
⛔ **Never touch:** `Vyrobel Test Workspace` · `Vyrobel Internal — TEST TENANT (not a customer)`.
Probes, one line each, all "(not a customer)": `PUBLIC-01 PROBE` · `ADMIN-P1` to `ADMIN-P5 PROBE` · `ADMIN03-W1 PROBE` ·
`ADMIN-03B PROBE` · `SESSIONS-01 PROBE` · `SESSIONS-01-FD PROBE` · `TONIGHT01 PROBE` · `TONIGHT01B PROBE` · `PW-SIGNUP PROBE`.

## ⚠️ IN FLIGHT — started, not finished
0. **Portfolio remove (2026-09-29)** — ✅ LIVE on `rel_AG` (V). Left: a signed-in look on production (his sign-in); his calls
   DEC-h (F-58) and DEC-i (Edit scope).
0b. **Portfolio Open + dialog fields (2026-09-29)** — ✅ LIVE on `rel_AH` (V). Left: a signed-in look on production.
0c. **Guests rebuilt (2026-09-29)** — centred dialogs, a sortable table, row Edit and Erase (`HISTORY.md`, "GUESTS").
    ✅ LIVE on `rel_AI` (V 2026-09-29 22:02 Baku). Left: a signed-in look on production; his calls A8 (the x-show sweep) and A9 (the other see-through
    dialogs), `OPEN.md`.
0d. **Maintenance rebuilt (2026-09-29/30)** — the board like Guests (centred dialogs, a drawer on the right, a sortable table,
    row Edit and Cancel) and the work order page's two dialogs made real (`HISTORY.md`, "MAINTENANCE"). ✅ LIVE on `rel_AJ`
    (V 2026-09-30 12:22 Baku). Left: a signed-in look on production; his calls A10 (the string `:style` sweep) and DEC-j.
0e. **Units rebuilt (2026-09-30)** — the add forms as aligned dialogs (his report: "Kind is down, its dropdown is small"),
    the Guests/Maintenance list, Pause / Resume, room types as cards, a setup guide, bulk pause and a real export
    (`HISTORY.md`, "UNITS"). ✅ LIVE on `rel_AK` (V 2026-09-30 17:29 Baku). **DEC-k ruled ("Fix this issue too") and
    fixed** — Add unit now checks the person's properties (`HISTORY.md`, "UNITS · DEC-k"); ✅ LIVE on `rel_AL` (V 2026-09-30
    19:13 Baku). Left: a signed-in look on production; his call DEC-l (the same check on the rate-plan writes).
    advisor-platform's dev server was stopped for the MariaDB run at his word and left stopped.
0f. **Photos show again (2026-09-30)** — his report: "when I add photo, the preview is not available". 🔬 Production refused
    every `/storage…` address by design (KNOWN-LIMITS L12p), so every uploaded image — room photos, logos, avatars,
    housekeeping and maintenance photos — answered 404 while its file sat on disk. The public disk now answers at `/media`
    (`PublicMediaController`; `HISTORY.md`, "PHOTOS"). ✅ LIVE on `rel_AM` (V 2026-09-30 20:42 Baku): 🔬 his five photos
    answer 200 `image/jpeg` at `/media` from outside, and his live storefront shows its two covers there. Left: a signed-in
    look at `/app/units` on production. **DEC-m ruled ("Fix this issue as well") and fixed:** owner message attachments
    open only for the people in that conversation, signed in — and the inbox now shows them (`HISTORY.md`, "ATTACHMENTS");
    ✅ LIVE on `rel_AN` (V 2026-09-30 21:59 Baku): 🔬 signed out, both attachment doors send you to `/login`; no public
    address serves one. Left: a signed-in look (his).
0g. **Rates rebuilt (2026-10-01)** — his request ("table design must be better, texts must look good … every single
    operation working … ship it live and verify it"); the 9-part plan approved, DEC-l included (`HISTORY.md`, "RATES").
    Every dialog centred, all fourteen nights, click or drag to edit, one cell design with a matching legend, bulk edit on its
    own dates, seasons refused while paused, a second plan that can be priced, refusals as alerts, every write back on the
    fortnight it started from, and every rate write inside the person's properties (DEC-l ruled and fixed). ✅ LIVE on
    `rel_AO` (V 2026-10-01 02:10 Baku). The live look is done (he signed in himself; look only): every dialog centred, the
    drag, the filter, 390 px, 0 console errors. It found two defects, fixed and ✅ LIVE on `rel_AP` (V 03:21 Baku) and
    re-checked there: no season form where no plan is on sale (a line says why), and 390 of 390 px at 390 with a season shown.
0h. **Owners rebuilt (2026-10-01)** — his request ("on Add owner page, modal design and buttons are mess … confirm with your
    password and its box is not well aligned … modify the entire design of this page … build a plan and execute it"); the
    7-part plan posted and executed (`HISTORY.md`, "OWNERS"). Add owner on the shared centred dialog with the password on its
    own row; the shared list; Invites waiting with Resend and Cancel; statuses that follow the payout run; a unit's owner never
    changed by an invite. ⚠️⚠️ **Two findings on the payout run, not fixed (his go — `OPEN.md` A11, DEC-n):** "Run payouts now"
    reaches every organisation's owners (🔬 verified in the test database; it runs only while `PAYOUT_ENGINE_ENABLED` is on),
    and only the automatic run reads a pause. ✅ LIVE on `rel_AQ` (V 2026-10-01 19:08 Baku). **The live look is done** (he
    signed in himself; look only, nothing sent): the page, the dialog centred at 720,450 with Cancel and Send invite both
    42 px on one line, focus on Name, Escape closes it; at 390 px the page is 390 of 390 px wide and the dialog fits with its
    buttons in view; no console message from the page. He was signed out and the automated browser closed.
0i. **Payouts (2026-10-02)** — his "Lets work on next steps"; the plan approved ("Yes, all of it") and **DEC-n ruled: a pause
    stops every payout** (`HISTORY.md`, "PAYOUTS"). A11 fixed ("Run payouts now" names its own organisation's owners; its
    run key carries the organisation); "Last run" shows only this organisation (another's counts were shown); the payout
    service refuses a paused owner on create, pay and retry, the engine never selects one, the payout run lists them apart;
    the "until ." wording; A12 (the Units export link). ✅ LIVE on `rel_AR` (V 2026-10-03 17:26 Baku). **The live look is
    done** (he signed in himself; look only, no payout button pressed): the payout run reads "No payout run yet here" with its
    honest empty states, the Owners page and the Units page render (the Units Export link carries the new behaviour), no
    console message from any page. He was signed out and the automated browser closed.
0j. **The owners module (2026-10-03)** — his request ("on Add owner page, modal design and buttons are mess … confirm with your
    password and its box is not well aligned … all the sort of work you have done in the previous pages … in this module …
    ship everything live"); the 5-part plan posted and executed (`HISTORY.md`, "OWNER MODULE"). The owner's page rebuilt
    (Remove owner with "Confirm it's you" on its own row, Keep / Remove at one height); four faults behind it fixed — the
    commission change (stacked agreements, a dropped fee), the portal-access field, the silent refusals; the payout run asks
    before every money write and shows Run payouts now disabled while the engine is off (D-PAYRUN-BUTTON); unit requests
    reviewed in dialogs, approving inside the person's properties (DEC-k); the Owners list's false unit hint corrected.
    **Asked of him: DEC-o (portal access from the owner's page), DEC-p (giving an existing owner units) and DEC-q (rate
    proposals and the property limit — found by the independent review, pre-existing).** ✅ LIVE on `rel_AS` (V 2026-10-03
    21:32 Baku). **The live look is done** (he signed in himself; look only, nothing pressed): Owners with Add owner centred at
    720,450, Cancel and Send invite both 42 px, Escape closes it; the payout run with Run payouts now disabled and its reason;
    unit requests' empty state and chips; no console message from those pages (the one entry was the CDN's first-load 403 on the
    sign-in page). Production holds no owner, so the owner's page itself was walked locally, not live. He was signed out and the
    automated browser closed.
0k. **New reservation (2026-10-04)** — his report ("it is not possible to do reservation … fix everything … ship everything
    live and then verify it"); the plan posted and executed (`HISTORY.md`, "NEW RESERVATION"). 🔬 Why (read-only on production):
    his workspace's first property has no rooms, the other has no price on any of the next 60 nights, and the wizard — with no
    property switcher — used the first and said "No units are free", or offered only a link that threw the booking away. Now: it
    asks which property, says when one has no rooms, lets `rates.manage` set the stay's price in place (the Rates write itself),
    keeps a refused booking (never a new guest's email or phone, #28) and reopens at the step to fix, checks each step, and shows
    the money as the folio charges it. Found and fixed: "Other" without a note was a 500; a refused booking left its new guest
    behind; the done screen showed other properties' bookings (the review). **Asked of him: DEC-r (an existing guest's email on a
    new guest) and a go on D-MONEY-COMMA ("150,50" is read as €15,050.00 by every money field but this page's).** ✅ LIVE on
    `rel_AT` (V 2026-10-04 04:41 Baku). 🔬 The preflight from the staged release (read-only): the gates and the view hold; his
    workspace's "Internal Test Property" has 0 rooms and "Villa Azure Lumière" 3 units in 2 room types with no price on the
    sample stays (both offered with the price box); the wizard's availability read 12 ms at most; his workspace holds 1 guest
    with no booking (counts cannot say from what; left as it is — protected). **The live look is done** (he signed in himself;
    look only, nothing written): the picker on "Internal Test Property" with "Internal Test Property has no rooms yet" and Go
    to Units; Villa Azure Lumière with "No price for these nights yet" and "Price a night · Set price" on both room types (the
    "350" and "Normal" plans named); Next answering "Pick a unit for these dates to continue."; 390 of 390 px at 390; no console
    message. The working property was switched back to "Internal Test Property" as he had it; he was signed out and the
    automated browser closed.
0l. **Calendar New reservation (2026-10-04)** — his report ("guest first name, last name, and their boxes are not well aligned"
    … "also do additional works the way you did for previous pages"); the plan posted and executed (`HISTORY.md`, "CALENDAR NEW
    RESERVATION"). Why (read from the code): the wireframe's one name box was split into two labelled names inside a grid sized
    for three pieces. Now: each label above its own box on the shared solid card; ⚠️ the guest's name out of the address (#28,
    D-CAL-GUEST-GET); rooms with no price or plan listed with the reason; the unit's own price; dates never in the past; a "+" on
    each empty night of a unit's row that opens the dialog with that unit picked. The review's 13 findings fixed. Recorded:
    D-RACK-START-TZ. ✅ LIVE on `rel_AU` (V 2026-10-04 18:45 Baku). 🔬 The preflight from the staged release (read-only): the
    gates and the four views hold; his workspace's "Internal Test Property" has 0 rooms, "Villa Azure Lumière" (Paris time) 3
    bookable units with 28 room-type nights to sell in the next 14, both room types without a price tonight; the dialog's reads
    10 ms. **The live look is done** (he signed in himself — the second time in the automated window; look only, nothing booked):
    "Internal Test Property has no rooms yet" with Go to Units; on Villa Azure Lumière 42 "+" links and today marked; a "+" opened
    the dialog with each label over its own box (First name 435/435, Last name 726/726, Check-in 435/435, Check-out 652/652 px at
    1440), focus on First name, both room types listed "No price for these nights yet" with the link, no Confirm; 390 of 390 px
    at 390; Escape closed it and dropped `new=1`; no console message. The working property was put back to "Internal Test
    Property"; he was signed out and the automated browser closed.
0m. **New reservation, second pass (2026-10-04)** — his report again ("it is not possible to do reservation … deeply analyze …
    more cool, advanced, minimal and operational"), and **from now on no GitHub push per task** (`RULES.md` Part 1). 🔬 Why (read-only
    on production): his working property has no rooms, and his other property never had a price — the €200 "Winter" season he
    saved on 28 Sep was never applied. Now: the no-rooms card offers the properties that can book; a saved season is applied from
    the booking through the Rates page's own disclosure; four steps; each night's price on the calendar; only true sentences
    (`HISTORY.md`, "NEW RESERVATION, SECOND PASS"). The review's 20 findings fixed. **Not yet live** — this line is updated by
    the deploy. ⚠️ His "Winter" season stays unapplied until HE applies it (his protected workspace: look only for the agent).
1. **PW-SIGNUP PROBE permanent delete** has **not started** — ADMIN 06 is live, so it is ready for him. **Not suspended**
   (V 2026-09-29, `status = active`).
   - Backup exists (V 2026-09-27): `vyrobel-db-before-delete-pw-signup-probe-20260926T190414Z.sql.gz`, 87,968 bytes, mode 600,
     in `~/domains/vyrobel.com/env-backups/` (folder 700), with the 26 September baselines beside it.
   - ⚠️ **Retake the backup and the before-baselines right before his click** — the 26 September ones predate ADMIN 06's
     migration. 🔬 The live preview now says **232 rows in 9 tables** (V 2026-09-29, preflight, read-only).
   - After-checks done: none.
2. **Email switch-over (TONIGHT 01 Part 2 / MAIL 02)** is waiting on `MAIL_PASSWORD` (V 2026-09-29: not set).
3. **`sales@` alias**: created in hPanel on 2026-09-26 (R), delivery **not yet proven**.
4. **The signed-in look at the new delete screen on production** (the ADMIN 05 signed-in pass, now with ADMIN 06's count):
   needs his sign-in.

## Waiting on the founder, in order
1. Change the exposed `contact@` password in hPanel (it was pasted into a chat), then put the new one in `.env` as
   `MAIL_PASSWORD`. This unblocks the email pass.
2. Copy `APP_KEY` into his password manager, off the server. Losing it makes every encrypted guest record unreadable.
3. ADMIN 06 is live: suspend, then delete, PW-SIGNUP PROBE with his password. The agent retakes the backup and
   baselines right before, and runs the before/after proof (`OPEN.md` A3).
4. KEYBOARD 01, at the keyboard (`briefs/KEYBOARD-01.md`). Its own rule is to run after TONIGHT 01 has closed.
5. Stripe live: keys, both webhook secrets, Connect verification. ⛔ Not before ADMIN 06 is live, B20 is settled, and
   payout confirmation is proven with one test-mode transfer (`OPEN.md` D-TRANSFER). ADMIN 06 is now live (V 2026-09-29).
6. The lawyer: the Article 28 agreements, who may hold customers' money, and how long money records must be kept.
7. The real tax rates (`tax:set-rate`, replacing the EXAMPLE rates).
8. Rulings DEC-a to DEC-i (`OPEN.md`). DEC-f decides whether the logo removal that shipped stays.
8b. Say go (or not) on A8, the x-show sweep of 22 elements on other screens (24 until the new reservation page's two went, 2026-10-04); A9, the other see-through dialogs; and A10,
    the string `:style` sweep of 10 elements. Rule on DEC-j (the property allow-list on the maintenance writes; DEC-k, the
    same on Add unit, was ruled and fixed 2026-09-30, and DEC-l, on every rate write, 2026-10-01). DEC-m (message
    attachments) was ruled and fixed 2026-09-30.
8c. ✅ Done 2026-10-02: A11 and A12 fixed, DEC-n ruled ("a pause stops every payout") and built. D-PAYRUN-BUTTON fixed
    2026-10-03 (the owners module pass).
8d. Rule on DEC-o (switching an owner's portal access from their page — and if so, does their team go too, and does someone
    who also works here keep their login?), DEC-p (giving an existing owner units later; today only their invite does) and
    DEC-q (should rate proposals check the person's properties, as every other rate and unit write does?).
8e. Rule on DEC-r (offer the existing guest when a new guest's email matches one) and say go (or not) on D-MONEY-COMMA (refuse
    "150,50"-style amounts in every money field, not only the booking wizard's). Smaller, recorded: D-STOREFRONT-ORPHAN-GUEST,
    D-GB-SYMBOL, D-RATE-DERIVED-PRIMARY, D-RACK-START-TZ (`OPEN.md`).
8f. Apply his "Winter" season (€200, Double room, 28 Sep–31 Oct) — from the booking page's "Apply Winter…" or the Rates page —
    so Villa Azure can take a booking; and say go (or not) on A13 (flag unapplied seasons on the Rates page).
9. Optional: one email to `sales@` to prove the alias.

**Which runs next.** ADMIN 06 is live, so nothing else can run without him: the email pass waits on `MAIL_PASSWORD`, the
PW-SIGNUP delete on his click, KEYBOARD 01 on him at the keyboard. **If he puts `MAIL_PASSWORD` in, the email pass (A2) is
next** — only one session deploys at a time. ⚠️ Any pass that swaps the local database engine asks him to pause
`advisor-platform` first (`OPEN.md` F9).

---
**Sources:**
- `ops/release.sh status`-equivalent reads over SSH (`readlink current`, `cat current/.release`) and `git ls-remote origin`.
- The byte-for-byte check: a sha256 manifest of `git archive <live commit>` streamed to `sha256sum -c -` inside the live
  release (nothing written on the host).
- A read-only production read of runtime config and counts, piped over stdin with the database session set READ ONLY
  (booleans, names and counts only): the same code as `tools/prodstate.php.txt`, plus loose-end counts.
- This pass's run outputs (suite on both engines, harness, money gate, public checks, the host preflight).

# OPEN — everything not finished, and who it is waiting on

**2026-09-29, ADMIN 06 FINISH; updated by each pass since — last by the CALENDAR SECOND pass (2026-10-04: a booking can be made from the Calendar — price and season set inside its dialog)** (first written 2026-09-27 by MEMORY 01). Labels: **V** = Verified (date given) ·
**R** = Reported (source given) · **F** = Founder said. **Where a pass verified something different, it wins, and the
change is said.** Briefs live in `briefs/`.

## Founder
| ID | What | Waiting on | Brief | Why it matters |
|---|---|---|---|---|
| F1 | Change the exposed `contact@` password in hPanel, then put the new one in `.env` as `MAIL_PASSWORD`. **Exposed because** it was pasted into a chat session during the email passes (R: MEMORY 01; exact date not recorded). It was never used, and must be rotated. (**V 2026-09-29:** not set; mailer `log`) | him | `TONIGHT-01.md` Part 2, `MAIL-02.md` | Until then every email is thrown away: invites, resets, verification. |
| F2 | Copy `APP_KEY` into his password manager, off the server | him | — | It exists in one place. Lose it and encrypted guest data is gone for good. |
| F3 | PW-SIGNUP PROBE: suspend, then delete, with his password. **V 2026-09-29:** still `active`, so not started | him — ready: ADMIN 06 is live (V 2026-09-29) | `PW-SIGNUP-DELETE-1.md`, `-2-follow-up.md` | The first real deletion; it proves the delete on production. The agent retakes the backup and baselines first (A3). |
| F4 | KEYBOARD 01 at the keyboard: (1) rotate the Google secret, proven by a real signup; (2) the four admin checks; (3) the heartbeat alarm (**V 2026-09-29:** unarmed); (4) the Stripe dashboard set-up, only after ADMIN 06 is live. **Its own rule: run it after TONIGHT 01 has closed.** | him | `KEYBOARD-01.md` | Proves the secrets and turns the heartbeat on. |
| F5 | Stripe live: keys, both webhook secrets, Connect verification, the four Price IDs, real prices | him | `docs/STRIPE-PRODUCTION.md` | ⛔ Not before ADMIN 06 is live (✅ V 2026-09-29, `rel_AF`), B20 is settled (D-B20) **and** payout confirmation is proven with one test-mode transfer (D-TRANSFER). |
| F6 | The lawyer: Article 28 agreements (Hostinger first); who may hold customers' money; how long invoices and payment records must be kept | him | — | The ADMIN 06 hold on deleting live money records waits on the third answer. |
| F7 | Real tax rates (`tax:set-rate`, replacing the EXAMPLE rates) | him | `docs/TAX-ENGINE-DESIGN.md` | The VAT figures shown are example rates. |
| F8 | Optional: one email to `sales@` to prove the alias | him | — | **R:** the alias was created 2026-09-26; delivery is not proven. |
| F9 | Keep `advisor-platform`'s test runs paused while a Vyrobel pass swaps the local database engine (MariaDB gate) — **F 2026-09-29: he paused them for ADMIN 06's finish.** **F 2026-09-30 (the Units pass): after an hour with the dev server still up, he said "stop advisor and complete vyrobel"; it was stopped with SIGTERM at 16:09 Baku (the Octane process and its frankenphp child) and left stopped.** Each pass still asks. | him, per pass | — | The engine swap stops the shared local MySQL for ~25 min; that project's tests and its Octane dev server use it. |

## Agent
| ID | What | Waiting on | Brief | Why it matters |
|---|---|---|---|---|
| A1 | **Finish ADMIN 06** — ✅ DONE and LIVE on `rel_AF @ 4c17edd2` (V 2026-09-29; `HISTORY.md`, "ADMIN 06 finish"). Left: the signed-in look at the delete screen (needs his sign-in). | him, for the signed-in look | `ADMIN-06.md` | ⛔ Must be live before any live Stripe key. |
| A2 | The email pass (TONIGHT 01 Part 2 / MAIL 02) | F1 | `TONIGHT-01.md`, `MAIL-02.md` | Switches production mail on. |
| A3 | PW-SIGNUP proof: **retake** the backup and the before-baselines right before his click (the 26 September ones predate ADMIN 06's migration); after his delete, run the proof `after` twice, `compare.py`, read the ledger back, keep the backup, delete the two ids files | F3 | `PW-SIGNUP-DELETE-1.md` | Proves only the probe's rows went, and nothing else moved. |
| A4 | The whole-day walk on a test hotel | A2 | — (R: planning side) | The first end-to-end use with real email. |
| A5 | F-36 / A10 | Channex going live | `docs/GO-LIVE-REQUIREMENTS.md` | R: blocked on Channex. |
| A6 | Remove two read-only scripts left in `env-backups/` (mode 644, inside the 700 folder): `admin06-prodfacts.php`, `memory01-prodstate.php` | a pass allowed to delete files on production (ADMIN 06's brief forbids deleting any) | — | Harmless, but tidy. |
| A7 | **Make the mutation harness's "another run is active" check tell projects apart** (by working folder, as `RULES.md` asks). Today it matches any `pest` on the Mac, so `advisor-platform`'s runs block it — found 2026-09-29, when it refused once and had to wait for a gap. | a brief | — | Not a defect in Vyrobel; it costs time, and a hurried session might be tempted to weaken the guard. Don't. |
| A8 | **The x-show + inline display sweep.** 22 elements on other screens carry `x-show` together with an inline `display` (security ×5, the owner portal ×10 across nine pages, and 7 more across housekeeping, roles, settings, schedule and passkeys; Maintenance's six were fixed 2026-09-29, the Units page's four 2026-09-30, the Rates page's eight with the unit price form's one 2026-10-01, the unit requests page's two 2026-10-03 and the new reservation page's two 2026-10-04). `x-show` removes the inline display when it shows the element, so each comes back as `block` after its first hide — the Guests dialogs opened in the top-left corner this way. `XShowInlineDisplayContractTest` holds them at a ceiling of 22 (V 2026-10-04, scanned: exactly 22 — and re-counted by the independent review). Each needs a browser look. | his go (asked 2026-09-29) | — | Some are dialogs that will open in the corner, as the Guests ones did. |
| A9 | **The see-through dialog panel on other screens.** The Inbox's three dialogs and Billing's still use the shared `.vy-modal` glass (the Calendar's New reservation dialog moved to the solid card 2026-10-04, with its rebuild). On the Portfolio's, the same glass measured 3.52:1 (sub-text) and 3.25:1 (labels), below AA (V 2026-09-29, sampled pixels); these four were **not measured**. The fix is one class per panel (`vy-modal-card`). | his go | — | Grey text in those dialogs is likely below AA. |
| A10 | **The string `:style` sweep.** 10 elements on other screens carry a static `style` AND a string `:style`, which Alpine turns into a REPLACEMENT of the whole attribute (schedule ×2, owner notification preferences ×2, operator notification preferences, maintenance settings, owner units, owner reports, housekeeping, email previews). On the work order page the same pattern shrank a switch to 16×6 px and stripped the dialogs' rows and tabs (🖥 V 2026-09-29). `AlpineStyleBindingContractTest` holds them at a ceiling of 10. Each needs a browser look; the fix is an object with the same values. | his go | — | Some controls on those screens probably render without their size or padding once Alpine binds them. |
| A11 | ✅ **FIXED 2026-10-02 (his go, "Yes, all of it")** — D-PAYRUN-TENANT: "Run payouts now" names its own organisation's owners, its run key carries the organisation, and "Last run" shows only this organisation (`HISTORY.md`, "PAYOUTS"). Live state: `STATE.md`. | — | — | — |
| A12 | ✅ **FIXED 2026-10-02** — D-UNITS-EXPORT: the Units Export link takes the view's address at the click, as on Owners. | — | — | — |
| A13 | **The Rates page could flag a saved-but-unapplied season more loudly.** On production his €200 "Winter" season sat saved and never applied for six days, the reason no booking could be made (V 2026-10-04). The booking page now offers it; the Rates page still shows it only as a "not applied" line in its season list. A banner ("1 season saved but not applied — no price is written until you apply it") is a small change to that page. | his go | — | Saving a season and expecting prices is an easy mistake; the two-step is deliberate (R-SEASON), its visibility is not. |

## Known defects (each its own ID)
- **D-B19**: delete could not see an open Stripe Checkout page. **Fixed by ADMIN 06**, including the two gaps review 2 found
  (a live page paid but not yet reported, D-R2-2; the guest page's half, D-R2-4).
- **D-3b**: the delete preview counted 17 of the 232 rows it removes. **Fixed by ADMIN 06**; the proof gap (D-R2-6) is closed.
- **D-A10 (F-36)**: waits on Channex. R: `docs/GO-LIVE-REQUIREMENTS.md`.
- **D-MARIADB3**: resolved — no test skips by engine (R: ADMIN 06).
- **D-LOGO**: replaced logo files were never deleted. **Built by ADMIN 06, as the brief asked**, but ruling DEC-f may reverse
  it: removing an old logo breaks the image in emails already sent.
- **D-LOGO-Q**: nothing to do. V 2026-09-27: 0 logos are referenced and there is no logo folder on disk.
- **D-OPS3**: ✅ fixed 2026-09-29. `release.sh stage` shipped the four drifted `ops/` files from git; on the host the drift
  check passes on the live `.htaccess` and its self-test fires 3/3; the battery reports `SERVED=0`; 1,401 of 1,401
  deployable files match git (V).
- **D-B20 (pre-existing; read, not run)**: a guest's card payment page cannot open with real Stripe. Under the SDK's
  pinned API version, a payment-mode Checkout page has no PaymentIntent. `docs/KNOWN-LIMITS.md` L20. Verify with one
  test-mode session.
- **D-TRANSFER (pre-existing; read, not run; review 2 confirmed)**: payouts are confirmed only by the Connect endpoint,
  but a platform-created transfer raises its events on the PLATFORM account. Live payouts could sit `processing`
  forever. Verify with one test-mode transfer before go-live (needs his Stripe test keys).
- **D-LOG**: ✅ fixed 2026-09-29 — `storage/logs/laravel-2026-09-20.log` set 775 → 644 (V; still 404 from the web).
- **D-HEARTBEAT**: the heartbeat alarm is unarmed; `OPS_HEARTBEAT_PING_URL` is not set (V 2026-09-29). Fixed by F4(3).
- **D-R3-3 (small, a call for him)**: a TEST-mode Checkout page's `checkout.session.expired`, arriving after its
  organisation was deleted, is recorded `failed` (its metadata names the organisation), so the daily reconciliation shows
  one red row for a page that moved no money. A live page cannot reach this state (the delete waits for Stripe's report).
  The late-webhook test pins `failed` deliberately; changing it is his call, not a quiet edit.
- **D-HOLD-STUCK (unreachable today)**: a card hold stuck at `authorized` would refuse a delete for ever (nothing releases
  it) — but nothing in `app/` calls `authorizeDeposit`, so nothing can create one. Recorded by review 3.
- **D-EDIT-SCOPE (found 2026-09-29, not fixed — his call)**: the Portfolio's **Edit** action (`PropertyController::update`)
  resolves the property through the tenant scope only, not the person's property allow-list (#38): staff scoped to some
  properties could edit another by its id. The new Remove/Keep actions do check the allow-list (a 404 outside it).
- **D-XSHOW (found 2026-09-29)**: see A8. Fixed on the Guests list and the guest profile; 45 sites elsewhere, under a ceiling.
- **D-MODAL-GLASS (found 2026-09-29)**: see A9. Fixed on the Guests pages and the Portfolio (measured after: 6.27 / 5.28 and
  6.18 / 5.30:1).
- **D-GL-FLAKE (seen once, not reproduced)**: during the browser walk, one click on a row's ⋯ left the page in select mode,
  scrolled, with the menu shut. Three exact replays (same order, including a dialog open before the navigation) behaved
  correctly, and a click logger saw one click on the ⋯ only. Not explained; the menu's closing logic was reworked afterwards
  for an unrelated measured reason. Watch for it on the signed-in look.
- **D-MT-STORE (found 2026-09-29, not fixed — his call, like DEC-i)**: creating a work order (`MaintenanceActionController::store`) resolves the unit through the tenant scope only, not the person's property allow-list (#38): staff scoped to some properties could raise a work order on another's unit by its id. The new Edit does check the allow-list (a 404 outside it); every other maintenance write (Start, Resolve, Cancel, Assign, parts, notes, photos, the bulk actions) and the work order page itself resolve the ticket by the tenant scope only, as before — the board never lists another property's work orders, but a known id reaches them. The housekeeping writes have the same shape (R: the independent review, 2026-09-29).
- **D-STYLE-STRING (found 2026-09-29)**: see A10. Fixed on both Maintenance pages (14 sites on the work order page).
- **D-MODAL-PAD (found 2026-09-29)**: the older `.vy-modal { padding: 26px }` stacks with the dialog sections' own 24 px. On a phone it left 250 px of a 350 px dialog on the Maintenance board (fixed there, scoped); the Guests, Portfolio and profile dialogs keep it (they were walked at 390 px in their passes).
- **D-UNIT-STORE — FIXED 2026-09-30, by his ruling on DEC-k** ("Fix this issue too"): Add unit now finds the room type inside the person's properties — the same `readIds()` rule as every other unit write — so another property's room type is a 404 and nothing is created (V: the test was RED first, a 302 where a 404 was due; mutations UL-M32 and UL-M33 caught). ✅ LIVE on `rel_AL @ 8cda8c7f` (V 2026-09-30 19:13 Baku). The same question on the Portfolio's Edit (DEC-i) and the maintenance writes (DEC-j) is still his.
- **D-MEDIA-ATTACH — FIXED 2026-09-30, by his ruling on DEC-m** ("Fix this issue as well … let only people in that conversation open them, after signing in"): owner message attachments are stored on the PRIVATE disk and open only through two signed-in routes — the owner portal's (the owner scope: that owner and their team) and the inbox's (`guests.view`, that organisation); the operator inbox now shows them. Another owner, another organisation, a staff role without the inbox, or a visitor gets nothing (V: tests RED first; mutations MA-M1..M7 caught; walked locally). ✅ LIVE on `rel_AN @ 761cf2dd` (V 2026-09-30 21:59 Baku). Production held 0 attachments (V, the preflight), so nothing needed moving.
- **D-RATE-PLAN-SCOPE — FIXED 2026-10-01, by his ruling on DEC-l** (the Rates plan, "Yes, all of it"): EVERY rate write — the grid editor, bulk edit, per-unit preview and apply, the four season writes and the three rate-plan writes — now finds its room type, plan or season inside the person's properties through one rule (`App\Support\Rates\RateScope`, the `readIds()` rule the unit writes use), and "All room types" is the room types on screen (it was every room type in the organisation: a bulk change on one property repriced the others). V: tests RED first (`RatesPageFixesTest`, DEC-l and "All room types"); mutations RP-M5..RP-M8 caught. ✅ LIVE on `rel_AO @ cac9e677` (V 2026-10-01 02:10 Baku).
- **D-UNIT-SINGLE-TXN (found 2026-09-30 by reading, not seen happen)**: the SINGLE Pause/Resume (`OwnerUnitStatusService::setActive`, shared by the operator's Units page and the owner portal's pause, #69) writes the flag, recomputes availability, then writes the audit row — with no transaction. If the recompute failed, the unit would stay flipped with stale availability and no audit row. The new bulk action is one transaction; the single one was left as it was because the owner portal shares it. Small; a pass that touches the owner pause can close it.
- **D-MT-COPY (small)**: the older maintenance flash lines say "Ticket …" ("Ticket resolved.", "Ticket cancelled.") where the page now says "work order"; bulk Assign to "No one" reports "Assigned 2 work orders." Left as they were.
- ✅ **D-PAYRUN-TENANT — FIXED 2026-10-02 (A11)**, `PayoutRunScopeAndPauseTest` RED first. The record as found (2026-10-01; 🔬 Verified in the test database,
  not on production): "Run payouts now" (`PayoutRunActionController::runNow`) calls `PayoutRunService::run()` with no owner
  list, and `owners(null)` selects every owner with payable work **in every organisation**, under super-admin. A throwaway
  probe (deleted after the run): an operator in organisation A pressed it and a payout was created **and paid** for
  organisation B's owner ("2 created · 2 paid", test mode). The route refuses unless `PAYOUT_ENGINE_ENABLED` is on; the
  scheduled run is meant to cover every organisation, the manual button is not. **On production the switch is OFF (V
  2026-10-01, the read-only preflight from `rel_AQ`), so it cannot fire there today.** Fix = A11, his go.
- ✅ **D-PAUSE-MANUAL — FIXED 2026-10-02 by his ruling on DEC-n ("a pause stops every payout")**: the payout service refuses a paused owner on
  create, pay and retry; the engine never selects one; the payout run lists them apart and its buttons skip and count them. The record as found: a payout pause is read only by the AUTOMATIC run
  (`PayoutRunService::dueOwnerIds`). The payout run page lists a paused owner's statement as ready, and Create payouts /
  Run payouts now (`owners(null)`) do not skip them. The Owners page now says exactly this in its pause dialog and row
  reason; the owner detail page still says "Amounts accumulate and carry forward until resumed." Whether a pause should stop
  the manual run too is DEC-n.
- ✅ **D-UNITS-EXPORT — FIXED 2026-10-02 (A12).** The record as found (2026-10-01 in the Owners browser walk): the Units page's Export link
  sits outside the regions the list swaps in place, so after a filter, search or sort changed in place it exports the view the
  page was loaded with. Fixed on Owners; Units is A12.
- ✅ **D-PAYRUN-LASTRUN (found 2026-10-02 by reading; fixed the same pass)**: run rows carry no organisation, and the payout
  run page showed the newest one — another organisation's created/paid/held counts (🔬 RED first: A's page read B's "2 created").
  It now reads only this organisation's own payouts ("Last run here").
- ✅ **D-PAYRUN-RUNKEY (found 2026-10-02 while fixing A11; fixed the same pass)**: the manual run key `manual:<timestamp>` was
  unique across the platform and a run whose key exists is handed back as it is, so two organisations' runs in one instant were
  one run — the second did nothing and reported the first's figures (🔬 under the suite's frozen clock). The key now carries the
  organisation.
- ✅ **D-PAYRUN-BUTTON — FIXED 2026-10-03 (the owners module pass, 🔬 RED first):** "Run payouts now" is shown disabled with
  the reason while `PAYOUT_ENGINE_ENABLED` is off, instead of a button that could only refuse; and every money write on the
  payout run now asks first (`HISTORY.md`, "OWNER MODULE").
- ✅ **D-OWNER-COMMISSION — FIXED 2026-10-03 (found by reading, 🔬 RED first):** the owner page's commission change decided
  against today's agreement — two changes in a month left two open-ended owner-wide agreements on one day (the payout maths
  takes whichever the database returns), and an undo wrote nothing — and the new agreement dropped the management fee, the term
  and the attribution rules. One rule now: `App\Support\Owner\CommissionChange`. Agreements an older write may already have
  stacked are not rewritten; production holds none (V, the preflight).
- ✅ **D-OWNER-PORTAL — FIXED 2026-10-03 (🔬 RED first):** Edit owner's portal-access field could never switch access off from
  the form, while a hand-made request switched any linked login off (bypassing `OwnerLoginLifecycle`, which spares a person who
  also works here) or opened an invited one. The field is no longer read. DEC-o asks whether it should be switchable at all.
- ✅ **D-OWNER-SILENT — FIXED 2026-10-03 (🔬 RED first):** every refused write on the owner's page closed its dialog and showed
  no message; each dialog now reopens with the reason beside its field and what was typed.
- ✅ **D-UR-SCOPE — FIXED 2026-10-03 under DEC-k (🔬 RED first):** approving a unit request created the unit in any property of
  the organisation; it now resolves inside the person's properties (another is a 404, nothing written) — the rule he ruled for
  every unit write on 2026-09-30, stated to him so he can object.
- **D-AMEND-PLANNED (found by the 2026-10-03 review, pre-existing, not changed):** an amendment the owner acknowledges
  (`AgreementAmendmentService::applyTerms`) is written open-ended, with no bound at a change already planned for later — the
  overlap the owner page's commission rule now prevents on its own side. Per-unit agreements are outside that rule too (nothing
  creates them today).
- ✅ **D-OWNER-HINT — FIXED 2026-10-03:** the Add owner unit hint said units could be given later from the owner's page (my own
  sentence of 2026-10-01); no screen does — a unit's owner is written only by an accepted invite or an approved request. DEC-p
  asks whether one should.
- ✅ **D-NEWRES-DEADEND — FIXED 2026-10-04 (his report: "it is not possible to do reservation"; 🔬 RED first).** On production his
  workspace's first property has no rooms and the other has no price on any of the next 60 nights (V, read-only, 2026-10-04); the
  wizard has no shell switcher, so under "All properties" it used the first property and said "No units are free", and the only
  way past an unpriced room type was a link that threw the booking away. It now asks which property, says when one has no rooms,
  and lets `rates.manage` set the stay's price in place through the Rates write (`HISTORY.md`, "NEW RESERVATION").
- ✅ **D-NEWRES-OVERRIDE-500 — FIXED 2026-10-04 (found on the walk, 🔬 RED first):** choosing "Other" as the reason to book over a
  restriction, with no note — or a reason not on the list — was a server error page (an uncaught `InvalidArgumentException`).
- ✅ **D-NEWRES-ORPHAN-GUEST — FIXED 2026-10-04 (found on the walk, 🔬 RED first):** every refused booking left the new guest it
  had just created — name, email and phone — with no stay. Removed now on a refusal, and only when no booking points at it.
- ✅ **D-NEWRES-DONE-SCOPE — FIXED 2026-10-04 (the independent review, pre-existing):** the done screen showed any booking of the
  organisation — guest name, reference, check-in code — by its id to staff limited to other properties.
- **D-MONEY-COMMA (found 2026-10-04, 🔬 measured; not changed — his call):** the shared money parser `MoneyInput::toMinor` (19 call
  sites: rates, payments, deposits, refunds, prices) drops commas as thousands separators, so **"150,50" is read as €15,050.00
  and "120,5" as €1,205.00**. A European habit becomes a 100× amount. The New reservation wizard now stops a comma-cents amount
  in its two fields; every other money field still accepts it (a payment is capped at the balance, a rate is not). The likely
  fix — refuse a comma followed by one or two digits at the end, everywhere — changes 19 inputs, so it is his go.
- **D-STOREFRONT-ORPHAN-GUEST (found 2026-10-04 by reading, not changed):** the public storefront's booking
  (`BookingEngineService::book`) creates the guest before the booking and keeps it when the unit was just taken ("unavailable") —
  the same orphan as D-NEWRES-ORPHAN-GUEST, from a guest's own form. Its own pass.
- **D-GB-SYMBOL (small, found 2026-10-04):** the Group booking wizard prints "GBP 90.00" where `MoneyFormatter` (and the rest of
  the app) prints "£90.00" — its script maps only EUR and USD.
- **D-RATE-DERIVED-PRIMARY (the 2026-10-04 review, pre-existing, not changed):** when a room type's main plan is a derived plan,
  the Rates editor's main-plan path (and so the wizard's price box) writes a price straight onto it, which the chosen-plan path
  refuses ("set the base plan instead").
- ✅ **D-CAL-NAMES — FIXED 2026-10-04 (his report: "guest first name, last name, and their boxes are not well aligned"):** the
  Calendar dialog's name row was one grid of three columns holding five pieces, so "Last name" ended one line and its box began
  the next. Each label now sits above its own box (`HISTORY.md`, "CALENDAR NEW RESERVATION").
- ✅ **D-CAL-GUEST-GET — FIXED 2026-10-04 (found by reading, pre-existing; #28):** the dialog's "Check rack" was a GET carrying the
  guest's first and last name (`gf`, `gl`) into the browser's history and the server's access logs, which no erase reaches. The
  name now stays in the tab and travels only in the booking's POST.
- ✅ **D-CAL-DEADEND — FIXED 2026-10-04 (pre-existing):** a room type with free units and no price vanished from the dialog ("No
  units are free (and priced)") — the dead end of D-NEWRES-DEADEND, on the Calendar. Now listed with the reason and where to fix it.
- ✅ **D-CAL-UNIT-PRICE — FIXED 2026-10-04 (pre-existing):** the dialog showed the room type's price, while the booking write charges a
  unit's own price where it has one (D18-5): the total shown could differ from the total charged.
- ✅ **D-CAL-PAST — FIXED 2026-10-04 (pre-existing; Confirm's half found by the review):** check-in defaulted to the calendar's first
  day even when that was in the past, and Confirm could book a past check-in (the box's `min` guards only the GET).
- ✅ **D-CAL-CLAIMS — FIXED 2026-10-04 (pre-existing, copied from the wireframe):** the dialog said it matched an existing guest's
  profile and that the guest got a confirmation email; it does neither (a new guest every time, and no email is asked for).
- **D-RACK-START-TZ (found 2026-10-04 by reading; not changed — shared with the Front Desk):** ⚠️ Seen live 2026-10-05 00:15 Paris:
  the top bar's date pill said "Sun 4 Oct" while Villa Azure's Calendar marked Mon 5 Oct as its today — the same app-day vs
  property-day split, in the shell. when the address names no week, the
  Calendar and the Front Desk start on the app's day (`BoardDate`, `CarbonImmutable::today()`), not the property's. Far from UTC the
  default week can start a day off the property's own today. The marked "today" column and the "+" links follow the property.
- ✅ **D-NR2-SEASON — FIXED 2026-10-04 (his second report; 🔬 read-only on production):** a saved season that was never applied
  left his property with no price on any night, and the booking page said only "No price for these nights yet". It now offers
  the season and applies it through the Rates page's own disclosure (`HISTORY.md`, "NEW RESERVATION, SECOND PASS").
- ✅ **D-NR2-NOROOMS — FIXED 2026-10-04:** a property with no rooms was a dead end with only a small picker; the card now offers the
  properties that can take a booking.
- ✅ **D-NR2-RULES — FIXED 2026-10-04 (pre-existing):** house rules invented "Not permitted" for pets and smoking on a property
  with no policy, printed the stored codes ("on_request") on one with a policy, and ignored a real cancellation policy.
- ✅ **D-NR2-CLAIMS — FIXED 2026-10-04 (pre-existing):** "The confirmation includes directions and parking" (it doesn't); "these go
  out with the guest's confirmation" (they don't); a check-in QR for a guest who cannot sign in; "Email the confirmation" ticked
  with no address. Each now says what is true, including that email isn't being sent from Vyrobel yet where it isn't.
- ✅ **D-NR2-SEASON-ONECLICK — FIXED 2026-10-04 (mine, caught by the independent review before commit):** the first version of
  Apply posted on the first click, without the Rates page's "Apply this season?" disclosure. Never shipped.
- **D-NR2-NEXT-NOPRICE (seen in his live look 2026-10-04; small; not changed — his go):** when no unit has a price for the dates,
  Next says "Pick a unit for these dates to continue." though there is no unit to pick; the cards above it say how to get one
  (Set price, or the saved season). A sentence for that case — "No unit has a price for these dates yet — set one above." — is a
  one-line change to the Stay step's check.
- **D-NR2-SCOPE-WORDING (seen in his live look 2026-10-04; RULED text, not changed — his call):** the season disclosure always
  shows "That count sees only rates agreed through a proposal…" (F-21: its scope is stated even at zero, on purpose), but when no
  owner-agreed count is shown above it, "That count" reads as if it meant "34 of 34 nights". Same text on the Rates page since
  `rel_AO`. A wording that keeps F-21 — "Owner-agreed nights are counted only when agreed through a proposal…" — is his call.
  ⚠️ Since the Calendar's second pass the same disclosure also opens from the Calendar's New reservation dialog.
- ✅ **D-CAL2-NOPRICE — FIXED 2026-10-04 (his report on the Calendar; 🔬 read-only on production):** the Calendar's New reservation
  dialog said "No price for these nights yet" with a link away to the full form, and nothing about his saved "Winter" season — so
  nothing could be booked there. Set price and Apply season now work inside the dialog and come back into it
  (`HISTORY.md`, "CALENDAR, SECOND PASS").
- ✅ **D-CAL2-NOROOMS — FIXED 2026-10-04:** on a property with no rooms the dialog said "Go to Units" and the Calendar "No room
  types on … yet"; both now offer the properties with rooms.
- ✅ **D-CAL2-LANDING — FIXED 2026-10-04:** a booking made on the Calendar landed on the full form's done screen; it comes back to
  the Calendar with its reference and "Open it".
- ✅ **D-CAL2-DRAG — FIXED 2026-10-04 (pre-existing, #30):** every back-office role saw draggable bars and "Drag a booking"; the
  move needs `reservations.assign`, which seven of the eleven do not hold — the drop was refused (403).
- ✅ **D-CAL2-REFUSED — FIXED 2026-10-04 (pre-existing):** a refused move was the same quiet status line as one that worked.
- ✅ **D-CAL2-FILTER — FIXED 2026-10-04 (pre-existing):** the arrows, Today, 7/14/30, New reservation and a dragged booking all
  dropped the room-type filter; choosing a filter dropped 7/14/30; a filter from another property emptied the grid ("No room
  types on … yet").
- ✅ **D-NR2-NEXT-NOPRICE — the Calendar's half FIXED 2026-10-04:** its dialog says the next step from what it shows ("U1 is picked
  — confirm to book it" / "Pick a unit below"). The New reservation page's "Pick a unit for these dates" stays for his go.
- **D-NR2-ONE-NIGHT (seen on the Calendar walk 2026-10-04; the New reservation page; not changed — his go):** for a one-night stay
  that page says "from 10 Oct to 10 Oct" and "1 of the 1 night of the stay that have no price"; the Calendar's dialog words a
  single night right since its second pass ("the night of 10 Oct", "has"). Two template sentences.
- **D-SHELL-PROPNAME (measured on the Calendar walk 2026-10-04, locally; on every operator page — his go):** the sidebar's
  working-property box is 141 px wide, so a name over ~135 px is cut ("Harbourpoint Walkthrough" 165 px). Seen on production in
  his live look 2026-10-05: "Internal Test Proper›".
- **D-SUBDISPUTE (recorded, not built)**: a dispute on an organisation's own SUBSCRIPTION charge names only a payment the
  app never stores, so it does not refuse a delete. It is recorded `failed` for him to answer at Stripe (GO-LIVE B19).

## Older open items (Reported; carried here so nothing is only in an old doc)
- **The ADMIN 05 signed-in live pass**: the redesigned forms on production, signed in. Waits on the founder's sign-in.
  R: `docs/DECISIONS.md`, ADMIN 05.
- **R-REVERSE**: the founder's ruling on the payout `reverse()` quarantine. R: `docs/AUDIT-SUMMARY.md` §4, REPAIR 01.
- **Founder calls recorded in `docs/KNOWN-LIMITS.md`** (one line each in `RULES.md` 2a):
  - L1: pre-sale copy on the Connect country;
  - L3: the guest data export and the legal text;
  - L5: whether `/security` says 2FA cannot be required;
  - L6: the phantom permissions;
  - L7: whether to state that the back office is desktop-only;
  - L10: the tentative-hold message;
  - L12m: a CDN purge, or disabling the CDN;
  - L12p: moving the docroot (the `/storage` media 404 is worked around at `/media` since 2026-09-30 — the server rule is unchanged);
  - L17: a database trigger for the ledger.

## Decisions (his rulings, not ours)
| ID | Question |
|---|---|
| DEC-1 | Hostinger's limits: 100 emails a day, and the free mail plan ends 2027-04-02 (R). Stay, or move to Postmark (a settings change)? |
| DEC-2 | Prices and tax rates. |
| DEC-3 | Should a password reset also cut off an owner's connected apps (OAuth)? |
| DEC-4 | The retention definition: 2 strict or 40 lenient, of 144 categories (R). |
| DEC-5 | Automatic cleanup of abandoned signups. |
| DEC-6 | Account deletion for hotel staff and owners (self-service). |
| DEC-7 | Free plans and trial extensions from the console. |
| DEC-8 | Keep or delete the other 12 test organisations (V: 13 probes plus the 2 protected; PW-SIGNUP is F3). |
| DEC-a | ADMIN 06: should an existing Stripe customer or Connect account, with no money moving, refuse a delete? |
| DEC-b | ADMIN 06: the hotel's own tax invoices and cash/bank payments are not Stripe records, so the hold does not cover them. Should it? |
| DEC-c | ADMIN 06: a **failed** payout with no retry means an owner is still owed money, but the delete does not refuse on it. |
| DEC-d | ADMIN 06: a staff invite to an existing account that is not active sets its password on acceptance (the re-invite design). |
| DEC-e | ADMIN 06: after a cancellation, an open Stripe invoice can remain at Stripe, unrecorded here. |
| DEC-f | ADMIN 06: removing a replaced logo breaks the logo in emails **already sent**. Keep old logos for email history instead? |
| DEC-g | ADMIN 06 finish: D-R3-3 above — should a test-mode page's expiry, arriving after a deletion, be `ignored` rather than `failed`? |
| DEC-h | Portfolio remove (2026-09-29): his approval put a leaving property's state on its Portfolio card, which updates ruling **F-58** ("per-property visibility on Settings is enough; no portfolio-wide list"). Confirm, or keep the card silent (one block in the view). |
| DEC-i | D-EDIT-SCOPE above: should Edit on the Portfolio be limited to the properties a person can access, as Remove is? |
| DEC-j | D-MT-STORE above: should creating a work order — and Start, Resolve, Cancel, Assign on one — be limited to the properties a person can access, as the new Edit is? |
| DEC-l | ✅ RULED 2026-10-01 ("Yes, all of it", the Rates plan) and fixed — D-RATE-PLAN-SCOPE above. |
| DEC-n | ✅ RULED 2026-10-02 ("Yes, a pause stops every payout") and built — D-PAUSE-MANUAL above. |
| DEC-o | The owner's page (2026-10-03): should an operator be able to switch an owner's portal access off and on from their page? If yes: does their team go with them, and does someone who also works for you keep their login (the removal rule)? Not offered today — the old checkbox could not switch it off, and its server side was unsafe (D-OWNER-PORTAL). |
| DEC-p | The owner's page (2026-10-03): should an existing owner be given units later? Today only their invite gives an owner existing units. It touches money: a unit's owner decides who is paid for its stays. |
| DEC-q | Rate proposals (found by the 2026-10-03 review, pre-existing): proposing a rate to an owner — and accepting their counter, which applies the rate — does not check the person's properties, while every other rate and unit write does (DEC-k, DEC-l). Should it, as those do? |
| DEC-r | The New reservation wizard (2026-10-04): a new guest whose email matches an existing guest is created again — the walk made four "Lena Walk"s. Offer the existing guest instead (by the email's blind index, never showing the email back), or keep creating a new one? |

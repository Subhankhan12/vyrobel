# OPEN — everything not finished, and who it is waiting on

**2026-09-29, ADMIN 06 FINISH; updated by each pass since — last by the GUESTS pass** (first written 2026-09-27 by MEMORY 01). Labels: **V** = Verified (date given) ·
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
| F9 | Keep `advisor-platform`'s test runs paused while a Vyrobel pass swaps the local database engine (MariaDB gate) — **F 2026-09-29: he paused them for ADMIN 06's finish.** | him, per pass | — | The engine swap stops the shared local MySQL for ~25 min; that project's tests and its Octane dev server use it. |

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
| A8 | **The x-show + inline display sweep.** 45 elements on other screens carry `x-show` together with an inline `display` (rates ×8, maintenance ×6, security ×5, units ×4, the owner portal ×10 across nine pages, and 12 more across housekeeping, new reservation, roles, unit requests, settings, schedule, passkeys and the unit price form). `x-show` removes the inline display when it shows the element, so each comes back as `block` after its first hide — the Guests dialogs opened in the top-left corner this way. `XShowInlineDisplayContractTest` holds them at a ceiling of 45 (V 2026-09-29, scanned). Each needs a browser look. | his go (asked 2026-09-29) | — | Some are dialogs that will open in the corner, as the Guests ones did. |
| A9 | **The see-through dialog panel on other screens.** The Inbox's three dialogs, the Calendar's New reservation dialog and Billing's still use the shared `.vy-modal` glass. On the Portfolio's, the same glass measured 3.52:1 (sub-text) and 3.25:1 (labels), below AA (V 2026-09-29, sampled pixels); these five were **not measured**. The fix is one class per panel (`vy-modal-card`). | his go | — | Grey text in those dialogs is likely below AA. |

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
  - L12p: the `/storage` media 404, and moving the docroot;
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

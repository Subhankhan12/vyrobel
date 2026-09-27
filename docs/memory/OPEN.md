# OPEN — everything not finished, and who it is waiting on

**2026-09-27, MEMORY 01.** Labels: **V** = Verified in MEMORY 01 · **R** = Reported (source given) · **F** = Founder said.
This starts from the planning side's list (27 September, 00:10 Baku). **Where this pass verified something different,
it wins, and the change is said.** Briefs live in `briefs/`.

## Founder
| ID | What | Waiting on | Brief | Why it matters |
|---|---|---|---|---|
| F1 | Change the exposed `contact@` password in hPanel, then put the new one in `.env` as `MAIL_PASSWORD`. **Exposed because** it was pasted into a chat session during the email passes (R: this session; exact date not recorded). It was never used, and must be rotated. (**V:** not set; mailer `log`) | him | `TONIGHT-01.md` Part 2, `MAIL-02.md` | Until then every email is thrown away: invites, resets, verification. |
| F2 | Copy `APP_KEY` into his password manager, off the server | him | — | It exists in one place. Lose it and encrypted guest data is gone for good. |
| F3 | PW-SIGNUP PROBE: suspend, then delete, with his password. **V:** still `active`, so not started | him, best after ADMIN 06 is live (A1) | `PW-SIGNUP-DELETE-1.md`, `-2-follow-up.md` | The first real deletion; it proves the delete on production. |
| F4 | KEYBOARD 01 at the keyboard: (1) rotate the Google secret, proven by a real signup; (2) the four admin checks; (3) the heartbeat alarm (**V:** unarmed); (4) the Stripe dashboard set-up, only after ADMIN 06 is live. **Its own rule: run it after TONIGHT 01 has closed.** | him | `KEYBOARD-01.md` | Proves the secrets and turns the heartbeat on. |
| F5 | Stripe live: keys, both webhook secrets, Connect verification, the four Price IDs, real prices | him | `docs/STRIPE-PRODUCTION.md` | ⛔ Not before ADMIN 06 is live (A1), B20 is settled (D-B20) **and** payout confirmation is proven with one test-mode transfer (D-TRANSFER). |
| F6 | The lawyer: Article 28 agreements (Hostinger first); who may hold customers' money; how long invoices and payment records must be kept | him | — | The ADMIN 06 hold on deleting live money records waits on the third answer. |
| F7 | Real tax rates (`tax:set-rate`, replacing the EXAMPLE rates) | him | `docs/TAX-ENGINE-DESIGN.md` | The VAT figures shown are example rates. |
| F8 | Optional: one email to `sales@` to prove the alias | him | — | **R:** the alias was created 2026-09-26; delivery is not proven. |

## Agent
| ID | What | Waiting on | Brief | Why it matters |
|---|---|---|---|---|
| A1 | **Finish ADMIN 06**: fix the review findings (D-R2-*); rerun every gate, one suite at a time and under `caffeinate` (the Mac sleeps); commit; push; stage `rel_AF`; **time the preview on the host before swapping**; swap; public checks; on the server, run `ops/htaccess-drift-check.sh` and `ops/exposure-battery.sh` (D-OPS3); confirm no stray `.env*` copies (the one shared `.env` stays, mode 600); `chmod 644` the 775 log (D-LOG) | nothing | `ADMIN-06.md` | ⛔ Must be live before any live Stripe key. **The work is uncommitted, on this disk only.** |
| A2 | The email pass (TONIGHT 01 Part 2 / MAIL 02) | F1 | `TONIGHT-01.md`, `MAIL-02.md` | Switches production mail on. |
| A3 | PW-SIGNUP proof: after his delete, run the proof `after` twice, `compare.py`, read the ledger back, keep the backup, delete the two ids files | F3 | `PW-SIGNUP-DELETE-1.md` | Proves only the probe's rows went, and nothing else moved. |
| A4 | The whole-day walk on a test hotel | A2 | — (R: planning side) | The first end-to-end use with real email. |
| A5 | F-36 / A10 | Channex going live | `docs/GO-LIVE-REQUIREMENTS.md` | R: blocked on Channex. |
| A6 | Remove two read-only scripts this session left in `env-backups/` (mode 644, inside the 700 folder): `admin06-prodfacts.php`, `memory01-prodstate.php` | a pass allowed to act on production | — | Harmless, but tidy. |

## Known defects (each its own ID)
- **D-B19**: delete could not see an open Stripe Checkout page. **Fixed in the working tree (ADMIN 06), not deployed, with two
  open gaps:** D-R2-2 (a live page paid but not yet reported) and D-R2-4 (the guest-page half is untested).
- **D-3b**: the delete preview counted 17 of the 232 rows it removes. **Fixed in the working tree, not deployed.** The
  proof gap is D-R2-6.
- **D-A10 (F-36)**: waits on Channex. R: `docs/GO-LIVE-REQUIREMENTS.md`.
- **D-MARIADB3** ("three tests don't run on MariaDB") is **resolved, and this changes the planning side's view.** No test
  skips by engine. The gap was timing: they were added after the full MariaDB run. The working tree runs 4,018 on both
  engines. R: ADMIN 06.
- **D-LOGO**: replaced logo files were never deleted. **Built in the working tree, as the brief asked**, but ruling DEC-f
  may reverse it: removing an old logo breaks the image in emails already sent.
- **D-LOGO-Q**: quarantine orphaned logos on production (ADMIN 06 #3). **Nothing to do.** V 2026-09-27: 0 logos are
  referenced and there is no logo folder on disk.
- **D-OPS3**: the "three laptop-only check scripts" are two scripts, `ops/exposure-battery.sh` (it probes URLs, so it
  can run from anywhere) and `ops/htaccess-drift-check.sh` (it runs ON the host), plus the reference file the drift check
  reads, `ops/reference/public_html.htaccess`. All three are old hand copies on the host, and `ops/ps-check.sh` is
  missing (V, 10:30 UTC).
  `ops/release.sh stage` now ships drifted files from git (working tree), so the next deploy fixes them. They still need
  running once on the server.
- **D-B20 (pre-existing; read, not run)**: a guest's card payment page cannot open with real Stripe. Under the SDK's
  pinned API version, a payment-mode Checkout page has no PaymentIntent. `docs/KNOWN-LIMITS.md` L20. Verify with one
  test-mode session.
- **D-TRANSFER (pre-existing; read, not run; review 2 confirmed)**: payouts are confirmed only by the Connect endpoint,
  but a platform-created transfer raises its events on the PLATFORM account. Live payouts could sit `processing`
  forever. Verify with one test-mode transfer before go-live.
- **D-LOG**: `storage/logs/laravel-2026-09-20.log` is mode 775 (V). Set it to 644 at the next deploy. The logs return
  404 from the web (V), and hold no sign-in link (V: counted, never printed).
- **D-HEARTBEAT**: the heartbeat alarm is unarmed; `OPS_HEARTBEAT_PING_URL` is not set (V). Fixed by F4(3).
- **D-DISK**: ADMIN 06 is uncommitted and exists only on this laptop. Fixed by A1.

### ADMIN 06 second review (2026-09-27): fix before committing (A1)
The review had **19 findings**. The 5 most severe went to a skeptic: **4 were confirmed** (D-R2-1, D-R2-2, D-R2-3, and the
pre-existing D-TRANSFER above) and **1 was refuted**. **The 14 others were not verified by the review:** D-R2-4 to D-R2-16,
plus the logo-in-sent-emails point, which is ruling DEC-f; both reviews raised that one. MEMORY 01 has since confirmed
D-R2-12 by reading.

Confirmed by a skeptic:
- **D-R2-1**: an operator's own Connect `account.updated` carries `metadata.organization_id`, so it is recorded `failed`,
  and the daily reconciliation goes red once any operator onboards. Fix: `account.updated` is never "ours" in
  `unresolved()`. Add a test with operator metadata, plus a mutation.
- **D-R2-2**: a LIVE subscription page paid at Stripe whose webhook has not arrived stops blocking the delete once its
  `expires_at` passes. Fix: refuse while a live session has no reported outcome.
- **D-R2-3**: nothing tests or mutates `livemode` being stored on payouts, desk card charges, or deposit holds and
  captures.

Found but not verified:
- **D-R2-4**: the guest-page half of B19 (recording and closing the page) has no test and no mutation.
- **D-R2-5**: the "one dispute closing hides another" mutation (A06-M47) dies on a crash, not on the property it claims to
  test.
- **D-R2-6**: "a mutation that drops ANY one table is caught" holds only for tables that have rows in the fixtures. Seed
  oauth, `llm_usage`, reset tokens and a kept person.
- **D-R2-7**: an unknown dispute status counts as closed, so the refusal fails open.
- **D-R2-8**: a dispute whose closing event never arrived blocks the delete forever, with no "resolve it by hand" wording.
- **D-R2-9**: `recordUnresolved()` is read-then-write, so it can demote a claim that is `processing` or just `processed`.
- **D-R2-10**: the premise check misses a table cleared directly that is also a cascade child.
- **D-R2-11**: a dispute on the organisation's own subscription charge is never linked to the organisation.
  `GO-LIVE B19` overstates this.
- **D-R2-12** (**confirmed by MEMORY 01 reading**): ADMIN 06's `KNOWN-LIMITS` L19 edit says "two of the three items beside
  it are closed". Only one is.
- **D-R2-13**: the #0 refusal table in `DECISIONS` leaves out the written-reason refusal.
- **D-R2-14**: no test proves the console never shows the founder's link, or never writes it to the ledger.
- **D-R2-15**: the Connect "unknown type" path and the unrecorded-Checkout path have no mutation, and the first has no
  test.
- **D-R2-16**: the top of `STRIPE-PRODUCTION.md` leaves out the cascade-depth refusal and the cross-organisation refusal.
- Review 1's findings are fixed in the working tree (listed in the ADMIN 06 entry in `docs/DECISIONS.md`, uncommitted).

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

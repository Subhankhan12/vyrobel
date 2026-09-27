# VYROBEL — ADMIN 06 — ⚠️ WHAT DELETE LEAVES BEHIND, AND WHAT IT MUST NEVER TOUCH

**Live: ADMIN 05. ⚠️ Read the release from `.release`, never type it. Suite 3995/3995 on MySQL · 3992/3992 on MariaDB (what production runs) · 238/238 mutations caught · money gate unmoved · 0 customers.**
**⚠️ Needs nothing from him: no password, no sign-in. ⚠️ Don't start while the email pass (TONIGHT 01 Part 2) is running. Only one session deploys at a time.**
**⚠️⚠️ THIS MUST BE LIVE BEFORE ANY LIVE STRIPE KEY GOES IN. KEYBOARD 01 Job 4 checks for it.**

> ADMIN 05's permanent delete "refuses while money is moving", but it has one known hole (B19). Production only runs test-mode Stripe, so nothing is at risk today. Once live keys go in, every gap between delete and money becomes a real one. ⚠️ **This pass closes those gaps and finds out what delete leaves behind.**

---

## ⚠️⚠️ THE RULES

- ⚠️⚠️ **NEVER DELETE AN ORGANISATION ON PRODUCTION.** That covers the console, the shell, tinker and probes alike. Real deletions are his, done with his password. ⚠️ Prove everything locally and in tests.
- ⚠️⚠️ **DELETE NEVER CALLS STRIPE.** It refuses instead. Cancelling or closing anything at Stripe is his action, in Stripe's dashboard.
- ⚠️ **Use test mode only. No live keys. Never initiate a real Stripe transfer.**
- ⚠️ **A sign-in link is a credential. Count links; never print one.** The same goes for tokens and secrets.
- ⚠️ **Don't delete any file on production in this pass.** Move it to quarantine instead.
- ⚠️ Keep the money gate FULL unmoved. Use additive migrations only. Never touch tenant one. Identifiers are read, never written. Run one suite at a time and make no edits while it runs.

---

## ⚠️ #0 — READ WHAT ADMIN 05 BUILT BEFORE CHANGING IT

- ⚠️ **Make one table listing every refusal delete has today.** Columns: the condition, where it's checked, the test that proves it, and the mutation that proves the test.
- ⚠️ Read B19 as it is written in the repo, and say where it lives.
- ⚠️ If the first real deletion (PW-SIGNUP PROBE) has happened, read its deletion record back. If a before/after row count was taken, report it. ⚠️ **If no count was taken, say so. Don't reconstruct one.**

## ⚠️ #1 — ⚠️⚠️ B19, AND EVERY OTHER WAY MONEY CAN STILL BE IN FLIGHT

- ⚠️ **B19: delete refuses while any Stripe Checkout page for the organisation is still open**, meaning created but not completed or expired.
  - ⚠️ If the app doesn't record Checkout sessions, it can't know whether one is open. Say so, then choose one:
    - record the sessions (additive), or
    - refuse for as long as a session could still be open.
  - 📖 Stripe's default Checkout expiry is 24 hours. ⚠️ Check that in Stripe's current docs, because my knowledge is dated.
  - ⚠️ Say what you chose and why.
- ⚠️⚠️ **Next, list every Stripe object an organisation can own in this codebase.** Include whichever of these exist: customer, subscription, saved card, Checkout session, payment, invoice, refund, dispute, Connect account, payout, transfer.
  - ⚠️ For each one, answer: **can delete run today while one is live or pending?**
  - ⚠️ **The rule: delete refuses while any of them is active or pending.**
  - ⚠️ Why: a subscription left running at Stripe keeps charging a hotel that no longer exists here, and nobody sees it.
- ⚠️ **Late webhooks.** A dispute can arrive months after the charge. ⚠️ A correctly signed Stripe event for an organisation that no longer exists must be **recorded and answered with a 2xx**. It must never:
  - return a 500 that Stripe retries for days,
  - crash, or
  - be silently dropped.
  - ⚠️ Test this with a signed test event, on both `/webhooks/stripe` and `/webhooks/stripe/connect`. ⚠️ An unsigned event must still get a 400.
- ⚠️ **Every refusal needs a test, plus a mutation that removes the check and is caught.**

## ⚠️ #2 — ⚠️⚠️ MONEY RECORDS: A HOLD UNTIL HIS LAWYER RULES

- ⚠️ In most countries, tax law requires invoices and payment records to be kept for years. His lawyer hasn't ruled on how long, or where they must be kept.
- ⚠️⚠️ **Until then, delete refuses any organisation that has live-mode money records:** a paid invoice, a charge, a refund or a payout. ⚠️ **Test-mode records don't block.** Every record on production today is test-mode, so this blocks nothing today.
- ⚠️ **Whether a record is live or test comes from Stripe's own `livemode` field, stored when the record was written.**
  - ⚠️ If the app doesn't store that field, add it (additive).
  - ⚠️ For existing rows, **say how you know their value and prove it. Don't assume.**
- ⚠️ Record this in `docs/DECISIONS.md` as **a hold pending his lawyer, not a policy.** He removes it when the ruling comes.

## ⚠️ #3 — ⚠️ FILES: WHAT DELETE LEAVES ON DISK

- ⚠️ **Find out whether delete removes the organisation's files:** logos, uploads, documents, exports, and anything else stored for it.
  - ⚠️ Read the code first.
  - ⚠️ **Then prove it locally:** make a test organisation that has files, delete it, and list what's left on disk.
- ⚠️ If files survive, **delete removes them after the database commit.**
  - ⚠️ A file that can't be removed is **named in the console's deletion record and reported as a failure, never as "done".**
  - ADMIN 05 already fixed the after-commit case for database rows. Files are the same failure.
- ⚠️ **Replaced logos:** remove the old file once the new one is saved.
- ⚠️ **Existing orphaned logos on production:**
  1. list them (name, size, organisation);
  2. move them to a private quarantine folder outside the web root;
  3. prove every organisation's current logo still loads.
  - ⚠️ Never delete them in this pass.

## ⚠️ #3b — ⚠️⚠️ THE PREVIEW MUST COUNT WHAT ACTUALLY GOES

- ⚠️ **Found on production on 27 September:** for PW-SIGNUP PROBE the preview said **17 records in 5 tables**, but the delete removes **232**. The preview only counts tables with an organisation column. It misses:
  - role permissions (212),
  - the organisation's own row,
  - the founder's account, and
  - the account's sign-in record.
- ⚠️ **The preview is the one number he reads before an irreversible click, and it showed 7% of what goes.** That's a defect even though the delete itself is right.
- ⚠️ **The fix: the preview counts every row the delete will remove, in every table**, whether it's removed by cascade, explicitly, or as an account that belongs nowhere else. Show it grouped in plain words.
- ⚠️⚠️ **THE TEST THAT KEEPS IT HONEST:** for a seeded organisation, **the number the preview shows equals the number of rows actually gone after the delete**, measured across every table. ⚠️ A mutation that drops any one table from the preview must be caught.

## ⚠️ #4 — ⚠️ THE THREE TESTS THAT DON'T RUN WHERE PRODUCTION RUNS

- ⚠️ 3,995 tests run on MySQL but only 3,992 run on MariaDB. **Production runs MariaDB.**
- ⚠️ **Name the three tests and say why they don't run there.**
  - ⚠️ If a test is skipped because the feature misbehaves on MariaDB, **that's a production bug. Fix it.**
  - ⚠️ If a test covers MySQL-only code that production never reaches, **prove that. Saying it isn't enough.**

## ⚠️ #5 — ⚠️⚠️ WHERE A SIGN-IN LINK GOES

- ⚠️ ADMIN 04 gives a new founder a set-password link. **Find out how that link reaches the founder, and how long it stays valid.**
- ⚠️⚠️ **If the console ever shows the link, anyone with console access can take over the account and act inside the hotel with full write access.** Break-glass was built to prevent exactly that: read-only, 30 minutes, audited.
  - ⚠️ The link is only ever emailed. It is never shown on screen and never logged anywhere it could be used.
  - ⚠️ If the founder's email already has an account, the link must not reset that person's existing password.
- ⚠️ **The mailer was `log` until the email pass switched mail on. Whole emails, links included, may be sitting in a log file on the server.** ⚠️ Find out:
  - which file it is,
  - its mode,
  - whether it can be reached from the web, and
  - **how many links in it are still valid. Count them; never print one.**
  - ⚠️ Every organisation is internal, so if any link is still valid, invalidate it and say how.
- ⚠️ Report what you find. Fix whatever needs it, with tests and mutations.

## ⚠️ #6 — THE THREE LAPTOP-ONLY CHECK SCRIPTS

- ⚠️ Say which three they are, and why they exist only on the laptop.
- ⚠️ **The server's copies must come from git through the deploy, never a hand copy, because a hand copy drifts.**
- ⚠️ Run each one once on the server and report the result. Never report a secret value.

## ⚠️ #7 — GATES, AND WRITE THE RULE WHERE THE NEXT SESSION WILL READ IT

- ⚠️ **At the top of `docs/STRIPE-PRODUCTION.md`, add: "No live key until these refusals are live."** List each refusal with its test name. KEYBOARD 01 reads that line before touching live keys.
- ⚠️ **Run all the gates:**
  - full suite on MySQL **and** MariaDB;
  - PHPStan and Pint;
  - money gate FULL, unmoved;
  - mutations all land and are caught (report the new count);
  - deploy via L12k;
  - `.release` read, never typed.
- ⚠️ **Check the front door from outside:**
  - the Google and X redirects;
  - `/signup`;
  - anonymous `/admin/*` sends you to login;
  - a 5xx sweep;
  - `SERVED=0`;
  - no `.env*` files under the web root.

---

## ⚠️ DON'T OVER-REACH

- ⚠️⚠️ **Never delete an organisation on production. Never press delete. Delete never calls Stripe.**
- ⚠️ **Don't touch the mail settings or the email pass.** Don't change the social sign-in rules, the verify gate, `RefuseEmailTaken` or `throttle:5,1`.
- ⚠️ **Don't build self-service account deletion, automatic pruning, or comping and trial extensions.** Each of those is his ruling.
- ⚠️ **If any public check fails after a deploy, roll back first and diagnose second.**
- ⚠️ If a question needs his ruling, write it down and move on. Don't guess.

---

## CLOSE: THREE SENTENCES, IN PLAIN WORDS

1. **Can delete ever run while money is moving, or remove records the law may require?**
2. **What does delete leave behind (files, links), and what did you do about it?**
3. **What is still waiting on him?**

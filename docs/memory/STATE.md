# STATE — where Vyrobel stands right now

**Snapshot: 2026-09-27, 14:25–14:50 Baku (10:25–10:50 UTC). Written by MEMORY 01.** Labels: **V** = Verified in MEMORY 01 (how) ·
**R** = Reported (from an earlier record, not re-checked; source given) · **F** = Founder said.
⚠️ Memory is a claim, not a fact. Re-read anything you are about to rely on. Sources are listed at the bottom.

## Live
- **Live release `rel_AE` → commit `1ede4c63`**. **V:** `bash ops/release.sh status` reads `current/.release`.
- **GitHub `origin/main` = `91b83801`** (the ADMIN 05 docs commit on top of the live commit). The live commit is on GitHub.
  **V:** `git ls-remote origin`.
- **The memory is not on the host:** `docs/` and `CLAUDE.md` are absent there, and their URLs return 404 from outside.
  **V:** 10:4x UTC, checked both ways.
- **Live code vs git, byte for byte: 1,391 of 1,395 deployable files match git at the live commit.** The 4 that differ
  are all check files in `ops/`, none of which the app runs. Three are older hand copies: `exposure-battery.sh`,
  `htaccess-drift-check.sh` and `reference/public_html.htaccess`. `ps-check.sh` is missing. **V:** sha256 manifest checked
  on the host, 10:30 UTC.

## Numbers
| What | Value | Source |
|---|---|---|
| Suite on the **live** commit | 3,995/3,995 MySQL 8.4.10; delete tests 81/81 on MariaDB | **R** `docs/DECISIONS.md`, ADMIN 05 (2026-09-26) |
| Suite on the **working tree** (HEAD + uncommitted ADMIN 06) | **4,018/4,018 MySQL 8.4.10 · 4,018/4,018 MariaDB 11.8.9** · 28,984 assertions each | **R** this session's run output, 2026-09-27 (MariaDB done 05:40, MySQL done 14:12 Baku). Not in the repo. |
| Mutations (working tree) | **291 landed and caught**, 0 not caught, 0 did not land (after re-aiming 2) | **R** harness run, 2026-09-27 ~02:20 Baku |
| Money gate FULL (working tree) | `929 / 10928000 / 0 / 42 / 16 / 0` · digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297` | **R** `ops/money-as-seeded.sh` output, 2026-09-27 14:13 Baku. Tenant one only; local only. |
| PHPStan L5 · Pint | 0 errors · clean | **R** working tree, 2026-09-27 |
| Public checks / exposure battery | 12 PASS · 0 FAIL · 0 could not measure (includes `SERVED=0`) | **R** ADMIN 05 deploy (2026-09-26) |
| Customers | **0.** 15 organisations, all internal | **V** production read, 10:26 UTC |

## Switches on production (V: read-only production read, 10:26 UTC; yes/no only, never a value)
- **Mail:** runtime mailer **`log`**, so every email is discarded. `MAIL_PASSWORD` set: **no**. Mail host is Hostinger:
  yes. Postmark token set: no.
- **Stripe:** **fake client ON**. `STRIPE_SECRET` set: no. Both webhook secrets set: no. Live: **no**.
- **Google and X sign-in:** client id and secret set, for both. All five switches **ON**: `signup`, `signup.google`,
  `signup.twitter`, `signin.google`, `signin.twitter`.
- **Heartbeat alarm:** `OPS_HEARTBEAT_PING_URL` set: **no**, so it is unarmed.
- **Break-glass:** enabled, 30 minutes (`config/platform.php`).
- **Other settings:** maintenance mode off · config cached · queue `database` · sessions `database` · cache `file`.

## Organisations on production (V: 10:26 UTC — all 15 `active`, all internal)
⛔ **Never touch:** `Vyrobel Test Workspace` · `Vyrobel Internal — TEST TENANT (not a customer)`.
Probes, one line each, all "(not a customer)":
- `PUBLIC-01 PROBE`
- `ADMIN-P1` to `ADMIN-P5 PROBE`
- `ADMIN03-W1 PROBE`
- `ADMIN-03B PROBE`
- `SESSIONS-01 PROBE`
- `SESSIONS-01-FD PROBE`
- `TONIGHT01 PROBE`
- `TONIGHT01B PROBE`
- `PW-SIGNUP PROBE`

## ⚠️ IN FLIGHT — started, not finished
1. **ADMIN 06** is built and gated on the laptop, but **not committed, not pushed and not deployed.** The change
   sits in the working tree (`git status`), **only on this disk.**
   - Its second adversarial review (2026-09-27) had 19 findings: **4 confirmed**, 1 refuted, **14 not verified**. The
     breakdown is at the head of `OPEN.md`'s ADMIN 06 section.
   - **Next step (agent):** fix those; rerun all gates, one suite at a time and under `caffeinate`; commit; push; stage
     `rel_AF`; time the preflight on the host; swap; run the public checks and the three `ops/` scripts on the server.
   - Brief: `briefs/ADMIN-06.md`. Needs nothing from the founder.
2. **PW-SIGNUP PROBE permanent delete** has **not started**.
   - **Not suspended.** **V:** `status = active`, 10:26 UTC.
   - **Backup exists (V):** `vyrobel-db-before-delete-pw-signup-probe-20260926T190414Z.sql.gz`, 87,968 bytes, mode 600,
     in `~/domains/vyrobel.com/env-backups/` (folder 700).
   - **Baselines there (V):**
     - `census-1-before.json`, 2026-09-26 19:04 UTC;
     - `footprint-1-before.json` and `footprint-ids.json`, 19:04;
     - `proof-trial/proof-before-20260926T193126Z.json` and `proof-trial/proof-ids.json`, 19:31. The ids files hold the
       probe's address; delete them after the final after-run.
   - The tools are in `tools/` (identical to the server copies).
   - **The preview said 17 records in 5 tables; the delete will remove 232.** That 232 is a pre-count on production
     (R, 2026-09-27); nothing has been deleted. The 232 are: 13 roles, 212 role permissions, 1 property, 1 subscription, 1 membership, 1 role link, the organisation row,
     the founder's account and its sign-in row.
   - **After-checks done: none.**
   - Best done **after ADMIN 06 is live**, so the preview shows the full count.
   - ⚠️ **Retake the backup and the before-baselines right before his click.** The 26 September ones predate ADMIN 06's
     migration.
   - The brief's "money gate numbers" means `census.php`'s money sums on production. The money gate itself is local
     only.
3. **Email switch-over (TONIGHT 01 Part 2 / MAIL 02)** is waiting on `MAIL_PASSWORD`.
4. **`sales@` alias**: created in hPanel on 2026-09-26 (R), delivery **not yet proven**.

## Waiting on the founder, in order
1. Change the exposed `contact@` password in hPanel (it was pasted into a chat), then put the new one in `.env` as
   `MAIL_PASSWORD`. This unblocks the email pass.
2. Copy `APP_KEY` into his password manager, off the server. Losing it makes every encrypted guest record unreadable.
3. After ADMIN 06 is live: suspend, then delete, PW-SIGNUP PROBE with his password. The agent runs the before/after
   proof.
4. KEYBOARD 01, at the keyboard (`briefs/KEYBOARD-01.md`). Its own rule is to run after TONIGHT 01 has closed, and its
   Stripe job waits for ADMIN 06 to be live.
5. Stripe live: keys, both webhook secrets, Connect verification. ⛔ Not before ADMIN 06 is live, B20 is settled, and
   payout confirmation is proven with one test-mode transfer (`OPEN.md` F5).
6. The lawyer: the Article 28 agreements, who may hold customers' money, and how long money records must be kept.
7. The real tax rates (`tax:set-rate`, replacing the EXAMPLE rates).
8. Optional: one email to `sales@` to prove the alias.

**Which runs next.** If nothing changes, **ADMIN 06's finish (`OPEN.md` A1)** runs next; it needs nothing from him. **If he
puts `MAIL_PASSWORD` in first, he picks the order.** The email pass (A2) could run first, but **only one session deploys
at a time, and ADMIN 06 must not start while the email pass runs** (`briefs/ADMIN-06.md`). Ask him; don't choose.

---
**Sources:**
- `bash ops/release.sh status` and `git ls-remote origin` (10:2x UTC).
- The host sha256 check (10:30 UTC).
- A read-only production read of runtime config, booleans only (10:26 UTC): `tools/prodstate.php.txt`, run with
  `php artisan tinker` inside the live release. It prints yes/no, names and counts, never a value.
- A listing of the private folder: names, sizes and modes only.
- `docs/DECISIONS.md`, the ADMIN 05 and ADMIN 06 entries.
- This session's run outputs (suite, harness, gate). They are **not** committed; the next record of them is the
  ADMIN 06 commit.

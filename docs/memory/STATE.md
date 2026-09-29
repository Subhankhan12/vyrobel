# STATE — where Vyrobel stands right now

**Snapshot: 2026-09-29, 13:36 Baku. Written by the ADMIN 06 FINISH pass** (first written 2026-09-27 by MEMORY 01).
Labels: **V** = Verified (how and when) · **R** = Reported (from an earlier record, not re-checked; source given) ·
**F** = Founder said. ⚠️ Memory is a claim, not a fact. Re-read anything you are about to rely on. Sources at the bottom.

## Live
- **Live release `rel_AE` → commit `1ede4c63`** (ADMIN 05). **V:** `readlink current` + `cat current/.release` over SSH,
  2026-09-29 11:38 Baku.
- **GitHub `origin/main` = `58800419`** (the MEMORY 01 commit). **V:** `git ls-remote origin`, 2026-09-29.
- ⚠️ **THIS COMMIT carries ADMIN 06, finished** (review 2 and review 3 fixed, every gate green). It is pushed and deployed
  as `rel_AF` straight after this commit; **the docs commit that follows records whether it went live.** If no such
  commit exists, the deploy did not finish — read `.release` on the host, never this line.
- **Live code vs git, byte for byte: 1,391 of 1,395 deployable files match git at the live commit** (V 2026-09-27). The 4
  that differ are `ops/` check files the app never runs; `release.sh stage` ships them from git at the next deploy.
- **The memory is not on the host:** `docs/` and `CLAUDE.md` are not deployed (V 2026-09-27).

## Numbers
| What | Value | Source |
|---|---|---|
| Suite on the ADMIN 06 finish tree (= this commit's code) | **4,033/4,033 on MySQL 8.4.10 · 4,033/4,033 on MariaDB 11.8.9** · 29,110 assertions each · 0 skipped | **V** run output 2026-09-29 (MariaDB 12:46–13:03, MySQL 13:04–13:19 Baku), both on tree fingerprint `8f8813b4fac362d8` |
| Mutations | **323/323 landed and caught across every set**, 0 not caught, 0 did not land (`admin-06` = 87, `admin-05` = 49) | **V** harness output 2026-09-29 |
| Money gate FULL | `929 / 10928000 / 0 / 42 / 16 / 0` · digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297` · `mode=FULL` ✅ | **V** `ops/money-as-seeded.sh` output 2026-09-29. Tenant one only; local only. |
| PHPStan L5 · Pint | 0 errors · clean | **V** 2026-09-29 |
| Public checks / exposure battery | 12 PASS · 0 FAIL · 0 could not measure (includes `SERVED=0`) — on `rel_AE`, 2026-09-27 16:0x Baku; re-run after the ADMIN 06 swap | **V** `ops/public-checks.sh` 2026-09-29 |
| Customers | **0.** 15 organisations, all internal | **V** production read, 2026-09-29 |

## Switches on production (V: read-only production read, 2026-09-27 15:5x Baku, re-read re-read after the ADMIN 06 deploy in the docs commit that follows; yes/no only, never a value)
- **Mail:** runtime mailer **`log`**, so every email is discarded. `MAIL_PASSWORD` set: **no**. Mail host is Hostinger:
  yes. Postmark token set: no.
- **Stripe:** **fake client ON**. `STRIPE_SECRET` set: no. Both webhook secrets set: no. Live: **no**.
- **Google and X sign-in:** client id and secret set, for both. All five switches **ON**: `signup`, `signup.google`,
  `signup.twitter`, `signin.google`, `signin.twitter`.
- **Heartbeat alarm:** `OPS_HEARTBEAT_PING_URL` set: **no**, so it is unarmed.
- **Break-glass:** enabled, 30 minutes. 0 sessions have ever been opened on production (V 2026-09-27).
- **Other settings:** maintenance mode off · config cached · queue `database` · sessions `database` · cache `file`.
- **Loose ends (V 2026-09-27):** 0 failed jobs · 0 suspended organisations · 0 break-glass sessions open · 1 admin, 0 invites
  pending · 0 announcements · 0 `/status` incidents.

## Organisations on production (V: all 15 `active`, all internal)
⛔ **Never touch:** `Vyrobel Test Workspace` · `Vyrobel Internal — TEST TENANT (not a customer)`.
Probes, one line each, all "(not a customer)": `PUBLIC-01 PROBE` · `ADMIN-P1` to `ADMIN-P5 PROBE` · `ADMIN03-W1 PROBE` ·
`ADMIN-03B PROBE` · `SESSIONS-01 PROBE` · `SESSIONS-01-FD PROBE` · `TONIGHT01 PROBE` · `TONIGHT01B PROBE` · `PW-SIGNUP PROBE`.

## ⚠️ IN FLIGHT — started, not finished
1. **ADMIN 06 deploy** — committed in this commit; next: push (the four hard stops), `release.sh stage rel_AF`, `migrate`
   (additive), a read-only preflight timing the delete preview on production's MariaDB, `swap`, `ops/public-checks.sh`,
   then on the server `ops/htaccess-drift-check.sh` and `ops/exposure-battery.sh`, the 775 log to 644, and no stray `.env*`.
   The outcome is in the docs commit that follows.
2. **PW-SIGNUP PROBE permanent delete** has **not started**. **Not suspended** (V 2026-09-29, `status = active`).
   - Backup exists (V 2026-09-27): `vyrobel-db-before-delete-pw-signup-probe-20260926T190414Z.sql.gz`, 87,968 bytes, mode 600,
     in `~/domains/vyrobel.com/env-backups/` (folder 700), with the 26 September baselines beside it.
   - ⚠️ **Retake the backup and the before-baselines right before his click** — the 26 September ones predate ADMIN 06's
     migration. The preview will count every row (232 was the pre-count, R 2026-09-27).
   - After-checks done: none.
3. **Email switch-over (TONIGHT 01 Part 2 / MAIL 02)** is waiting on `MAIL_PASSWORD` (V 2026-09-27: not set).
4. **`sales@` alias**: created in hPanel on 2026-09-26 (R), delivery **not yet proven**.

## Waiting on the founder, in order
1. Change the exposed `contact@` password in hPanel (it was pasted into a chat), then put the new one in `.env` as
   `MAIL_PASSWORD`. This unblocks the email pass.
2. Copy `APP_KEY` into his password manager, off the server. Losing it makes every encrypted guest record unreadable.
3. Once ADMIN 06 is live: suspend, then delete, PW-SIGNUP PROBE with his password. The agent retakes the backup and
   baselines right before, and runs the before/after proof (`OPEN.md` A3).
4. KEYBOARD 01, at the keyboard (`briefs/KEYBOARD-01.md`). Its own rule is to run after TONIGHT 01 has closed.
5. Stripe live: keys, both webhook secrets, Connect verification. ⛔ Not before ADMIN 06 is live, B20 is settled, and
   payout confirmation is proven with one test-mode transfer (`OPEN.md` D-TRANSFER).
6. The lawyer: the Article 28 agreements, who may hold customers' money, and how long money records must be kept.
7. The real tax rates (`tax:set-rate`, replacing the EXAMPLE rates).
8. Rulings DEC-a to DEC-g (`OPEN.md`). DEC-f decides whether the logo removal that shipped stays.
9. Optional: one email to `sales@` to prove the alias.

**Which runs next.** Once ADMIN 06 is live, nothing else can run without him: the email pass waits on `MAIL_PASSWORD`, the
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

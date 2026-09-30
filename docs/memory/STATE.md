# STATE — where Vyrobel stands right now

**Snapshot: 2026-09-29, live state read 13:41–13:55 Baku; updated by each pass since — last by the MAINTENANCE pass (2026-09-30)** (first written 2026-09-27 by MEMORY 01).
Labels: **V** = Verified (how and when) · **R** = Reported (from an earlier record, not re-checked; source given) ·
**F** = Founder said. ⚠️ Memory is a claim, not a fact. Re-read anything you are about to rely on. Sources at the bottom.

## Live
- **Live release `rel_AJ` → commit `a3604b9c`** (the Maintenance rebuild, on top of the Guests rebuild, Portfolio Open +
  fields, the Portfolio remove and ADMIN 06). **V:** `ops/release.sh swap` output + `cat current/.release` over SSH after the
  swap, 2026-09-30 12:22 Baku. Rollback targets: `rel_AI @ 9f704a3a` (Guests), `rel_AH @ 3b012e93` (Portfolio Open + fields),
  `rel_AG @ e4117aeb` (Portfolio remove), `rel_AF @ 4c17edd2` (ADMIN 06) — `ops/release.sh rollback rel_<X>`.
- **GitHub `origin/main`** carries `a3604b9c` and the docs commit that records this deploy (**V** `git ls-remote origin`).
- **Live code vs git, byte for byte: 1,409 of 1,409 deployable files match git at `a3604b9c`.** **V** 2026-09-30 12:29 Baku,
  sha256 manifest checked inside the live release.
- **ADMIN 06's refusals are LIVE** — the first of the three conditions before any live Stripe key (`OPEN.md` F5).
- **The memory is not on the host:** `docs/` and `CLAUDE.md` are not deployed.

## Numbers
| What | Value | Source |
|---|---|---|
| Suite (the Maintenance commit) | **4,087/4,087 on MySQL 8.4.10** (tree `3ac61b96bc916ce4`, 2026-09-30 01:14–01:39 Baku) · **4,087/4,087 on MariaDB 11.8.9** (the same tree, 10:57–11:32 Baku) · the final run **4,087/4,087 on MySQL 8.4.10** (tree `4d9f12e34c4bdba0` = that code plus these docs, 11:35–12:16 Baku) · 29,794 assertions · every test passed (0 skipped) | **V** run output 2026-09-30. |
| Mutations | **393/393 landed and caught across every set**, 0 not caught, 0 did not land; baseline 209/209 green (`admin-06` = 87, `admin-05` = 49, `maintenance-board` = 26, `guests-list` = 24, `portfolio-remove` = 14, `portfolio-fields` = 6) | **V** harness output 2026-09-30 10:24–10:55 Baku, tree `3ac61b96bc916ce4` |
| Money gate FULL | `929 / 10928000 / 0 / 42 / 16 / 0` · digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297` · `mode=FULL` ✅ | **V** `ops/money-as-seeded.sh` output 2026-09-30 11:33 Baku (the Maintenance pass). Tenant one only; local only. |
| PHPStan L5 · Pint | 0 errors · clean | **V** 2026-09-30 |
| Public checks / exposure battery | **12 PASS · 0 FAIL · 0 could not measure** (`SERVED=0`) on `rel_AJ`, 2026-09-30, run straight after the 12:22 Baku swap (and on `rel_AI`, `rel_AH`, `rel_AG`, `rel_AF` on 2026-09-29); drift check passes, self-test 3/3 (V 12:29) | **V** `ops/public-checks.sh` 2026-09-30 |
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
- **Loose ends (V 2026-09-29, after the `rel_AF` deploy; errors re-read after `rel_AJ`: 0 logged since its swap, V 2026-09-30 12:29 Baku):** 0 failed jobs · 0 errors logged since the swap · 0 suspended organisations · 0 break-glass sessions open · 1 admin, 0 invites
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
8b. Say go (or not) on A8, the x-show sweep of 39 elements on other screens; A9, the other see-through dialogs; and A10,
    the string `:style` sweep of 10 elements. Rule on DEC-j (the property allow-list on the maintenance writes).
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

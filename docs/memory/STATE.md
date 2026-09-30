# STATE — where Vyrobel stands right now

**Snapshot: 2026-09-29, live state read 13:41–13:55 Baku; updated by each pass since — last by the RATES pass (2026-10-01)** (first written 2026-09-27 by MEMORY 01).
Labels: **V** = Verified (how and when) · **R** = Reported (from an earlier record, not re-checked; source given) ·
**F** = Founder said. ⚠️ Memory is a claim, not a fact. Re-read anything you are about to rely on. Sources at the bottom.

## Live
- **Live release `rel_AP` → commit `9b9b80c8`** (the Rates rebuild with DEC-l, `rel_AO @ cac9e677`, plus the two fixes its
  live look found — on top of DEC-m, photos at `/media`, DEC-k, the Units, Maintenance and Guests rebuilds, Portfolio Open +
  fields, the Portfolio remove and ADMIN 06). **V:** `ops/release.sh swap` output (`.release = 9b9b80c8…`), 2026-10-01
  03:21 Baku. Rollback targets: `rel_AO @ cac9e677` (Rates, before the two fixes), `rel_AN @ 761cf2dd` (DEC-m),
  `rel_AM @ 9e9b1193` (photos), `rel_AL @ 8cda8c7f` (DEC-k), `rel_AK @ a4e2b6e5` (Units), `rel_AJ @ a3604b9c` (Maintenance), `rel_AI @ 9f704a3a` (Guests), `rel_AH @ 3b012e93` (Portfolio Open +
  fields), `rel_AG @ e4117aeb` (Portfolio remove), `rel_AF @ 4c17edd2` (ADMIN 06) — `ops/release.sh rollback rel_<X>`.
  ⚠️ A rollback to `rel_AL` or earlier puts photo addresses back on `/storage` — every photo 404s again.
- **GitHub `origin/main`** carries `9b9b80c8` and the docs commit that records this deploy (**V** `git ls-remote origin`).
- **Live code vs git, byte for byte: 1,434 of 1,434 files in the live release match git at `9b9b80c8`, 0 differ** (the other
  1,200 git files — tests, docs, tools, screenshots — are never deployed). **V** 2026-10-01 03:23 Baku, sha256 manifest checked
  inside the live release.
- **ADMIN 06's refusals are LIVE** — the first of the three conditions before any live Stripe key (`OPEN.md` F5).
- **The memory is not on the host:** `docs/` and `CLAUDE.md` are not deployed.

## Numbers
| What | Value | Source |
|---|---|---|
| Suite (the RATES follow-up commit) | **4,148/4,148 on MySQL 8.4.10** (tree `0f40aa8b2640cd26` = the code plus these docs, 2026-10-01 02:31–02:46 Baku) · **4,148/4,148 on MariaDB 11.8.9** (the same tree, 03:03–03:18 Baku) · 30,552 assertions · every test passed (0 skipped). The RATES commit before it (`cac9e677`): 4,146/4,146 on both engines, tree `abc822f5672e411a` | **V** run output 2026-10-01. |
| Mutations | **488/488 landed and caught across every set**, 0 not caught, 0 did not land; baseline 262/262 green (`admin-06` = 87, `admin-05` = 49, `rates-page` = 49, `units-list` = 33, `admin-p1` = 31, `maintenance-board` = 26, `guests-list` = 24, `message-attachments` = 7, `public-media` = 6, and 12 older sets) | **V** harness output 2026-10-01 02:47–03:03 Baku, tree `0f40aa8b2640cd26` |
| Money gate FULL | `929 / 10928000 / 0 / 42 / 16 / 0` · digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297` · `mode=FULL` ✅ | **V** `ops/money-as-seeded.sh` output 2026-10-01 03:19 Baku (the RATES follow-up; reseeded 03:19; also 02:07 for `cac9e677`). Tenant one only; local only. |
| PHPStan L5 · Pint | 0 errors · clean | **V** 2026-10-01 |
| Public checks / exposure battery | **12 PASS · 0 FAIL · 0 could not measure** (`SERVED=0`) on `rel_AP`, 2026-10-01, run straight after the 03:21 Baku swap (and on `rel_AO` at 02:10 the same day, on `rel_AN` at 21:59 on 2026-09-30, `rel_AM` at 20:42, `rel_AL` at 19:13, `rel_AK` at 17:29 and `rel_AJ` at 12:22 the same day; on `rel_AI`, `rel_AH`, `rel_AG`, `rel_AF` on 2026-09-29); drift check passes, self-test 3/3 (V 2026-10-01 03:23) | **V** `ops/public-checks.sh` 2026-10-01 |
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
- **Loose ends (V 2026-09-29, after the `rel_AF` deploy; errors re-read after `rel_AP`: 0 logged since its swap, V 2026-10-01 03:24 Baku):** 0 failed jobs · 0 errors logged since the swap · 0 suspended organisations · 0 break-glass sessions open · 1 admin, 0 invites
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
8b. Say go (or not) on A8, the x-show sweep of 26 elements on other screens; A9, the other see-through dialogs; and A10,
    the string `:style` sweep of 10 elements. Rule on DEC-j (the property allow-list on the maintenance writes; DEC-k, the
    same on Add unit, was ruled and fixed 2026-09-30, and DEC-l, on every rate write, 2026-10-01). DEC-m (message
    attachments) was ruled and fixed 2026-09-30.
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

# STATE — where Vyrobel stands right now

**Snapshot: 2026-09-29, live state read 13:41–13:55 Baku; updated by each pass since — last by the REPOSITORY-AND-RULINGS session (2026-11-14: F14 ruled, the commits pushed, DEC-2 and the row buttons ruled; no code, no deploy); before it the EIGHT DECISIONS pass (2026-11-13: DEC-1…DEC-8 ruled and built, live on `rel_CO`); before it the REMAINING-ITEMS pass (2026-11-12: the "found, not fixed" items that need no ruling of his, live on `rel_CN`); before it the THREE DECISIONS pass (2026-11-12: DEC-h/o/p/r/s/t ruled and built, live on `rel_CM`); before it the SECOND OPEN LIST pass (2026-11-11: the "code I can do on your go" list, live on `rel_CK`); before it the OPEN LIST pass (2026-11-11: ~40 open items closed, live on `rel_CJ`); before it I18N-01 (2026-11-02: Russian live on `rel_CG`); before it I18N-01 (2026-11-02: Brazilian Portuguese live on `rel_CF`); before it I18N-01 (2026-11-02: Italian live on `rel_CE`); before it I18N-01 (2026-11-02: Portuguese live on `rel_CD`); before it I18N-01 (2026-11-02: Dutch live on `rel_CC`); before it I18N-01 (2026-11-02: Spanish live on `rel_CB`); before it I18N-01 (2026-11-02: French live on `rel_CA`, the owner welcome page keyed); before it I18N-01 (2026-11-02: Turkish live on `rel_BZ`, with the date-order and number-field fixes); before it I18N-01 at ⛔ G3 (2026-10-29: German + Arabic live on `rel_BY`); before it the SECURITY REDESIGN pass (2026-10-24/25: Parts 1+2, then Part 3 of his approved plan); before it the SIGNUP PASSWORD SHOW/HIDE pass (2026-10-21); before it the INBOX COMPOSER REFINEMENTS (2026-10-21); before it the INBOX COMPOSER RESTYLE (2026-10-21); before it the INBOX THREAD-ACTIONS FIX (2026-10-21); before it the INBOX DESIGN POLISH pass (2026-10-21); before it the LOGIN PASSWORD SHOW/HIDE pass (2026-10-21); before it the SEARCH 500 HOTFIX (2026-10-21); before it the SEARCH DATE PICKER pass (2026-10-21); before it the SECURITY PASSWORD SHOW/HIDE pass (2026-10-21); before it the INSIGHTS DATE PICKER OVERLAP FIX (2026-10-21); before it the INSIGHTS DATE PICKER pass (2026-10-21); before it the CALENDAR ROOM-TYPE FILTER pass (2026-10-21); before it the FAVICON pass (2026-10-21); before it the MEMORY AND GITHUB pass (2026-10-10); before it THE ONE SELECT ENGINE pass (2026-10-10); before it THE VYROBEL SELECT pass (2026-10-10); before it the TOP BAR BELL pass (2026-10-09); before it the OWNER TOP BAR follow-up (2026-10-09); before it the SIDEBAR POSITION pass (2026-10-09); before it the ROLE SIGN-IN pass (2026-10-09); before it the GitHub and memory pass (2026-10-06), after the team chat pass went live** (first written 2026-09-27 by MEMORY 01).
Labels: **V** = Verified (how and when) · **R** = Reported (from an earlier record, not re-checked; source given) ·
**F** = Founder said. ⚠️ Memory is a claim, not a fact. Re-read anything you are about to rely on. Sources at the bottom.

## ✅ 2026-11-14 — F14 RULED, THE COMMITS PUSHED, THREE RULINGS RECORDED (a session with no code and no deploy; see HISTORY, "F14 — THE PUBLIC REPOSITORY")
- **Founder said (2026-11-14):** (1) the GitHub repository **stays public for a while** — "you can keep updating, not a problem. Later on we will make it private" (`briefs/F14-2026-11-14.md`); (2) **D-DEC5-ROW-BUTTONS:** leave the suspend/delete buttons on the customer's page; a row Suspend only if it gets tiring; **never a Delete on a row**; (3) **DEC-2 confirmed:** the public price is **per unit**, and tax rates are **set when the first customer arrives** ("Lets do this"). ⚠️ **Not ruled:** the founding rate's terms and my "rate before the first real charge" guardrail (my add-ons); the other four waiting items — the complimentary count, the retention list, the mail rotation's hand-sending gap, the soft deletion wording — were explained to him with a recommendation each and are waiting for his word (`OPEN.md` D-COMP-NOT-ON-OVERVIEW, D-DEC4-REGISTRY-NEEDS-A-COMMIT, D-MAIL-HUMAN-SENDS, D-DEC6-LEGAL-WORDING).
- ✅ **PUSHED 2026-11-14 — F14 RULED (his word).** `Subhankhan12/vyrobel` is PUBLIC (V 2026-11-14: `private: false`, `visibility: public`, `updated_at` 2026-11-13 21:44 UTC; who changed it is not known from the record) and he ruled it **stays public for a while** — "you can keep updating, not a problem. Later on we will make it private" (`briefs/F14-2026-11-14.md`). Hard stop 1 is waived while that stands (`RULES.md` Part 1). Taken on the 4 commits: stop 2 passed (`Subhankhan12`) · stop 3 passed (`origin/main` `31ad827e` is an ancestor of HEAD: a fast-forward) · stop 4 read — 819 hits in 63 locations (45 blobs), the same figures as the earlier read; the 3 blobs new to this range are translation key names (`lang/.applied/ar.json`) and a Blade prop name (`:has-password=` in both Security pages), none a credential; plus my own check of the 1,169 added lines outside `lang/` and `tests/`: 0 IP addresses, 0 key or token shapes, only `vyrobel.com` / `example.com` addresses. Pushed `31ad827e..ddcf7dd2` (V: `git ls-remote` = `ddcf7dd2`). **CI on `ddcf7dd2`:** run 43547242659 completed **success** (V: `gh run view`, 2026-11-14 02:36 → 03:19 UTC, 43 min) — job Parity image success, job Gates success. Production is unchanged (`rel_CO @ a01bf072`). Still his: make it private later; optionally a new production SSH key. See `OPEN.md` F14.
- **V — GitHub CI (read with `gh run view`):** `ddcf7dd2` run 43547242659 **success** (03:19 UTC) · `ba97b021` run 43551241199 **success** (04:04 UTC) · `ef2ce57e` run 43554966562 **cancelled** at 04:18 UTC (Parity image success; Gates cancelled at Gate 3) — **cancelled by my next push, not failed**: the workflow supersedes an in-flight run on the same branch (`concurrency: cancel-in-progress: true`, `.github/workflows`), so its content is covered only by the run on the commit after it · `6f5bc84e` run 43556000079 **cancelled** by the push of `ae99d3c9` · **`ae99d3c9` run 43556143462 completed SUCCESS** (V: `gh run view`, 04:20 → 04:56 UTC, Parity image success, Gates success) — it contains `ef2ce57e` and `6f5bc84e`, so every pushed commit is covered by a passing run. Local commit `6fb51d8d` (the five founder decisions) is NOT pushed yet — push once, at the end of the sitting (`RULES.md` Part 2d).
- **Still his:** make the repository private later (tell a session the same day — then hard stop 1 applies again) · optionally a new production SSH key · F13/F1 (mail) · the real prices, Price IDs and tax rates (F5, F7) · the lawyer (F6) · the four waiting items above · the founding rate's terms. **Production unchanged: `rel_CO @ a01bf072`.**

## ✅ SHIPPED 2026-11-13 — the EIGHT DECISIONS pass, live on `rel_CO @ a01bf072` (see HISTORY, "THE EIGHT DECISIONS"; the rows DEC-1…DEC-8 at the foot of OPEN.md)
- **Founder said (2026-11-13):** he asked for DEC-1…DEC-8 to be explained with options; then ruled — **DEC-1** keep Hostinger's own daily limit; the free mail plan ending 2 Apr 2027 "I will extend it" (his); and send from **two mailboxes** (contact@ and sales@): when one reaches its daily limit, use the other, and go back to the first when its limit frees · **DEC-2** "pick A … whatever you have selected" (read as: my picks — a per-unit public price with a founding rate; tax rates unconfigured until the first customer, then the first customers' countries with the accountant — ✅ confirmed by him 2026-11-14: per unit, and tax rates when the first customer arrives; the founding rate's terms are not ruled, see OPEN D-DEC2-READING) · **DEC-3 A** a password change or reset cuts off every connected app · **DEC-4 C** three buckets · **DEC-5 B now, C later** · **DEC-6 C** (a request button; not B) · **DEC-7 C** extend a trial + a complimentary plan · **DEC-8 A** keep the other test organisations ("I will check later"). Then: "go ahead, test everything, deploy everything live, fix everything … update the entire memory and make sure the GitHub is updated."
- **V — live now:** `rel_CO` → `a01bf0720a4218e496a5ac85fa42a69cd56f41ff` (read from the server's `.release` after the swap). Rollback target: **`rel_CN @ 376c362b`**. Two ADDITIVE migrations ran from the staged release before the swap: `subscriptions.complimentary_until` (nullable timestamp) and `mail_send_log.mailbox` (nullable string + an index). Public checks 12/0/0 before and 12/0/0 after the swap.
- **V — gates on the final code (`a01bf072`; docs commits follow):** MySQL 8.4.10 **4,903 tests, 38,682 assertions, 0 failed · 0 errors · 0 skipped** (JUnit) · MariaDB 11.8.9 **4,903 tests, 38,682 assertions, 0 failed · 0 errors · 0 skipped** (JUnit) · PHPStan L5 0 · Pint clean · `lang:check` OK (10,710 keys, 27 languages) · `lang:todo` 0 new/0 changed/0 hardcoded/0 removed · money gate **"✅ MONEY AS SEEDED · FULL"** (digest `a0e1e01d…` unchanged; tenant one's four covered tables only, as the gate says) · `render-all` German and Arabic 940 pages each, 0 problems · mutation sets: `decisions-nov13` 41/41 and the 22 older sets that aim at files this pass changed 614/614 (655/655 in all, every one caught). ⚠️ MySQL's FIRST whole run read 4,900 passed / 3 failed — the new routes had no "no reason" fixture in `PlatformAuditLedgerTest`, the new read had no READ classification, and two older mutations (A03-M2, MGS-M2) were aimed at lines this pass edited; all three fixed (the read now reuses the existing `platform.customer.current_plan`), the files re-run green, and then the WHOLE suite re-run on the committed code on both engines.
- **Founder-visible changes to say plainly:** (1) **DEC-3** — changing or resetting a password now also cuts off every app the person had connected (their API access ends at once; the audit row records how many, never which); (2) **DEC-7** — on a customer's page in the console: **Extend the trial** (1–60 days, a trial never ends more than 90 days from today, his password + a written reason) and **Complimentary plan** (free until an end date between tomorrow and a year ahead; his password + a reason; never for a workspace billed through Stripe; **End it now** sends it to the paywall at once). A complimentary workspace tells its own staff "Complimentary — no payment needed, until <date>", and is not counted as a trial or as revenue; (3) **DEC-5** — the console's triage page has a fourth list, "Abandoned signups (30+ days)": unverified for 30+ days AND nobody ever signed in. It only lists; deleting is still his, on the customer's page; (4) **DEC-6** — every staff member and owner (and owner-team member) has a "Delete my account → Request deletion" card on their Security page: it files a request on the Privacy desk (due in one month, channel "In the app") and erases **nothing**; (5) **DEC-4** — the Privacy desk's retention card shows three buckets (pruned by a timer / kept on purpose / no rule yet), counted from the inventory, and no longer says it awaits a ruling; (6) **DEC-1** — the app can send from two mailboxes (`MAIL_MAILER=rotating`) — ⚠️ **BUILT, NOT SWITCHED ON**: production's mailer is still `log`. Switching on is his (see OPEN F13); (7) the Privacy desk's own "how each request is fulfilled" lines no longer say there is no staff export / no account deletion.
- **Translations:** 58 new + 3 changed texts × 27 languages (batches 044/045), machine translation with a translator agent per language group and `check.php`; no second reviewer, no native check. Seven translator agents were stopped by the account's 5-hour usage limit mid-way and resumed after it reset — nothing was lost (parts are saved on disk).
- **Not tested live — needs his sign-in:** every console page this pass touched (the customer page's new forms, the triage bucket, the Privacy desk card and the health card) and both Security pages' new card. Tested instead by 4,903 tests on both engines, by 46 mutations (set `decisions-nov13`), locally in a browser where noted below, and anonymously on the live site.
- **V — re-verified 2026-11-14 06:2x Baku, from the systems themselves:** production 217 migrations ran / 0 pending (both new ones in batch 17) · live `rel_CO @ a01bf072` · 1,564 of 1,566 deployable files + **all 17,277 `lang/` files** identical to git on the server, 0 differ · public checks 12/0/0 · 0 error-level log lines on 13 and 14 Nov · nothing deployable changed after the live commit (the 3 commits after it are documents) · **seen in a real local browser (tenant three, disposable):** the staff Security page's "Delete my account" card, its dialog, and the end-to-end request (the card then reads "We received your request on 14 Nov 2026 … answer by 14 Dec 2026"). ⚠️ NOT seen in a browser: the owner Security page (tenant three has no owner login and tenant one must not be touched — covered by tests and the render pass), every console page.
- ⚠️ **DEC-1 is code only:** the rotation was proven with in-memory transports (the suite never sends a real email, by design). No real message was sent from either mailbox and none will be until he switches it on.

## ✅ SHIPPED 2026-11-12 — the REMAINING-ITEMS pass, live on `rel_CN @ 376c362b` (see HISTORY, "THE REMAINING-ITEMS PASS"; the closed items at the head of OPEN.md "Known defects")
- **Founder said (2026-11-12):** he pasted the status report that closed the three-decisions pass and wrote "everything that is remaining from these … complete all of these and then work on these". ⚠️ **The second "these" named nothing** — the pass did the first list (the repo's own "found, not fixed" items that need no password, secret, live key or ruling) and says so. He answered "Yes, swap (Recommended)" to the engine-swap question.
- **V — his report verified first:** `git ls-remote` = local = `7fbb741b`; `ops/release.sh status` = `rel_CM @ f042e759`.
- **V — live now:** `rel_CN` → `376c362b` (read from the server's `.release` after the swap, 4 ms; no migration — `pending migrations: none`). Rollback target: **`rel_CM @ f042e759`**. Public checks **12/0/0 before and 12/0/0 after**; 0 error-level lines in the host log since; the staged release's read-only preflight (no database): PHP 8.4.27, 310 views compile AND parse, the new pieces load, the 3 new/changed texts present in all 28 language folders. **V live, anonymous:** `GET /book/<unknown>/search` with `Accept: application/json` answers **404 `application/json`** `{"message":"Unknown property."}` (it was an HTML page); `/login?lang=de` 200 `<html lang="de">`.
- **V — gates on the final code (`376c362b`; docs commits follow):** MariaDB 11.8.9 **4,843 tests, 38,346 assertions, 0 failed · 0 errors · 0 skipped** (JUnit) · MySQL 8.4.10 first run **4,843 tests, 2 failed** — both mutation-set bookkeeping (`OL2-M4` re-anchor; 28 duplicate mutation ids), fixed and that file re-run green; ✅ **the whole MySQL suite was then re-run on the pushed commit `783c623b` (2026-11-13, 01:00–01:21 Baku): 4,843 tests, 38,346 assertions, 0 failed · 0 skipped (JUnit) — identical to MariaDB's count** (only `tests/Mutations` had changed since the first run) · PHPStan L5 0 · Pint clean · `lang:check` OK (10,656 keys) · `lang:todo` 0 new/0 changed/0 hardcoded/0 removed (27 languages; it takes 7½ min) · money gate **"✅ MONEY AS SEEDED · FULL"** (digest `a0e1e01d…` unchanged; tenant one's four covered tables only) · `render-all` Arabic and German 940 pages, 0 problems · mutation sets: owner-since 25/25, json-error-routes 4/4, hk-clock 3/3, invite-picker 3/3, owner-module 49/49, open-list-two 19/19, housekeeping-five 24/24, dec-p-unit-assignment 23/23, dec-o-portal-access 38/38, admin-p3 24/24, units-five 37/37, sessions-01 8/8, public-media 6/6, owners-page 41/41.
- **Founder-visible changes to say plainly:** (1) an owner given a unit on a later day now sees figures and screens for the nights from that day only — occupancy, rates, revenue windows, calendars, the Block Calendar's guest names, booking pages and PDFs, analytics and reviews — as the money already did (a unit with no start day reads exactly as before); (2) the public booking engine's machine endpoints answer a bad call as JSON (422 with the field errors, or a JSON 404), not a redirect or an HTML page; (3) Housekeeping's "started / done" clock is the property's own; (4) a unit promised on a waiting owner invitation is no longer offered by "Give a unit" (and is refused if asked for); (5) the Add owner dialog's hint says a unit can be given from the owner's page once they have accepted; (6) the agreement page says "who bears OTA costs and who receives cleaning fees and add-ons".
- **Translations:** 3 texts × 27 languages (batches 042/043), machine translation with AI review (translator + `check.php`), no second reviewer, no native check.
- **Not tested live (needs his sign-in):** every signed-in screen this pass touched — the owner pages with a unit given mid-period (production holds **0 owners**, so none could show a cut), the Housekeeping card, the Add owner dialog. Tested instead by 4,843 tests on MariaDB, by mutation, locally in a rendered 940 pages × 2 languages and anonymously on the live site as above.
- ✅ **Pushed to GitHub at his word** (2026-11-12 evening: "make sure that the GitHub is updated and resync"), under the four hard stops taken one at a time, as a fast-forward from `7fbb741b` (code `376c362b`, docs after it). ⚠️ This line was written just before the push: the commit is whatever `git ls-remote origin main` reads — believe git — and GitHub's checks for it are read after the push (a record of a run can only be written after it; the result is in the session report, not in this line). **V before it:** live `rel_CN @ 376c362b` read from `.release`; production **215 migrations ran, 0 pending** (the same 215 files as local, 0 pending); the only difference between what is live and `HEAD` is these four memory documents.
- ⚠️ **Left for him / open:** D-GIVE-UNIT-INVITED-OWNER (his question), the Inbox owner tab for a switched-off owner in a browser (`dop22` covers the HTML), DEC-s's real fix (F1 → A2), F12, D-PROD-DB-BLIP (diagnosed from the log, the database uptime NOT confirmed — the harness declined the read), D-SUBDISPUTE, the lawyer's items, and the second "these".

## ✅ SHIPPED 2026-11-12 — the THREE DECISIONS pass, live on `rel_CM @ f042e759` (see HISTORY, "THE THREE DECISIONS"; the open items at the head of OPEN.md "Known defects")
- **Founder said (2026-11-11/12):** DEC-h keep the Portfolio "Leaving" card · DEC-o operators may switch an owner's portal access off and on · DEC-p an existing owner may be given units later, with limits · DEC-r offer the existing guest when an email matches · DEC-s NO for now · DEC-t rule 8 names its second vantage point; then "Do the best of our work on all of this … ship it live, update the GitHub, and test everything via MCP Playwright … on the live server." He answered "Yes, swap now" to the engine-swap question and signed in himself in the browser for the signed-in looks.
- **V — live now:** `rel_CM` → `f042e759` (read from the server's `.release` after the swap, 2026-11-12 00:1x UTC, 4 ms); before it `rel_CL @ 35b38525` (this pass's first swap, 2026-11-11 ~22:17 UTC, which carried the migrations). Rollback target: **`rel_CL @ 35b38525`** (it is built for the new schema; `rel_CK @ b8f99ade` also still runs on it — both migrations are additive). Two ADDITIVE migrations ran from the staged `rel_CL` before its swap: `owners.portal_disabled_at` (nullable timestamp) and `units.owner_since` (nullable date). **V, the preflight:** production holds **0 owners** and 4 units with no owner — the switch and "Give a unit" have never run there.
- **V — gates on the final code (`f042e759`; docs commits follow):** MySQL 8.4.10 **4,807/4,807** (38,165 assertions) · MariaDB 11.8.9 **4,807/4,807** (38,165) · PHPStan L5 0 · Pint clean · `lang:check` OK (10,654 keys) · `lang:todo` 0 new/0 changed/0 hardcoded/0 removed · money gate **FULL** unmoved (`929 / 10928000 / 0 / 42 / 16 / 0`, the four row digests match — tenant one's four covered tables only, as the gate says) · `render-all` Arabic and German 940 pages, 0 problems · public checks 12 PASS / 0 FAIL / 0 could-not-measure before and after each swap · the host log clean since. Mutation sets: dec-r 10/10, dec-o 38/38, dec-p 23/23, open-list-two 19/19, owner-module 48/48, owners-page 41/41, calendar-new-reservation 33/33 and 27 more — all caught.
- **V — tested live with MCP Playwright, anonymously:** home `Organization` + `WebSite`, pricing `BreadcrumbList` + `FAQPage`, `/login?lang=` in 28 languages (no raw key; `dir="rtl"` on Arabic and Persian), the three new endpoints refusing a visitor (302 to sign-in with a token, 419 without), `/owner`, `/owner/security`, `/app/owners`, `/app/reservations/create` → sign-in. **Signed in (he signed in; read-only, nothing saved on production):** the wizard's guest step with the offer card shown by a stubbed answer, both buttons, the refusal text; real lookups for unknown/malformed/blank emails; the Owners page; five operator pages with the property's own day in the top bar; the Add owner dialog without the stale hint.
- **V — tested locally in a real browser (disposable tenant three):** Give a unit, the portal switch off/on, the owner locked out and back in, the list chip. ⚠️ **NOT tested anywhere in a browser:** the Inbox owner tab for a switched-off owner; ⚠️ **never run on production:** DEC-o and DEC-p's write paths (0 owners there).
- **Founder-visible changes to say plainly:** the New reservation wizard offers the guest who already has the typed email (name and history only) instead of creating them again; an operator can switch an owner's portal off and on from the owner's page (reason to switch off, password to switch on; the owner and their team see a plain page; nothing is deleted); an operator can give an existing owner a unit that has no owner, from a chosen day, with the money following that day; rule 8 now needs the second look before a rollback; the Add owner dialog no longer claims an invite is the only way to give units.
- **Translations:** 83 new texts in all 27 languages, machine translation with AI review (translator + `check.php`), no second reviewer, no native check.
- ⚠️ **Not pushed until the end of this pass; pushed at the end under the four hard stops** (the commit is what `git ls-remote origin main` reads — believe git, not this line).
- ⚠️ **Left for him / open (as of that pass — ✅ D-OWNERSINCE-WINDOWS, D-HINT-LATER and D-ATTRIBUTION-STANDARD-WORD were closed the next day, see the remaining-items section above):** D-OWNERSINCE-WINDOWS (the owner's occupancy/ADR/revenue figures are not cut at the day a unit was given — the largest), D-HINT-LATER, D-GIVE-UNIT-INVITED-OWNER (a question), DEC-s's real fix (turn email on: F1 → A2), F12 (his signed-in look at the team chat with two people), D-PROD-DB-BLIP (a database blip on the host at 16:01 UTC on 2026-11-11, before this pass's swap).

## ✅ SHIPPED 2026-11-11 — the SECOND OPEN LIST pass, live on `rel_CK @ b8f99ade` (see HISTORY, "OPEN LIST, SECOND PASS"; closed items in OPEN.md)
- **Founder said (2026-11-11):** "Go ahead and do all these" — the "code I can do on your go (small, found, not fixed)" list of a status report. He answered "Yes, swap" to the engine-swap question.
- **V — live now:** `rel_CK` → `b8f99ade` (read from the server's `.release` after the swap, 2026-11-11 18:05:35 UTC, 4 ms). Rollback target: **`rel_CJ @ df8f8548`**. **No migration** (the Inbox reactions were fixed by value, not by a column change — `ops/release.sh` refuses a column change). Public checks **12/0/0 before and 12/0/0 after** the swap (the rewritten `ops/public-checks.sh`, its second-look path not exercised: nothing failed).
- **V — gates on the last code commit (`4a2f0583`; docs commits follow):** MySQL 8.4.10 **4,749/4,749** (37,562 assertions) · MariaDB 11.8.9 **4,749/4,749** (37,562) · PHPStan L5 0 · Pint clean · `lang:check` 0 · `lang:todo` 0 new/0 changed/0 hardcoded/0 removed (27 languages, 10,572 keys) · money gate **"✅ MONEY AS SEEDED · FULL"** (digest `a0e1e01d…` unchanged) · mutations: this pass's set 19/19 + admin-p4, admin-06, housekeeping-five, property-scope all caught · render 940 pages × Arabic and German, 0 problems. The preflight on the host (read-only, from the staged release): PHP 8.4.27, MariaDB 11.8.9, 309 views parse, every language file loads, no new sentence missing in any language, 0 Inbox reactions and 0 card holds on production, the day lookup 9 ms.
- **V — tested live, anonymously:** `/login?lang=<l>` for all 27 languages + English answers with the right `<html lang>`, `dir="rtl"` on Arabic and Persian only; the home page carries `Organization` + `WebSite` on `https://vyrobel.com` and `/pricing` a `BreadcrumbList` + its FAQ — no price, offer or rating anywhere; `/favicon.svg` (the logo it names) 200; 0 error-level log lines since the swap (11 warnings, all `security.refusal` from the exposure battery); `team-chat:prune-typing` is on the host's schedule (hourly).
- ⚠️ **NOT tested live — it needs his sign-in:** every signed-in screen — the folio's new "Release hold" button (no card hold exists on production to press it on), the folio lines said in his language, the Housekeeping day, the owner pages' "unit" wording, the console privacy page in a language other than English, a phone-width dialog. Tested instead locally in a browser (a probe owner and the Guests/Portfolio dialogs at 390px, Arabic owner pages, the upload pre-check), by 4,749 tests on both engines, and by mutation.
- **Founder-visible changes to say plainly:** the Inbox's emoji reactions now keep two different ones from one person; the top bar's date and Housekeeping's "Today / Tomorrow" are the property's own day; "Assign all" really puts the waiting guests' rooms first (it did not reliably); owners' pages say "unit" instead of "villa", and "Cleaning fee goes to" / "Add-ons go to"; the folio's own lines ("Room · Double Room", "Wallet credit applied"…) read in his language; an operator who may refund can release a card hold from the reservation's payments; the platform console's sub-processor list reads in his language; the home page tells search engines the company name, address and logo (not a price, an offer or a rating); sectioned dialogs have their padding back on a phone; **and the previous pass's 13 changed texts (ticket → work order…) finally reach every language** (they never had).
- **Translations:** 48 new + 12 changed texts in all 27 languages, plus the 13 of the previous pass: machine translation with AI review (translator + `check.php`, no second reviewer, no native check).
- **Not pushed to GitHub (his rule: only when he says).** `main` is 6 commits ahead of `origin/main` (`355a2ca0`, read from the local ref — not re-fetched); production is `b8f99ade` (the docs commits after it are not on the server).
- ⚠️ **Left for him / open (as of that pass — ✅ D-ATTRIBUTION-STANDARD-WORD and D-HK-UTC-CLOCK were fixed on 2026-11-12):** the signed-in look (F12), D-SUBDISPUTE, D-ATTRIBUTION-STANDARD-WORD and D-HK-UTC-CLOCK (found, not fixed), DEC-t, the lawyer's rows for the login-activity access note, "Spotless villa", D-GL-FLAKE (not reproduced in 30 attempts).

## ✅ SHIPPED 2026-11-11 — the OPEN LIST pass, live on `rel_CJ @ df8f8548` (see HISTORY, "OPEN LIST, ONE PASS"; closed items in OPEN.md)
- **Founder said (2026-11-11):** "Do all these … make sure you test everything live … update the github." He pasted a status report (V: live `rel_CI @ 1daeed71`, local = origin = `4267652c`, clean tree, 210 migration files) and then the whole waiting-on-his-go list.
- **V — live now:** `rel_CJ` → `df8f8548` (read from the server's `.release` after the swap, 2026-11-11 09:52:10 UTC, 4 ms). Rollback target: **`rel_CI @ 1daeed71`**. Three ADDITIVE migrations ran first, from the staged release, while `rel_CI` was still live (`add_edit_delete_redact_to_team_messages`, `create_team_message_reactions_table`, `create_team_typing_table`); public checks were **12/0/0 before and 12/0/0 after** the swap.
- **V — gates on the staged commit (`09e591b6` + docs):** MySQL 8.4.10 **4,703/4,703** (37,164 assertions) · MariaDB 11.8.9 **4,703/4,703** (37,164) · PHPStan L5 0 · Pint clean · `lang:check` 0 · `lang:todo` 0 new/0 changed/0 hardcoded · money gate **"✅ MONEY AS SEEDED · FULL"** (digest `a0e1e01d…` unchanged) · mutation sets for every guard touched · render 940 pages × Arabic and German, 0 problems. Stage printed: drifted-from-git on the host 2 (two `.md` files), files to ship 718.
- **V — tested live, anonymously (all a signed-out visitor can reach):** `/login?lang=<l>` answers with the right `<html lang>` for all 27 languages + English, `dir="rtl"` and the new chevron rule on Arabic and Persian only; the three new tables/columns exist; 309 views compile and parse and every language file loads (a read-only preflight on the live release); the protected team-chat routes send a signed-out visitor to `/login`; **0 error-level log lines since the swap** (11 `security.refusal` warnings, all the exposure battery's probes); `MailSafetyGuard::sendingNow()` is **false** (email is still off), which is why the new "Email isn't being sent from Vyrobel yet" refusals and the "Invite saved" wording are what a signed-in person will see.
- ⚠️ **NOT tested live — it needs his sign-in, and no session types his password:** every signed-in screen. Specifically the NEW team chat with two people (edit/delete/reactions/search/typing/online/desktop notifications), the Inbox owner tab (production holds 0 owners with a portal sign-in, V by the preflight, so it shows the disabled state), the Rates banner, the Guests table, the roles/settings dialogs on production data, Arabic chevrons. Tested instead: locally (browser, real server, demo accounts), by 4,703 tests on both engines, and by mutation. His look is `OPEN.md` F12.
- **Founder-visible changes to say plainly:** a replaced brand logo is now KEPT (so old emails keep their picture); screens no longer say "emailed/sent" while email is off; an owner's address change and a resend are refused while email is off; staff limited to some properties now get a 404 on other properties' work orders, housekeeping tasks, rate proposals and Portfolio edits; the Calendar/Front Desk open on the property's own day; a hotel's checkout says "Choose your room"; refusals say "checked in", not `checked_in`. Not shipped from his list: D-SUBDISPUTE (his call).
- **Translations:** 80 new + 13 changed texts in all 27 languages, machine translation with AI review (translator + `check.php`, no second reviewer, no native check). Hindi PDFs still print some English wording.
- ✅ **Pushed to GitHub (V):** `main` = `origin/main` after the push of 2026-11-11, under the four hard stops (private, `Subhankhan12`, a fast-forward from `4267652c`, secret scan 436 hits all known/none new — taken twice). ⚠️ **GitHub's FIRST run of the pushed code (`a48165c3`, run 43000193565) was RED: 5 tests of my `ProcCheckTest`** — the CI image has no `ps`/`lsof` (the product was unaffected; `rel_CJ` had been live for 4½ hours). Fixed in `fcdbef36` (the tests skip, with their reason, where the tools are absent). **Run 43006066737 on `c48e8ce7` is GREEN** (Parity image + Gates, 2026-11-11 15:26 Baku): the full suite, the money gate and every other gate. The code on production is `df8f8548`; the commits after it are tests and docs only. ✅ **Run 43012021274 on `96411b97` (docs only) is also GREEN** (completed success, read from `gh run list` on 2026-11-11). Verified at the end of the day, in one read: live `rel_CJ @ df8f8548` (`ops/release.sh status`), local `main` = `origin/main` = `96411b97` (`git ls-remote`), clean tree. ⚠️ The commit that records this line has its own CI run, which this line cannot report (a record of a run can only be written after it).

## ✅ SHIPPED 2026-11-05 — I18N-01, all 27 languages; leftovers fixed + shipped 2026-11-06 (see HISTORY)
- **Founder said (2026-11-02):** finish every language on localhost, then ship them all in ONE release; no per-language releases.
  ✅ **Corrected 2026-11-11:** that was true when it was written (live was `rel_CG @ 7dde368e`, Russian). All of it then shipped — `rel_CH`
  (all 27 languages, 2026-11-05) and `rel_CI` (the leftovers, 2026-11-06) — and was pushed; the live release and what is pushed are in the **Live** section below.
- **R/V — committed locally, newest last (each: 10,433 of 10,433 texts, machine translation with AI review, no native check):**
  Romanian `c6a4b22d`, Polish `d7892071`, Bulgarian `77205f47`, Traditional Chinese `cec04865`, Hindi `0a940b01`, Persian
  `74aa9af2`, Thai `32013c9a`, Serbian (Cyrillic) `e1a66dac`, Estonian `f0036e2c`, Albanian `dd66961e`, Greek `75f44f96`,
  Georgian `f5b0d7bb`; then the code-fix pass `14148ee8` and the PDF-font fix `69c37767`; then Indonesian, Vietnamese,
  Simplified Chinese, Japanese and Korean `125ac2a0` (already translated + reviewed + consistency-edited earlier). All 27
  planned languages are built; `config('app.available_locales')` holds all of them.
- **V (2026-11-05) gates on `125ac2a0` (27 languages):** MySQL 8.4.10 4,599/4,599 (36,299 assertions) · MariaDB 11.8.9 4,599/4,599 (36,299) ·
  PHPStan L5 0 · Pint clean · money gate "✅ MONEY AS SEEDED · FULL" · render-all (929 pages, 0 problems) for bg, zh_TW, hi, fa,
  th, sr, et, sq, el, ka, id, vi, zh_CN, ja, ko (the leftover-English lists show only demo data stored in English and words a language keeps).
- **V — found and fixed by the code-fix pass / render pass (all code-only, English identical):** the free-cancel date in the
  booking-confirmation email printed an English month in EVERY language (also the live ones) · Persian dates were Solar Hijri in
  the browser · 12-hour clocks in el/sq/ko/hi/ar/zh_TW where the English source is 24-hour · Georgian capitals/months/ordinals ·
  decimal marks and list separators · **Thai/Chinese/Korean PDFs printed no characters** (`storage/fonts` did not exist on a
  fresh checkout; a CJK font also needed more than 128M once) · a Georgian title/month inside an English guest tax invoice.
  ⚠️ **His call:** the 24-hour change also moves LIVE Arabic from 12-hour to 24-hour time (hi, zh_TW are not live yet).
- **(Historical — every step below was done for `rel_CH`/`rel_CI`; kept as the record.) Before the single release:** (1) the production SSH block (82.25.102.188:65002 was closed from this Mac on 2026-11-02) must
  be solved; (2) saved-language count (read-only) → `./ops/release.sh stage` → preflight with ALL new codes in both loops →
  swap → `bash ops/public-checks.sh` → `/login?lang=<l>` for each; (3) render the new languages once more on the staged
  commit; (4) production needs `storage/fonts` writable (created on first PDF).
- `php artisan lang:todo` (V 2026-11-05, on `69c37767`): TOTAL — 0 new · 0 changed · 0 hardcoded · 0 removed · languages: 22.

## Live
- **Live release `rel_CO` → commit `a01bf072`** (the EIGHT DECISIONS pass; the record is the section at the top and `HISTORY.md`). Rollback target: `rel_CN @ 376c362b`.
- **Before it, live `rel_CN` → `376c362b`** (the REMAINING-ITEMS pass), and before that:
- **Live release `rel_CN` → commit `376c362b`** (the REMAINING-ITEMS pass; the record is the section at the top and `HISTORY.md`). Rollback target: `rel_CM @ f042e759`.
- **Before it, live `rel_CM` → `f042e759`** (the THREE DECISIONS pass), and before that:
- **Live release `rel_CK` → commit `b8f99ade`** (the SECOND OPEN LIST pass; the record is the section at the top and `HISTORY.md`). Rollback target: `rel_CJ @ df8f8548`.
- **Before it, live `rel_CJ` → `df8f8548`** (the OPEN LIST pass; rollback target then `rel_CI @ 1daeed71`).
- **Before it, live `rel_CI` → `1daeed71`** (the I18N leftovers, 2026-11-06), and before that:
- **Live release `rel_CH` → commit `a5e68e16`** (I18N-01 — **all 27 languages are on**: the 17 new ones are ro, pl, bg, zh_TW, hi,
  fa, th, sr, et, sq, el, ka, id, vi, zh_CN, ja, ko; machine translation with AI review, no native check; plus the code-fix pass and
  the PDF-font fix). **V:** MySQL 8.4.10 4,599/4,599 (36,299) · MariaDB 11.8.9 4,599/4,599 (36,299) · PHPStan L5 0 · Pint clean ·
  lang:check 0 (27 languages) · lang:todo 0 · money gate "✅ MONEY AS SEEDED · FULL" · render 929 pages × 17 languages, 0 problems ·
  saved-language count on production before the swap (read-only): users en × 17, empty × 3; guests empty × 18; platform admins
  empty × 1; orgs en × 15 — nobody had saved a new language · host preflight (PHP 8.4.27, memory_limit 512M; 309 views parse; 606
  lang files each, all 27 codes in both loops; `storage` writable) · stage needed one fix to `ops/release.sh` (xargs split the
  10,400-file list over several `git archive` runs; the count check refused it, nothing uploaded) → payload from one extracted
  tree · swap 2026-11-05 (4 ms) · public checks 12/0/0 · live `/login?lang=<l>` → correct `<html lang>` and translated heading
  for all 19 non-English codes checked, `fa` has `dir="rtl"`. ⚠️ His call still open: the 24-hour clock fix also moved LIVE
  Arabic from 12-hour to 24-hour time. Rollback target: `rel_CG @ 7dde368e`.
- **Live release `rel_CG` → commit `7dde368e`** (I18N-01 — **Russian is on**, 10,433 texts, machine translation with AI review, no
  native check). **V:** MySQL 8.4.10 4,588/4,588 (36,216) · MariaDB 11.8.9 4,588/4,588 (36,216) · lang:check 0 (de, ar, tr, fr, es,
  nl, pt, it, pt_BR, ru) · lang:todo 0 · money gate "✅ MONEY AS SEEDED · FULL" · Russian render 929 pages 0 problems · host preflight
  (PHP 8.4.27; 309 views parse; 606 lang files each; "27 окт 2026 г.") · swap 2026-11-02 02:56:33 UTC (5 ms) · public checks 12/0/0 ·
  live /login?lang=ru → `<html lang="ru">` "С возвращением" (en unchanged). No saved ru choice on production (read-only count: users
  en × 17, empty × 3; guests empty × 18; orgs en × 15). ⚠️ Not pushed. Rollback target: `rel_CF @ c229beaa`.
- **Live release `rel_CF` → commit `c229beaa`** (I18N-01 — **Brazilian Portuguese is on**, 10,433 texts, machine translation with AI
  review, no native check). **V:** MySQL 8.4.10 4,588/4,588 (36,216) · MariaDB 11.8.9 4,588/4,588 (36,216) · lang:check 0 (de, ar, tr,
  fr, es, nl, pt, it, pt_BR) · lang:todo 0 · money gate "✅ MONEY AS SEEDED · FULL" · Brazilian Portuguese render 929 pages 0 problems
  · host preflight (PHP 8.4.27; 309 views parse; 606 lang files each; "27 de out de 2026") · swap 2026-11-02 02:08:45 UTC (4 ms) ·
  public checks 12/0/0 · live /login?lang=pt_BR → `<html lang="pt-BR">` "Olá de novo" (pt → `pt-PT`, en unchanged). No saved pt_BR
  choice on production (read-only count: users en × 17, empty × 3; guests empty × 18; orgs en × 15). ⚠️ Not pushed. Rollback target:
  `rel_CE @ ecad8f6b`.
- **Live release `rel_CE` → commit `ecad8f6b`** (I18N-01 — **Italian is on**, 10,433 texts, machine translation with AI review,
  no native check). **V:** MySQL 8.4.10 4,588/4,588 (36,216) · MariaDB 11.8.9 4,588/4,588 (36,216) · lang:check 0 (de, ar, tr, fr,
  es, nl, pt, it) · lang:todo 0 · money gate "✅ MONEY AS SEEDED · FULL" · Italian render 929 pages 0 problems · host preflight (PHP
  8.4.27; 309 views parse; 606 lang files each; "27 ott 2026") · swap 2026-11-02 01:25:29 UTC (5 ms) · public checks 12/0/0 · live
  /login?lang=it → `<html lang="it">` "Le diamo il bentornato" (en unchanged). No saved it choice on production (read-only count:
  users en × 17, empty × 3; guests empty × 18; orgs en × 15). ⚠️ Not pushed. Rollback target: `rel_CD @ 77c00570`.
- **Live release `rel_CD` → commit `77c00570`** (I18N-01 — **European Portuguese is on**, 10,433 texts, machine translation with AI
  review, no native check). **V:** MySQL 8.4.10 4,588/4,588 (36,216) · MariaDB 11.8.9 4,588/4,588 (36,216) · lang:check 0 (de, ar, tr,
  fr, es, nl, pt) · lang:todo 0 · money gate "✅ MONEY AS SEEDED · FULL" · Portuguese render 929 pages 0 problems · host preflight (PHP
  8.4.27; 309 views parse; 606 lang files each; "27/10/2026") · swap 2026-11-02 00:43:39 UTC (6 ms) · public checks 12/0/0 · live
  /login?lang=pt → `<html lang="pt-PT">` "Olá de novo" (nl, en unchanged). No saved pt choice on production (read-only count).
  ⚠️ Not pushed. Rollback target: `rel_CC @ 20c6aef7`.
- **Live release `rel_CC` → commit `20c6aef7`** (I18N-01 — **Dutch is on**, 10,433 texts, machine translation with AI review, no
  native check; a one-night minimum stay now reads right in French, Arabic and Dutch). **V:** MySQL 8.4.10 4,588/4,588 (36,216) ·
  MariaDB 11.8.9 4,588/4,588 (36,216) · lang:check 0 (de, ar, tr, fr, es, nl) · lang:todo 0 · money gate "✅ MONEY AS SEEDED · FULL"
  · Dutch render 929 pages 0 problems · host preflight (PHP 8.4.27; 309 views parse; 606 lang files each; "27 okt. 2026") · swap
  2026-11-02 00:01:35 UTC (8 ms) · public checks 12/0/0 · live /login?lang=nl → `<html lang="nl">` "Welkom terug" (es, en
  unchanged). Production had no saved nl choice (read-only count: users en × 17, empty × 3; guests empty × 18; orgs en × 15).
  ⚠️ Not pushed. Rollback target: `rel_CB @ 99ebf43a`.
- **Live release `rel_CB` → commit `99ebf43a`** (I18N-01 — **Spanish is on**, 10,433 texts, machine translation with AI review, no
  native check; Turkish now tells the two unbookable unit conditions apart: « Arızalı » / « Bakımda »). **V:** MySQL and MariaDB
  4,588/4,588 (36,216) · lang:check 0 · lang:todo 0 · money gate FULL · Spanish render 929 pages 0 problems · preflight (PHP 8.4.27)
  · swap 2026-11-02 23:17:36 UTC (4 ms) · public checks 12/0/0 · live /login?lang=es → `<html lang="es">` "Hola de nuevo". No saved
  es choice on production. ⚠️ Not pushed. Rollback target: `rel_CA @ 48103e15`.
- **Live release `rel_CA` → commit `48103e15`** (I18N-01 — **French is on**, 10,433 texts, machine translation with AI review, no
  native check; the owner welcome page and the star-rating label keyed — DELTA 033/034 in de, ar, tr, fr; older plural bell rows
  said in the reader's language). **V:** MySQL 8.4.10 4,588/4,588 (36,216) · MariaDB 11.8.9 4,588/4,588 (36,216) · PHPStan L5 0 ·
  Pint · lang:check 0 (de, ar, tr, fr) · lang:todo 0 · money gate "✅ MONEY AS SEEDED · FULL" · French render 929 pages 0 problems,
  owner portal de/ar/tr 144 each 0 problems · host preflight (PHP 8.4.27; 309 views parse; 606 lang files each of en/de/ar/tr/fr;
  "27 oct. 2026") · swap 2026-11-01 22:25:40 UTC (4 ms) · public checks 12/0/0 · live /login?lang=fr → `<html lang="fr">` "Bon retour
  parmi nous" (tr, en unchanged). Production had no saved fr choice (read-only count). ⚠️ Not pushed. Rollback target: `rel_BZ @ 974b28b2`.
- **Live release `rel_BZ` → commit `974b28b2`** (I18N-01 — **Turkish is on**, 10,408 texts, machine translation with AI review, no
  native check; D-I18N-DATE-ORDER and D-NUMBER-FIELDS closed). **V:** MySQL 8.4.10 4,587/4,587 (36,212) · MariaDB 11.8.9 4,587/4,587
  (36,212) · PHPStan L5 0 · Pint · lang:check 0 (de, ar, tr) · lang:todo 0 since live · money gate "✅ MONEY AS SEEDED · FULL" ·
  mutations A03-M3/M9, HK-M12, OM-M15 re-anchored, 4/4 caught · host preflight (PHP 8.4.27, intl; 309 views parse; 604 lang files
  each of en/de/ar/tr load; dates in the language's order) · swap 2026-11-02 ≈01:25 Baku (4 ms; the script prints no clock time) · public checks 12/0/0 · live
  /login?lang=tr → `<html lang="tr">` "Tekrar hoş geldiniz" (de, en unchanged). Production had no saved tr choice (read-only count:
  users en × 17, empty × 3; guests empty × 18; orgs en × 15). ⚠️ Not pushed. Rollback target: `rel_BY @ bfad2754`.
- **Live release `rel_BY` → commit `bfad2754`** (I18N-01 ⛔ G3 — **German and Arabic are on**, 10,408 texts each, machine translation
  with AI review, no native check; plus the render fixes in `e9ac1f68`; migration `2026_10_29_000000_add_locale_to_platform_admins`,
  additive). **V:** MySQL 8.4.10 4,582/4,582 (34,348) · MariaDB 11.8.9 4,582/4,582 (34,348) · PHPStan L5 0 · Pint · lang:check 0
  (de, ar) · lang:todo 0 · money gate "✅ MONEY AS SEEDED · FULL" (digest a0e1e01d… unchanged) · renders 929 page×role each, 0
  problems · host preflight (PHP 8.4.27; 309 views parse; 604 lang files each of en/de/ar load) · swap 2026-10-29 01:40:50 UTC
  (4 ms) · public checks 12/0/0 · live: /login, /guest/login and a booking page in de and ar (ar right-to-left), the choice
  remembered · 0 errors in the log since (security.refusal warnings only, the public checks' probes). Production had no saved
  de/ar choice (read-only count), so nobody's screen changed until they pick. ⚠️ Not pushed. Rollback target: `rel_BX @ d45bd320`.
- **Live release `rel_BX` → commit `d45bd320`** (D-MONEY-COMMA, his ruling 2026-10-28: every typed amount read the way the page's
  language writes it; "150,50" = €150.50). **V:** MySQL 4,569/4,569 · MariaDB 4,569/4,569 (34,281) · PHPStan 0 · Pint · lang tools 0 ·
  anchors 0/1,099 · money gate FULL · number parity 0/80,100 · host preflight (309 views parse; "150,50" → 15050) · swap 2026-10-28
  01:20:36 UTC (4 ms) · public checks 12/0/0 · 0 errors since. ⚠️ Not pushed. Rollback target: `rel_BW @ 8c1a9d5f`.
- **Live release `rel_BW` → commit `8c1a9d5f`** (I18N-01 ⛔ G2 — Phase A + B: every screen's words are translation keys, English
  unchanged; only English is applied). **V:** MySQL 8.4.10 4,563/4,563 · MariaDB 11.8.9 4,563/4,563 (34,221 each) · PHPStan L5 0 ·
  Pint clean · lang:check 0 · lang:todo 0 · money gate FULL unmoved · English render diff unchanged but 9 script-written pages
  (browser-checked) · host preflight 307/307 views parse · swap 2026-10-27 23:31:06 UTC (5 ms) · public checks 12/0/0 · 0 errors in
  the log since. ⚠️ Not pushed (his rule). Rollback target: `rel_BV @ f426f449`. Detail: `HISTORY.md`, "I18N-01, ⛔ G2".
- **Live release `rel_BV` → commit `f426f449`** (the SECURITY REDESIGN, Part 3 — login alerts, the session timeout, "password last
  changed", login approval on the back office and Approve / Deny from a signed-in device; `HISTORY.md`, "SECURITY REDESIGN, PART 3").
  **V:** gates on the final code — MariaDB 11.8.9 **4,541/4,541** (34,120 assertions) · MySQL 8.4.10 4,540/4,541, the one failure (my
  duplicate mutation ids) fixed and its file green alone · PHPStan L5 0 · Pint clean · mutations 14/14 + 25/25 · money gate **FULL**
  unmoved (all in §0x). Deploy: stage — 21 files, nothing drifted; the additive migration from the staged release (118 ms); the read-only
  preflight on the host (PHP 8.4.26, MariaDB 11.8.9): the 7 new routes present and throttled, the timeout in the web group, both columns
  in place, the 13 views compile AND parse, email sent **no** (so login approval stays refused ON), 0 accounts with a timeout, 0 approvals
  waiting, the new-device check for all 20 accounts 559 ms, the slowest Part 3 read for one account 135 ms; all 20 accounts take the staff
  alert key — 🔬 checked a second way: production has **0 owners, 0 owner team users, 0 External Owner holders**; swap 2026-10-24
  21:04:50 UTC (01:04 Baku), 12 ms; public checks **12/0/0**; 1,485/1,485 deployable files match git; 0 error-level lines since the
  swap — 11 warnings, all `security.refusal`, from the exposure battery; both Security pages 302 → /login for anon. ⚠️ Not pushed (his
  rule). Rollback target: `rel_BU @ c763ff59`. Before it: **`rel_BU` → commit `c763ff59`** (the SECURITY REDESIGN, Parts 1+2 — both Security pages on his design and one kit;
  `HISTORY.md`, "SECURITY REDESIGN"). **V:** gates on the final code — **4,528/4,528 on MySQL 8.4.10** (34,049 assertions; 22:37–23:11
  Baku) · **4,528/4,528 on MariaDB 11.8.9** (34,049; 23:12–23:33 Baku) — JUnit 0 failed, 0 errors, 0 skipped; the engine swap asked
  (F: "Yes, swap") and checked (nothing on 3306, no advisor process); PHPStan L5 0 errors (106 s, a cleared cache); Pint clean; mutations
  `security-redesign` 25/25, `security-five` 22/22, CB-M48 caught, all 1,085 anchors land; the money gate **FULL** `929 / 10928000 / 0 /
  42 / 16 / 0`, digest unchanged (V 23:3x Baku). Deploy: stage — 22 files, nothing drifted, the additive migration run from the staged
  release (12 ms); the read-only preflight on the host (PHP 8.4.26, MariaDB 11.8.9): the 12 views compile AND parse, the routes and column
  in place, email sent **no**, **0 accounts with login approval on** (the review's M2 affects nobody), and 🔬 **the IP the app logs is the
  visitor's** — a marked request from this Mac through the CDN logged the same address the server sees on this Mac's SSH connection (D-SX-IP's
  measurement; yes/no only, no address printed); swap 2026-10-24 19:36:58 UTC (23:36 Baku), 5 ms; public checks **12/0/0**; 1,476/1,476
  deployable files match git; 0 error-level lines since the swap — 11 warnings, all `security.refusal`, from the exposure battery; both
  Security pages 302 → /login for anon. ⚠️ Not pushed (his rule). Rollback target: `rel_BT @ 4923e342`. Before it: **`rel_BT` → commit `4923e342`** (the SIGNUP PASSWORD SHOW/HIDE pass — his request for the reveal on
  `https://vyrobel.com/signup` too. The onboarding page (`auth/onboarding.blade.php`, `/signup` → `OnboardingController@create`) has
  two password inputs (Password + Confirm) in the non-social path; each is now wrapped in `.vy-input-wrap` with a `.vy-eye` button that
  toggles the type and swaps an open/closed eye icon. Self-contained view with vanilla JS (no Alpine), so it uses the auth-page
  `.vy-eye` + `data-toggle` pattern (as login/reset/forgot/set-password), the toggle JS added to the page's existing `<script>` (a safe
  `querySelectorAll('.vy-eye[data-toggle]')` — zero elements on the social path, both otherwise). The strength meter and the
  double-submit provisioning guard are untouched; the inputs keep name/autocomplete/required. No DB, no logic, no migration.
  **V (hardened preflight):** the onboarding view COMPILES AND its compiled PHP TOKEN-PARSES on the host, two eye toggles
  (password + password_confirmation), the toggle script and eye CSS present, both password fields keep their names; stage — 1 file,
  nothing drifted, no migration; swap 2026-10-21 ~23:10 UTC, 4 ms; **the LIVE public page confirmed — `/signup` renders HTTP 200 and the
  served HTML carries 12 `vy-eye` refs, 2 `data-toggle` buttons and the toggle script**; public checks **12/0/0**. ⚠️ Not pushed to
  GitHub (his rule: push at the very end). Rollback target: `rel_BS @ 432cdcbc`. Before it: **`rel_BS` → commit `432cdcbc`** (the INBOX COMPOSER REFINEMENTS — his "still need a lot of improvements, make it more
  cool and user friendly" (he chose: keep refining the composer). Further to the composer card (`operator-inbox.blade.php`): the reply
  textarea now AUTO-GROWS with content — starts at one row, grows to a 180px cap then scrolls, and snaps back after a send (Alpine
  `grow(el)` bound to `@input`/`x-init`, reset on `$nextTick` after the body clears); the attach/image/mic tools are rounded tiles
  (radius 11) with a terracotta hover tint and a press animation (were flat muted circles); the ⌘/Ctrl+Enter hint is shown as keycap
  chips (`.vy-kbd`) instead of plain grey text; the Send button is bolder with a hover lift and a clear dimmed disabled state; softer
  placeholder. CSS + Alpine (grow/hintKey) only — no server logic, no DB, no migration. **V (hardened preflight):** the inbox view
  COMPILES AND its compiled PHP TOKEN-PARSES on the host, the auto-grow binding + `grow()` method + keycap hint + tool tiles + send
  disabled state present; stage — 1 file, nothing drifted, no migration; swap 2026-10-21 ~23:00 UTC, 10 ms; the live log shows NO
  `operator-inbox` error; `/app/inbox` serves (302→/login for anon); public checks **12/0/0**. ⚠️ Not pushed to GitHub (his rule: push
  at the very end). Rollback target: `rel_BR @ 186da56a`. Before it: **`rel_BR` → commit `186da56a`** (the INBOX COMPOSER RESTYLE — his "the bottom type box and buttons are not looking
  cool, make them cool". The reply composer at the foot of a thread (`livewire/operator/operator-inbox.blade.php`) was a plain
  side-by-side flex; it is now one rounded glass "composer card": the children stack (textarea on top, the action row beneath), the
  whole card gets a focus-within terracotta ring (turns white + a soft glow when the type box is focused), the textarea is borderless
  inside the card (auto height 46–180px, 14px text), the action row's buttons share a 38px height, and the Reply/Send button is a pill
  with a paper-plane icon (the dynamic Sending…/Reply label kept via a bound `<span>`). CSS + markup only; no logic, no DB, no migration.
  **V (hardened preflight):** the inbox view COMPILES AND its compiled PHP TOKEN-PARSES on the host, the composer-card focus-within CSS,
  the send button + paper-plane icon, and the Reply/Sending label binding present; stage — 1 file, nothing drifted, no migration; swap
  2026-10-21 ~22:50 UTC, 5 ms; the live log shows NO `operator-inbox` error; `/app/inbox` serves (302→/login for anon); public checks
  **12/0/0**. ⚠️ Not pushed to GitHub (his rule: push at the very end). Rollback target: `rel_BQ @ abb626d6`. Before it: **`rel_BQ` → commit `abb626d6`** (the INBOX THREAD-ACTIONS FIX — his report that the four thread-header buttons
  (Assign · Snooze · the reservation reference e.g. `R-…` · Change link) were still "messed" in height/alignment. Cause: they are
  mixed element types — a `<button>`, a `<button>` inside an inline `<form style=display:inline>`, and an `<a>` — so with only
  `.vy-btn`'s padding (no fixed height) they rendered at slightly different heights and the inline form threw off the baseline. Fix
  (`livewire/operator/operator-inbox.blade.php`, CSS + markup): a scoped `.vy-thread-actions` rule gives every control one 34px height
  and centered baseline, the `.vy-ta-form` wrapper is `inline-flex; margin:0`, each button got a small icon, and the reservation
  reference is now a distinct terracotta code chip (`.vy-ta-ref`) rather than a plain oversized button; wraps right-aligned on narrow
  widths (32px there). No logic, no DB, no migration. **V (hardened preflight):** the inbox view COMPILES AND its compiled PHP
  TOKEN-PARSES on the host, the `vy-ta-btn`/`vy-ta-ref`/fixed-height rules present and the old inline-form style gone; stage — 1 file,
  nothing drifted, no migration; swap 2026-10-21 ~22:36 UTC, 4 ms; the live log shows NO `operator-inbox` error; `/app/inbox` serves
  (302→/login for anon); public checks **12/0/0**. ⚠️ Not pushed to GitHub (his rule: push at the very end). Rollback target:
  `rel_BP @ ab5f6c93`. Before it: **`rel_BP` → commit `ab5f6c93`** (the INBOX DESIGN POLISH pass — his "improve the design of `/app/inbox` more and make
  there are no spacing issues". The page was already well-built; this tightened the vertical rhythm and refreshed two components, CSS +
  one markup wrapper only. Changes (`livewire/operator/operator-inbox.blade.php`): the stacked header sections now share a consistent
  16px gap (stat strip, toolbar `.vy-ibx-bar`, subbar — were an uneven 16/10/12); the 3-pane grid gap 16→18px; the stat cards redone as
  a clean two-line number-over-label (new `.vy-istat-text` wrapper) with a larger figure (20→22px), roomier padding, softer radius
  (14→16) and a hover lift+shadow on the clickable Unread card; conversation rows a touch roomier (12/14→13/15) with a terracotta
  left-accent bar on the active one (`.vy-conv.is-active::before`). No logic, no DB, no migration. **V (hardened preflight):** the inbox
  view COMPILES AND its compiled PHP TOKEN-PARSES on the host, the `.vy-istat-text` wrapper (×5), the active left-accent and the 18px
  grid gap present; stage — 1 file, nothing drifted, no migration; swap 2026-10-21 ~22:07 UTC, 13 ms; the live log shows NO
  `operator-inbox` error at all; `/app/inbox` serves (302→/login for anon); public checks **12/0/0**. ⚠️ Not pushed to GitHub (his
  rule: push at the very end). Rollback target: `rel_BO @ 1910a860`. Before it: **`rel_BO` → commit `1910a860`** (the LOGIN PASSWORD SHOW/HIDE pass — his request that the show/hide reveal work on
  `https://vyrobel.com/login` too. The login page's single password field is now wrapped in `.vy-input-wrap` with a `.vy-eye` button
  that toggles the input between `password` and `text` and swaps an open/closed eye icon. `/login` is a self-contained view (its own
  `<head>`/`<style>`/`<script>`, NOT the operator shell and NOT Alpine), so this uses the auth-page `.vy-eye` + `data-toggle` pattern
  (as reset/forgot/set-password already do), with the toggle JS added to the page's existing `<script>`. The password input keeps its
  name/autocomplete/required and submits unchanged; works only as an enhancement (no JS = a plain password field). Self-contained view
  — no DB, no logic, no migration. **V (hardened preflight, post the SEARCH 500 lesson):** the staged login view COMPILES AND its
  compiled PHP TOKEN-PARSES on the host (not just compiles), the eye toggle markup/CSS/JS present, the password field keeps its name;
  stage — 1 file, nothing drifted, no migration; swap 2026-10-21 ~22:35 Baku, 4 ms; **the LIVE page confirmed: `/login` renders HTTP
  200 and the served HTML carries `vy-eye`, `data-toggle="password"` and the toggle script**; public checks **12/0/0**. ⚠️ Not pushed to
  GitHub (his rule: push at the very end). Rollback target: `rel_BN @ 978b8f71`. Before it: **`rel_BN` → commit `978b8f71`** (the SEARCH 500 HOTFIX — `/app/search` threw a 500 FOR SIGNED-IN USERS after the
  SEARCH DATE PICKER pass. ⚠️⚠️ **The bug, and the lesson:** the CSS comment I added to explain the stacking fix contained the literal
  date-field component tag text, and **Blade compiles a component tag even inside a `<style>`/CSS comment** — so it was parsed as an
  unclosed component that swallowed the rest of the file, producing a compiled-PHP parse error ("unexpected end of file, expecting
  elseif/else/endif"). ⚠️ This is the SAME trap already written in `SYSTEMS.md` §20 for the select engine. ⚠️ It passed `view:cache`
  and a `$compiler->compile()` preflight because those only GENERATE the PHP — they do not PARSE/execute it, and anon hits 302 before
  render, so only a signed-in render surfaced it. **Fix:** reworded the comment so it never names a component tag; **now verified by
  compiling AND token-parsing the compiled PHP** (the check that actually catches this). Views only — no DB, no logic, no migration.
  **V:** preflight on the staged release — the view compiles AND its compiled PHP parses, only the 2 real `<x-date-field>` tags remain;
  the whole app's compiled views all parse locally; stage — 1 file, nothing drifted, no migration; swap 2026-10-21 17:39:23 UTC
  (~21:39 Baku), 16 ms; live log — the last `operator-search` error was 17:39:00, 23 s BEFORE the swap; none since; `/app/search`
  serves (302→/login for anon). ⚠️ Not pushed to GitHub (his rule: push at the very end). Rollback target: `rel_BM @ 0790af9d`. Before it: **`rel_BM` → commit `0790af9d`** (the SEARCH DATE PICKER pass — the same themed date picker on `/app/search`. The two
  native `<input type="date">` check-in-range fields were replaced by `<x-date-field>` (the colourful trigger + custom calendar popover
  from the Insights pass), with the same stacking-context fix (`.vys-form:has(.vy-df-trigger.is-open){z-index:60}`) so the popover sits
  above the result rows. ⚠️ This pass's STATE line first claimed "no 500" — WRONG: it 500'd for signed-in users (see the SEARCH 500
  HOTFIX above); the preflight's compile-only check missed it. The native input stays the truth — it submits with the GET form (the
  Search button) unchanged. Views only — no DB, no logic, no migration; swap 2026-10-21 ~22:00 Baku, 4 ms. Rollback target:
  `rel_BL @ b30e5570`. Before it: **`rel_BL` → commit `b30e5570`** (the SECURITY PASSWORD SHOW/HIDE pass — his request that every password field on
  `/app/security` carry a show/hide button. The five password inputs there (change/set password's current + new + confirm, the
  sign-out-others dialog, the revoke-device dialog) now use a new reusable `<x-password-field>` component
  (`resources/views/components/password-field.blade.php`): the input defaults to `type=password` and an eye button toggles it to text
  (swapping an open/closed eye icon). Alpine-only enhancement (ships with Livewire); the input keeps its `name`, `required`,
  `autocomplete` and submits exactly as before; the eye is `type=button`, `tabindex=-1`, so it never submits and never fights a
  dialog's focus trap. Views only — no DB, no logic, no migration. CSS scoped to `.vy-pw` / `.vy-pw-eye` in the operator layout.
  **V:** the read-only preflight from the staged release on the host's PHP — the 3 views compile, the component renders the password
  input (keeps required), the eye toggles `:type`, the page uses exactly 5 `<x-password-field>` and 0 native password inputs remain;
  stage — 3 files shipped, 0 deleted, nothing drifted, no migration; swap 2026-10-21 ~21:35 Baku, 4 ms; public checks **12/0/0** — the
  5xx sweep clean over 168 public routes, exposure battery SERVED=0; `/app/security` serves (302→/login for anon, no 500). ⚠️ Not
  pushed to GitHub (his rule: push at the very end). Rollback target: `rel_BK @ cb378e72`. Before it: **`rel_BK` → commit `cb378e72`** (the INSIGHTS DATE PICKER OVERLAP FIX — his report that both date pickers overlap
  on open. Cause: the `.vy-fade` entrance animation puts a `transform` on both the toolbar and the KPI cards below it, and a
  non-`none` transform makes each element a STACKING CONTEXT — so the popover's `z-index:60` was trapped inside the toolbar's context
  and the later cards painted over it. Fix: `.vy-ins .vy-rack-toolbar` gets `position:relative`, and `:has(.vy-df-trigger.is-open)`
  raises its context to `z-index:60` only while a picker is open, so the calendar sits above the cards. CSS only, scoped to `.vy-ins`;
  no DB, no logic, no migration. **V:** the read-only preflight from the staged release on the host's PHP — the 3 views compile, both
  fix rules present; stage — 1 file shipped, 0 deleted, nothing drifted, no migration; swap 2026-10-21 ~21:10 Baku, 3 ms; public
  checks **12/0/0** — the 5xx sweep clean over 168 public routes, exposure battery SERVED=0; `/app/insights?range=ytd` serves
  (302→/login for anon, no 500). ⚠️ Not pushed to GitHub (his rule: push at the very end). Rollback target: `rel_BJ @ abcc5384`. Before it: **`rel_BJ` → commit `abcc5384`** (the INSIGHTS DATE PICKER pass — the custom date range on `/app/insights` gets a
  themed date picker. The two native `<input type="date">` fields were replaced by a new reusable `<x-date-field>` component
  (`resources/views/components/date-field.blade.php`): a colourful on-theme trigger pill (terracotta gradient tint, calendar icon,
  caret) and, on open, a custom calendar popover (gradient header, month nav, a terracotta-gradient selected day, a today ring,
  hover states, Today/Clear). ⚠️ The native `<input type="date">` STAYS and stays the truth — it carries the name/value, is still
  submitted with the GET form, and the no-JS fallback is kept (D3-1); Alpine (shipped with Livewire) only enhances, writing the
  chosen ISO date back onto the native input and firing change. Views only — no DB, no logic, no migration. CSS scoped to `.vy-df` /
  `.vy-ins-range` in the operator layout. **V:** the read-only preflight from the staged release on the host's PHP — the 3 changed
  views compile, the component renders the native input + trigger + calendar + its Alpine fn, insights uses `<x-date-field>` and the
  old inline inputs are gone, the popover and gradient-selected-day CSS present; stage — 3 files shipped, 0 deleted, nothing drifted,
  no migration; swap 2026-10-21 ~20:45 Baku, 6 ms; public checks **12/0/0** — the 5xx sweep clean over 168 public routes, exposure
  battery SERVED=0; `/app/insights?range=ytd` serves (302→/login for anon, no 500). ⚠️ Not pushed to GitHub (his rule: push at the
  very end). Rollback target: `rel_BI @ f3a7ab82`. Before it: **`rel_BI` → commit `f3a7ab82`** (the CALENDAR ROOM-TYPE FILTER pass — the "All room types" drop-down on
  `/app/calendar` resized and polished. The filter is a glass pill (`.vy-rack-filter`) wrapping a borderless native select; the one
  select engine forces `min-height:40px` + `padding-right:36px` on every bare select, so the pill had ballooned to ~54px and sat
  above the ~34px nights pill beside it. Now the pill is 34px (level with the nights pill), the engine's forced sizing is
  neutralised INSIDE the pill while its chevron and open panel are kept, and a leading room-type icon + hover/focus-within polish
  added. Scoped to `.vy-cal .vy-rack-filter` (used nowhere else); views only — no DB, no logic, no migration. **V:** the read-only
  preflight from the staged release on the host's PHP — both changed views compile, the new icon markup and the three CSS changes
  present; stage — 2 files shipped, 0 deleted, nothing drifted, no migration; swap 2026-10-21 ~20:20 Baku, 3 ms; public checks
  **12/0/0** — the 5xx sweep clean over 168 public routes, exposure battery SERVED=0; `/app/calendar` serves (302→/login for anon,
  no 500). ⚠️ Not pushed to GitHub (his rule: push at the very end). Rollback target: `rel_BH @ e72ce69d`. Before it: **`rel_BH` → commit `e72ce69d`** (the FAVICON pass — the terracotta "V" logo as the favicon on every surface: the front
  website, the operator/owner/guest/admin panels and every shared auth/error/status/oauth/billing front door, through ONE
  `<x-favicon>` component — `public/favicon.svg` + the admin panels' own inline data-URI as the fallback; the admin panels' prior
  inline favicon folded into the component. Views and one static asset only — no DB, no logic, no migration. **V:** the read-only
  preflight from the staged release on the host's PHP — `favicon.svg` present (547 bytes), the component present, all 14 edited
  shells/front-doors compile, the component renders both the svg link and the data-URI fallback; stage — 24 files shipped, 0 deleted,
  nothing drifted, no migration; swap 2026-10-21 ~20:01 Baku, 9 ms; public checks **12/0/0**; `https://vyrobel.com/favicon.svg` serves
  HTTP 200 `image/svg+xml` 547 bytes with the gradient-V content; the 5xx sweep clean over 168 public routes; exposure battery
  SERVED=0). ⚠️ Not pushed to GitHub (his rule: push at the very end). Rollback target: `rel_BG @ 6bde9910`. Before it: **`rel_BG` → commit `6bde9910`** (THE ONE SELECT ENGINE pass — every drop-down on one engine. **V:** the read-only preflight
  from the staged release on the host's PHP 8.4.26 — all 24 changed views compile, 419 zones, the combobox gone; swap 2026-10-10
  03:38:26 Baku, 4 ms; 25 files shipped, 1 deleted, no migration, nothing drifted; public checks **12/0/0**; live signup's two selects
  open the panel and close with Esc, no page error (Playwright, after the CDN's bot check let the page through); 0 error-level lines
  since — 11 warnings, all `security.refusal`, from the exposure battery). ⚠️ The signed-in live walk was offered and he chose to
  skip it (F 2026-10-10 03:4x Baku). Rollback target: `rel_BF @ 94c56965`. Before it: **`rel_BF` → commit `94c56965`** (THE VYROBEL SELECT pass — the new select on /app/profile, awaiting his approval for
  the rest of the app. **V:** the read-only preflight from the staged release on the host's own PHP (now **8.4.26**, not the 8.4.19
  recorded earlier): 419 zones of 419 identifiers, Baku labelled "Baku UTC+4", the Portfolio's list unchanged, both views compile;
  swap 2026-10-10 00:18:16 Baku, 3 ms; 4 files shipped, no migration, nothing drifted on the host; public checks **12/0/0**; 0
  error-level lines since — 11 warnings, all `security.refusal`, from the exposure battery). Rollback target: `rel_BE @ 79d0cb60`.
  Before it: **`rel_BE` → commit `79d0cb60`** (the TOP BAR BELL pass — the unread badge on the bell's outer corner, the top bars
  spaced. **V:** swap 2026-10-09 22:21:55 Baku, 5 ms; 4 files shipped, no migration, nothing drifted on the host; public checks
  **12/0/0**; the glyph on the host carries the corner offsets; 0 error-level lines since — 11 warnings, all `security.refusal`, from
  the exposure battery). Rollback target: `rel_BD @ 906b7dd9`. Before it: **`rel_BD` → commit `906b7dd9`** (the OWNER TOP BAR follow-up — the owner portal's top bar stands still too. **V:** swap
  2026-10-09 20:53:32 Baku, 4 ms; 1 file shipped, no migration, nothing drifted on the host; public checks **12/0/0**; the rule on the
  host carries no animation; 0 error-level lines since — 11 warnings, all `security.refusal`, from the exposure battery). Rollback
  target: `rel_BC @ 2e2194e5`. Before it: **`rel_BC` → commit `2e2194e5`** (the SIDEBAR POSITION pass — every sidebar keeps its place across pages and reloads;
  `HISTORY.md`, "SIDEBAR POSITION". **V:** swap 2026-10-09 19:07:25 Baku, 3 ms; 6 files shipped, no migration, nothing drifted on the
  host; public checks **12/0/0** straight after; the three shells on the host carry the component; 0 error-level lines since — 11
  warnings, all `security.refusal` (layer `abort`), from the exposure battery). Rollback target: `rel_BB @ 6ca6a10b`. Before it:
  **`rel_BB` → commit `6ca6a10b`** (the ROLE SIGN-IN fix — a lapsed workspace's staff get a plain page with Sign out, never
  the 403 loop; `HISTORY.md`, "ROLE SIGN-IN". **V:** `ops/release.sh swap` output, 2026-10-09 17:53:48 Baku, 10 ms; 6 files shipped, no
  migration, nothing drifted on the host; public checks **12 PASS · 0 FAIL · 0 could not measure** straight after; `.release` read
  back on the host; 0 error-level lines since the swap — 11 warnings, all `security.refusal` (layer `abort`), from the exposure
  battery). Rollback target: `rel_BA @ 16b15e2f`. Before it: **`rel_BA` → commit `16b15e2f`** (team chat and a live Messages page — `HISTORY.md`, "TEAM CHAT" — on top of the Inbox's filter tabs on a phone and the FIVE PAGES pass: the Inbox's New
  message, Housekeeping, Maintenance, Units — Edit and a Delete that removes only what nothing has recorded — and Security; on top of
  the Guests second pass, the Calendar second pass, the New reservation second pass, the Calendar New reservation pass, the New reservation pass, the owners module pass, the payouts pass, the Owners rebuild, the Rates rebuild with DEC-l and its two live
  fixes, DEC-m, photos at `/media`, DEC-k, the Units, Maintenance and Guests rebuilds, Portfolio Open + fields, the Portfolio
  remove and ADMIN 06). **V:** `ops/release.sh swap` output (`.release = 16b15e2f…`, 5 ms), 2026-10-06 18:59:10 Baku; `.release`
  re-read on the host at 19:1x (a read-only count). The three team chat tables were added by `ops/release.sh migrate` from the staged release, before the swap.
  Rollback targets: `rel_BF @ 94c56965` (the profile's select, before the engine), `rel_BE @ 79d0cb60` (the bell, before the select), `rel_BD @ 906b7dd9` (the owner top bar, before the bell), `rel_BC @ 2e2194e5` (the sidebar, before the owner top bar), `rel_BB @ 6ca6a10b` (the ROLE SIGN-IN fix, before the sidebar), `rel_BA @ 16b15e2f` (team chat, before the ROLE SIGN-IN fix), `rel_AZ @ 59799d4d` (the Inbox tabs, before team chat — ⚠️ the three team chat tables stay, unused by it), `rel_AY @ c73a97cd` (the five pages, before the tabs), `rel_AX @ 8345c86e` (Guests second pass), `rel_AW @ a2831e72` (Calendar second pass), `rel_AV @ bac3997b` (New reservation second pass), `rel_AU @ 7819226d` (Calendar New reservation), `rel_AT @ 282109e4` (New reservation), `rel_AS @ 4751d2c9` (the owners module), `rel_AR @ d90f40ca` (payouts), `rel_AQ @ 5d391dbe` (Owners), `rel_AP @ 9b9b80c8` (Rates with its two fixes), `rel_AO @ cac9e677` (Rates, before the two fixes), `rel_AN @ 761cf2dd` (DEC-m),
  `rel_AM @ 9e9b1193` (photos), `rel_AL @ 8cda8c7f` (DEC-k), `rel_AK @ a4e2b6e5` (Units), `rel_AJ @ a3604b9c` (Maintenance), `rel_AI @ 9f704a3a` (Guests), `rel_AH @ 3b012e93` (Portfolio Open +
  fields), `rel_AG @ e4117aeb` (Portfolio remove), `rel_AF @ 4c17edd2` (ADMIN 06) — `ops/release.sh rollback rel_<X>`.
  ⚠️ A rollback to `rel_AL` or earlier puts photo addresses back on `/storage` — every photo 404s again.
- **GitHub:** pushed at his word on **2026-10-10** ("update the entire memory … push everything to the GitHub … without any bugs") —
  the commits of 2026-10-09/10 (`9e4f717f..` the commit holding this line: ROLE SIGN-IN, SIDEBAR POSITION, OWNER TOP BAR, TOP BAR BELL,
  THE VYROBEL SELECT, THE ONE SELECT ENGINE, and this memory pass) — `9e4f717f..351cd844` at 04:10:46 Baku after the four hard stops
  (private `true` · `Subhankhan12` · a fast-forward of 14 · the history scan 128 hits in the same 40 locations, none in the push's 56
  files; **V** `git ls-remote` read `351cd844` back), then the commit holding this line. **GitHub's checks green on `351cd844`** (run
  38007913457, 04:10–04:34 Baku) — `HISTORY.md`, "MEMORY AND GITHUB, 2026-10-10". ⚠️ The record's own push (`8f460c2d`) went RED on a
  flaky check — a random Livewire checksum held "4242" (run 38010017082); fixed in the tests only and pushed with this line; the run
  on this commit is read with `gh run list`. ⚠️ This file cannot hold the hash of the commit it is in: `git ls-remote origin` is the
  reading. Production `rel_BG @ 6bde9910` is behind GitHub only by docs commits. Before: pushed 2026-10-06
  (five fast-forwards, `fc1ee357..` → `9e4f717f`; GitHub's checks green end to end from that day). The rule is unchanged: local commits,
  live deploys, push when he says.
- **Live code vs git, byte for byte: 1,460 of 1,460 deployable files in the live release match git at `6bde9910`, 0 differ**
  (`app bootstrap config database public resources routes ops artisan`, less `bootstrap/cache`, `public/build`, `resources/css|js`).
  **V** 2026-10-10 ~04:1x Baku, a sha256 manifest of `git archive 6bde9910` checked inside the live release — every line counted OK.
  Before: 1,457 of 1,457 at `16b15e2f` (2026-10-06).
- **`PAYOUT_ENGINE_ENABLED` is OFF on production** (**V** 2026-10-03, the read-only preflight from `rel_AS`: `false`;
  production holds 0 owners, 0 management agreements, 0 unit requests, 0 payouts). D-PAYRUN-TENANT is fixed live since `rel_AR`;
  D-PAYRUN-BUTTON since `rel_AS`.
- **ADMIN 06's refusals are LIVE** — the first of the three conditions before any live Stripe key (`OPEN.md` F5).
- **The memory is not on the host:** `docs/` and `CLAUDE.md` are not deployed.

## Numbers
| What | Value | Source |
|---|---|---|
| Suite (the SECURITY REDESIGN, Part 3) | **4,541/4,541 on MariaDB 11.8.9** (34,120 assertions; 2026-10-25 00:30–01:00 Baku) — JUnit 0 failed, 0 errors, 0 skipped · MySQL 8.4.10 4,540/4,541 (23:58–00:25 Baku); the one failure (duplicate mutation ids, mine) fixed and its file green alone. Parts 1+2: 4,528/4,528 on both engines (the Live line) | **V** run outputs and JUnit logs 2026-10-25. |
| Suite (THE ONE SELECT ENGINE pass) | **4,511/4,511 on MySQL 8.4.10** (33,898 assertions; 2026-10-10 02:24–02:43 Baku) · **4,511/4,511 on MariaDB 11.8.9** (33,898 assertions; 02:44–03:32 Baku) — JUnit 0 failed, 0 errors, 0 skipped. A first MySQL run was 4,509/4,511 (two contracts; fixed) | **V** run outputs and JUnit logs 2026-10-10. |
| Suite (THE VYROBEL SELECT pass) | **4,503/4,503 on MySQL 8.4.10** (33,260 assertions; 2026-10-09 23:33–23:53 Baku) · **4,503/4,503 on MariaDB 11.8.9** (33,260 assertions; 23:53–00:14 Baku) — JUnit 0 failed, 0 errors, 0 skipped. A first MySQL run was 4,502/4,503 (the keyboard contract; fixed) | **V** run outputs and JUnit logs 2026-10-09/10. |
| Suite (the TOP BAR BELL pass) | **4,498/4,498 on MySQL 8.4.10** (33,214 assertions; 21:24–21:55 Baku) · **4,498/4,498 on MariaDB 11.8.9** (33,214 assertions; 21:56–22:19 Baku) — JUnit 0 failed, 0 errors, 0 skipped | **V** run outputs and JUnit logs 2026-10-09. |
| Suite (the OWNER TOP BAR follow-up) | **4,496/4,496 on MySQL 8.4.10** (33,197 assertions; 19:26–20:21 Baku, slowed by another tool's load) · **4,496/4,496 on MariaDB 11.8.9** (33,197 assertions; 20:22–20:49 Baku) — JUnit 0 failed, 0 errors, 0 skipped | **V** run outputs and JUnit logs 2026-10-09. |
| Suite (the SIDEBAR POSITION pass) | **4,495/4,495 on MySQL 8.4.10** (33,187 assertions; 18:23–18:42 Baku) · **4,495/4,495 on MariaDB 11.8.9** (33,187 assertions; 18:43–19:01 Baku) — both on the final code before these docs, read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped | **V** run outputs and JUnit logs 2026-10-09. |
| Suite (the ROLE SIGN-IN pass) | **4,490/4,490 on MySQL 8.4.10** (33,151 assertions; 17:04–17:26 Baku) · **4,490/4,490 on MariaDB 11.8.9** (33,151 assertions; 17:27–17:49 Baku) — both on the final code before these docs, read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped | **V** run outputs and JUnit logs 2026-10-09. |
| Suite (the GitHub and memory pass) | **4,482/4,482 on MySQL 8.4.10** (32,958 assertions; 20:23–20:40 Baku) · **4,482/4,482 on MariaDB 11.8.9** (32,958 assertions; 20:41–20:58 Baku) — both on commit `d0dd97aa`, read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped. The fixes after it (tests, the workflow, `.env.example`) were run as the changed files (137/137) and under GitHub's own settings (56/56, a reproduced `.env`). The time-limit follow-up (`<= 300`, one test, one mutation): the delete's test file **28/28 on MySQL 8.4.10** (22:31:14–22:31:23 Baku) and **28/28 on MariaDB 11.8.9** (22:28:53–22:29:04), 249 assertions each, JUnit 0 failed, 0 errors, 0 skipped; ADMIN 05 mutations 52/52 (22:26–22:27); the full suite on that code is GitHub's run on the pushed commit | **V** run outputs and JUnit logs 2026-10-06. |
| Suite (the team chat pass) | **4,480/4,480 on MySQL 8.4.10** (32,952 assertions; 17:38–17:58 Baku) · **4,480/4,480 on MariaDB 11.8.9** (32,952 assertions; 18:00–18:19 Baku) — both on tree `0b3f80cfe6b338b6` (the code before one Blade comment was corrected and these docs written) and read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped | **V** run outputs and JUnit logs 2026-10-06. |
| Suite (the Inbox tabs follow-up) | **4,434/4,434 on MySQL 8.4.10** (32,565 assertions; tree `d24833fe404086c0`, 02:44–03:02 Baku — the code before two comment dates were corrected) · **4,434/4,434 on MariaDB 11.8.9** (32,565 assertions; tree `78d633402013dbf0` = the final code before these docs; 03:03–03:20 Baku) — both read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped | **V** run outputs and JUnit logs 2026-10-06. |
| Suite (the FIVE PAGES commit) | **4,432/4,432 on MySQL 8.4.10** (tree `aba564f6e42d92a0` = the code plus these docs, before these figures were written; 2026-10-05 20:47–21:05 Baku) · **4,432/4,432 on MariaDB 11.8.9** (tree `73ef8334b2507a1f` = the same plus one mutation-set file; 21:43–22:01 Baku) · 32,538 assertions · 0 failed, 0 errors, 0 skipped (read from each run's JUnit log). The GUESTS SECOND commit before it (`8345c86e`): 4,359/4,359 on both engines | **V** run outputs and JUnit logs 2026-10-05. |
| Mutations | **1,060 listed across 41 sets** — the WHOLE harness re-run on the engine pass, **1,060/1,060 landed and caught** (V 2026-10-10 01:09–01:37 Baku; vy-select rewritten to 11, select-engine new with 13, PF-M6 repointed, each re-run after the last edits). Before: **1,048 listed across 40 sets** (V 2026-10-10 00:15 Baku) = the 1,036 below + `vy-select` (12, all landed and caught). Before: **1,036 listed across 39 sets** (V 2026-10-09 22:19 Baku) = the 1,029 below + `topbar-bell` (7, all landed and caught). Before: **1,029 listed across 38 sets** (V 2026-10-09 ~19:3x Baku) = the 1,028 below + SP-M12 (`sidebar-position` re-run whole, 12/12 caught). Before: **1,028 listed across 38 sets** (V 2026-10-09 19:0x Baku) = the 1,017 below + `sidebar-position` (11, all landed and caught). Before: **1,017 listed across 37 sets** (`--list`, **V** 2026-10-09 17:5x Baku) = the 1,008 below + `lapsed-staff` (9, all landed and caught, V 2026-10-09); the three existing mutations on the suspended-workspace gate (P2-M1, P2-M18, P3-B16) re-run, 3/3 caught. Before: **1,008 listed** (`--list`, **V** 2026-10-06 22:53 Baku) = the 1,005 below + A05-M50/M51 (GitHub's first full run) + A05-M52 (the time-limit follow-up). `admin-05` re-run whole on the final code: **52/52 landed and caught** (V 22:26–22:27); the other four sets that mutate the same controller (`admin-03`, `admin-04`, `admin-06`, `admin-p2`) search none of the changed lines and their filters do not reach the new test (grep), so they were not re-run. Before: **1,005 = the 948 below + `team-chat` (29) + `inbox-live` (28)** — the whole harness re-run on the final code — **1,005/1,005 landed and caught across all 36 sets** (`team-chat` 29 and `inbox-live` 28 new; V 18:21–18:53 Baku); 0 not caught, 0 did not land. Before: **948 = the 941 below + `inbox-compose` IB-M18…M24** — that set re-run whole after the Inbox tabs follow-up, 24/24 caught (V 2026-10-06 02:42–02:43 Baku); no other set aims at the changed view (grep), so the rest were not re-run. Before: **941/941 landed and caught across every set** (34 sets), 0 not caught, 0 did not land; baseline 527/527 green (the five new sets: `units-five` = 37, `housekeeping-five` = 24, `maintenance-five` = 23, `security-five` = 22, `inbox-compose` = 17). ⚠️ The full run had UL-M8 green: the pass's new database-level refusal answered for the pre-check it mutates; re-aimed at the test that tells the layers apart, caught | **V** harness output 2026-10-05 21:05–21:32 Baku (UL-M8 21:36) |
| Money gate FULL | `929 / 10928000 / 0 / 42 / 16 / 0` · digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297` · `mode=FULL` ✅ | **V** `ops/money-as-seeded.sh` output 2026-10-10 03:33 Baku (THE ONE SELECT ENGINE pass; reseeded 03:33); 00:15 Baku (THE VYROBEL SELECT pass; reseeded 00:15); 2026-10-09 22:19 Baku (the TOP BAR BELL pass; reseeded 22:19); 20:50 Baku (the OWNER TOP BAR follow-up; reseeded 20:50); 19:02 Baku (the SIDEBAR POSITION pass; reseeded 19:02); 17:49 Baku (the ROLE SIGN-IN pass; reseeded 17:49); 2026-10-06 17:59 Baku (the team chat pass; reseeded 17:58); the same at 03:20 (the Inbox tabs follow-up; reseeded 03:20); the same as 2026-10-05 22:02 (the FIVE PAGES pass). Tenant one only; local only. |
| PHPStan L5 · Pint | 0 errors (31 s, a cleared result cache, `PAO_DISABLE=1`) · clean (the changed files) | **V** 2026-10-09 ~17:00 Baku (the ROLE SIGN-IN pass). Before: 0 errors (21 s, from a result cache deleted by hand, `PAO_DISABLE=1`) · clean (the three changed files), **V** 2026-10-06 22:28 Baku (the time-limit follow-up). Before: 0 errors (31 s, from a result cache deleted by hand and run with `PAO_DISABLE=1` — a run under the output wrapper had read the old cache in 0 s) · clean (the changed files), **V** 2026-10-06 18:21 Baku (the team chat pass). Before: 0 errors (1,089 of 1,089 files, 24 s), **V** 2026-10-06 03:21 Baku (the Inbox tabs follow-up; the change is a view, a test and a mutation set). ⚠️ Two runs before it took one second each (result-cache hits): a `clear-result-cache` run under the agent-mode output wrapper printed nothing, and the analysis after it still took one second. Only a visibly cleared cache is a whole-codebase run. Before: 2026-10-05 20:46 |
| Public checks / exposure battery | **12 PASS · 0 FAIL · 0 could not measure** (`SERVED=0`) on `rel_BA`, straight after the 2026-10-06 18:59 Baku swap (19:00–19:02; the drift check passes, self-test 3/3); on `rel_AZ`, straight after the 03:29 Baku swap (03:30–03:35); on `rel_AY`, straight after the 2026-10-05 22:08 Baku swap, and again 2026-10-06 02:39–02:44 — a re-run: the run at 02:31–02:38 answered **1 · 10 · 0**, the edge answering this Mac after its bot check (504s and 307s to the same address in ~80 ms, `/up` included); from the server's own address through the public hostname every page answered 200; no rollback — DEC-t, D-EDGE-CHECKS. Before: on `rel_AX`, 2026-10-05, run straight after the 05:25 Baku swap (and on `rel_AW` at 01:51 that day, on `rel_AV` at 22:14 on 2026-10-04, on `rel_AU` at 18:45 that day, on `rel_AT` at 04:41 that day, on `rel_AS` at 21:32 on 2026-10-03, on `rel_AR` at 17:26 that day, on `rel_AQ` on 2026-10-01 at 19:08, on `rel_AP` at 03:21 that day, on `rel_AO` at 02:10, on `rel_AN` at 21:59 on 2026-09-30, `rel_AM` at 20:42, `rel_AL` at 19:13, `rel_AK` at 17:29 and `rel_AJ` at 12:22 the same day; on `rel_AI`, `rel_AH`, `rel_AG`, `rel_AF` on 2026-09-29); drift check passes, self-test 3/3 (V 2026-10-05, after the 05:25 Baku swap) | **V** `ops/public-checks.sh` 2026-10-01 |
| GitHub checks (CI) | ✅ **green on `89cb2647`** (run 40139824815, 2026-10-25 02:44–03:26 Baku — the push of 30 commits, `rel_BH` → `rel_BV` and their docs, at his word "yes update the github"): every gate; the suite **4,539 passed + 2 skipped** (the auto-memory tests, by design) = the 4,541 of the local runs, 34,116 assertions; PHPStan L5 no errors; money FULL `929 / 10928000 / 0 / 42 / 16 / 0`; marketing 9 · claims 23 · cookies 15. The run on `d3c3ef9a` (38013549058) was green too — so the 10-10 flake below is closed. Before: ⚠️ **red on `8f460c2d`** (run 38010017082, 2026-10-10 04:39–05:21 Baku, a docs-only commit): one flaky check — `OwnerSettingsTest`'s `assertDontSee('4242')` read a Livewire checksum that held 4242 by chance (once in 300 renders, measured); fixed in the tests (`withoutRandomTokens()`), the class swept; the run on the fix is read with `gh run list`. Before: ✅ **green on `351cd844`** (run 38007913457, 2026-10-10 04:10–04:34 Baku): every gate; the suite **4,509 passed + 2 skipped** (the auto-memory tests, by design) = 4,511, 33,894 assertions; money FULL `929 / 10928000 / 0 / 42 / 16 / 0`; marketing 9 · claims 23 · cookies 15. Before: ✅ **green end to end since 2026-10-06** — run 37503697885 on `693b68fc`: every gate; the suite 4,480 passed + 2 skipped (the auto-memory tests — that file lives on this Mac only), 32,957 assertions, 2,125 s; money FULL `929 / 10928000 / 0 / 42 / 16 / 0`; marketing 9 · claims 23 · cookies 15. Again on `02946350` (run 37509822810, docs only, 22:14–22:45 Baku): every gate, the suite 4,480 passed + 2 skipped (32,957 assertions, 1,519 s), money FULL with the same six figures and row digest. And on `1f79446c` (run 37514423506, the time-limit fix, 22:50–23:13 Baku): every gate, the suite **4,481 passed** + 2 skipped (32,960 assertions — the new test), money FULL unchanged. Before: **never ran a gate** — 106 runs (2026-08-20 → 10-04), 104 failed, 2 cancelled, every one at "Prepare the environment" (`OPEN.md` D-CI-NEVER-RAN, closed) | **V** `gh run view` and the runs' logs, 2026-10-06 22:10, 22:46 and 23:14 Baku |
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
- **Loose ends (V 2026-09-29, after the `rel_AF` deploy; errors re-read after `rel_BA`: 0 error-level lines since its 18:59:10 Baku swap, 11 warnings, all `security.refusal`, written while the exposure battery ran, and nothing logged during his live look — V 2026-10-06 19:11 Baku; after `rel_AZ`: 0 error-level lines since its swap, 11 warnings, all `security.refusal`, written while the exposure battery ran — V 2026-10-06 03:35 Baku (the counter proven on the same log: 44 warnings since midnight UTC); after `rel_AY`: 0 error-level lines since its swap — V 2026-10-06 02:38 Baku, the app's logs for the UTC days 10-04 and 10-05; after `rel_AX`: 0 logged since its swap — 11 warnings, all `security.refusal`, written while the exposure battery ran — V 2026-10-05, read at 05:30 and again at 17:47 Baku, after his live look):** 0 failed jobs · 0 errors logged since the swap · 0 suspended organisations · 0 break-glass sessions open · 1 admin, 0 invites
  pending · 0 announcements · 0 `/status` incidents.

## Organisations on production (V: all 15 `active`, all internal)
⛔ **Never touch:** `Vyrobel Test Workspace` · `Vyrobel Internal — TEST TENANT (not a customer)`.
⚠️ **`Vyrobel Internal — TEST TENANT`'s trial now ends 2027-03-31 23:59:59 UTC** — extended at his word (F 2026-10-09, "Extend its
trial"); it had ended 2026-10-07 12:05 UTC and closed `/app` to all six role accounts (V: the guarded write's read-back — on trial,
174 days left; an audit row `subscription.trial_extended`). ⚠️ Every other organisation here was made in late September with a
14-day trial, so most are probably lapsed too — `Vyrobel Test Workspace` included. Not read (`OPEN.md` D-INTERNAL-TRIALS).
Probes, one line each, all "(not a customer)": `PUBLIC-01 PROBE` · `ADMIN-P1` to `ADMIN-P5 PROBE` · `ADMIN03-W1 PROBE` ·
`ADMIN-03B PROBE` · `SESSIONS-01 PROBE` · `SESSIONS-01-FD PROBE` · `TONIGHT01 PROBE` · `TONIGHT01B PROBE` · `PW-SIGNUP PROBE`.

## ⚠️ IN FLIGHT — started, not finished
0y. **I18N-01 — every app screen in English + 27 languages (2026-10-27, multi-day, gated G1–G4)** — his brief, saved word for word
    (`briefs/I18N-01.md`). **⛔ At G1: waiting on his rulings** (`OPEN.md` F10). Delivered: `docs/i18n/INVENTORY.md` (the coverage
    map, realm → role → screen, generated by the G1 draft tool `~/vyrobel-local/i18n/tools/inventory.php`) and `docs/i18n/PLAN.md`
    (order of work, choices made, proposals, the lawyer list). E (draft scanner): 193 in-scope GET routes, 162 real pages, ~6,200
    distinct English strings (~35,000 words); 10 translation calls today, no `lang/` (V). V (production, counts only): no user, guest
    or organisation has a non-English language saved. **Nothing in the app changed**; resume from `~/vyrobel-local/i18n/STATE.md`.
0x. **Security, both pages, on his design (2026-10-24)** — his screenshot of the `Owner Security.html` wireframe ("this is what is missing
    inside our security page … color and most of important things"); the audit and a three-part plan posted; **F 2026-10-24: "Both pages
    (Recommended)" and "All of it (Recommended)"** (two deploys: Parts 1+2, then 3). Parts 1+2 built (`HISTORY.md`, "SECURITY REDESIGN"):
    `/app/security` and `/owner/security` on ONE kit (`x-security.*`, `App\Support\Security\{SecurityScore,SecurityOverview,SignInProblems}`)
    — the score ring derived from real settings (one rule for both pages), the warm cards, three columns, compact rows whose forms open in
    centred dialogs, a real "Sign-in blocked" card from the sign-in log with "This was me" (`users.security_alerts_seen_at`, additive),
    recovery codes left; the owner page's near-invisible password boxes and its one-error-under-five-fields (D-OWNER-SEC-ERRORS, 🔬 5×)
    fixed; ⚠️⚠️ login approval refused ON while email is not sent (it was a lockout waiting to happen — `OPEN.md` D-LOGIN-APPROVAL-MAIL).
    Refused as fabrication: a place name, "trusted locations", SMS, re-showing recovery codes, sample apps. **Part 3 next** (login alerts,
    session timeout, password last changed, login approval + approve-from-here on the back office) — drafted, not in the repo yet.
    **✅ Parts 1+2 LIVE on `rel_BU @ c763ff59`** (V swap 2026-10-24 23:36 Baku; public checks 12/0/0; 0 errors since).
    **Part 3 built (2026-10-24/25):** login alerts made real (`NewDeviceSignIn` + `NotifyNewDeviceSignIn`: a sign-in from a device the
    account has not used in 90 days → the bell, and email once email is sent; staff switch = Notification preferences' `new_device_login`,
    one stored setting; an owner's is `account_security`, always on), the session timeout (15 / 30 / 60 min, `EnforceSessionTimeout`; the
    page's own polling never counts as activity), "password last changed" (the User model stamps every path), login approval on the back
    office and Approve / Deny of a waiting sign-in from a signed-in device (both pages) — one `AccountSecurityController` for both realms.
    Additive migration: `users.password_changed_at`, `users.session_timeout_minutes`.
    **V gates on the final code:** MySQL 8.4.10 **4,540/4,541** (23:58–00:25 Baku) — the one failure was mine: my new mutation ids
    `SP-M1`–`SP-M12` repeated the sidebar set's (`MutationSetsStillLandTest`, ids must be unique); renamed `SXP-M1`–`SXP-M14`, that file
    green alone on MySQL (4 tests, 1,112 assertions) and no other test reads the sets · MariaDB 11.8.9 **4,541/4,541** (34,120 assertions;
    00:30–01:00 Baku; JUnit 0 failed, 0 errors, 0 skipped; nothing on 3306 first) · PHPStan L5 0 errors (48 s, a cleared cache) · Pint clean
    (the 21 changed PHP files) · mutations `security-protections` 14/14 (re-run after the rename), `security-redesign` 25/25 · money gate
    **FULL** `929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged (V 01:01 Baku).
    **✅ Part 3 LIVE on `rel_BV @ f426f449`** (V swap 2026-10-25 01:04 Baku, 12 ms; public checks 12/0/0; 1,485/1,485 files match git;
    0 errors since). **✅ His look, signed in — F 2026-10-25 02:37 Baku: "its look good"** (`/app/security` on production; the owner
    page cannot be seen there — production has 0 owner accounts). 🔬 Asked "i hope you didnt remove previous features": checked against
    `rel_BT @ 4923e342` — 0 of the 69 security-area routes gone (77 now), every page route still has a form or button (2FA through the
    panel's route prefix), 0 form fields gone, 0 test files deleted, 0 tests dropped (3 renamed for the new layout; 4,352 → 4,380).
0w. **The one select engine, everywhere (2026-10-10)** — his approval ("the same sort of select and same sort of drop down in the
    entire application, everywhere … test everywhere … on live server"). Every select in the six realms opens the Vyrobel panel through
    one engine included in 19 shells; the combobox retired; the menus share the panel; the console's searchable drop-down restyled
    (`HISTORY.md`, "THE ONE SELECT ENGINE"). Walked locally everywhere; gates green on both engines; the whole harness 1,060/1,060.
    **✅ LIVE on `rel_BG @ 6bde9910`** (V swap 03:38:26 Baku; public checks 12/0/0; live signup walked; 0 errors since). The signed-in
    live walk: offered, skipped at his choice. Recorded for his go: `OPEN.md` D-ADMIN-COMBO.
0v. **The Vyrobel select, on /app/profile (2026-10-09/10)** — his request ("make me very cool drop-downs … first work on this page …
    when I approve … apply it in the entire application"). Language and Timezone are `<x-select>`; Timezone offers every zone (it
    offered five — a person in Baku saw, and could save, Lisbon), searchable, with live clocks (`HISTORY.md`, "THE VYROBEL SELECT").
    Gates green on both engines. **✅ LIVE on `rel_BF @ 94c56965`** (V swap 2026-10-10 00:18:16 Baku; public checks 12/0/0; 0 errors
    since). **His approval:** F 2026-10-10 ~00:34 Baku — "that's very cool. I really like it" — and the app-wide rollout asked for
    at once (→ 0w).
0u. **Top bar bell (2026-10-09)** — his report ("the notification icon is completely messed … no proper spacing … make it proper").
    The unread badge sat INSIDE the bell's circle and a "9+" covered the bell; it now sits on the circle's outer corner, clear of
    the bell, in all three shells (one shared glyph), and the top bars' items are 14px apart (`HISTORY.md`, "TOP BAR BELL"). ⚠️ The
    old prototype placement was pinned by `NotificationBellAppearanceTest` guard 4 — rewritten for the new look at his word, said to
    him. Gates green on both engines. **✅ LIVE on `rel_BE @ 79d0cb60`** (V swap 22:21:55 Baku; public checks 12/0/0; 0
    errors since). **Left: his look.**
0t. **Sidebar position (2026-10-09)** — his report ("upon the page refresh the sidebar is going up again … it shouldn't be moving …
    across the entire application"). Every sidebar (operator, owner portal, admin console) now keeps its exact position across clicks
    and reloads and shows the current item; none slides in any more (`HISTORY.md`, "SIDEBAR POSITION"). 🖥 walked in all three
    realms; gates green on both engines. **✅ LIVE on `rel_BC @ 2e2194e5`** (V swap 19:07:25 Baku; public checks 12/0/0; 0
    errors since). **His look done** (F 2026-10-09 ~19:18 Baku: "Cool it works fine."). Then his "Do this too" on
    D-OWNER-TOPBAR-FADE: the owner portal's top bar stands still too (`HISTORY.md`, "OWNER TOP BAR"); gates green; **✅ LIVE on
    `rel_BD @ 906b7dd9`** (V swap 20:53:32 Baku; public checks 12/0/0; 0 errors since). Left: his look. Recorded for his go: `OPEN.md`
    D-OWNER-TOPBAR-FADE.
0s. **Role sign-in (2026-10-09)** — his report ("I can't log into any of the role … you don't have access to this … back to
    dashboard is also not working"). 🔬 Production's refusal log: one user refused `/app/billing` again and again from the day the
    internal test workspace's 14-day trial ended (7 Oct) — every `/app` page of a lapsed workspace went to the Paywall, which only
    `billing.manage` may open, and the 403's "Back to the dashboard" looped back (`HISTORY.md`, "ROLE SIGN-IN"). Done: his
    workspace's trial extended to 2027-03-31 at his word (V, read back); and the loop fixed in code — only someone who can renew is
    sent to the Paywall, everyone else gets a plain "subscription has ended" page with Sign out and their other workspaces; the
    switcher let through. Gates green on both engines (Numbers). **✅ LIVE on `rel_BB @ 6ca6a10b`** (V: swap 2026-10-09 17:53:48 Baku;
    public checks 12/0/0; 0 errors since). **Left: his look** — sign in as any of the six role accounts (his passwords).
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
    (`HISTORY.md`, "NEW RESERVATION, SECOND PASS"). The review's 20 findings fixed. ✅ **LIVE on `rel_AV` @ `bac3997b`** (V swap
    22:14 Baku; public checks 12/0/0; 1,443/1,443 files match git; 0 errors). **His live look done** (V 2026-10-04 22:5x Baku; he
    signed in himself in the automated window — at the second ask; at the first, that window was still signed out): the no-rooms
    card offers Villa Azure Lumière; 3 steps (no extras); "—" under every night; 10→13 Oct offers Winter on the Double room; the
    dialog opened with the Rates disclosure + "it prices 3 of the 3 nights", and was cancelled (nothing sent); Next refused; 390 px
    with no sideways scroll; email box off with its reason; house rules "Not set" + Settings; 0 console messages; signed out, browser
    closed. ⚠️ His "Winter" season stays unapplied until HE applies it (his protected workspace: look only for the agent).
    ⚠️ His Deluxe Sea View Suite's rate plan is **named "350"** (V read-only, made 28 Sep), so the page says "on the 350 plan" —
    his data, renamed on the Rates page if he meant a price. Two wordings for his go: `OPEN.md` D-NR2-NEXT-NOPRICE, D-NR2-SCOPE-WORDING.
0n. **Calendar, second pass (2026-10-04)** — his report ("https://vyrobel.com/app/calendar?start=2026-09-28&days=14 … it is not
    possible to do reservation. Fix it … if there's any other issue on this page"). 🔬 Why (read-only on production, 19:02 UTC):
    the same as the New reservation page — his working property has no rooms, Villa Azure has 3 units and no price on any night,
    "Winter" saved and never applied, 0 reservations, 0 errors — and the Calendar's dialog offered only a link away. Now: Set
    price and Apply season INSIDE the dialog (each back into it on the same stay, the clicked unit picked, the name back from the
    tab); the properties with rooms offered from the no-rooms dialog and the empty Calendar; a booking made here comes back here;
    the grid marks the nights it cannot sell and says why; past nights greyed; drag-to-move only for whoever may move (seven of
    eleven back-office roles were offered a drop the server refused); a refused move said as an error; the room-type filter kept
    (`HISTORY.md`, "CALENDAR, SECOND PASS"). ✅ **LIVE on `rel_AW` @ `a2831e72`** (V swap 01:51 Baku 2026-10-05; public checks
    12/0/0; 1,447/1,447 files match git; 0 errors). **His live look done** (V 02:15 Baku; he signed in himself in the automated
    window, at the first ask): "Internal Test Property has no rooms yet" + Villa Azure offered → its Calendar with the note (both
    room types, Winter named), the nights gone greyed and the marked nights; "+" on 101 → Double room first, Winter and Set price;
    the Apply view opened, Escape stepped back (nothing sent); the name boxes still aligned (his earlier report); 390 px clean;
    0 console messages; signed out, browser closed. ⚠️ His "Winter" stays unapplied until HE applies it.
0o. **Guests, second pass (2026-10-05)** — his report ("https://vyrobel.com/app/guests … Text is not visible completely. Serach box
    design is not good and its symbol is not well-aligned … the same work as on the previous pages"). 🔬 Why (measured locally on a
    copy of real shapes): below ~1,180 px the table was wider than its card (Status cut, Nationality under the menu on a phone); the
    search squeezed between the chips and the buttons with an "i" against its edge; 📖 "Message" claimed "Sent by email" while
    production's mailer is `log`. Now: the table fits its card at every width (rows stack at 960 px of card and below); the shared
    search box redone for Guests, Owners, Units and Maintenance; Message refuses while email isn't sent and, when it is, goes out as
    the property and says where replies go; the column is "Stay" and says each booking's own state; nationality picked by name, an
    unlisted one kept (`HISTORY.md`, "GUESTS, SECOND PASS"). The review's 13 findings: the HIGH ("Left <date>" for stays nobody left)
    and the four MEDIUM fixed, the low ones fixed or recorded. Recorded for his go: `OPEN.md` D-MAIL-CLAIMS, D-INBOX-DELIVERED,
    D-REVIEW-REPLY, D-MAIL-GUARD-SHAPES, D-GS2-STACK-A11Y and DEC-s. ✅ **LIVE on `rel_AX` @ `8345c86e`** (V swap 05:25 Baku 2026-10-05;
    public checks 12/0/0; 1,448/1,448 files match git; 0 errors). 🔬 The preflight (read-only, from the staged release): email sending
    now "no"; 249 countries named by the host's ICU; his workspace's one guest carries a nationality code the list lacks (a count — the
    case the review's Edit fix exists for); 0 guests anywhere can sign in to the guest portal; no property has a reply-to address.
    **His live look done** (V 2026-10-05 ~17:40 Baku; he signed in himself in the automated window, at the first ask — about twelve
    hours after the deploy; look only): at 1,440 / 1,200 / 1,024 / 390 px no sideways scroll, no cell past the card, no page overflow
    (the full table at 1,440, stacked rows below); the search box 40 px like the buttons, its icon centred to the pixel, no "i"; the
    column "Stay"; the top bar's search on one line; Add guest offers 250 choices; Edit on his guest opened on its saved code "(as
    saved)" and was closed unsaved; Message says email isn't being sent, with the box, switch and Send off (nothing sent); Units,
    Owners and Maintenance share the new search box; 0 console messages; signed out (a read-only check then went to sign-in), the
    automated browser closed.
0p. **Five pages (2026-10-05)** — his request: the Inbox's New message ("2 text is repeated"), Housekeeping and Maintenance ("Text is
    not visible completely", the search box, Board and List, Note / Progress / Part ordered), Units ("there must be an option to edit
    and delete the unit … Add inventory … is so confusing") and Security ("this page design is not good"), "the same work as the last
    pages … creativity, advanced features". Built (`HISTORY.md`, "FIVE PAGES"): every list fits its card at every width; the shared
    search box and dialogs; the stay said by one rule; "Part ordered" marks the part awaited; Units — room types first as compact cards,
    Edit on every row, a Delete that removes only what nothing has recorded (and says why otherwise), several units at once ("101-110"),
    "All properties" asks which property, a Dorm's units no longer fail; Security — two named groups of equal cards, a device signed out
    from its row, the allowlist lockout on remove refused, X called X. Three independent reviews (one finding each on Inbox/Housekeeping/
    Maintenance and on Security; none at the bar on Units, two smaller ones fixed anyway) and a second review of the fixes (one finding,
    fixed: only the unit-code index is answered as "already used").
    Recorded for his go: `OPEN.md` D-OWNER-SEC-ERRORS, D-STAY-WORD. **✅ LIVE on `rel_AY @ c73a97cd`** (V: `ops/release.sh swap`,
    2026-10-05 22:08:19 Baku, 4 ms; public checks 12/0/0 straight after; 1,452 of 1,452 deployed files match git, 0 differ; the
    `.htaccess` drift check and its self-test pass; 0 error-level lines logged since the swap — re-read 2026-10-06 02:38). The
    read-only preflight from the staged release: only Vyrobel Internal has inventory — 2 properties, 2 room types (0 priced), 3 units,
    2 of which Delete would remove; every blocker read in 29 ms. **His live look** (he signed in himself in the automated window,
    ~02:25 Baku on 2026-10-06; look only): Units — the guide, the room-type cards, the table, the Delete dialog for unit 101 opened and
    cancelled (nothing deleted); Security and Housekeeping at 1,280 px as built; all five pages measured at 1,024 and 390 px with no
    sideways scroll and nothing past its card — except the Inbox's filter tabs at 390 px, "Closed" cut to "Clos" (older than this
    pass, the same kind of defect as his report) → 0q. **The look ended early, at the edge:** at ~02:30 the host's CDN put its
    "Checking your browser" page (403) in front of the automated browser, and after it the browser reached only the sign-in page.
    The app did not end the session (V production read 02:44, times only: its row still there, last activity 02:27:56, expiring by
    itself at 04:27:56) — the browser's sign-in cookie had been replaced by a signed-out one; its vyrobel.com cookies were then
    cleared and the browser closed. So Maintenance at 1,280 px and the Inbox's New message dialog were not opened live (both measured
    live at 1,024 and 390 px; both walked locally) → `OPEN.md` D-EDGE-SIGNOUT. The public checks run after that (02:31–02:38)
    answered 1 PASS · 10 FAIL — the edge answering this Mac, not the site (V: a curl from this Mac at 02:35 got 200s; at 02:38 the
    server, asking through the public hostname from its own address, got 200 on every page; 0 errors logged; the re-run, 02:39–02:44,
    12/0/0). ⚠️ No rollback — rule 8 says roll back first; a control ran first, and the ruling is his: `OPEN.md` DEC-t, D-EDGE-CHECKS.
0q. **The Inbox's filter tabs on a phone (2026-10-06)** — seen on his live look of `rel_AY`: at 390 px the five tabs (All, Unread,
    Arriving today, Assigned to me, Closed) ran 24 px past their box and "Closed" read "Clos" — the shared box's `overflow: hidden`
    let it shrink under its own words. Built: at 560 px and below two even rows (All · Unread · Arriving today over Assigned to me ·
    Closed), every word shown — a label wraps rather than being cut on the narrowest phones — with New message across the line
    beneath; above 560 px one row as before. The tab shown is marked for a screen reader (`aria-current`), not by colour alone. 🖥
    local walk at 320 / 360 / 375 / 390 / 430 / 560 / 561 / 768 / 1,024 / 1,280 px: no tab clipped, no sideways scroll.
    `InboxComposeRedesignTest` +2 (13; both RED first), `inbox-compose` IB-M18…M24 caught (24/24). Gates: **4,434/4,434 on MySQL 8.4.10** (32,565 assertions; tree `d24833fe404086c0`, 02:44–03:02 Baku — before two comment dates were corrected) · **4,434/4,434 on MariaDB 11.8.9** (32,565 assertions; tree `78d633402013dbf0` = the final code before these docs; 03:03–03:20 Baku) — both from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped · `inbox-compose` 24/24 caught (no other set aims at the changed view) · the money gate **FULL, unmoved** (`929 / 10928000 / 0 / 42 / 16 / 0`, digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297`; reseeded 03:20 Baku) · PHPStan L5 0 errors (1,089 files, from a cleared cache), Pint clean · the doc-reading tests — 69 files, the list re-derived from the tests (630 tests, 0 failed). Recorded for his go:
    `OPEN.md` D-NOTIFPREFS-320 (found while checking the shared box's other pages). Committed locally (`59799d4d`); pushed
    2026-10-06 with the GitHub and memory pass. **✅ LIVE on `rel_AZ @ 59799d4d`** (V: swap 2026-10-06 03:29:40 Baku, 4 ms; 1 file shipped — the Inbox view; the read-only
    preflight from the staged release: the seven route gates, sessions on `database`, all eleven views compile on PHP 8.4.19 / MariaDB
    11.8.9; public checks 12/0/0; 1,452/1,452 files match git, 0 differ; the drift check passes, self-test 3/3; 0 errors since the
    swap — 11 warnings, all `security.refusal`, from the exposure battery). **Left: his look at a phone's width** — the Inbox is behind
    his sign-in.
0r. **Team chat and a live Messages page (2026-10-06)** — his request ("messages must be owner and the employees as well … toggle
    for client and employees … attachment … picture … voice … notification icon … notification sound … Ajax based runtime … test
    everything with MCP playwright"); the plan posted and executed (`HISTORY.md`, "TEAM CHAT"; `docs/TEAM-CHAT-DESIGN.md`). Built:
    a Guests | Team toggle; team chat for every back-office user (its own tables, members only, a support session refused); the
    Guests side live (a stamp, the page re-reading itself, reading only for a person who is there); the bell, the badges and a
    chime on every back-office page; files, pictures and voice notes in team chat and to owners and Direct guests. The independent
    review's 17 findings fixed (three HIGH: a skipped message, a refresh that read and looped, a refresh that swapped
    conversations); the walk's five (line breaks, the "Direct" chip, the unread dot, the oldest-first opening, a framework
    message). Recorded for his go: D-PROD-CSP, D-TC-LATER, D-TC-PII, D-UPLOAD-LIMITS. Gates: **4,480/4,480 on MySQL 8.4.10** (32,952 assertions; 17:38–17:58 Baku) · **4,480/4,480 on MariaDB 11.8.9** (32,952 assertions; 18:00–18:19 Baku) — both on tree `0b3f80cfe6b338b6` (the code before one Blade comment was corrected and these docs written) and read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped · PHPStan L5 0 errors (a whole run, 31 s at 18:21 Baku, from a result cache deleted by hand — a run under the output wrapper had read the old cache in 0 s) · Pint clean (the changed files) · money gate FULL `929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged (V 17:59 Baku, reseeded 17:58). Committed (`16b15e2f`); pushed with the GitHub and memory pass (2026-10-06, at his word — `HISTORY.md`, "GITHUB AND MEMORY"). **✅ LIVE on `rel_BA @ 16b15e2f`** (V: swap 2026-10-06 18:59:10 Baku, 5 ms; 45 files shipped; the additive migration — the three team chat tables — run from the staged release before the swap, 1 s; the read-only preflight from the staged release: the nine route gates, this host's finfo reads all three browser recording formats as playable, the private disk's folder writable, PHP's ceilings 256 MB (so our 10 MB stands), the twelve views compile on PHP 8.4.19 / MariaDB 11.8.9; public checks 12/0/0; 1,457/1,457 deployable files match git; the drift check passes, self-test 3/3; 0 errors since the swap — 11 warnings, all `security.refusal`, from the exposure battery — and nothing logged during his look). **His live look is done** (he signed in himself; look only, his choice — nothing sent, nothing started): Messages with the Guests | Team toggle and its honest empty states; Team with "No conversations yet" and New conversation (the dialog centred, 480 px, focus inside, his colleagues listed, Escape closes it); the microphone allowed on Team only (`microphone=(self)` there, `()` on the dashboard); the page's own limits from the web PHP — 10 MB a file, 255 MB a request; the stamp every 5 s and the pulse, all 200, no read sent; 390 px with no sideways scroll on both pages; no console message; 0 team rows and 0 team or Inbox bell entries on production afterwards (read-only). ⚠️ A correction, said to him: I told him his workspace had one back-office person — that is "Vyrobel Test Workspace"; he works in the organisation whose working property is "Internal Test Property", where team chat lists five colleagues. He was signed out, the site's cookies cleared and the automated browser closed. Left: a two-person team chat test on production (his go — it writes in a protected workspace).
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
8. ✅ **DEC-f, DEC-g, DEC-i DONE 2026-11-11 (his "do all"); DEC-a to DEC-e and DEC-h are still his.** *(as it stood:)* Rulings DEC-a to DEC-i (`OPEN.md`). DEC-f decides whether the logo removal that shipped stays.
8b. ✅ **A8, A9, A10 and DEC-j DONE 2026-11-11 (his "do all"; `OPEN.md` closed-list).** *(as it stood:)* Say go (or not) on A8, the x-show sweep of 22 elements on other screens (24 until the new reservation page's two went, 2026-10-04); A9, the other see-through dialogs; and A10,
    the string `:style` sweep of 10 elements. Rule on DEC-j (the property allow-list on the maintenance writes; DEC-k, the
    same on Add unit, was ruled and fixed 2026-09-30, and DEC-l, on every rate write, 2026-10-01). DEC-m (message
    attachments) was ruled and fixed 2026-09-30.
8c. ✅ Done 2026-10-02: A11 and A12 fixed, DEC-n ruled ("a pause stops every payout") and built. D-PAYRUN-BUTTON fixed
    2026-10-03 (the owners module pass).
8d. ✅ **DEC-q DONE 2026-11-11; DEC-o and DEC-p are STILL HIS.** *(as it stood:)* Rule on DEC-o (switching an owner's portal access from their page — and if so, does their team go too, and does someone
    who also works here keep their login?), DEC-p (giving an existing owner units later; today only their invite does) and
    DEC-q (should rate proposals check the person's properties, as every other rate and unit write does?).
8e. ✅ **D-STOREFRONT-ORPHAN-GUEST, D-GB-SYMBOL, D-RATE-DERIVED-PRIMARY, D-RACK-START-TZ DONE 2026-11-11; D-MONEY-COMMA closed 2026-10-28; DEC-r is STILL HIS.** *(as it stood:)* Rule on DEC-r (offer the existing guest when a new guest's email matches one) and say go (or not) on D-MONEY-COMMA (refuse
    "150,50"-style amounts in every money field, not only the booking wizard's). Smaller, recorded: D-STOREFRONT-ORPHAN-GUEST,
    D-GB-SYMBOL, D-RATE-DERIVED-PRIMARY, D-RACK-START-TZ (`OPEN.md`).
8f. ✅ **A13 DONE 2026-11-11 (the Rates page now flags unapplied seasons); applying the "Winter" season itself was not checked this pass.** *(as it stood:)* Apply his "Winter" season (€200, Double room, 28 Sep–31 Oct) — from the booking page's "Apply Winter…" or the Rates page —
    so Villa Azure can take a booking; and say go (or not) on A13 (flag unapplied seasons on the Rates page).
8g. (2026-10-09/10) Say go (or not) on D-INTERNAL-TRIALS — the other workspaces' trials have probably lapsed; the same guarded one-row
    extension (or an "Extend trial" console action) per workspace, his yes each time — and on D-ADMIN-COMBO (fold the console's own
    searchable drop-down into the one select engine). The signed-in live walk of the select engine was offered and skipped at his
    choice: a look of his at any back-office page with a drop-down is the remaining live check.
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

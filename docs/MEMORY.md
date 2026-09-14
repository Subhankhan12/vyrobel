# Vyrobel — WHAT THIS PROJECT LEARNED

**Written 2026-09-05.** ⚠️⚠️ **THE FIGURES BELOW ARE CORRECT AS HISTORY AND STALE AS CURRENT STATE.** Each
carries a mark and a date; re-run the command beside it rather than quoting it. A figure without a date is
the defect that sat at the top of `CLAUDE.md` for 86 commits.

**🔬 2,108 words · ~9.5 minutes at 220 wpm — measured 2026-09-05, not estimated.** ⚠️ **If it stops being finishable it stops
working.** Nothing here is repeated from another document: everything else is a **pointer**.

### The four marks — only the first two are facts
**🔬 MEASURED** executed and observed · **🖥 BROWSER** driven in a real browser ·
**📖 ASSERTED** read from source, never run · **📄 RECORDED** written once by an earlier pass, never re-checked.

### Read in this order
1. **`CLAUDE.md`** — what the product is and the non-negotiable conventions. ⚠️ Audited 2026-09-05; **nine
   claims were false or stale**, incl. a PWA claim that was never true. See `docs/CLAUDE-MD-AUDIT.md`.
2. **`docs/STATE.md`** — the dated state of each track. ⚠️ Last updated **2026-08-28**; predates the whole
   billing/auth/season sequence. Honest about its own staleness; trust its marks, not its recency.
3. **This file** — the rules, the traps, the pins, the rulings, the blocked-on map.
4. **`docs/INSTRUMENT-ERRORS.md`** — the full trap catalogue. Read before writing any guard or probe.
5. Then the per-topic document for whatever you are touching (§7).

---

## 1 · ⚠️⚠️ The rules that settled real arguments

Each was decided once, against a concrete case. **They are the most reusable thing here.**

**On stating things**
- ⚠️⚠️ **A wrong number stated confidently is the worst outcome available.** Established three times: a €0
  charge with `tax_known: true`; a stale counter-offer shown to an operator; a false decline.
- **Never type a number into a page.** A figure must be derived from the thing it describes.
- **Never promise a future date.**
- **Never negate a false claim by quoting it** — *"does not ship PWAs"* under a PWA heading plants the idea.
  **Remove the subject.**
- **Never soften a false claim into an unfalsifiable one** — *"is PWA-ready"* is the same claim with better
  manners. **Correct it or delete it.**
- ⚠️ **A count is a claim. Prefer a pointer.** *"defined in `config/authorization.php`"* cannot go stale;
  *"there are 11 roles"* already did. Every count this sequence touched moved.

**On building**
- **A case removed beats a case handled** — re-fetch the row instead of reasoning about a watermark.
- **Resolve from the object, never from intent** — the Stripe Price, not the metadata that hoped for it.
- **A term is anything that decides what the customer pays** — so it belongs in the snapshot.
- **Money first, trail second** — never let an audit-row failure roll back a completed payment.
- **Disclosure is not policy.** Telling an operator what a season will overwrite is not deciding whether it
  may.
- **A false undo is worse than none**, and a false disclosure is worse than silence: whoever trusts a
  warning also trusts its silence.
- ⚠️ **Never widen access to green a gate.** If a role cannot reach what it obviously needs, the permission
  is wrong — report it.

**On checking**
- **Unreachable is not clear.** A check that cannot run must report a third outcome, never a pass.
- **Report, never repair.** A silent correction destroys the evidence that something was wrong.
- **A guard that cannot be written honestly should not be written.** Four candidate rules were measured for
  the test-scope sweep; three over-fired and were rejected, and saying so was the result.
- **A guard that discovers beats one that lists.** Read the models for soft-deletion; do not keep a list.
- ⚠️ **Prove both directions.** A gate proven only in the deny direction may be denying everyone.

### ⚠️⚠️ And the rule that governs all of them

**THE BRIEF IS WRONG ROUGHLY HALF THE TIME.** 📄 Tally over the recent sequence: a correction was found in
**almost every pass**, and the **central premise** was wrong at least three times — the last-admin floor did
*not* reason from a password; the ungated-screen count does *not* change with the role; a negotiated rate
*is* traceable. **STOPPING IS A CORRECT OUTCOME**, and *"I could not construct a failure"* is a real result.

---

## 2 · ⚠️ The traps that fired more than once

**The catalogue is `docs/INSTRUMENT-ERRORS.md` — 🔬 54 instances in 13 classes, counted 2026-09-01.** Read it
before writing a guard. ⚠️ The per-brief running tally (~168 "shapes") is a **different counter** and is not
what the repo records; do not conflate them.

The ones that recur most, with their faces:

| trap | what it looks like |
|---|---|
| ⚠️⚠️ **A scan reads its own needle** — 10× | The comment explaining the forbidden call *contains* it. **A heredoc is a string; comment-stripping does not remove it.** Fired most recently *inside a guard written against it*. Tokenise, and assemble the needle from fragments. |
| **A mutation that did not LAND** | Not a green — not a result at all. Print whether the edit applied. |
| ⚠️ **A green mutation is a GAP** | Unless provably a no-op — and then say why. Every green here that was investigated turned out to be a real hole. |
| **`can()` is FALSE with no team** | 3 sightings. spatie teams mode: an unset team denies everything, so a permission test "passes" by denying a user who holds it. |
| **`withoutGlobalScopes()` drops soft-deletion** | 2 in `app/`, 2 false greens in tests. A refusal assertion passed against a removal that *had* gone through. |
| **`artisan serve` serves a STALE `app/` class** | An HTTP A/B on a source change looks identical both ways. ⚠️ **Never use it for a code-level claim.** |
| **`pkill -f "artisan serve"`** | Kills the **parent**; the child `php -S …` keeps listening. Verify by full command line, kill by PID from the listening port. |
| **An assertion behind a null check** | `$s['reservations'][0] ?? null` where the fixture has no such key — the test passes while checking nothing. |
| **`RefreshDatabase` is deliberately OFF** | The test DB persists. Fixed ids accumulate and break *neighbouring* files on the second run, not the first. |
| **`not->toContain($needle, $msg)`** | Variadic — the message becomes a second needle, and the assertion is unconditionally true. |
| **A vacuity floor satisfiable by deleted rows** | "Plans exist" passed with every plan trashed. |

---

## 3 · ⚠️ The pinned figures — **two distinct sets, and they were being confused**

### ⚠️⚠️ A correction recorded here because it was mine
Recent passes reported *"money gate `mode=FULL`, all six figures"* while actually quoting the **pinned
counts** below. **Both sets are real and both were unmoved — but they are different gates, and `mode=FULL` is
the money gate's vocabulary.** Borrowing a gate's label for a different measurement is the same class of
error this sequence spent its time catching. The gate below has now been run properly.

**A · The money gate — `ops/money-as-seeded.sh`**
```
export VY_SEED_DATE=2026-09-01 && php artisan migrate:fresh --seed && bash ops/money-as-seeded.sh
```
🔬 **2026-09-05 — `mode=FULL`, all six unchanged: `929 / 10928000 / 0 / 42 / 16 / 0`**
(rate_calendar / SUM(price_minor) / unit_rate_overrides / reservations / payments / rate_seasons).

⚠️ **QUOTE IT WITH ITS MODE.** `INVARIANT` mode asserts only four figures and deliberately stops catching a
changed rate price or booking set. ⚠️ It has also refused a **PARTIAL** database rather than compare against
it — correctly, since that would blame whatever code was in the tree.

**B · The pinned counts** — `php artisan tinker` / a `DB::table(…)->count()`
🔬 **2026-09-05, and date-invariant**: `owner_ledger` **29 rows / 730640** · `plans` **4** ·
`plan_features` **12** · `role_has_permissions` **260** · `permissions` **51** · `roles` **14** ·
`model_has_roles` **14**.

⚠️⚠️ **`model_has_roles` was re-pinned 7 → 14 on 2026-09-05, and the reason is what makes it legitimate:**
seven back-office roles had no demo account, so seven role **assignments** were added — no role, permission
or money row changed. **A re-pin without an attributed cause is a moved goalpost.**

**C · The storefront figures are DATE-RELATIVE.** ⚠️ **Record them, never pin them.** The demo seeder is
deliberately date-relative so a prospect never opens a storefront full of past bookings.

---

## 4 · ⚠️⚠️ Open rulings — **the founder's. A session must not decide these.**

| question | options / cost | costed in |
|---|---|---|
| **A provenance column on `rate_calendar`** | ~1 migration + 4 writer edits + backfill-as-unknown. ⚠️ **One answer to two questions**: it makes undo possible *and* closes the owner-self-set-rate gap | `AGREED-RATE-DISCLOSURE.md` §2, `SEASON-EDIT-DELETE.md` |
| **May a season overwrite an agreed rate at all?** | warn-and-proceed (built) · second confirmation · refuse · notify the owner | `AGREED-RATE-DISCLOSURE.md` §3 |
| **Three permission rulings** — the tax/fee mismatch (matrix promises what the route refuses) · 8 featureless permissions · the ungated screens | each moves `role_has_permissions` (260) or widens access | `PHANTOM-PERMISSIONS.md` |
| **`/owner` answers 200 to operators** | protected by scope, not by a permission; `owner_portal.view` gates none of those routes | `DEMO-ROLE-ACCOUNTS.md` §3 |
| **Should plans be deletable?** | recommended **against**; a plan with subscribers 404s their billing page | `PLAN-CATALOGUE-RESIDUALS.md` |
| **A grace period for a failed payment** | the status is accurate; what *gates* on it was deliberately not changed | `BILLING-EVENT-RESOLUTION.md` |
| **Option (c)** — a 2FA code substituting for a password | reported, never ruled | `PASSWORDLESS-GATE.md` |
| **Notifying an owner** when their rate is replaced | needs the notification path + a volume decision | `AGREED-RATE-DISCLOSURE.md` §6 |
| **Socialite vs the direct OAuth implementation** | direct was chosen to avoid an unasked-for dependency; nothing above the interface would change | `GOOGLE-SIGN-IN.md` |
| **Tap targets to the stricter threshold** · **the legal document's self-contradiction** (author + lawyer) | — | the design + legal records |

### ⚠️ Standing exposures — nobody's decision, just true
- **52 test sites** carry a bare `withoutGlobalScopes()` on a soft-deleting model where nothing is trashed
  **yet**. Inert today; armed the day someone adds a `->delete()`. — `TEST-SCOPE-SITES.md`
- **First-wins monotonicity** on settlement, and the **out-of-order watermark** for status and period.
- ⚠️ **`docs/KNOWN-LIMITS.md` is UNTRACKED** — never committed, and `CLAUDE.md` does not mention it. 🔬 2026-09-05.
- ⚠️ **36 of this sequence's `docs/*.md` are untracked.** 🔬 2026-09-05. The records exist only in the working tree.

---

## 5 · What is blocked on what

**Blocked on a SERVER** — the domain · the certificate · Redis · the first-deploy checklist · running the
reconciliation against real traffic. ⚠️ **The scheduler cron is *not* blocked**: `ops/deploy.sh` step 10/12
installs it. `DEPLOYMENT.md`.

**Blocked on STRIPE credentials** — live keys · **both** webhook secrets (account *and* Connect) · Connect
enablement · the four Price IDs · the `PAYMENTS_GATEWAY` flip. Identifiers **B7–B11**, `GO-LIVE-REQUIREMENTS.md`.

**Blocked on the FOUNDER** — real prices · the Google console client (**B18**) · the lawyer (**B12**, **B13**) ·
every ruling in §4.

### ⚠️ What is proven, and what needs a human
- ⚠️⚠️ **A faked provider proves the application's half and nothing else.** Never report it as a round trip.
  A real OAuth round trip **cannot be automated** — Google blocks it.
- 📄 **MCP Playwright does not launch in this environment** (five passes hit the same blocked helper).
  **`chrome-headless-shell` over CDP is the working route.**
- Needs a human at go-live: that Google accepts the client and each redirect URI · that the consent screen is
  right · that one real account completes the flow · a real card · a real email delivery.

---

## 6 · Where the detail lives

**Billing** `BILLING-PURCHASED-PLAN` · `-SHAPE-FROM-SNAPSHOT` · `-PLAN-ASSUMPTIONS` · `-INVOICE-ORDERING` ·
`-EVENT-RESOLUTION` · `POST-COMMIT-TRAIL` · `RECONCILIATION` · `PLAN-CATALOGUE-RESIDUALS` · `PRICING-PAIRING`
**Auth** `PASSWORDLESS-GATE` · `GOOGLE-SIGN-IN` · `SOCIAL-AUTH-DESIGN`
**Access** `PHANTOM-PERMISSIONS` · `DEMO-ROLE-ACCOUNTS`
**Pricing** `SEASON-EDIT-DELETE` · `AGREED-RATE-DISCLOSURE`
**Correctness** `SOFT-DELETE-SCOPE` · `TEST-SCOPE-SITES` · `INSTRUMENT-ERRORS`
**Interface** `DIALOG-CONTRACT-COMPLETE` · `CONTROL-NAMES` · `GUEST-HEADINGS` · `CLOAK-CENSUS` · `REMAINING-OVERFLOW`
**Shipping** `DEPLOYMENT` · `GO-LIVE-REQUIREMENTS` · `KNOWN-LIMITS` (⚠️ untracked) · `CLAUDE-MD-AUDIT`

All under `docs/`. ⚠️ **A path that rots is guarded**: `ProjectMemoryTruthGuardTest` fails if `CLAUDE.md`
points at a document that does not exist, if a pinned framework constraint drifts from `composer.json`, or if
a service worker or `rel="manifest"` appears without the documentation changing.

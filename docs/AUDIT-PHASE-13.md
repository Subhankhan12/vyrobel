# AUDIT — PHASE 13 · THE OWNER PORTAL · A DIFFERENT INSTRUMENT

**Realm:** owner (`/owner`) — `web` guard, `External Owner` role, authorization by **capability matrix**.
**Accounts driven:** `demo-owner@` (primary, acts as co-owner) · `demo-owner-accountant@` (role
`accountant`) · `demo-owner-viewer@` (role `view_only`) — plus `demo-staff@` (Hotel Manager) used once, to
test the portal's admission gate.
**Instrument:** MCP Playwright → `chrome-headless-shell` via the project `.mcp.json`
**Date:** 2026-09-12
**Application code changed:** none — verified, §9. **Suite not run:** no application code changed, so it
is not required. Stated per the brief.

**Evidence tiers.** 🔬 MEASURED · 🖥 BROWSER · 📖 ASSERTED · 📄 RECORDED. **Only 🔬 and 🖥 are facts.**

---

## 0. #1 — THE CAPABILITY MODEL, DERIVED FROM CODE

**There is no `can:` middleware here.** 🔬 The model is **7 capabilities over 3 fixed roles**
(`app/Support/Owner/OwnerTeamRole.php`):

| capability | co_owner | accountant | view_only |
|---|---|---|---|
| `CAP_FINANCIALS` (`financials.view`) | ✓ | ✓ | ✓ |
| `CAP_DOCUMENTS` (`documents.view`) | ✓ | ✓ | — |
| `CAP_CALENDAR` (`calendar.view`) | ✓ | — | ✓ |
| `CAP_EXPORTS` (`exports`) | ✓ | ✓ | — |
| `CAP_ACTIONS` (`actions.write`) | ✓ | — | — |
| `CAP_PAYOUT` (`payout.manage`) | ✓ | — | — |
| `CAP_TEAM` (`team.manage`) | **— held by NO role** | — | — |

### Where each check lives — and the architecture is sound

| layer | mechanism | sites |
|---|---|---|
| Route gate | `EnsureOwnerCapability` → `OwnerContext::can()` — **a UNION across the user's owners** | 🔬 applied **27×** (`$capActions`), plus `$capPayout` 4, `$capExports` 5, `$capCalendar` 5, `$capDocuments` 2 |
| **Write/row layer** | **`OwnerContext::canOn($model->owner_id, CAP)` — the authoritative PER-OWNER check** | 🔬 **16 call sites** |
| UI | `@ownercan` Blade directive (union) | 🔬 36 uses |
| Team management | **`EnsurePrimaryOwner`** — not a capability at all | 🔬 route group + 3 routes |

🔬 The 16 `canOn()` sites are in exactly the right places — every write controller (block, expense
approval, unit status, rate proposal, payout method, attention) and every sensitive PDF (statement,
remittance, archive zip). **The union can only open the door; `canOn` refuses the row.** That is
defence-in-depth done correctly, and it is the opposite of F-18's shape — here the authoritative check
*is* called, on the layer that writes.

⚠️ **One latent asymmetry, not reachable today.** `OwnerContext`'s own docblock says a capability is
granted per-owner *"NEVER as the union/highest across all"* — yet the route gate uses the union `can()`.
For a user linked to two owners with different roles, the route gate would admit them on their stronger
role and leave `canOn` to refuse the row. 🔬 **With one seeded owner every user has exactly one owner, so
union ≡ per-owner and this cannot be exercised.** Recorded as latent, like F-18, not scored.

### The matrix, verified by measurement — not by reading

🔬 Each account walked all 35 plain owner GET screens:

| account | REACHED | DENIED | DOWNLOAD | denials match its grid? |
|---|---|---|---|---|
| **primary** (co-owner) | 25 | **0** | 10 | ✓ holds everything |
| **accountant** | 18 | **7** | 10 | ✓ exactly `add-unit · bookings · calendar · payout-method · rates · team · units` = CALENDAR + ACTIONS + PAYOUT + team |
| **view_only** | 19 | **15** | 1 | ✓ exactly DOCUMENTS (`documents`,`tax`) + all 8 EXPORTS + ACTIONS + PAYOUT + team |

**The matrix holds precisely in the browser.** No capability over-grants and none under-grants.

### The phantom equivalent — 2 of 7, both explicable

- **`CAP_FINANCIALS`** — 🔬 granted to all three roles, named in the team UI and mapped by `OAuthScopes`,
  but **no route applies it**. Since every role holds it, a gate on it could only exclude a non-owner —
  and `EnsureOwnerCapability:43` skips non-owners by construction. **Structurally inert as a gate.**
  ⚠️ The consequence: the money screens (`balance`, `payouts`, `reports`, `insights`, `expenses`) carry
  **no capability gate at all**. Functionally identical today; a fourth role added without
  `CAP_FINANCIALS` would still reach them.
- **`CAP_TEAM`** — 🔬 deliberately held by no role and referenced only in its own class and one test; the
  real gate is `EnsurePrimaryOwner`. Documented and intentional.

---

## 1. 🟠 F-23 (MEDIUM) — **there is no positive "is an owner" gate.** No data leaks; the surface misleads.

🔬 Verified in code first: `SetOwnerScopeForPortal::handle()` contains **no abort and no redirect** — it
always calls `$next()`. Its own docblock states the model: *"the portal ALWAYS activates the scope. A user
with no linked owner activates it with an EMPTY set → the OwnerScope returns nothing (never all owners)."*
The group is `['auth', SetOwnerScopeForPortal]` — **fail-closed by data scope, not by admission.**

🖥 Measured with a Hotel Manager (`demo-staff@`, not an owner):

| URL | result |
|---|---|
| `/owner` | **HTTP 200** — full portal, nav reading "EARNINGS · Statements · Payouts · Balance", greeted *"Good morning, Demo"* |
| `/owner/balance` | **200** — "Balance & withdrawals", 🔬 **€0.00 ×3**, "No balance activity yet." |
| `/owner/payouts` | **200** — 🔬 **€0.00 ×3**, "No payouts yet." |
| `/owner/settings` | **200** — **their own** profile, *"67% complete"* |
| `/owner/statement-history/csv` | **file downloaded** |
| `/owner/team` | **403** — `EnsurePrimaryOwner` catches this one |

**🔬 THE SCOPE HOLDS COMPLETELY. No owner data leaks.** Every money figure is €0.00; zero data rows. And
the downloaded CSV is 🔬 **2 lines** — a watermark naming the downloader (*"Downloaded by Demo Staff
(demo-staff@…) · Confidential"*) and a header row. **Zero data rows.** The export watermark works.

**So this is not a breach. It is an unguarded admission with a misleading surface:** a staff member who
mistypes a URL lands in a complete-looking owner portal addressed to them by name, inviting them to
finish their profile and offering "Balance & withdrawals".

⚠️ **The sharp point is the asymmetry.** Invariant #26 correctly bounces an owner *out* of `/app` with a
redirect — and every phase has been warned not to mis-report that. **The symmetric protection does not
exist**: an operator can wander into `/owner`.

---

## 2. #2 — CAN AN OWNER UNDERSTAND IT ALONE? **On denial, yes — better than anywhere in the operator realm.**

🖥 Every capability refusal renders:

> **You don't have access to this.** Your owner account doesn't include this area. **If you need it, ask
> the primary owner on your account to grant the capability.** · *Back to your portal*

🔬 Identical and correct on all five denials tested (`tax`, `documents`, `rates`, `payout-method`,
`team`). **This is the best denial copy the programme has measured.** It names the capability model, names
**the specific human to ask** — the primary owner, who actually can grant it — and offers a way back.
For a third party with no colleague and no front desk, that is exactly the right answer, and it stands in
direct contrast to F-13's silence in the operator realm.

🔬 **And there is a route to a human:** `/owner/help` renders "Help & support" with a real **Ask** form
posting to `/owner/help/ask`. The product does not simply stop.

⚠️ **Where it fails is not refusal — it is silence about change.** §3.

---

## 3. #3 — THE TWO SILENCES, ESTABLISHED BY READING. **Nothing was archived.**

🔬 The complete set of owner notifications is **8**: `AccountSecurity` · `ArrivalSecretRevealed` ·
`OwnerMessageReceived` · `OwnerNewBooking` · `OwnerReviewPosted` · `PayoutSent` · `StatementReady` ·
`UnitRequestReviewed`.

### 🟡 F-24 — an archived property notifies the owner of nothing

🔬 `PropertyRemovalService::archive()` hides the property row by `SoftDeletes` while **all ~20 child
tables survive intact**. 🔬 **Zero notifications, listeners or mailables reference a property archive.**

**What an owner would see:** the property (and its units) disappears from the screens that traverse a
property; their **money history survives** — and that is deliberate and well-handled.
`OwnerPortalService:33` states the rule outright: *"the property was archived; the money was not. **Money
that was earned does not stop having been earned.**"* `:60` explicitly handles *"a unit whose PROPERTY has
been archived out from under it."*

**So the financial consequence was thought through carefully; the communication was not.** An owner
watching their portfolio shrink has no message, no entry, and no explanation — and 📄 it is irreversible
in-app. **Established by reading. I archived nothing.**

### 🟠 F-25 — an owner cannot discover that a season replaced their negotiated rate

📄 The operator's apply dialog says, in as many words, *"Applying replaces those prices, **and the owner is
not told**."* This phase establishes what the owner can find out afterwards. 🔬 **Nothing:**

| possible route to discovery | measured |
|---|---|
| A notification | ❌ none of the 8 covers a rate change |
| A listener on the event | ❌ the **only** listener on `rate_calendar.updated` is `EnqueueAriPushOnInventoryChange` — it pushes to **channel managers, not owners** |
| A rate history | ❌ `OwnerRates:197` reads `rate_calendar` for **today** ("real, or 'Not set'"). No prior value exists anywhere — Phase 11 proved `before` is `null` at all 11 rate-path audit sites |
| A statement line | ❌ a statement carries the net effect, never the nightly rate that produced it |

🔬 **The only trace is the owner's own approved `rate_proposals` row**, which records what was *agreed*.
An owner could infer a replacement by manually comparing that against today's figure on `/owner/rates` —
**if they thought to look.** ⚠️ And `/owner/rates` is `actions.write`-gated: 🔬 **co-owner only.** An
accountant or view-only sub-user cannot perform even that comparison (both measured 403).

**This is the missing half of a disclosure the project deliberately built.** F-21 established that the
operator's warning has an invisible scope; F-25 establishes that the owner's side of it does not exist at
all. Both belong in the open ruling, now with evidence from inside the portal.

---

## 4. #4 — THEIR MONEY: **the figures reconcile EXACTLY between the two realms and with the database**

🔬 Same owner, same moment, both realms:

| figure | owner's view | operator's view | database |
|---|---|---|---|
| Paid this year / Paid out | **€2,804.40** | **€2,804.40** | 🔬 `payouts` paid sum **280440** = `owner_statements` paid sum **280440** — difference **0** |
| Pending payouts / Next payout | **€442.80** | **€442.80** | 🔬 the one `pending` payout, 44280 |
| Balance / Net position | Available €1,406.40 · On hold €442.80 · In transit €0.00 · Paid €2,804.40 | **€4,653.60** *"Net of every owner-ledger line"* | — |

🔬 **And the Balance identity holds to the cent:** 1,406.40 + 442.80 + 0.00 + 2,804.40 = **€4,653.60**.
**No drift. One number, two views, same answer.**

⚠️ I nearly reported the opposite — see near-false-positive #36. It was my scraper, not the product.

### Guest PII in the owner realm — the rule inverts, and it holds

🔬 Measured by kind, never transcribed, across `bookings · calendar · units · balance · expenses ·
reviews · messages`:

| | emails | phones | card-like | IBAN-like |
|---|---|---|---|---|
| every owner data screen | **0** | **0** | **0** | **0** |

🔬 And on `/owner/balance` the owner's **own** payout IBAN renders **masked**. An owner sees their money
and their units; they see no guest email, no guest phone, and no card data anywhere.

---

## 5. #5 — A SEPARATE DESIGN SYSTEM, VERIFIED NOT INHERITED

### 375px — 🔬 `bodyOverflow: 0` on **all 23** owner screens measured

The element-level overflows are (a) `vy-amb-streak1` ambient decoration, 120px, on every screen, and
(b) `vy-table` inside its own scroller on the data screens (bookings 337 · expenses 304 · payouts 306 ·
archive 198 · balance 187 · reports 124). **Since the body never scrolls sideways, the tables are
correctly contained** — the same pattern the operator realm reached.

⚠️ 📄 The brief reports the owner shell *"received HALF the operator's overflow fix"*. 🔬 **Whatever
remained has landed**: zero body overflow, every screen, at 375px. Said as plainly as a defect.

### Headings — 🔬 2 of the 3 recorded double-headings confirmed; the third is not a page

🔬 `/owner/agreement` (2 `h1`) and `/owner/welcome` (2 `h1`) — **confirmed for all three accounts**.
The third recorded screen, `/owner/agreement/pdf`, is 🔬 a **PDF download** with no DOM, so this
instrument cannot count its headings: **NOT EXAMINED**, not refuted. 🔬 Zero screens with no `h1`.
📄 All three were "left alone by instruction" — so this is confirmation of a known item, not a new one.

### Console and clicks — F-20 does **not** reproduce

🔬 Console captured on **every screen × all three accounts = 105 loads**: `appErrors: []` for every
account. The only console output is the 403 document's own status on gated screens (7 for the accountant,
15 for the viewer). 🔬 **47 buttons clicked** across `overview · balance · payouts · units · calendar` —
**zero errors thrown**.

**F-20 remains bounded to its single instance on `/app/rates`. The class is not reopened.**

---

## 6. #6 — ISOLATION BETWEEN OWNERS: **UNTESTED, not passed**

🔬 **`owners` = 1.** One owner, three users (primary + `accountant` + `view_only`), all on the same owner.

**So owner-to-owner isolation could not be exercised at all, and I did not build a second owner** (it
would move pinned figures). ⚠️ **This is UNTESTED, not passed** — stated plainly, as the brief requires.
What *was* testable and did hold: the per-owner `canOn()` boundary (§0), the sub-user capability grid
(measured exactly), and the empty-scope fail-closed behaviour for a non-owner (§1, €0.00 everywhere).

**What a second owner would make testable** — and should be the first thing Phase 15+ or a build phase
arranges: cross-owner statement/ledger/unit reach by URL and by identifier, and whether the union `can()`
gate (§0) admits a multi-owner user to a capability they hold on only one of their owners.

---

## 7. #7 — MEASURED BEFORE, UNCHANGED AFTER

| table | before | after |
|---|---|---|
| **`rate_calendar`** | 929 / 10912000 / `4fc27b4af1076e86c0d268056067ffa4` | 🔬 **byte-identical** |
| `owner_ledger` (sum **730640**) · `owner_expenses` · `owner_statements` · `owner_statement_lines` | 29 · 6 · 6 · 24 | unchanged |
| `owners` · `owner_users` · `owner_team_invites` · `management_agreements` | 1 · 2 · 1 · 1 | unchanged |
| `properties` · `units` · `reservations` · `payments` · `messages` | 2 · 8 · 42 · 16 · 9 | unchanged |
| `audit_events` · `domain_events` | 160 · 196 | **160 · 196 — zero residue** |

**Third consecutive zero-residue phase.** Nothing was archived, no season applied, no proposal accepted or
declined, no money route touched. 🔬 **All 14 demo accounts authenticate — including all three owner-realm
accounts.** 🔬 `DemoDataSafetyGuard::DEVELOPMENT_ENVIRONMENTS` still `['local','testing']`.

---

## 8. ⚠️ WHAT THIS BRIEF GOT WRONG

1. **"Ten owner screens had their own overflow offenders… it received HALF the fix."** 🔬 Body overflow is
   now **0 on all 23 screens** measured at 375px. The remaining half has landed (§5).
2. **"Three double-heading screens."** 🔬 Two are confirmable in a browser; the third is a **PDF
   download** with no DOM. Not refuted — not measurable by this instrument (§5).
3. **The owner surface is larger than recorded.** 📄 The prior audit counted **29** owner screens;
   🔬 there are now **35 plain + 12 parameterised = 47**. Re-measured, not inherited.
4. **"No positive is-an-owner gate — verify it."** 🔬 **Correct**, and now measured end-to-end (§1) —
   including the important half the record did not state: **no data leaks when it is exercised.**

---

## 9. #8 — NOTHING FIXED

🔬 `git diff --name-only` filtered by session mtime returns **zero tracked files**. The only repo write is
this document. F-1 … F-22 stay open; **F-23, F-24, F-25** are added.

---

## 10. NEAR-FALSE-POSITIVES — three new, **38 total**

36. **⚠️ A one-cent money discrepancy between the owner and the operator — which does not exist.** My
    label-pairing DOM scraper produced `"Paid this year" → €2,804.39` and `"Balance" → €4,653.59`.
    🔬 Searching the rendered text for those literal strings returns **zero occurrences** on either page;
    both render €2,804.40 and €4,653.60. I then chased the phantom through two wrong mechanisms (float
    truncation in `OwnerPayouts`, then in `MoneyFormatter` — 🔬 `display(280440,'EUR')` returns €2,804.40
    correctly) before testing the premise. **On a money figure, grep the rendered text for the literal
    string before believing your own extractor** — and test the premise before the mechanism.
37. **"Every owner capability gate is defined and never applied."** My shell escaping (`\$$v`) made
    `grep` report **0** references for `$capActions`. 🔬 It is applied **27 times**. That would have been
    the largest false finding of the programme. **Verify a zero with a second, differently-shaped query.**
38. **"The money screens are ungated, therefore under-protected."** 🔬 They are ungated *because*
    `CAP_FINANCIALS` is held by all three roles and the middleware skips non-owners — the gate would be a
    no-op. The real protection is the empty-scope fail-close, measured working (§1). **An absent gate is
    not the same as an absent boundary.**

---

## 11. HAND-FORWARD — ⛔ PHASE 14 · THE GUEST REALM (`/guest`)

**Findings open:** F-1 … F-25.

**Baselines for Phase 14 — all unchanged:** `rate_calendar` **929 / 10912000 /
`4fc27b4af1076e86c0d268056067ffa4`** · `audit_events` **160** · `domain_events` **196** ·
`owner_ledger` 29 / 730640 · `owner_expenses` 6 · `owner_statements` 6 · `messages` 9 ·
`reservations` 42 · `payments` 16 · `owners` 1 · `owner_users` 2.

### ⚠️ What will NOT transfer — the data rule inverts

- **The PII test inverts, and this is the single most important carry-over.** In the operator realm the
  rule is *name-only outside the CRM*; in the owner realm it is *your money, never a guest's*. **In the
  guest realm the guest SHOULD see their own email, phone, payments and documents.** 🔬 Measuring "zero
  emails on screen" — the test that passed in Phases 12 and 13 — **would be the wrong test and would
  report a correct screen as a defect.** The right test is: *every field shown belongs to the
  authenticated guest, and none belongs to anyone else.*
- **A genuinely separate `guest` guard over the `guests` table**, not `users`. Neither the operator
  permission model nor the owner capability matrix applies. Derive the model from code first, exactly as
  this phase did — 📄 expect no middleware matrix at all, and self-ownership as the boundary.
- 📄 `guests.email`/`phone` are **encrypted casts** with blind-index login (`sha1($email)`,
  `GuestAccountService:57`), and access is **booking-tied claim**, not open signup. An expired or
  already-claimed link is the interesting path.
- 📄 An **append-only wallet ledger** exists — apply the money discipline (checksum before and after,
  establish-never-perform on anything irreversible), not the CRUD discipline.
- 📄 The prior audit measured **13 guest screens + 14 isolation probes** and found the realm **properly
  responsive at 375px where the operator realm was not**. 🔬 Re-measure the count — this phase found the
  owner surface had grown from 29 to 47 since that audit, so 13 should not be trusted either.
- **Isolation IS testable here** — unlike owner-to-owner. 🔬 `guests` = **10**, so a claimed guest can be
  tested against another guest's reservation, folio, wallet and documents by URL and by identifier.
  📄 Phase 1b already proved a freshly claimed guest could not reach another booking; extend that method
  to every guest surface.

**What DOES transfer:** the console sweep, click-every-button, row-state proof, checksums,
measure-before-touching, the positive control, and — newly earned this phase — **grep the rendered text
for a literal figure before believing an extractor** (#36).

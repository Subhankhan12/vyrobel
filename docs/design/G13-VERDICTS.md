# G13 — THE THREE NO-ROUTE GUEST PROTOTYPES · **VERDICTS + FOUNDER DECISIONS**

**Date:** 2026-08-12 · **HEAD at verdict:** `e869d5d` (SAVE v8 — the design-parity walk closed)
**Status:** ⚠️ **A VERDICT PASS, NOT A GATE.** No product code was written. `git diff` is `docs/*` only.

---

## 0 · WHY THIS IS NOT A GATE

`Guest Help`, `Guest Documents` and `Guest Referral Landing` are the **three prototypes with no live route**
(`GUEST-RECON.md` §4). There is nothing built to render, nothing to diff, no guard to mutate and no contrast to
measure. The gate machinery (§N.4k sweep, contrast anchors, mutation discipline, browser parity) **does not
apply** and was not run.

**What G13 does instead**, per prototype: **decode it fresh → verify against the ENGINE what backend actually
exists → render a verdict (BUILD / DEFER / DON'T-BUILD) → surface it as a FOUNDER DECISION.** The verdict is a
**recommendation**. Nothing here was decided unilaterally, and nothing was built, deferred or deleted.

**This closes the guest realm's prototype accounting:** 32 prototypes → **12 gates** (§G-1…§G-12) + **3 verdicts**
(this document). Every guest prototype now has either a gate record or a verdict.

---

## ⚠️ 1 · THE PREMISE CORRECTIONS — and one that CHANGED TWO VERDICTS (§N.8b)

Five claims were read against the code. **Three survive, one is materially wrong, one needed a qualification —
and two ratified register rows that the recon did not consult moved two verdicts.**

| claim | source | verdict |
|---|---|---|
| **`DO-INV-3`** — *"no public help destination and no configured support address; `MAIL_FROM_ADDRESS` is still the `hello@example.com` placeholder"* | invite-email gate | ✅ **SURVIVES.** `.env.example:72` is still `MAIL_FROM_ADDRESS="hello@example.com"`; `grep -rn "support" config/*.php` returns no support URL or address; no public help route exists. |
| **`DO-G7-2`** — *"a guest cannot start a thread from Messages — only reply"* | §G-7 | ❌ **WRONG — the sixth §N.8b correction, and one of the walk's OWN entries.** `POST /guest/messages` → `GuestMessagesController@start` (`routes/web.php:370`, `throttle:12,1`) calls the certified `InboxService::startThread()` + `postGuestMessage()`, and `guest/messages.blade.php:34-43` renders an **always-visible "New message" form** — not behind the empty state; the `@empty` copy at line 30 even points at it. **RETRACTED in place.** |
| ⚠️ **DIVERGENCE-REGISTER: "Guest Help" — ✅ RESOLVED (`82506bb`)** … *"Given a real destination (a 'Help & support' row → the existing operator relay). **NOT a help centre: its content would have to be invented…** (#39)."* | 2026-08-07 audit, GATE 6 | ✅ **THE FACT SURVIVES; ⚠️ THE REASONING IS NOW STALE.** `guest/account.blade.php:184` really does render a **"Help & support · Message your property directly"** row → `guest.messages`. **So a guest is NOT without a support destination** — ⚠️ **my first read of this pass said they were, and it was wrong.** What is absent is a help **centre** (searchable articles). ⚠️ **But the refusal's stated premise — "its content would have to be invented" — is now FALSIFIED:** the product has since built `HelpKnowledgeBase`, a **curated, real, non-invented** FAQ whose every answer links a real screen. |
| ⚠️ **DIVERGENCE-REGISTER: "Guest Documents hub" — ↺ RECLASSIFIED — RATIFIED (location divergence)** … *"Never a gap: REAL on `/guest/payments`."* | wireframe-parity sweep | ✅ **SURVIVES, with one precision.** `guest/account.blade.php:175` renders **"Receipts & documents"** → `guest.payments`, and `guest/payments.blade.php:112-121` renders a **"Receipts & invoices"** card, one row per booking → `guest.receipt` → the five PDFs. The register's *"invoices, folios, receipts … on `/guest/payments`"* is substantively right, though the PDFs themselves are **one hop further**: Account → Payments → Receipt → PDF is **three hops**. ⚠️ **This is a RATIFIED divergence, so building the hub REOPENS a ratified decision** — which is precisely a founder's call, not the pass's. |
| **GUEST-RECON §4** — Guest Help *"ties directly to `DO-INV-3` … Likely one fix serving both."* | recon | ⚠️ **HALF RIGHT — qualified in §2.4.** One root cause, but opposite sides of the auth boundary. |

> ⚠️ **The lesson this pass adds to §N.13.6.** `GUEST-RECON.md` §4 established *"no live route"* by route-list and
> grep — **and that was true**. But *"no route"* is not *"no function"*: the function had been **relocated** and
> **ratified** in a different document eight months of gates earlier, and the recon (and my first read) did not
> consult `DIVERGENCE-REGISTER.md`. **A prototype with no route is not automatically a gap. Check whether the
> function already lives somewhere else and was ratified there** — otherwise a verdict pass re-litigates a
> settled decision without knowing it.

---

## 2 · 🟡 GUEST HELP — **VERDICT: BUILD the FAQ centre (⚠️ this REOPENS a ratified refusal, on new evidence)** · **DON'T-BUILD the SLA / phone / 24-7 half**

### 2.1 What the prototype shows (decoded fresh from `docs/design/raw/Guest Help.html`)

- **Hero** — a green pill *"Support online · replies under an hour"*, `Help & support`, a property-named
  strapline, and a primary CTA **"Message the host"** → Guest Messages.
- **Four stat tiles** — `{{ faqCount }}` Help articles · `{{ catCount }}` Topics · `{{ replyStr }}` **Typical
  reply** · **`24/7`** Urgent line.
- **A search bar** over the FAQ (live filter, clear button, an honest empty state — *"No answers for '…' … ask
  the team"* with a Message-the-host CTA).
- **FAQ list** — category chips with per-category counts, then an accordion of Q/A, each answer optionally
  carrying a **deep link into a real screen** (`{{ f.linkHref }}` / `{{ f.linkLabel }}`).
- **Contact sidebar** — *Message the host* (in-app chat · **under an hour**) · a **phone** `+351 289 400 118`
  (*Call / WhatsApp · 08:00–20:00 WEST*) · an **email** `stays@seasidevillas.ie` (*reply within a day*).
- **Quick links** — Manage booking · Payments · Arrival info · **Documents**.
- **A dark "Urgent during your stay?" card** — an on-call line `+351 289 400 911`, *"open 24/7 for guests
  currently checked in"*.

### 2.2 ⚠️ THE ENGINE — verified, grep-cited

| the prototype's feature | does the backend exist? | evidence |
|---|---|---|
| **A discoverable support destination** | ✅ **ALREADY LIVE** — Account → *"Help & support · Message your property directly"* → the relay. **Not a gap.** | `guest/account.blade.php:179-185` |
| **"Message the host" → a real channel** | ✅ **REAL AND REACHABLE** — `guest.messages.start` → `InboxService::startThread()` + `postGuestMessage()`, guest-scoped, rate-limited. | `routes/web.php:370` · `guest/messages.blade.php:37` |
| **A curated FAQ with categories, search + deep links** | ✅ **THE PATTERN EXISTS AND IS PROVEN — in the owner realm.** `app/Support/Help/HelpKnowledgeBase.php` is a curated article list (`id`/`keywords`/`question`/`answer`/`route`/`label`) read by BOTH the FAQ blade and the assistant so they cannot drift; `HelpRetriever` scores it deterministically. Its docblock is the standard: *"REAL content only — never invented policy, numbers, or commitments (#39)."* ❌ **No guest equivalent exists.** | `HelpKnowledgeBase.php:1-83` · `HelpRetriever.php:40-50` · `livewire/owner/help.blade.php` |
| **A grounded "ask" assistant** | 🟡 **EXISTS, OWNER-ONLY.** `AskVyrobelService` grounds strictly on the KB, escalates to the real relay when it has no answer, degrades honestly on cost caps. `HelpRetriever::retrieve()` **hardcodes** `HelpKnowledgeBase::articles()` — extending it is a parameterisation, not a rewrite. | `AskVyrobelService.php:37-80` · `HelpRetriever.php:50` · `routes/web.php:1147` |
| **`{{ faqCount }}` / `{{ catCount }}`** | ✅ derivable — a `count()` over the KB. | — |
| 🚫 **`{{ replyStr }}` + "replies under an hour"** | ❌ **NO BACKEND.** No SLA field, no first-response metric, no support-hours store. **The identical claim is already a ratified refusal one page over.** | no SLA column on `message_threads`; `guest/messages.blade.php:3-4` (*"NO 'reply within ~10 min' SLA"*) |
| 🚫 **Phone + WhatsApp + hours + the 24/7 urgent line** | ❌ **NO BACKEND.** `properties` has **no phone and no support-email column** — `name`, `brand`, `type`, `address`, `lat/lng`, `timezone`, `star_rating`, `currency`, check-in/out times, `status`, plus the later brand block (`brand_accent`, `brand_logo_path`, `brand_email_from_name`, **`brand_reply_to`**, `brand_powered_by`, `bookings_paused_at`). **`brand_reply_to` is an email reply-to setting, not a published support line**, and it is nullable/fails-open. No on-call roster; WhatsApp is account-gated. **Already ratified as refused, twice.** | `create_properties_table.php` · `add_property_settings_brand_pause_columns.php` · `guest/messages.blade.php:3-4` · `OwnerHelp.php:28-31` |
| 🚫 **A support-ticket queue** | ❌ refused in the owner realm — *"messaging IS the tracked channel; no fake ticket."* | `OwnerHelp.php:29` |

~~There is **no guest help route** (`grep -rn "guest.help\|guest/help" routes/ resources/views` → nothing) and the
guest nav is exactly `Trips · Payments · Wallet · Messages · Saved · Account`
(`components/guest/shell.blade.php:15-21`).~~

⚠️ **SUPERSEDED 2026-08-12 — true when this memo was written, false now.** The founder ruled on `DO-G13-1` and
**split it**: the FAQ centre was **BUILT** (`23bf335` — `GET /guest/help` over the certified
`HelpKnowledgeBase`, with a `Help` nav entry), and the **support half was SCOPED rather than refused**
(§N.15.6). ⚠️ **The rest of this memo's reasoning stands, and the build honoured every refusal it attached to
the page** — no SLA pill, no Typical-reply tile, no phone/WhatsApp/hours, no 24/7 card.

### 2.3 ⚠️ WHAT ACTUALLY CHANGED SINCE THE 2026-08-07 REFUSAL

The audit refused a help centre **for a stated reason**:

> *"NOT a help centre: **its content would have to be invented**, and a page of made-up FAQ answers is worse than
> a direct line to the people who can answer (#39)."*

That reasoning was correct **at the time**. It is no longer sound, and the counter-example is in this repo: the
owner realm's `HelpKnowledgeBase` is a help centre whose content is **not invented** — every article answers a
question the product genuinely handles and deep-links the real screen that handles it, and the same source feeds
the assistant so the two cannot drift. **The refusal rested on a constraint the product has since removed.**

⚠️ **That does not make the refusal wrong to have made, and it does not make reopening it my call.** It makes it
a **founder decision with changed facts** — which is the only honest way to put a ratified refusal back on the
table (the §N.8b/G1 pattern: a record that recorded as impossible something the product has since built).

### 2.4 ⚠️ THE `DO-INV-3` TIE — **RESOLVED: it DEPENDS on a shared root cause; it does NOT resolve DO-INV-3**

The recon read this as *"Same gap, second realm. Likely one fix serving both."* **Half right, and the wrong half
matters.**

- **`DO-INV-3` is a PUBLIC problem.** The owner-invite email's *"Get help"* footer has nowhere to point because
  the recipient **has no account yet** — `/owner/help` is authed and would bounce to login. It needs a **public**
  help destination or a **published support address**.
- **Guest Help is an AUTHED problem.** `/guest/help` would sit behind `auth:guest` exactly like `/owner/help`.
  **Building it gives the invite email nowhere new to point.**

They share ONE root cause — **no support contact is configured anywhere in the product** (verified: the
placeholder `MAIL_FROM_ADDRESS`, no `config` support address, no property phone column). That root cause blocks
Guest Help's **contact sidebar** and **all of `DO-INV-3`**.

> **Resolution: Guest Help DEPENDS ON the same root cause as `DO-INV-3`. It does not RESOLVE it and does not
> SUPERSEDE it. `DO-INV-3` stays OPEN regardless of what is decided here.** Conversely, configuring a real
> support contact is the single change that serves both — **that config decision, not the page, is the shared
> fix.**

### 2.5 THE VERDICT — **BUILD (reopening the refusal), scoped hard**

**Recommendation: BUILD the FAQ centre**, on the grounds that the refusal's premise no longer holds and the
pattern is proven one realm over. ⚠️ **This is explicitly a request to reopen a ratified divergence** — flagged,
not smuggled.

**Buildable honestly today, with no new subsystem:**
1. `GET /guest/help` under `auth:guest` + `SetTenantForGuestRequest`, in `x-guest.shell` (+ a nav entry, and
   repoint the Account row at it).
2. A **`GuestHelpKnowledgeBase`** mirroring the owner KB, answers deep-linking **real** guest routes (Manage
   booking, Payments, Arrival info, Wallet, Account, Check-in, Review). ⚠️ **Content written from what the
   product actually does — never an invented policy, figure or commitment (#39).**
3. Search + category chips + accordion + the honest empty state — client-side over the KB, as `/owner/help` does.
4. **"Message the host" → `guest.messages.start`** — real and reachable.
5. Quick links to the three live routes (the fourth, Documents, only if §3 is built).
6. `faqCount` / `catCount` as real `count()`s.

**Must be REFUSED (#39) either way:** the *"replies under an hour"* pill, the **Typical reply** tile, the
**phone / WhatsApp / hours**, and the **24/7 urgent line**. ⚠️ The last one is not merely dishonest but
**dangerous**: a guest locked out at 02:00 who is shown a 24/7 emergency number nobody answers is worse off than
one shown the real relay.

**FOUNDER DECISION — three paths:**

| path | cost | what the guest gets |
|---|---|---|
| **(a) BUILD the honest FAQ centre** *(recommended)* | ~1 gate. One route + controller + KB class + Blade, mirroring `/owner/help`. **No migration, no new dependency.** Reopens the 2026-08-07 refusal on the stated new evidence. | Self-service answers, plus the relay they already have. |
| **(b) BUILD it AND configure real support contacts first** | (a) **plus a schema + product decision**: per-property support phone / email / hours (and an on-call policy if the 24/7 card is wanted). ⚠️ Publishing a phone number is an **operational commitment** — someone must answer it. **Also unblocks `DO-INV-3`.** | The prototype's full sidebar, honestly. |
| **(c) KEEP THE REFUSAL — don't build** | Zero, and **defensible**: the support path is already discoverable and the relay reaches people who can actually answer. | Status quo: Account → Help & support → Messages. |

⚠️ **Separately and smaller: extend the grounded assistant to guests?** **Recommend deferring.** Ship the
deterministic FAQ first; the assistant reads the same KB and can follow with no rework (it needs `HelpRetriever`
parameterised by KB, `HelpRetriever.php:50`, plus a per-guest cost cap).

---

## 3 · 🟡 GUEST DOCUMENTS — **VERDICT: DEFER** (the function is live and RATIFIED elsewhere; the hub is a convenience) · **DON'T-BUILD the ZIP and the "12 months"**

### 3.1 What the prototype shows (decoded fresh from `docs/design/raw/Guest Documents.html`)

- **Hero** — a *"Balance settled · €0.00"* pill, `Documents`, the strapline *"…ready to download and **kept on
  file for 12 months**"*, and a primary **"Download all (ZIP)"**.
- **Four stat tiles** — `{{ docCount }}` Documents · `{{ balanceStr }}` Balance settled · `{{ invoicedStr }}`
  Total invoiced · **`12 mo`** Kept on file.
- **"Your documents"** — `{{ docs }}` rows, each a PDF tile (`{{ d.kind }}` badge, name, optional status badge,
  `{{ d.meta }}`) linking `{{ d.href }}`; footnote *"kept on file for 12 months. Need something that isn't here?
  **Message the property**."*
- **Sidebar "Get a copy"** — **"Email me all documents"** (*"Sent to lukas.novak@email.com"*) + another **ZIP**.
- **Sidebar "Account"** — Total invoiced / **Refunded** / **Balance due**, a *Settled* chip, *"Paid in full ·
  16 Sep 2025"*.

### 3.2 ⚠️ WHERE A GUEST REACHES THESE DOCUMENTS TODAY — verified

**A rollup already exists, in a different place, and it was RATIFIED as a location divergence:**

```
Account  ──"Receipts & documents"──▶  /guest/payments   (guest/account.blade.php:175)
/guest/payments  ──"Receipts & invoices" card, one row per booking──▶  /guest/reservation/{r}/receipt
                                                       (guest/payments.blade.php:112-121)
/…/receipt  ──▶  5 PDF buttons                          (guest/receipt.blade.php:57-62)
```

| document | route | its ONLY link site today |
|---|---|---|
| Folio Receipt PDF | `guest.doc.receipt` | `guest/receipt.blade.php:57` |
| VAT Invoice PDF | `guest.doc.invoice` | `guest/receipt.blade.php:58` |
| Statement of Account PDF | `guest.doc.statement` | `guest/receipt.blade.php:59` |
| Refund Receipt PDF | `guest.doc.refund` | `guest/receipt.blade.php:61` — **conditional** |
| Credit Note PDF | `guest.doc.credit-note` | `guest/receipt.blade.php:62` — **conditional** |
| Arrival Info PDF | `guest.arrival.pdf` | `guest/arrival.blade.php:10` |

**So the real remaining gap is narrow:** a guest looking for *"my VAT invoice"* is **three hops** away and must
know to open a page called *Receipt* to find it; and there is no single cross-booking list. The **money figures
the prototype's hero shows are already rendered on `/guest/payments`** (`total_charged`, `total_paid`).

| the prototype's feature | does the backend exist? | evidence |
|---|---|---|
| **The documents themselves** | ✅ **REAL**, rendered from the append-only folio ledger via `PdfService` + shared builders, each resolved through `$this->guest()->reservations()->findOrFail()` (#28 — a cross-guest id 404s). | `GuestDocumentController.php:33-72` |
| **Which documents exist per booking** | ✅ **REAL and already computed** — receipt/statement/invoice always; **refund + credit-note only on a real succeeded refund** (`ownedWithRefund()` → `abort_unless($hasRefund, 404)`). | `GuestDocumentController.php:74-86` |
| **`invoiced` / `refunded` / `balance` + the Settled chip** | ✅ **REAL and already shared** — `GuestPayments::overview()` returns `total_charged` / `total_refunded` / `total_balance` across **all** the guest's bookings, integer minor units, reconciling to the cent (#17) against the same `FolioTotals` the operator and owner surfaces read. **No new computation needed** — and two of the three already render on Payments. | `GuestPayments.php:34-100` · `Folio/FolioTotals.php` · `guest/payments.blade.php:95-96` |
| 🚫 **"Download all (ZIP)"** (×2) | ❌ **NO BACKEND ANYWHERE.** `grep -rln "ZipArchive" app` → nothing. | grep |
| 🚫 **"Email me all documents"** | 🟡 **no guest writer — but a proven precedent**: `OwnerReportEmailController` → queued `OwnerReportMail` attaching the statement PDF **to the owner's own inbox**, `MailSafetyGuard`-guarded. | `OwnerReportMail.php:27` · `OwnerReportEmailController.php:56` |
| 🚫 **"kept on file for 12 months" / the `12 mo` tile** | ❌ **FALSE — the §N.13.7 policy-claim shape.** Nothing is kept on file: **every PDF is generated on demand** (no stored artefact, no `documents` row, no disk path, no purge job, no retention config). The claim is also **backwards** in the direction that matters — availability is bounded by the row's lifetime, and the **GDPR erase** (`DO-G9-1`) changes what these documents contain. | `GuestDocumentController.php:33-66`; grep finds no retention config |
| 🚫 **"Paid in full · 16 Sep 2025"** | ❌ a hardcoded prototype literal; a real settled-date would have to be derived from the last succeeded payment and is not in the data contract. | decoded prototype, line 178 |

### 3.3 ⚠️ §N.8f — the duplication trap a build would walk into

*"Does this booking have a refund?"* is derived **inside** `GuestDocumentController::ownedWithRefund()` and
**again** in `guest/receipt.blade.php`'s conditional. A hub makes it **three**. It must be extracted into ONE
shared reader first — the drift that matters is a hub row linking to a 404.

### 3.4 THE VERDICT — **DEFER** (downgraded from an initial BUILD read, once the ratified row was verified)

⚠️ **My first read of this pass said BUILD.** That was reached before consulting `DIVERGENCE-REGISTER.md`, which
records the hub as a **ratified location divergence** — *"Never a gap: REAL on `/guest/payments`."* Verified: the
function **is** live, in two places, and reachable. **Recorded as a correction rather than quietly revised.**

**Recommendation: DEFER.** Real and wanted, but it is a **convenience over an already-reachable, already-ratified
function** — collapsing three hops into one and adding a cross-booking list. It does not unblock anything and
nothing is dishonest today. It belongs in the backend phase's nice-to-have tier, not ahead of correctness items.

⚠️ **One thing should be fixed regardless of the hub decision, because it is a real (small) usability defect, not
a missing feature:** a guest cannot tell that their **VAT invoice** lives behind a button labelled *Receipt*.
Surfacing the document kinds on the Payments "Receipts & invoices" rows is a **one-view change** that captures
most of the prototype's value without reopening anything.

**FOUNDER DECISIONS:**

| decision | recommendation | cost of each path |
|---|---|---|
| **Build the hub at all?** | 🟡 **DEFER** | **Build:** ~1 gate — a route + a cross-booking list over the existing exports + the shared refund predicate extracted; **reopens a ratified divergence**. **Defer:** nothing is lost; every document stays reachable in three hops. **Cheap middle:** label the document kinds on the Payments rows. |
| **"Download all (ZIP)"** | ⚠️ **DON'T-BUILD; omit the control rather than disable it** | **Build:** a ZIP writer (the first in the product), streaming five dompdf renders per booking under a request timeout, plus size/rate bounds — real infrastructure for a convenience. **Omit:** per-booking downloads, as today, and no control lies. **Disable-with-a-label:** the repo's honest middle (cf. the owner *"Withdraw early"*), but a dead control on a page whose whole purpose is downloading. |
| **"Email me all documents"** | 🟡 **DEFER** — real and cheap, but **it is a send** | **Build:** a queued guest analogue of `OwnerReportMail`, to the guest's **own** address only (#28), rate-limited, `MailSafetyGuard`-guarded — and it must survive `DO-INV-4` (the email-contrast sweep is still open). **Defer:** no loss of access, only of convenience. |
| **The "12 months kept on file" copy** | 🔴 **DON'T-BUILD — never ship this line** | ⚠️ Do not soften it to *"kept on file"* either. Say nothing, or say the true thing: documents are generated from your booking record. |

---

## 4 · 🔴 GUEST REFERRAL LANDING — **VERDICT: DON'T-BUILD (as drawn) · #39**

### 4.1 What the prototype shows (decoded fresh from `docs/design/raw/Guest Referral Landing.html`)

A **public, unauthenticated** split-card landing:

- **Left** — a villa photo under a brand gradient, *"Your friends know a good thing when they stay in one."*, and
  a rating chip *"4.9 average across 180+ stays"*.
- **Right, pre-claim** — an *"You've been invited"* gift pill; the headline **"{{ referrerName }} sent you
  {{ amountStr }} toward your first stay"**; *"**Create a free account** and the credit is applied automatically
  at checkout. No card needed to claim."*; a **dashed voucher ticket** showing `{{ amountStr }}` "Travel credit"
  and **`{{ code }}`** *"Your invite code — applied for you"* with a copy button; a numbered `{{ steps }}` list;
  a **"Claim"** CTA with a spinner state; and the terms line *"Credit is valid for **90 days** after you join,
  applies to stays of `{{ minStayStr }}` or more, **one per guest**. **{{ referrerName }} earns {{ amountStr }}
  after your first stay**."*
- **Right, post-claim** — *"All set — {{ amountStr }} is in your wallet"*, *"**We created your account** and
  applied {{ referrerName }}'s gift"*, a wallet card with `{{ walletStr }}`, a **countdown + expiry progress
  bar** (`{{ countdownStr }}` / `{{ expiryStr }}` / `{{ barWidth }}`), *"{{ referrerName }} earns
  {{ amountStr }} once you complete your first stay"*, and links to Browse + *View my wallet*.

### 4.2 ⚠️ THE ENGINE — **G10's finding CONFIRMED at `e869d5d`, and it is worse than one missing writer**

`grep -rni "referral" app database routes resources tests` returns **six** hits. **Every one is a slot or a
label; not one is a writer:**

```
app/Models/GuestCreditEntry.php:42          public const TYPE_REFERRAL = 'referral';   ← an enum constant
app/Http/.../GuestWalletController.php:93   TYPE_REFERRAL => 'Referral bonus',         ← a display label
app/Services/GuestWalletService.php:82      /** … refund / promo / birthday / referral … */  ← a docblock
…create_guest_credit_ledger_and_promo_codes.php:27,29  enum('type', [… 'referral' …])  ← a column value
resources/views/guest/wallet.blade.php:4,102  ⚠️ HONEST (#39): the wireframe's REFERRAL block … has NO backend
tests/Feature/GuestWalletTest.php:175,186     asserts the fabricated referral invite program is REFUSED
```

**The only production credit writer is `GuestWalletService::redeemCode()`** (`:188-219`), posting `TYPE_PROMO`
against a `promo_codes` row. Nothing writes `TYPE_REFERRAL`. **`DO-G10-1` holds unchanged.**

And the gap is not one writer — **five subsystems are absent**, each presented by the prototype as working:

| the prototype asserts | what would have to exist | what exists |
|---|---|---|
| `{{ code }}` — a personal invite code | a **referral-code entity** (issued per referrer, single-use per invitee) | ❌ nothing. `promo_codes` is org-wide with a shared `max_redemptions` and **no referrer column** — it cannot express "Ana's code". |
| `{{ referrerName }}` — *who* invited you | **attribution** — a `referred_by` link from the new guest to the referrer | ❌ nothing. `grep -rn "referred\|referrer\|invite_code\|invited_by" database/migrations app/Models/Guest.php` finds only the **owner-team** invites — a different realm, a different mechanism, no guest analogue. |
| *"Create a free account"* / *"We created your account"* | **open guest self-signup** | ❌ **structurally absent, deliberately.** The realm is **booking-tied claim only** — `GuestClaimController` resolves an existing claimable `Guest` row from a signed link (`resolveClaimable()`); no path creates a guest from nothing. **Open self-signup is a ratified refusal (`DO-G11-3`).** |
| *"{{ referrerName }} earns {{ amountStr }} after your first stay"* | a **reward trigger** on stay completion, crediting a *different* guest | ❌ nothing — no listener, no job, no writer. |
| *"valid for 90 days"*, the countdown + progress bar | a **referral expiry policy** | 🟡 the ledger has a nullable `expires_at`, so an expiry could be *stored* — but **no 90-day rule exists anywhere**, and G10 already caught this page-family printing a **self-contradicting** expiry (a 60-day chip above a "valid for 12 months" claim). |
| *"4.9 average across 180+ stays"* | a real aggregate | 🟡 `ReviewRating` is real, but this is a **hardcoded prototype literal**, not a binding. |

⚠️ **Note the shape.** This is not "a UI without a service behind it". The landing's core action — *claim → an
account is created → a credit lands* — would require **inverting the guest realm's identity model**
(booking-tied claim → open signup) **and inventing a money writer** (a credit with no promo row behind it). Both
were explicitly refused by the walk: `DO-G11-3`, and §G-11's *"the welcome credit has no writer at all"*.

### 4.3 THE VERDICT — **DON'T-BUILD as drawn**

**Recommendation: DO NOT BUILD.** Shipping this page would put a **money promise** in front of a stranger that
the engine cannot honour: a code that redeems nothing, a referrer who is never paid, a countdown against a rule
that does not exist, and a *"create your account"* button with no account-creation path behind it. That is the
**#39 fabrication shape** at its sharpest, and the honest-absence principle applies directly: *don't ship UI for
a feature the engine can't honour.* **The same refusal is already ratified twice** — on Wallet
(`guest/wallet.blade.php:4, 102-103`, test-defended at `GuestWalletTest.php:175`) and at G11 (`DO-G11-3`).

**FOUNDER DECISION — the referral programme is a roadmap question, not a page question:**

| path | cost | recommendation |
|---|---|---|
| **(a) DON'T BUILD — no referral programme** | Zero. Archive the prototype as a **ratified refusal**; `TYPE_REFERRAL` stays a reserved enum slot; Wallet's honest deferral stands. | ✅ **Recommended** absent an active founder intent to run referrals. |
| **(b) BUILD THE BACKEND FIRST, then the landing** | A real, non-trivial feature — **and it is real money**. Minimum: a referral-code entity keyed to a referrer · `referred_by` attribution on `guests` · **a new guest-creation path** (i.e. revisiting booking-tied-claim-only — **a security-posture change, not a feature toggle**) · a first-stay reward trigger · an expiry policy · abuse controls (self-referral, disposable emails, one-per-guest, a cap) · all on the append-only ledger with idempotency keys (#17). Only then is the landing a **real** feature, worth building exactly as drawn. | Legitimate — but scope it as a **programme**, not a gate. |
| **(c) A stripped landing over `promo_codes` today** | Cheap, and ⚠️ **recommended against.** A shared org-wide code has no referrer, so *"{{ referrerName }} sent you…"*, *"{{ referrerName }} earns…"* and *"one per guest"* would all still be fabrications — the page keeps its dishonest 80% and loses its point. | ❌ Do not. |

⚠️ **Whatever is chosen, do not "fix" the Wallet page's honest deferral** — it is a ratified refusal with a test
defending it.

---

## 5 · SUMMARY — THE THREE VERDICTS

| prototype | backend reality (verified) | **verdict** | the founder's call |
|---|---|---|---|
| **Guest Help** `DO-G13-1` | 🟢 the support **destination already exists** (Account → relay) · 🟢 the FAQ **pattern** is proven owner-side (`HelpKnowledgeBase` + `HelpRetriever` + `AskVyrobelService`) · ❌ no guest KB · ❌ no SLA / phone / hours / on-call | **BUILD** the FAQ centre — ⚠️ **reopening the 2026-08-07 refusal**, whose premise (*"content would have to be invented"*) the owner KB has since falsified · **DON'T-BUILD** the SLA pill, Typical-reply tile, phone/WhatsApp, 24/7 card | build honestly now (a) · configure real support contacts first, which also unblocks `DO-INV-3` (b) · keep the refusal (c) |
| **Guest Documents** `DO-G13-2` | 🟢 all 5 exports **REAL** + #28-isolated · 🟢 all 3 money figures **already shared** (`GuestPayments::overview()`, #17) and 2 already rendered on Payments · 🟢 **the rollup already exists and was RATIFIED** on `/guest/payments` (3 hops to a PDF) · ❌ **no ZIP anywhere** · 🟡 no guest email-all (owner precedent exists) · ❌ **"12 months" is FALSE** | **DEFER** the hub *(downgraded from BUILD once the ratified row was verified)* · **DON'T-BUILD** the ZIP + the retention claim · **DEFER** email-all · 🟢 **cheap win regardless:** label the document kinds on the Payments rows | build/defer the hub · omit/build the ZIP · defer/build email-all |
| **Guest Referral Landing** `DO-G13-3` | ❌ **FIVE subsystems absent** — no code entity, no attribution, **no open signup (ratified refusal)**, no reward trigger, no expiry rule. Only `redeemCode()` writes credit. | **DON'T-BUILD (#39)** | no programme (a) · backend first, then the page (b) · ⚠️ never (c) |

**Entered in the consolidated DO-register as `DO-G13-1` / `DO-G13-2` / `DO-G13-3`** — inputs to the **backend
phase**, not work completed here.

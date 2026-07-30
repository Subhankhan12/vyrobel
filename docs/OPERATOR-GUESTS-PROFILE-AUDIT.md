# Operator Guests + Guest Profile — exact-match audit (PHASE 1, read-only)

**Pages:** `/app/guests` (`GuestsList`) + `/app/guests/{guest}` (`GuestProfile`) — both already exist (Wave 2 · Sub-gates 4–5; RESTRUCTURED in the design-fidelity pass).
**Wireframes:** `Operator Guests.html` · `Operator Guest Profile.html` (dc-runtime bundles — decoded).
**Date:** 2026-07-30 · **Status:** PHASE 1 complete; awaiting the consent / prefs / delete decisions.

---

## 0 · The core finding — most of the backend already EXISTS; this is a "make the honest omissions real" job

The two pages shipped in Wave 2 with a scrupulous REAL / DERIVED / **OMITTED** map. Several omissions were *"no backend yet"* and are now **buildable** (reviews module #76 now exists; the mandate authorizes the prefs/consent/city stores). The GDPR delete is **already built and exemplary**. So the work is: **match the wireframes exactly + turn the honestly-omitted promises into real, wired features** — not a from-scratch build.

**⚠️ #28 scope confirmed:** the operator legitimately sees + edits **FULL guest contact** (email/phone/city). This is the ONE PII surface by locked decision 4; the name-only rule is **owner-surfaces-only**. `OperatorGuestsListTest` asserts the *other* operator surfaces (front desk / reservations / inbox) stay name-only — that perimeter must stay green. Do **not** strip contact here.

---

## 1 · What already exists (reuse — anti-drift)

| Concern | Reuse |
|---|---|
| **GDPR erasure** | `Guest::anonymize()` — nulls PII, hard-deletes ID-doc **bytes**, scrubs message bodies + note bodies, sets `anonymized_at`, **keeps the row so reservations/folios/ledger are RETAINED + anonymized**, audited + domain-evented. Single-guest route `guests.anonymize` (typed "ERASE" server-confirm, `can:guests.erase`). |
| **Guest CRM writes** | `GuestService` (updateProfile/addNote/attachTag/detachTag — audited, records WHICH fields changed never values) + `GuestActionController` (thin adapter, PRG, tenant-scoped string id never RMB). |
| **Aggregates (reconciling #17)** | `GuestSpend::for()`/`forGuestIds()` (billed/balance/stays per currency), `GuestHistory::for()` (stays/nights/since), `FolioTotals::for()`, `ReservationStage` (stage badge + `scope()` for in-house/upcoming). |
| **Reviews (#76)** | `Review::where('guest_id',…)->latest()->first()` (tenant-scoped); stars `str_repeat('★',rating).str_repeat('☆',5-rating)`; `GuestName` byline. |
| **Message relay** | `InboxService::startThread(['channel'=>'manual','guest_id'=>…,'assigned_to'=>…])` + a guest-message write; the template is `OwnerMessageActionController::start()`. Bulk email seam: `Mail::to($guest->email)->send(...)` (as `SendGuestTransactionalEmails::sendToGuest()`). |
| **Read-only (#84)** | `OperatorWorkspace::isReadOnly()` — ⚠️ `GuestActionController` does **not** check it today; new + existing guest writes must (mirror `ReservationCreateController`). |
| **Front Desk** | `FrontDeskService::present()` builds arrival rows (loads `leadGuest`, **name only** today). |

**Guests columns today:** `first_name,last_name,email(enc),phone(enc),nationality(char2),dob,language,is_vip,risk_flag,marketing_consent,anonymized_at,external_refs,…`. **Missing:** `city`, `prefers_email`, structured `preferences`.

---

## 2 · GUESTS LIST — per-element (`Operator Guests.html`)

| Element | Class | Plan |
|---|---|---|
| Header · Notif bell · account menu · shell · read-only banner | ✅ REAL | shared operator shell (exists) |
| **Search** "name, email or phone" | ⚠️ REAL(name) + honest | email/phone are **encrypted** → not full-text searchable; keep name search + honest note. **Option:** add EXACT-email match via the `email_hash` blind index (a real, safe improvement). |
| Filter tabs **All · In-house · Upcoming · VIP** | 🔨 BUILD | In-house/Upcoming are REAL stay-state via `ReservationStage::scope()`; VIP is the real column. (The current At-risk + tag chips are extra — keep or fold in.) |
| **Select** (bulk-select mode) | 🔨 BUILD | checkbox column + select-all tri-state + selection state |
| Stat tiles **Guest profiles · In-house tonight · Repeat guests %** | ✅ REAL | already exactly these (real aggregates). The `+4%` delta stays omitted (no snapshot — #39). |
| Table: avatar/name/**VIP**/email · Country · Stays · **Last stay** · **Lifetime** · Status | ✅/🔨 | restructure to the wireframe columns: email under name; **Last stay** (real — latest stay date/state); **Lifetime** = billed (primary currency, honest — not netted); Country = `nationality` (honest label). |
| **Bulk bar: Message · Add tag · Export · Delete** | 🔨 BUILD | **Message** = real (email selected → operator inbox); **Delete** = the GDPR erasure (below); **Add tag** = reuse `attachTag` (cheap); **Export** = a PII decision (see §5-H). |
| **Delete confirm dialog** | 🔨 BUILD | the wireframe copy ("history removed") is WRONG — the real behaviour is **anonymize-not-destroy**. Match the dialog design; HONEST copy: *PII erased, reservations retained (anonymized)*. Typed-ERASE **server** confirm + `can:guests.erase` + audited (bulk of `Guest::anonymize()`). |
| **Add guest** | 🔨 BUILD | real create (name/email/phone/city/nationality/vip/prefers-email/consent) → `GuestService::create` (new), audited. |
| Pagination "Showing N of M" + chevrons | ⚠️ | keep the honest render-all + true footer count (current) OR add real pagination (minor). |

## 3 · GUEST PROFILE — per-element (`Operator Guest Profile.html`)

| Element | Class | Plan |
|---|---|---|
| Breadcrumb · header (avatar/name/**In-house·unit**/**Repeat**/subline) · Message · Edit · Current stay | ✅/🔨 | mostly exists; **Message** → operator→guest thread start (BUILD, reuse `InboxService::startThread`). |
| Stat strip **Stays · Nights · Avg/night** (+ **source split**) | ✅ REAL | exists (Account card) → restructure to the 3-up strip; source split real (OTA not brand). |
| **Stay history** (dates · unit·ref·source·nights · total **+refund** · status) | ✅/🔨 | exists; **BUILD** the per-stay refund line (sum `folio->payments` where type=refund/succeeded). |
| **Latest review** (stars · quote · attribution) | 🔨 BUILD | reviews #76 now exists → the guest's real latest review (was omitted "no reviews table"). |
| **Lifetime value hero** (dark card · billed · 3 stays·14 nights·avg · **open folio** pill) | ✅ REAL | restructure the existing billed/balance into the dark hero (reconciles #17). |
| **Contact** (email · phone · **city** · "prefers email · marketing consent on file") | 🔨 BUILD | **city** column (BUILD); prefers-email + consent rows real. |
| **Preferences & notes** (chips + note box + Add note · **"shown to HK & front desk"**) | 🔨 BUILD | a real structured **preferences** store (§5-C) + keep free-text notes; the "shown to HK/front desk" promise made **true** (§5-D). |
| **Edit modal** (name/email/phone/**city**/**prefs**/VIP/**prefers-email**/consent) | 🔨 BUILD | match the modal; add city + prefs + prefers_email to the real write; footer "changes apply to future confirmations, not sent emails" (honest). |
| GDPR erase (typed ERASE) | ✅ REAL | exists; add the read-only guard. |

---

## 4 · Reuse map
`Guest::anonymize()` · `GuestService` (+ new `create`) · `GuestActionController` · `GuestSpend`/`GuestHistory`/`FolioTotals`/`ReservationStage` · `Review` (#76) · `InboxService::startThread`/`OwnerMessageActionController` pattern · `SendGuestTransactionalEmails::sendToGuest` seam · `OperatorWorkspace` · `FrontDeskService::present` · `<x-operator.shell>` · the `--vy-*`/`vy-*` design system.

## 5 · The decisions (⛔ present + pause)

- **A · GDPR bulk-delete = anonymize-not-destroy (confirm, not really a choice).** Bulk = loop `Guest::anonymize()` per selected guest — PII erased, **reservations/folios/ledger retained (anonymized)**, `guests.erase`-gated, typed-ERASE server-confirm, audited. The dialog copy is corrected to tell the truth (the wireframe's "history removed" is wrong/dangerous). **Recommend: yes.**
- **B · Marketing consent enforcement seam.** Consent is stored but gates nothing today. **Recommend:** a real `GuestMarketing` policy (`consent && !anonymized && has-email`) enforced at a real marketing send seam — the **bulk-message** gains a "marketing message" mode that **skips non-consented recipients** (reported honestly: "sent to N · M skipped, no consent"), and the `WinbackMail` path is gated the same way. **Transactional** emails (booking/refund) stay ungated (non-marketing). *Alt: keep consent a record only.*
- **C · Preferences store shape.** A fixed catalog (sea_view/high_floor/late_checkout/quiet_room/ground_floor/early_checkin/extra_towels/crib). **Recommend:** a JSON `preferences` column on `guests` (array of active keys) + keep free-text notes. *Alt: a separate table.*
- **D · "Shown to housekeeping & front desk" flow.** **Recommend:** wire **Front Desk** for real — `FrontDeskService::present()` surfaces the guest's `is_vip` (a VIP flag on arrivals) + preferences chips, making "VIP flags the front desk" TRUE. Housekeeping tasks are **unit**-scoped (no guest) — surface prefs there via the unit's current reservation, OR scope the hint to "front desk & arrivals" (honest). *Which: front-desk-only, or front-desk + housekeeping?*
- **E · city + prefers_email columns.** Add `city` (string, not encrypted — coarse residence) + `prefers_email` (bool default true). **Recommend: yes.**
- **F · Add guest.** Real create form + `GuestService::create` (audited). **Recommend: yes.**
- **G · Search.** name + **exact-email via `email_hash`** (real) + honest note that partial email/phone isn't searchable. **Recommend: yes.**
- **H · Bulk CSV Export.** The current app deliberately omitted exporting decrypted emails (a PII decision). **Recommend:** build it (the operator legitimately sees this contact) **audited** — OR keep omitted. *Build or omit?*

## 6 · Guards (all gates)
🔒 operator-scoped (full contact — the manager, NOT owner #28); #17 aggregates reconcile (shared helpers); #20 every CRUD + the bulk delete controller-routed + audited; the **delete anonymizes-not-destroys** (financials retained); consent **genuinely** gates marketing email; read-only (#84) respected on every write; #39 no fabrication (no fake guest/aggregate/consent/review). The `OperatorGuestsListTest` name-only perimeter on the OTHER surfaces stays green.

## 7 · Build plan
**GATE 1 (list):** migration (city/prefers_email/preferences) → filters (In-house/Upcoming stay-state) → bulk-select + bar (Message/Delete[/Add-tag][/Export]) → bulk `GuestBulkActionController` (message + anonymize, gated + audited + read-only) → Add-guest create → table restructure (Last stay/Lifetime) → tests (bulk anonymize erases PII + retains anonymized reservations; message; add-guest; perimeter green) → browser → commit.
**GATE 2 (profile):** the prefs store + `GuestService` prefs/city/prefers_email writes → the FrontDesk VIP/prefs flow → the marketing-consent gate → latest review (#76) → per-stay refund → restructure (dark LTV hero / contact / prefs / stat strip / edit modal / Message-start) → consolidate docs → tests (consent gates email; prefs flow to front desk; review; refund reconciles; edit audited) → browser → commit + push.

## RESOLUTION
_(appended at GATE close)_

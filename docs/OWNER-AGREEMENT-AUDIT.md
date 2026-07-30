# Owner Agreement — exact-match audit (PHASE 1, read-only)

**Page:** `/owner/agreement` → `App\Livewire\Owner\OwnerAgreement` + `resources/views/livewire/owner/agreement.blade.php`
**Wireframe:** `docs/design/raw/Owner Agreement.html` (dc-runtime bundle — decoded)
**Date:** 2026-07-30 · **Status:** PHASE 1 complete, awaiting the amendment-flow decision before PHASE 2.

---

## 0 · Decode — what the wireframe actually is

The `__bundler/template` script is a char-array of the fully-rendered HTML; joined it reconstructs a
48 KB document. The component's `renderVals()` logic (recovered from the bundle) is decisive:

```js
const comm = this.props.commissionRate ?? 18;   // ← the mock ITSELF defaults to 18, not 15
const commStr = comm + '%';  const lowStr = (comm - 2) + '%';
const included = ['Listing & channel distribution','Guest communications & 24/7 support',
  'Housekeeping coordination','Dynamic pricing & revenue management',
  'Monthly statements & payouts','Damage & incident handling'];
const addons = ['Deep cleaning & repairs','Professional photography','Linen & consumables'];
const unitTerms = [
  { villa:'Villa Coralis', comm:commStr, cleaning:'€90', since:'2023', low:false },
  { villa:'Villa Dunes',   comm:commStr, cleaning:'€75', since:'2023', low:false },
  { villa:'Casa Aloe',     comm:lowStr,  cleaning:'€70', since:'2024', low:true } ];
const terms = [
  { label:'Payout schedule', value:'Monthly, by the 5th' },
  { label:'Notice period',   value:'60 days, either side' },
  { label:'Owner stays',     value:'Up to 30 nights a year, fee-free' },
  { label:'Cancellations',   value:'Handled by us per your policy' },
  { label:'Renewal',         value:'12 months, auto-renewing' } ];
// amendment: needsSign ↔ signed, sign() → localStorage('agreement2026','1') + setState({signed:true})
```

**Reuse the shared owner chrome** (`<x-owner.shell active="agreement">`) + `--vy-*` warm-glass tokens +
`vyFadeUp` animations (already the page's idiom; respect `prefers-reduced-motion`).

---

## 1 · The real backend (reuse map)

| Concern | Real thing to reuse | Notes |
|---|---|---|
| The agreement | `ManagementAgreement` (commission_pct, management_fee, payout schedule/currency, effective_from/to, status `active\|ended`, attribution_rules, unit_id) | Effective-dated; `unit_id` null = portfolio-wide, set = per-unit override. **NOT owner-scoped** — the page scopes EXPLICITLY via `OwnerContext::ownerIds()` (fail-closed). Demo agreement = **18%**, open-ended, portfolio-wide. |
| The commission rate | `App\Support\Owner\OwnerCommissionRate::percent()` | The ONE shared read of the real `commission_pct` (18% demo) — the SAME figure Reports/Settings quote. **Anti-drift** #17. |
| Audited write | `AuditEventRecorder::record()` + `OwnerContext::canOn($ownerId, CAP_ACTIONS)` + a controller route | The pattern `OwnerGuardrailController` uses (#20). |
| PDF | `barryvdh/laravel-dompdf` + a pure `App\Support\Pdf\*Document` builder + a controller `$capExports`-gated + `ExportAudit::stamp()` | Exactly `OwnerRemittanceDocument`/`OwnerStatementDocument`/`OwnerBookingConfirmationDocument`. |
| The relay | `owner.messages.start` → `OwnerMessageActionController::start` (owner↔**operator** `InboxService`) | Owner→operator, **never** owner→guest. |

**No amendment model, no signature model, no term/notice/renew columns exist** (confirmed by grep + the
migration). This is the build surface.

---

## 2 · Section-by-section classification

| # | Section | Wireframe | Classification | Plan |
|---|---|---|---|---|
| 1 | Header | "Management agreement · Your terms with {operator}…" | ✅ REAL-NOW | Static copy; "{operator}" = the real org/operator name. |
| 2a | Active — commission | `{{ commission }}` of booking revenue | ✅ REAL | `OwnerCommissionRate` (18%), reconciling with the financials. |
| 2b | Active — unitCount / started / full-service copy | manages `{{ unitCount }}` villas · Started `{{ startDate }}` | ✅ REAL | owner unit count; `effective_from`. |
| 2c | Active — term / notice / auto-renews `{{ renewDate }}` | Term `{{ term }}` · Notice `{{ notice }}` · Auto-renews … | 🔨 **BUILD** (additive) *or* honest-omit | **Decision A.** Not modelled. Recommend small additive nullable columns (`term_months`, `notice_days`, `auto_renews`) rendered honestly (row omitted when null — never a fabricated "12 months"). |
| 2d | "Download agreement" | button → PDF | 🔨 **BUILD** | Real generated agreement PDF from the real terms, `$capExports`-gated. "Signed date" honest (generated-on / effective-from — never a fabricated "Signed 14 Mar 2023"). |
| 3 | **Amendment ready to sign** ↔ "signed" | Review & sign / Preview → "Signed by {owner}" | 🔨 **BUILD** (the key investigate) | **Decision B (headline).** See §3. Real amendment record + real **acknowledgment** (who/when/version, audited) → the new terms apply via the **certified effective-dating**. Framed "**Acknowledged in your portal**", NOT legal e-signature. Never a fake "signed" badge. |
| 4 | What your fee covers — included (6) / at-cost (3) | generic lists | 🟠 REAL-where-real / honest | **Decision C.** Included = descriptive "what full-service management includes" copy (honest, generic service description — not a fabricated per-owner term). At-cost add-ons = the REAL owner-billed **expense categories** (deductible categories on the agreement / Expenses), so it reconciles. |
| 5 | Terms by villa — commission / cleaning / since | per-property | 🟠 REAL commission + since / honest cleaning | Per-villa **commission** REAL (per-unit `ManagementAgreement` override, most-specific-wins, falling back to portfolio-wide — the SAME rate the statements apply, #17). **Since** = the governing agreement's `effective_from`. **Cleaning** honest ("At cost" / omit) — no per-unit cleaning rate is modelled (never €90/€75/€70 fabricated). |
| 6 | Key terms — payout / notice / owner-stays / cancellations / renewal | 5 rows | 🟠 REAL / honest | Payout schedule REAL; notice + renewal REAL **iff** 2c columns built, else omitted; **owner-stays + cancellations OMITTED** (not modelled — never "30 nights fee-free" / "per your policy" fabricated). |
| 7 | Documents — agreement PDF + fee-amendment PDFs | Signed {date} · PDF | 🔨 BUILD | Base agreement PDF + one row **per real amendment** with its **real acknowledgment date**; `$capExports`-gated. Honest date for the base agreement. |
| 8 | Want to change your terms — "Message your manager" | → messages | ✅ REAL (stale refusal) | Wire the real `owner.messages.start` relay (owner→operator). The old disabled "Soon" is now stale — the relay exists (#77). |

---

## 3 · The amendment-signing flow — the investigate + proposal

**There is no amendment or signature model.** A legally-binding e-signature (identity assurance, tamper-
evident audit, consent-to-sign, signature certificate) is a substantial regulated feature Vyrobel does
**not** provide — so we will **not** claim it.

**Proposal (the real, buildable version — per the mandate):**

- **`agreement_amendments`** (new table): `organization_id`, `owner_id`, `version` (e.g. "2026"),
  `title`, `summary`, `effective_date`, `new_terms` (json — the changed commission/fee/schedule/etc.),
  `status` (`draft` → `published` → `applied`), `created_by`. The operator publishes an amendment; it is
  owner-scoped-explicitly (same trap as the agreement).
- **`agreement_amendment_acknowledgments`** (new table): `amendment_id`, `owner_id`,
  `acknowledged_by` (user), `acknowledged_at`, `acknowledged_version`. A **real record of consent** —
  **audited** (`AuditEventRecorder`, #20), CAP_ACTIONS-gated (a money-class control, co-owner only), one
  per amendment (unique).
- **Applying the new terms** reuses the **certified effective-dating** (never a rewrite of history):
  acknowledging **creates a superseding `ManagementAgreement` row** with `effective_from =
  effective_date` carrying the amendment's new terms, and closes the current one at `effective_date − 1`.
  Because the model's certified `effectiveOn` scope keys off the date, the new terms **become "in effect"
  automatically when the date arrives** — no scheduler, no history rewrite, the same machine the
  attribution engine already trusts. Before acknowledgment, nothing changes.
- **Framing (honesty):** the button is "**Review & acknowledge**"; the signed-state is "**Acknowledged in
  your portal by {name} · takes effect {date}**" — a real record, **never** a fake "signed" badge, and
  **never** a claim of legally-binding e-signature. A true e-signature is **honest-deferred** to real
  e-sig infra (DocuSign-style) with a one-line honest note. **Preview** shows the real amendment content.

**Alternative (if the founder prefers):** honest-defer the whole amendment section (style the "no
amendments pending" honest empty state, build nothing) — smaller, but leaves the wireframe's headline
interaction unbuilt.

**Recommendation: BUILD the real acknowledgment** (per the mandate) with the honest framing above.

---

## 4 · Guards (unchanged posture)

- **#28** — owner-scoped explicitly (`OwnerContext::ownerIds()`, fail-closed); no guest data anywhere on
  the page (there is none to leak here).
- **#17** — the commission is the ONE real rate (`OwnerCommissionRate`), reconciling with the statements;
  per-villa commission is the same rate the attribution applies. No cross-currency sum on the page.
- **#20** — the acknowledgment is a controller POST, CAP_ACTIONS-gated, audited.
- **#42** — the agreement + amendment PDFs are `$capExports`-gated + `ExportAudit`-stamped.
- **#39** — no fabrication: no fake "signed" badge, no invented term/notice/renew/cleaning-rate/
  owner-stays/cancellation figure, no fabricated inclusion. Real-or-honest throughout.

---

## 5 · Build list (PHASE 2, one gate)

1. Migrations: `agreement_amendments` + `agreement_amendment_acknowledgments` (+ Decision A: additive
   `term_months`/`notice_days`/`auto_renews` on `management_agreements`).
2. Models: `AgreementAmendment`, `AgreementAmendmentAcknowledgment` (explicit owner-scoping helpers).
3. Service: `AgreementAmendmentService::acknowledge()` (CAP_ACTIONS, audited, creates the superseding
   effective-dated agreement) — reuses the certified effective-dating.
4. Controller + routes: `OwnerAgreementController::acknowledge` (CAP_ACTIONS) + a PDF controller for the
   agreement + amendment PDFs (`$capExports`).
5. PDF builder(s): `OwnerAgreementDocument` (+ amendment) mirroring the remittance/statement pattern.
6. `OwnerAgreement` (Livewire) + `agreement.blade.php` — the exact 3-part layout (active + fee-covers +
   terms-by-villa left; key-terms + amendment + documents + change-terms right), every token + animation.
7. Wire the relay (`owner.messages.start`) on "Message your manager".
8. Demo seed: a real pending amendment for the demo owner (flagged demo) so the flow demos end-to-end.
9. Tests: acknowledgment records who/when/version + audits + applies the new effective-dated terms;
   commission reconciles; isolation holds; the relay posts owner→operator; PDFs gated. Update the existing
   `OwnerAgreementViewTest` "never invents a signing flow" assertion → assert the REAL acknowledgment flow
   (the isolation + honesty assertions stay).
10. Consolidate docs + bump HEAD; commit + push.

---

## RESOLUTION (GATE closed 2026-07-30 — invariant #82)

All three founder decisions taken **Recommended** (build real acknowledgment · build additive term/notice/
renew columns · descriptive included copy + real at-cost categories). Single gate, shipped:

- **Migrations** — `agreement_amendments` + `agreement_amendment_acknowledgments`; additive nullable
  `term_months`/`notice_days`/`auto_renews` on `management_agreements`. `AgreementAmendment` added to the
  enforced morph map (it is an audit subject).
- **The amendment ACKNOWLEDGMENT is REAL + BUILT** — `AgreementAmendmentService::acknowledge()` records a
  real consent row (who/when/version), audits it (#20, `owner.agreement.amendment_acknowledged`), and
  applies the new terms via the **certified effective-dating** (bounds the current portfolio agreement at
  effective_date − 1, creates a superseding row from effective_date) — **history is never rewritten**.
  Idempotent (unique index + a re-submit 404s as no-longer-published). Framed "acknowledged in your
  portal", **never** a fake "signed" badge; a legally-binding e-signature is **honest-deferred**.
- **The agreement is REAL** — commission via the real `commission_pct` (18%, the same rate the statements
  apply, `OwnerCommissionRate` unchanged, #17); per-villa commission via the shared `OwnerAgreementTerms`
  (the SAME `resolveFor` the attribution engine uses — anti-drift). Term/notice/renew rendered honestly
  (omitted when null). Fee-covers = descriptive standard-service copy + the **real** deductible expense
  categories (Cleaning/Maintenance in the demo). Cleaning per-villa = "At cost" (no rate modelled).
- **PDFs** — a real agreement summary PDF + per-amendment PDF (`OwnerAgreementDocument` +
  `OwnerAgreementPdfController`, `$capExports`-gated #42, `ExportAudit`-stamped, owner-scoped explicitly);
  honest "summary, not a signed legal contract" footer.
- **Relay** — "Message your manager" → the real `owner.messages` (owner→operator). Stale "Soon" removed.
- **Verify** — `OwnerAgreementViewTest` (7, updated to the new layout) + `OwnerAgreementAmendmentTest` (5,
  new): acknowledgment records/audits/applies; idempotent; owner-scoped 404; honest framing + relay; PDFs
  download. Full suite **1283/9649** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.
  Browser-verified as the demo owner: page exact-matches, Preview shows the real 17% change, acknowledge →
  real record + the superseding 17% agreement "Starts 1 Oct 2026" in history (current stays 18%).

# Owner Tax Center — wireframe-EXACT-match audit

**Date:** 2026-07-28 · **Author:** build session · **Status:** ✅ **BUILT — invariant #64** (single gate). The page matches the decoded wireframe on REAL, already-built backends (REUSED, anti-drift): the tax-hold (Payouts/Balance), the ONE `owner.tax.save` store (Settings/Payouts), the annual tax PDF + Documents. Withholding is honest ("None" — not modelled, never a fabricated rate #39); the VAT-registered toggle stores a real additive `vat_registered` flag. Mutation-proven end-to-end: saving a tax ID **released the real hold** and cleared it on Payouts too (shared-hold anti-drift). Suite green; PHPStan L5 (0); Pint clean; browser-verified (save→release→green complete, VAT toggle reveals number, annual PDF 200). *(The PHASE-1 analysis below is preserved as the audit of record.)*
**Page:** `/owner/tax` · **Component:** `App\Livewire\Owner\OwnerTaxCenter` + `resources/views/livewire/owner/tax-center.blade.php`
**Writes:** `owner.tax.save` (POST → `OwnerTaxController::save` → `OwnerTaxService::saveTaxDetails`, `$capActions`, audited) · **PDF:** `owner.tax.annual` (`$capExports`).
**Wireframe:** `docs/design/raw/Owner Tax Center.html` (decoded → `/tmp/owner_taxcenter_decoded.txt`, `<x-dc>` body + a `Component` JS block).

---

## 0. Headline — this is one of the MOST-REAL owner pages; almost everything is already built

The live page already wires the **real** tax-hold, the **real** `owner.tax.save` store (encrypted, audited, self-only), and the **real** annual-summary PDF. The delta is **presentation** (match the fuller wireframe design) + **two honest additions**:
- **Withholding** — the wireframe's residency-driven "15% / 20% withheld on payouts" is **fabricated** (Vyrobel withholds nothing) → render the section design as an **honest "no withholding" state**, never a fabricated rate.
- **VAT-registered toggle** — the wireframe stores a VAT-registration **flag** independent of the number; the store has no such column → **one small additive `vat_registered` boolean** (the task's "stored yes/no").

No section requires a new engine; the whole page reuses existing services (anti-drift).

---

## 1. Backend investigation (verified against the repo + a live browser audit)

| Capability | Verdict | Evidence |
|---|---|---|
| **Tax-hold** (held amount + complete/incomplete state) | ✅ REAL — REUSE | `OwnerTaxService::heldTotalMinor($owner)` sums the owner's **real pending `Payout` rows** held while `!hasTaxDetails()`; `Owner::hasTaxDetails()` = `filled(tax_id)`. This is the SAME hold Payouts/Balance surface — the disbursement path (`OwnerPayoutService::pay()`) genuinely refuses to pay without a tax ID. Live: demo owner (no tax_id) shows **"€885.60 is on hold"** — real. |
| **Tax profile store** (legal name / residency / tax ID / entity type / VAT number) | ✅ REAL — REUSE (the ONE path) | `owner.tax.save` → `OwnerTaxController::save` (validates) → `OwnerTaxService::saveTaxDetails` (whitelisted `FIELDS = legal_name, type, tax_country, tax_id, vat_number`; `forceFill`+save; **audited** `owner.tax_details_updated` naming fields not values). `tax_id` + `vat_number` **encrypted** at rest (#28). Self-only (resolved by `user_id`, never a request id). This is the SAME store the **Settings** "Business & tax" + **Payouts** tax section write to (the ONE `owner.tax.save`). Saving a tax_id **releases the real hold**. `$capActions`-gated (co-owner writes; accountant reads). |
| **Annual summaries + PDF** | ✅ REAL — REUSE (reconciles #17) | `OwnerTaxService::availableYears` + `annualSummary($owner, $year)` (real gross/net per year from finalized statements) → `owner.tax.annual` → `OwnerAnnualTaxDocument` (the 12-month combined PDF; **no fabricated totals, no OTA brand, no withholding** — its own comment). `$capExports`-gated (#42). Live: "2026 · year to date · Net €3,690.00 · gross €4,500.00 · PDF". |
| **VAT invoices & documents** | ✅ REAL — REUSE | the real `owner.documents` archive (all statements + tax PDFs). The wireframe's card just links there. |
| **About-your-tax note** | ✅ REAL (honest) | already present, copy honest ("we don't file taxes for you… nothing here is tax advice"). |
| **Withholding tax** | 🔴 **NOT MODELLED → HONEST** | grep-confirmed: **zero** withholding engine — the only `withhold` references are comments in `OwnerTaxCenter` ("a per-country engine we don't have… NOT rendered") + `OwnerAnnualTaxDocument` ("no withholding — we don't compute it"). Owner-ledger deductions are commission / ota_cost / expense **only**; no rate reduces any payout. The wireframe's residency map (PT 0%, IE/ES/FR/GB 15% "treaty", OT 20%) is **fabricated** — a "15% withheld on payouts" that reduces nothing. |
| **VAT-registered flag** | 🟡 not stored → **one additive column** | the store has `vat_number` (encrypted) but **no `vat_registered` boolean**. The wireframe has an independent toggle. → add a small **additive `vat_registered` boolean** (default false) to the whitelisted FIELDS (the task's "stored yes/no + number"). Additive; disturbs nothing. |

---

## 2. ⚠️ THE WITHHOLDING RESOLUTION (the one investigate point)

**Confirmed NOT modelled.** The wireframe's `Component` hardcodes a residency→rate map and claims *"A 15% treaty rate is withheld on payouts to Ireland and reported on your annual summary."* **Vyrobel withholds nothing** — the owner receives their full net earnings; the only deductions are the real commission/fees. A "15% withheld" figure that doesn't actually reduce a payout is a fabrication (#39), and a **dangerous** one (an owner could under-report income expecting a withholding credit that was never remitted).

**RESOLUTION — HONEST, render the section in the wireframe's design with a truthful "no withholding" state (universal, not residency-driven):** the "Withholding tax" card shows **"None"** in the figure slot + an honest note — *"Vyrobel doesn't withhold tax from your payouts. You receive your full net earnings and declare your rental income in your own tax return."* This matches the wireframe's honest PT=0% case for **every** owner and REFUSES the fabricated 15%/20%. (Alternative: omit the card entirely — but rendering the honest state fills the wireframe's section and pre-empts the "where's my withholding?" question. Recommended: **render honest**.)

**No founder decision needed** — it's the same discipline as the Reports VAT block: refuse the fabricated figure, style the honest state.

---

## 3. Section-by-section: wireframe vs current build (the exact-match delta)

Layout: status banner → flex row [LEFT: Tax profile + Withholding · RIGHT: Annual summaries + Documents link + About-your-tax (dark)].

| # | Wireframe section | Current build | Delta to close |
|---|---|---|---|
| 1 | Header ("Tax center" + subtitle) | ✅ present (shorter subtitle) | Match the full subtitle "…withholding and year-end summaries — all in one place." |
| 2 | **Status banner — 2 states** (accent *incomplete* + **GREEN *complete*** #4F7A46/#6FA764 ✓) | incomplete ✅ real; complete = plain accent line | Add the **green "Tax details complete"** design (checkmark circle). Real states (hold present / `hasTaxDetails`). |
| 3 | **Tax profile form** (Legal name · Residency select · Tax ID/NIF* · **Registered-as segmented toggle** · **VAT-registered toggle** + conditional VAT number · Save + "Saved") | form present (legal/type-select/residency/tax_id/vat_number) | Reorder to the wireframe; **Registered-as** as a segmented Individual/Company toggle; add the **VAT-registered toggle** (new `vat_registered` col) gating the VAT number. REUSE `owner.tax.save` (encrypted, audited, `$capActions`). |
| 4 | **Withholding tax** card (rate + note) | omitted | Add the card — **HONEST "None" + no-withholding note** (§2), never the fabricated 15%/20%. |
| 5 | **Annual summaries** (card-rows: icon · year · Net/gross · tag · PDF) | rows + PDF button ✅ real | Match the card-row design; keep the real per-year PDF (`owner.tax.annual`, `$capExports`), real years, reconciling. |
| 6 | **VAT invoices & documents** link card → Documents | omitted | Add the link card → `owner.documents` (real archive). |
| 7 | **About your tax** (dark panel, honest) | ✅ present (dark) | Minor design alignment. |

---

## 4. The reuse map (proving anti-drift — every section wires to an ALREADY-BUILT feature)

- **Tax-hold** → `OwnerTaxService::heldTotalMinor` + `Owner::hasTaxDetails` → the SAME hold as **Payouts/Balance** (`OwnerPayoutService::pay` refuses without a tax ID). *Releasing it here (saving a tax_id) clears it everywhere.*
- **Tax store** → `owner.tax.save` (`OwnerTaxController` → `OwnerTaxService::saveTaxDetails`) → the SAME path **Settings "Business & tax"** + **Payouts** write. *Editing here reflects there.* Encrypted (#28), audited, self-only, `$capActions` (#20).
- **Annual PDF** → `owner.tax.annual` (`OwnerAnnualTaxDocument`) → the SAME PDF **Documents** offers; reconciling (#17), `$capExports` (#42).
- **Documents** → `owner.documents` archive.
- The ONLY new backend is the small **additive `vat_registered` boolean** (+ its FIELDS/validation entry). No new service, no new PDF, no new hold.

---

## 5. Build plan — PHASE 2 (single gate)

**⛔ GATE 1 — Tax center exact-match: real reuse + honest withholding + the `vat_registered` flag + docs.**
1. **Additive migration** `vat_registered` boolean (default false) on `owners`; add to `Owner::$fillable` (cast bool), `OwnerTaxService::FIELDS`, and `OwnerTaxController::save` validation (`boolean`).
2. **Rebuild `tax-center.blade.php`** to the wireframe (page-scoped `.vy-tax-*` + shared `<x-owner.shell>` + `--vy-*`; green #3F6A38/#6FA764 complete state): the 2-state banner, the tax-profile form (Registered-as segmented toggle + VAT-registered toggle gating the VAT number), the **honest Withholding card**, the annual-summary card-rows, the Documents link card, the dark About-your-tax panel. Alpine for the toggles (a `vat_registered` hidden input + segmented `type`); the form still POSTs to `owner.tax.save` (a real controller write, #20 — NOT `/livewire/update`).
3. **Guards:** #28 owner-scoped + tax_id/vat_number encrypted; #17 summaries reconcile; #20 controller-route save + audited; #42 PDFs gated; #39 nothing fabricated (esp. **no fake withholding**). ANTI-DRIFT: the same tax store + hold + annual PDF as the other pages — proven shared (edit here → reflects in Settings/Payouts; save a tax_id → the hold clears everywhere).
4. **Tests:** extend the owner tax test — the shared store round-trips `vat_registered`; **saving a tax_id releases the real hold** (mutation-proven: hold present before, gone after); the withholding section is honest (no "15%"/"20%"/"withheld on payouts"); the VAT toggle stores the flag. Existing suite stays green.
5. **Consolidate docs:** this file (→ resolved), `DECISIONS.md` (dated — the reuse map + the withholding honest resolution + the `vat_registered` add), `DIVERGENCE-REGISTER.md` (Tax center → exact-design), `PROJECT-STATE.md` (+ invariant bump), `RESUME.md`.
6. **Verify (STOP):** page matches the wireframe exactly; each section wired to its REAL backend (reused, proven shared); withholding honest; saving releases the hold (mutation-proven). Full suite green + PHPStan L5 + Pint + `migrate:fresh --seed` clean. Browser-verify (edit tax → saves + releases the hold + reflects in Payouts; download the real annual PDF). Per-gate commit + PUSH.

### Guards (the gate)
🔒 #28 owner-scoped + tax data encrypted · #17 summaries reconcile · #20 controller-route tax-save + audited · #42 PDFs CAP_EXPORTS-gated · #39 no fabrication (esp. **no fake withholding rate/deduction**). REUSE the tax-hold + `owner.tax.save` + annual PDF + Documents (anti-drift). The `vat_registered` add is additive — DISTURB NOTHING existing (the green suite is the proof).

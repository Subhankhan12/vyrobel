# Vyrobel — VAT + Withholding Tax Engine · DESIGN

**Status:** PHASE 0 — investigation + design (read-only). Presented for approval before any build.
**Date:** 2026-07-29
**⚠️ TAX FIGURES HAVE LEGAL CONSEQUENCES — an owner may file on them.** The governing discipline of this whole build:

> **The ENGINE is built in code. The RATES are CONFIGURED by the founder, per jurisdiction. The session NEVER invents, hardcodes, or presents-as-authoritative a tax rate.** It seeds ONLY clearly-labelled **EXAMPLE** rates the founder MUST replace. Every VAT/withholding output is honestly labelled *"computed from the rates configured for your jurisdiction — verify with your accountant; this is not tax advice."* Unconfigured jurisdiction → an honest **"not configured"** state, never a fabricated figure. The computation is deterministic, integer money (#17), and reconciles.

This makes REAL what the Reports "Tax & VAT summary" and the Tax Center **correctly REFUSED** as fabrication (`reports.blade.php:422` — *"an owner could file on it — dangerous even 'for information only'"*). "Real" here means **computed from founder-configured rates**, not invented.

---

## 1. The two engines — a critical distinction

| | **VAT** | **WITHHOLDING** |
|---|---|---|
| What it is | The owner's own VAT position — output VAT on their rental income minus deductible input VAT on the agency's service fee | Tax the agency withholds from the owner's payout on their behalf (common for non-resident owners) |
| Money effect | **NONE** — informational. The owner remits/reclaims VAT with their own tax authority; it does not touch the payout | **REAL** — reduces the net paid to the owner (a genuine deduction) |
| Computed on | `gross_minor` (output) − `abs(commission_minor)` (input) | the statement's pre-withholding net |
| Surfaced as | a computed report (Reports VAT summary · Tax Center · VAT-return CSV/PDF) | a real ledger deduction + a reported figure (Tax Center · Balance · statement) |
| Reconciles via | (informational — reconciles to `gross`/`commission`) | a `withholding` ledger entry → statement bucket → `net_payable` (#17) |

Keeping these separate is the core of the design: **VAT never reduces a payout** (that would be wrong + dangerous); **withholding does, and reconciles through the certified ledger→statement→payout path.**

---

## 2. What already exists (REUSE — do not rebuild)

| Piece | Where | Role |
|---|---|---|
| `OwnerTaxService::annualSummary()` (committed statements → gross/deductions/net, per-month, reconciling) | `app/Services/OwnerTaxService.php:126` | The VAT/withholding figures extend this shape |
| The statement money columns — **`gross_minor`** (rental income), **`commission_minor`** (agency service fee), signed | `OwnerStatement` + `OwnerStatementService` BUCKET/LABEL | The VAT bases + the withholding target |
| The **`payment_fee`** bucket pattern (Wave C #70) — a new ledger `entry_type` → statement bucket → `net_payable`, reconciling | `OwnerStatementService` + migration `2026_08_19_000000` | **Withholding follows this EXACT pattern** |
| `owners.tax_country` (plain string = jurisdiction AND residency) · `vat_registered` · `vat_number` · `tax_id` | `Owner` | The rate key + the VAT-treatment gate |
| `Money::percentage(rate)` (rounds half-up to minor units) + `allocate()` (no cent leaks) + integer discipline (#17) | `app/Support/Money/Money.php` | All VAT/withholding math |
| The Reports "Tax & VAT summary" honest-deferred block · the Tax Center withholding "None" card · the annual PDF | `reports.blade.php:422` · `tax-center.blade.php:177` · `OwnerAnnualTaxDocument` | The surfaces that become REAL |
| House-style honesty copy ("not tax advice", "share with a qualified accountant", `Not yet available` chip, dashed `.vy-rep-vat` card, `#39`) | the tax/reports views | My labels mirror this |
| `OwnerPayoutService::createFromStatement` (`payout.amount_minor == net_payable_minor`) + `PayoutRunService` (reads `net_payable`) | payout engine | **Unchanged** — withholding baked into `net_payable` flows through automatically |

**The engine reuses the certified ledger/statement/payout amounts — it never re-derives income (anti-drift).** VAT reads `gross`/`commission`; withholding is one more reconciling bucket.

---

## 3. The rate-configuration model (real rates in — NEVER invented)

A new **`tax_rates`** store — the ONE place a real rate enters the system:

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | |
| `jurisdiction` | string (country) | matches `owners.tax_country` |
| `rate_type` | enum(`accommodation_vat`, `service_vat`, `withholding`) | the three rates the engines need |
| `percent` | decimal(6,3) | e.g. `6.000`, `23.000`, `25.000` |
| `effective_from` | date | |
| `effective_to` | date, nullable | open-ended when null |
| `is_example` | boolean | **⚠️ true = a seeded EXAMPLE the founder must replace** — never authoritative |
| `note` | string, nullable | e.g. "EXAMPLE — replace with the real rate for PT" |
| timestamps | | |

- **`TaxRateService::rateFor(jurisdiction, rateType, onDate)`** → the effective `percent` (or **null** if none configured). Prefers a real rate (`is_example = false`) over an example; if only an example exists, returns it flagged as example.
- **Admin-configurable:** a platform-admin mechanism sets real rates per country — a `tax:set-rate` command (and/or a platform-admin screen) writing `is_example = false`. The founder supplies the real rates.
- **Seed ONLY clearly-labelled EXAMPLE rates** (`is_example = true`, `note` = "EXAMPLE — replace…"). The engine reads configured rates; it never hardcodes one.
- **Honesty rule for the surface:** when a computed figure rests on an **example** rate, the surface shows the figure BUT with a prominent *"Based on EXAMPLE rates — your operator hasn't configured real rates for {country} yet. Illustrative only, not for filing."* banner. When a **real** rate is configured, the normal *"computed from your configured rates — verify with your accountant; not tax advice"* label. When **no** rate exists → the honest **"VAT rates not configured for your jurisdiction"** state (no figure). Three honest states; **no rate is ever presented as authoritative unless the founder set it.**

---

## 4. The VAT engine (informational — computed, never a payout deduction)

`OwnerVatService::summary(Owner, int $year)` (owner-scoped, #28): for the owner's committed statements in each **quarter** of the year (Q1–Q4):

- **Output VAT** = `Money(gross_minor).percentage(accommodation_vat_rate)` — VAT on the owner's rental income.
- **Input VAT (deductible)** = `Money(abs(commission_minor)).percentage(service_vat_rate)` — VAT on the agency's management commission (the service the owner is charged). *(The exact base — inclusive/exclusive, whether `management_fee` counts — is documented + the label directs the owner to verify with their accountant; the engine applies the configured rate to a stated base, deterministically.)*
- **Net VAT** = output − input (payable if positive, reclaimable if negative), per quarter + annual.
- **Gated:** only when the owner is `vat_registered` AND a rate is configured for their `tax_country`. Not registered → honest "not VAT-registered." No rate → honest "not configured." **Never a fabricated figure.**
- Integer money throughout (`Money::percentage`), reconciling to `gross`/`commission` to the cent (#17). VAT is **not** written to the ledger and **does not** change any payout — it's a report.

---

## 5. The withholding engine (⚠️ REAL money — reduces the payout, reconciles)

**Withholding follows the certified `payment_fee` pattern exactly** (Wave C #70), so it reconciles by construction and the payout engine needs zero changes:

1. **New ledger `entry_type` `withholding`** (raw `ALTER` on `owner_ledger`, mirroring `payment_fee`).
2. **New statement bucket `withholding`** (`BUCKET`/`LINE_ORDER`/`LINE_TYPE`/`LABEL` + the projection) — a signed deduction summed into `net_source_minor`/`net_payable_minor`.
3. **Computed at statement finalize** from the configured rate: `withholding = Money(pre_withholding_net).percentage(withholding_rate)` for the owner's `tax_country`, posted as a `withholding` **debit** ledger line dated in the statement's period, claimed by that statement. *(Non-circular: computed on the pre-withholding net — the sum of all other buckets — then added.)*
4. **The effect flows automatically:** `net_payable_minor` now includes the withholding → `OwnerPayoutService::createFromStatement` copies it verbatim into `Payout.amount_minor` (the payout is genuinely smaller) → `PayoutRunService` picks it up unchanged. The Balance clearing identity holds because the withholding is a real ledger line (Net position drops by it).
5. **Reported** on the Tax Center (the "None" card → the real withheld figure when configured) + the statement/Balance.
6. **No rate configured for the owner's residency → withholding = €0, line omitted** (exactly like `payment_fee`) — never a fabricated rate. **#17 proven in BOTH states.**

⚠️ **This affects real payout amounts** — so it is mutation-proven: withholding reduces the net correctly + reconciles; an unconfigured residency withholds nothing. **Demo stays unchanged:** the demo owner (PT) gets **no** configured withholding rate → withholding €0 → **Sep net €885.60 unchanged**. Withholding is demonstrated via tests (and, if wanted, a separate clearly-flagged example owner), never by silently changing the demo money.

---

## 6. Honest labelling (every VAT/withholding output)

- **Real configured rate:** *"Computed from the rates configured for your jurisdiction — verify with your accountant. This is not tax advice."*
- **Example rate only:** the figure + *"Based on EXAMPLE rates — real rates for {country} aren't configured yet. Illustrative only, not for filing."*
- **No rate / not registered:** the honest not-configured state (the existing `Not yet available` chip + dashed card) — **no figure.**
- Mirrors the house style (investigator-confirmed): state the source, then the caveat; tag `#39` inline; never a fabricated figure an owner could file on.

---

## 7. Phase plan (gated; per-gate commit; suite green + PHPStan L5 + Pint throughout)

- **PHASE 1 — the rate-configuration mechanism.** `tax_rates` table + model + `TaxRateService` (`rateFor`, real-over-example) + validation; a `tax:set-rate` admin command (real rates, `is_example=false`); seed clearly-labelled **EXAMPLE** rates (`is_example=true`) for the demo country. **Prove:** the engine reads configured rates, never a hardcoded one; a real rate overrides an example; unknown jurisdiction → null (honest); suite green.
- **PHASE 2 — the VAT computation engine.** `OwnerVatService` (output/input/net VAT per quarter from configured rates × real `gross`/`commission`), owner-scoped, integer, reconciling; gated on `vat_registered` + a configured rate. **Prove:** the computation reconciles to `gross`/`commission` to the cent; an unconfigured jurisdiction → honest "not configured" (never invented); an example rate is flagged, not authoritative.
- **PHASE 3 — the withholding engine + money effect.** The `withholding` ledger entry_type + statement bucket, computed at finalize from the configured rate; reduces `net_payable` (reconciling #17); reported. **Mutation-prove:** withholding reduces the net correctly + reconciles (#17 in BOTH states); an unconfigured residency withholds nothing; the payout amount drops by exactly the withheld amount; the demo net is unchanged (no PT withholding rate).
- **PHASE 4 — surface + consolidate.** Reports "Tax & VAT summary" (honest-deferred → REAL computed output/input/net VAT per quarter, honestly labelled, + a VAT-return CSV/PDF from the real figures + a real filing-status store); Tax Center (the withholding card shows the real configured withholding, else honest; the annual summary reflects VAT/withholding). Consolidate `TAX-ENGINE-DESIGN.md` (built) + `DECISIONS.md` + `DIVERGENCE-REGISTER.md` (Reports/Tax-Center VAT → real, configured) + `PROJECT-STATE.md` + the invariant list; bump HEAD. Browser-verify (configure a rate → VAT computes + reconciles + is honestly labelled; no rate → honest "not configured"). Push all. **STOP** with a report.

---

## 8. Standing guarantees held throughout

⚠️ Tax figures have legal consequences: the **engine** is code, the **rates are founder-configured** (a real `tax_rates` store; the session builds the MECHANISM + clearly-labelled EXAMPLE rates the founder MUST replace; it NEVER invents/hardcodes/presents-as-authoritative a rate #39). Deterministic + integer money (#17) + reconciling (every figure = a configured rate × a real ledger amount). Withholding reduces the net payout correctly (mutation-proven, reconciles); **VAT never touches a payout**. Unconfigured jurisdiction/residency → honest "not configured" (NEVER a fabricated figure an owner could file on). Every output labelled *"computed from your configured rates — verify with your accountant; not tax advice."* Owner-scoped (#28); reuse the certified ledger/statement/payout amounts (anti-drift); additive — **disturb nothing** (the green suite is the proof); the demo money is unchanged (no configured withholding).

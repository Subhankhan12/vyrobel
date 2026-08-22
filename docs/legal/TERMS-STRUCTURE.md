# Terms of Service — STRUCTURE AND FACTS, FOR COUNSEL TO DRAFT FROM

**front-website Gate 7 · 2026-08-22 · ⚠️ NOT TERMS. NOT PUBLISHED. NOT LEGAL ADVICE.**

---

## ⚠️ THE BOUNDARY

⚠️ **NO TERMS TEXT IS DRAFTED HERE**, for the same reason no policy text is: it is jurisdiction-specific legal
drafting, and terms that look finished are worse than none because a customer would be asked to accept them.
What follows is the design's own section order, and — under each — **what the product actually does**, so
counsel is redlining facts rather than guessing at them.

⚠️ **The design's draft is already well-judged** and should be kept as the starting point: it is labelled
*"Draft — not yet reviewed by counsel"*, says *"not legal advice and must not be published as-is"*, and
closes with a **"Commercial terms still to settle"** table of nine `TBC` decisions. That table is genuinely
good work and its nine rows are unchanged by this pass — they are business decisions, not build facts.

⚠️ What this pass adds is the corrections: **four of the draft's factual clauses do not match the product.**

---

## 🔴 THE FOUR CLAUSES THAT ARE FACTUALLY WRONG TODAY

### ⚠️ §3 "Plans are billed PER UNIT, monthly or annually in advance… Adding units mid-cycle is prorated."
**Both halves are wrong, and this is a contractual clause about money.**
- The billing engine runs **two FLAT plans** — Essential €49/mo and Pro €99/mo (`config/billing.php:63,76`),
  both marked PLACEHOLDER pending the founder's real prices. **There is no per-unit pricing anywhere.**
- **There is no proration.** A `$prorate` flag exists in the Stripe client
  (`app/Payments/Stripe/StripeClient.php:129`) and its own comment records why it is off: *"a flat one has
  nothing to prorate."* No caller sets it.
- ⚠️ **Related, and already live on the published site**: `/pricing`'s comparison table still carries the
  design's four invented tiers — **Starter / Growth / Pro / Enterprise** — as column headers, even though the
  plan CARDS above it were rebuilt from the real catalogue (B12). Terms must not inherit those names, and the
  table should be corrected separately.

### ⚠️ §5 "…under the data processing agreement that accompanies these terms."
**No DPA exists**, and this phrasing asserts one is attached. The founder has already ruled the DPA claim
removed from marketing copy and Article 28 is a recorded go-live blocker. ⚠️ Note this sentence **evaded the
existing CI guard**: the guard's four needles were written from the phrasings on the pages then visible, and
matched none of the six DPA mentions on the Gate 7 pages. Now corrected in the generator.

### ⚠️ §6 "We aim for continuous availability and PUBLISH INCIDENTS when they occur. Planned maintenance is
ANNOUNCED IN ADVANCE."
There is **no status page, no incident process and no maintenance-notification mechanism** in the product.
There is a liveness endpoint and scheduler/queue alerting (`docs/DEPLOYMENT.md` §7.2), which is monitoring —
not a published incident record. ⚠️ The draft's own "Commercial terms" table already flags the uptime
commitment as `TBC`; these two sentences should move into that decision rather than stand as commitments.

### ⚠️ §8 "On termination you keep the right to export."
Export is real and in open formats (CSV, PDF, XLSX) — but ⚠️ **cancelling makes the workspace READ-ONLY**
(`SubscriptionLifecycleService.php:44-53`), and read-only access to the export routes is what "keep the right
to export" depends on. Counsel should tie the clause to a **stated window**, because the code defines none.

---

## Section-by-section: what the code supports

| § | Section | Verdict | Note |
|---|---|---|---|
| 1 | Agreement | ⚠️ **PLACEHOLDER** | No legal entity exists in the repo. Founder must supply. |
| 2 | Your account | ⚠️ **Correct with one caveat** | 2FA *is* available and recommended — but it is **opt-in and cannot be enforced**, so the clause must not imply an administrator can require it. |
| 3 | Subscription and billing | 🔴 **WRONG** | See above — flat plans, no proration. |
| 4 | Acceptable use | ✅ Supported | Tenant isolation is structural and enforced; the prohibition matches what the code prevents. |
| 5 | Your data | ⚠️ **DPA clause wrong** | The ownership statement is fine; the DPA assertion is not. |
| 6 | Availability and support | 🔴 **WRONG** | No status page, no incident process, no maintenance notice. |
| 7 | Third-party channels and payments | ⚠️ **Premature** | Correct in principle. ⚠️ **No OTA channel can be connected today**, so the clause governs a relationship that cannot yet exist. Stripe is real. |
| 8 | Termination | ⚠️ **Incomplete** | Export real; ⚠️ **no deletion exists** and no window is defined — the draft's own `Data on termination` row is the right place for it. |
| — | Commercial terms still to settle | ✅ **Keep as-is** | Nine `TBC` rows: liability cap, uptime commitment, governing law, notice period, price changes, data on termination, payment failure, indemnities, sub-processor changes. All are business decisions. |
| 9 | Changes | ⚠️ **Check the mechanism** | "Material changes are notified in advance by email and in the app" — there is a real notification feed and real transactional email, so this is **buildable**, but no such notice mechanism exists today. |

---

## ⚠️ ONE ROW TO ADD TO THE "STILL TO SETTLE" TABLE

| Term | Draft says | Why it matters | Decision |
|---|---|---|---|
| **Account deletion** | *"You may close your account at any time"* | ⚠️ Closing sets the workspace read-only. **There is no deletion path for an organisation or any user account.** Either build one or say plainly that closure is retention with access withdrawn — the two are very different promises, and the second is defensible while the first is not currently true. | **TBC** |

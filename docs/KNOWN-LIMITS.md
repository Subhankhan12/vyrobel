# Vyrobel — KNOWN LIMITS, and where each one is told

**Verified 2026-08-31 against the code, not inherited from a report.** Every statement below carries the
code fact that makes it true, so a future reader can re-check it rather than trust it. ⚠️ **A limits
document that goes stale is worse than none.**

> ⚠️ **"Tell customers" is not "write it down somewhere."** A limit discovered *after* signing up is a
> support ticket and a refund request; the same limit stated where someone would meet it is a design. Each
> row below names **where** the telling belongs, and whether it is there yet.

> ⚠️ **No future is promised anywhere in this file.** Every limit is stated as a present fact, in the
> present tense, with no implied intent to change it — because an implied roadmap is a promise a reader
> will hold you to, and a limit that is genuinely temporary is still not one you may print.

---

## L1 · One payment account per organisation, and its country is fixed at creation

**What is true.** An organisation has exactly one `organizations.stripe_connect_account_id`
(`OperatorConnectService::startOnboarding` is idempotent on it — a second call re-links the *same*
account and never creates a second one). **Stripe fixes the account's country during its own hosted
onboarding and it cannot be changed afterwards.**

⚠️ **Whose constraint it is, precisely.** Stripe's, not ours — but note what our code does and does not do:
`createConnectAccount(string $email, array $metadata)` **takes no country parameter at all**
(`StripeClientInterface:105`). We never choose it; the account holder does, inside Stripe's flow. So this
is not a path we declined to build — there is no country for us to set.

⚠️ **The schema cannot express the alternative.** `organizations.country` is `char(2)` **nullable**, and
`properties` has **no country column at all** (`docs/CONNECT-TARGET-DESIGN.md` §302). A multi-country
operator would need one account per country — a one-to-many the schema has no room for.

**An operator who is already wrong.** There is **no in-product path**: no route clears
`stripe_connect_account_id`, so an organisation cannot be re-onboarded to a different country from any
screen. Changing it requires a new Stripe account *and* a console action to clear the stored id — and,
because the limit is one account per organisation, a genuinely multi-country operator needs a **second
organisation**, with its own properties, owners and books. That is a re-setup, not a migration.

**Where it is told — ✅ in product, at the point of the irreversible choice.**
`resources/views/livewire/operator/billing.blade.php` renders, **only while the account does not yet
exist** (`@if (! $connect['connected'])`), a card headed *"Before you start: the country cannot be changed
later"*. That is the right place: before creation, not in a document read afterwards.

⚠️ **What is NOT told: pre-sale.** Nothing on the marketing site raises country or the one-account limit,
so an operator with properties in two countries can reach onboarding before meeting it. **Reported — the
copy is a founder decision, not a correction.**

---

## L2 · No OTA channel can be connected

**What is true.** The pipeline exists and is tested — routes `app.channels`, `webhooks.channex`,
`admin.integrations.*` are registered — but `FakeChannexClient` is the bound default
(`config/services.php:47`), so **nothing reaches a real channel**.

⚠️ **"Stopped" is the wrong word: it never started.** No live sync was ever switched off; the real client
has never been bound outside a test. The `/integrations` page already states this plainly and carries the
disclosure markers the founder ruled must stay.

**Where it is told — ✅ pre-sale, on `/integrations`.** That is the correct place: it is a buying
criterion, and someone comparing Vyrobel to a channel manager must meet it before signing up, not after.

---

## L3 · A guest cannot export their own data; they *can* erase it

**What is true, and the halves differ.**

- **Erasure — EXISTS.** `POST guest/account/delete` plus `guest.account.delete.undo` (a real grace period).
- **Export — DOES NOT EXIST.** No guest export route is registered. A guest can download per-booking PDFs
  (folio receipt, VAT invoice), which is not a subject-access export.

⚠️ **This transfers an obligation, and that is the part worth saying.** The operator is the data
controller, so **the operator services every Article 15 request by hand.** What the product can give them:
the Guests CRM profile, the bulk guest CSV (`app.guests.bulk.export` — name, email, phone, city,
nationality, stays, VIP, marketing consent), and the per-booking PDFs. What it cannot: a single
guest-initiated bundle.

**Where it is told — the legal record and the DPA conversation**, not marketing copy. It is already
recorded: `docs/legal/DATA-INVENTORY.md` §170 ("subject-access and portability are **partial**") and §316
("no Art.15 export for guests at all"). `docs/DATA-RESIDENCY-INVENTORY.md` records the same, and was
corrected once already for wrongly denying the erasure half.

⚠️⚠️ **AND THE LEGAL RECORD CONTRADICTS ITSELF — still open, reported again here.** §299 states *"there is
no /app export route"* on the strength of an enumeration of `routes/web.php:836-897`, while
`/app/security/export` sits at **`routes/web.php:983`** — outside the range read — and §170 of the same
file records that export correctly. **The conclusion may still be right** (that export emits only the
acting user's own account fields, so Art.15 portability is absent) **but its stated reason is false.**
⚠️ Not edited here: a legal document is corrected by its author and a lawyer, not by a build pass.

---

## L4 · Retention windows are fixed in code and cannot vary by jurisdiction

**What is true.** `App\Support\Retention\RetentionPolicy::WINDOWS` sets one window per table for every
tenant — `failed_jobs` 7 days, `sessions` 30, `notifications` / `webhook_events` / `export_downloads` /
`llm_usage` 90 — pruned daily by `retention:prune` (`routes/console.php:51`). The class says so itself:
*"THE WINDOWS ARE FIXED IN CODE AND CANNOT VARY BY JURISDICTION."* `audit_events` and `domain_events` are
deliberately **never** pruned (`RetentionPolicy::NEVER_PRUNED`).

⚠️ **This also transfers an obligation.** An operator whose jurisdiction requires a *shorter* window
cannot comply through the product; one requiring a *longer* one cannot either. What they must do: raise it
in the DPA and, where a shorter window is mandatory, treat it as a manual control outside Vyrobel.

**Where it is told — the legal record and the DPA**, and it already is
(`docs/DATA-RESIDENCY-INVENTORY.md` §3, plus the class's own docblock, which is where an engineer looks
for a knob).

---

## L5 · An organisation cannot require two-factor of its staff

⚠️ **The limit is the MANDATE, not the feature — a different answer to a security-conscious buyer.**

**What exists.** Full TOTP two-factor with recovery codes, trusted devices and a login challenge
(`TwoFactorController`, `two-factor.challenge`). **It is mandatory for platform administrators** —
`EnsurePlatformAdminTwoFactor` enforces it, and `PlatformAdmin` documents it. So the product both
implements 2FA and enforces it *somewhere*.

**What does not exist.** No organisation-level setting requires it of operator staff: nothing in `app/`,
`config/` or the migrations carries a `two_factor_required` or equivalent. Enrolment is per person, opt-in.

⚠️ **And what an operator can do today is: nothing — they cannot even see it.** `two_factor_confirmed_at`
is read by **no operator view or Livewire component**, so an administrator cannot list who has enrolled,
let alone require it. Saying "we don't enforce it" without saying "and you can't check" would understate it.

**Where it is told — pre-sale, in the security answer a buyer asks for.** `/security` already carries
disclosure markers of this kind. ⚠️ Whether to state it there is a founder decision; **reported, not
written**, because the honest sentence names a gap a buyer weighs.

---

## L6 · ⚠️ NOT A LIMIT — the role toggles that gate nothing

⚠️⚠️ **This does not belong on this page and no customer-facing sentence is written for it.** *"Some
permission toggles do not take effect"* is a sentence no product should ship: an administrator who
unchecks *"manage taxes"* believes access was removed. That is the same class as a dead control or a false
claim — and this project **fixed** both of those rather than documenting them.

**Verified here, all of them — 12 of 46 permissions have no enforcement site**, not 14 of 51.
⚠️ The earlier count was reached with a pattern narrower than the codebase's idioms — and so were two of
my own attempts at it: a shell census undercounted by one because the permission list had no trailing
newline (`while read` silently drops the last line), and a `grep` for `availability.view` matched prose
because the `.` was an unescaped regex wildcard. The figure below comes from a literal `str_contains`
per permission over every `.php` file in `app/`, `routes/` and `resources/` — 966 files — and is pinned by
`KnownLimitsAreStillTrueTest` so it cannot drift again.

⚠️⚠️ **In no case is a write left unprotected.** The 11 fall into three different shapes:

| Shape | Permissions | What is actually true |
|---|---|---|
| **Coarser gate** — the write exists and is protected, by a *broader* permission | `taxes.manage`, `fees.manage`, `invoices.issue` | `/settings/tax-fees` and `/settings/fees/add` are `can:properties.manage`; `/settings/invoice` is `can:settings.manage` |
| **Wrong realm** — an operator-catalogue entry for a non-operator capability | `invoices.view`, `payments.view` | the matching routes are guest-realm (`/reservation/{r}/invoice.pdf`, `/payments`) |
| **No distinct action** — nothing in the product performs this as a separate operator write | `amenities.manage`, `availability.manage`, `availability.view`, `restrictions.manage`, `guest_documents.manage`, `corporate_accounts.manage`, `addons.manage` | no route matches the capability at all; `availability.view` is named nowhere in the codebase |

**The options, reported — none implemented, because this is a product decision.**

1. **Remove them from the catalogue.** Truthful immediately, and the code favours it: the catalogue is
   config (`config/authorization.php`) and `OrganizationRoleProvisioner` re-provisions from it, so removal
   is a data change, not a migration. ⚠️ Cost: any org that customised a role loses that row's history.
2. **Wire them** — make each the real gate for its write. Correct, and the largest: it means a finer gate
   *replacing* a coarser one on live routes, which is a security change needing its own mutation proof.
3. **Label them** in the matrix as governed by a broader permission. Cheapest, and the weakest: it leaves
   a switch that does nothing and explains why, which is the shape this project keeps removing.

⚠️ **The surrounding code favours (1) for the seven with no action at all** — a permission for a capability
the product does not expose is not granularity, it is a phantom — **and (2) for the three with a coarser
gate**, where the finer permission is the one an administrator reasonably expects to bind.

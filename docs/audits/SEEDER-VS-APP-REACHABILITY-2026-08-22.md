# SEEDER-vs-APP REACHABILITY SWEEP — what the demo creates that the running application cannot

**Read-only · 2026-08-22 · map, classify, surface — nothing fixed**

> ⚠️ Three independent findings share one shape: `RoomType`/`RatePlan`/`Channel` have no creation path in
> `app/` (**P-6**); the `property_user` write is gated on a condition only that same write could establish
> (**P-7**); and `finalize()`/`generateDraft()` have no callers outside seeders and tests. **The common cause
> is that the demo seeder calls models and services directly, while a real tenant must go through
> routes → controllers/Livewire → services.** Where only the seeder has a path, the demo looks complete and
> coherent while the running product is not.
>
> ⚠️ **Nothing here is fixed.** No path created, no caller added, no condition changed, no seeder altered.
> Every gap is surfaced as a founder decision with three options and **none is picked**.

---

## §1 ⚠️ THE INSTRUMENT, VALIDATED — and the three corrections it needed

**This audit's entire output is claims of absence, and a grep proves the absence of *its needles*, not of the
thing.** So the search was built as a script, validated against entities that are demonstrably reachable, and
corrected every time a known-reachable case slipped through.

### The call shapes searched

| | shape |
|---|---|
| A | `Model::create(` |
| B | `Model::make` / `firstOrNew` / `firstOrCreate` / `updateOrCreate` / `upsert` / `forceCreate` |
| C | `Model::query()->` … `create` / `insert` / `firstOrNew` / `firstOrCreate` / `updateOrCreate` / `upsert` / `make` |
| D | `new Model(` **and** `new Model;` (no parentheses) |
| E | `$parent->relation()->` `create` / `createMany` / `save` / `saveMany` / `make` / `firstOrNew` / `firstOrCreate` / `updateOrCreate` |
| F | `DB::table('<table>')->` `insert` / `insertGetId` / `upsert` / `updateOrInsert` |
| G | `Model::factory(` |
| H | `Model::class` (a *reference*, used to drive the hand-read — never counted as a create) |

Searched across **`app/` and `routes/`** — which covers controllers, Livewire components, services, jobs,
console commands, listeners/observers, API controllers and importers, since all of them live there.
Seeders, factories, tests and migrations were scanned separately as the comparison set.

⚠️ **What an unsearched shape would mean:** every "SEEDER-ONLY" below would be an unproven absence. That is
not hypothetical — it happened three times in this audit.

### ⚠️ The three corrections, each found by cross-checking a feature known to exist

| # | the defect | what it would have produced |
|---|---|---|
| 1 | **Comments were being deleted, not blanked** — so every reported line number was shifted. | It cited `Property::create` at `TenantProvisioningService:74`; the real line is **110**. ⚠️ A mis-citation is worse than none, because it looks checkable. Fixed by replacing comments with equal-length whitespace. |
| 2 | **`firstOrNew` was not in the shape list.** | It reported `OwnerStatement` as having no creation code at all — while `OwnerStatementService::generateDraft()` builds one with exactly that shape at `:81`. |
| 3 | **`new Model;` without parentheses was not matched** (shape D required `(`). | It reported `OwnerTeamInvite` as seeder-only — while `OwnerTeamInviteService.php:97` creates one with `?? new OwnerTeamInvite;`. The owner-team invite flow demonstrably works. |

### The positive controls

| entity | found at | why it validates the search |
|---|---|---|
| **`Property`** | `app/Http/Controllers/Operator/PropertyController.php:58` · `app/Services/TenantProvisioningService.php:110` | Same family as the suspects, and reachable by **two** actors (self-serve tenant, signup). Both citations opened and verified line-exact. |
| **`Reservation`** | `app/Services/AssignmentService.php:85, :150, :219` | The certified single write path; proves the search sees creates buried inside a service. |
| **`RateCalendar`** | `app/Services/RateAndRestrictionService.php:48, :78, :115` | Proves shape **C** works — a naive one-line grep for `Model::create` reported this as unreachable, which was **wrong**. |
| **`OwnerTeamInvite`** | `app/Services/OwnerTeamInviteService.php:97` | Recovered by correction #3 — proves the widened shape D works. |

**Only after all four were found did any negative below get written down.**

---

## §2 THE DIFF — `/signup` versus the seeder

### What a real `/signup` provisions

`app/Services/TenantProvisioningService.php:74-128`, in one transaction:

| | |
|---|---|
| `Organization` | `:74` (created pre-tenant, via the super-admin escape) |
| `User` (founder) | `:91` |
| `Membership` | `:99` |
| roles | provisioned for the org; the founder is assigned `Property Owner` (`:108`) |
| `Property` | `:110` — **exactly one** |
| trial subscription | `:125` |

**That is everything.** No room type, no unit, no rate plan, no rate calendar, no availability, no tax, no
policy, no channel.

### What the demo seeder creates

`database/seeders/DemoSeeder.php` (1,572 lines) creates **26 entity types** directly, including — beyond
what signup provisions — `RoomType` (×3), `Unit` (×3), `RatePlan` (×5), `RateCalendar`, `Tax` (×2),
`Fee`, `PropertyPolicy` (×2), `Floor` (×2), `Building`, `Amenity` (×3), `AddOn`, `PromoCode`, `Tag` (×2),
`Channel`, `ChannelMapping` (×2), `Owner`, `ManagementAgreement`, `AgreementAmendment`, `OwnerUser`,
`OwnerTeamInvite`, `PayoutMethod`, `OwnerMonthlyTarget`, `Media`, `Guest` (×5), plus reservations, folios,
payments, ledger entries, **finalised statements and paid payouts**.

### ⚠️ THE FIRST POINT A NEW TENANT IS BLOCKED FROM TAKING A BOOKING

**A room type.** `BookingEngineService::search()` (`app/Services/BookingEngineService.php:48-58`) builds its
offers by iterating `RoomType` rows for the property. **With zero room types the offer list is empty, so the
storefront can display nothing and no booking can be taken** — and `RoomType` has **no creation path in the
running application at all** (§3).

⚠️ The blockage is not single-file. Even with a room type, a sellable night also needs a **`Unit`** and a
**`RatePlan`** — and the `Unit` path is **transitively blocked by the same absence**: approving an owner's
unit request resolves `RoomType::query()->where('property_id', …)->findOrFail($roomTypeId)`
(`app/Services/OwnerUnitRequestService.php:211`), and the operator's picker is populated from the property's
room types. With none, the required id cannot be supplied and the lookup 404s. ⚠️ **A tenant who signs up
today reaches a property they cannot make sellable.**

### ⚠️ The application tells the tenant to do three impossible things

On successfully creating a property, the app redirects with this message
(`app/Http/Controllers/Operator/PropertyController.php:81`):

> *"{name} added to your portfolio. **Finish setup — add units, rates & channels — to go live.**"*

**None of those three is possible.** Units are gated behind a room type that cannot be created; rates need a
rate plan that cannot be created; channels cannot be created at all. ⚠️ The instruction is not wrong about
what is *needed* — it is an accurate description of the remaining work, addressed to someone with no way to
do any of it.

⚠️ A second instance of the same shape: `app/Http/Controllers/Operator/RateActionController.php:129-131`
handles the no-rate-plan case explicitly — *"A room type with no rate plan has nowhere to store a price"* —
so **the application knows this state exists and reports it, while offering no remedy.**

---

## §3 THE CLASSIFICATION — by entity, by actor

⚠️ Every **SEEDER-ONLY** row below was confirmed twice: zero hits across shapes A–G, **and** a hand-read of
every `app/`+`routes/` occurrence of the model name, each of which proved to be a morph-map entry, a relation
definition, a read query, or a type hint.

### SEEDER-ONLY — no creation path in the running application, for any actor

| entity | consequence |
|---|---|
| ⚠️ **`RoomType`** | **The first block on taking a booking.** 14 references in `app/`, every one a read or a relation. |
| ⚠️ **`RatePlan`** | No nightly price can exist without one; the rates grid can only *edit* plans that already exist. |
| ⚠️ **`Channel`** | No operator can connect an OTA channel. (Independently consistent with the Gate-5 finding that live sync is account-gated.) |
| **`ChannelMapping`** | Follows from `Channel`. `ChannelMappingGuard` only *resolves* mappings. |
| ⚠️ **the channel TRANSITIONS** | `ChannelConnectionService::connect()` (`:50`) and `::activateMapping()` (`:82`) have **zero callers** in `app/`+`routes/` — verified exhaustively. Only `disconnect()` is called, from `PropertyRemovalService`. ⚠️ So even if a channel existed, nothing could connect it or activate a mapping. `/app/channels` is a read-only Livewire page (`OperatorChannels` has only `render()`), and `/admin/integrations` writes `PlatformIntegration` — a **global platform table**, not a tenant `channels` row — so the admin route does not rescue it either. |
| **`Tax`** | ⚠️ **PARTIAL, precisely**: `SettingsService::updateTax()` (`:150`) *edits* an existing tax; nothing creates one. A tenant can change a tax rate they were never able to add. |
| **`AddOn`** | Offered at booking (`NewReservation.php:192` reads them), never created. |
| **`Amenity`** | A read-only catalogue (`OwnerUnitRequestService.php:67`). |
| **`PromoCode`** | Redeemable (`GuestWalletService.php:193`), never issuable. |
| **`Building`, `Floor`** | Only morph-map entries in `app/`. |
| **`AgreementAmendment`** | ⚠️ The *acknowledgment* is creatable (`AgreementAmendmentService.php:67`); **the amendment itself is not**. An owner can acknowledge an amendment nobody can raise. |

### REACHABLE — with the path cited

| entity | actor | path |
|---|---|---|
| `Property` | self-serve tenant · signup | `POST /app/portfolio` → `PropertyController.php:58`; `TenantProvisioningService.php:110` |
| `Reservation` | tenant · guest (storefront) | `AssignmentService.php:85/150/219` |
| `RateCalendar` | self-serve tenant | rates routes → `RateAndRestrictionService.php:48/78/115` |
| `Owner` | self-serve tenant | `OwnerInviteService.php:115` |
| `ManagementAgreement` | self-serve tenant | `OwnerManageController.php:60` |
| `OwnerTeamInvite`, `OwnerUser` | owner (primary) | `OwnerTeamInviteService.php:97, :154` |
| `PayoutMethod` | owner | `OwnerPayoutMethodService.php:71` |
| `OwnerMonthlyTarget` | owner | `OwnerBookingsTargetController.php:59` |
| `PropertyPolicy`, `Fee` | self-serve tenant | `SettingsService.php:89, :208` |
| `Tag` | self-serve tenant | `GuestService.php:199` |
| `Media` | self-serve tenant | `HousekeepingActionController.php:236` (+5 more) |

### GATED-UNREACHABLE — a path exists whose condition cannot be satisfied

| entity | detail |
|---|---|
| ⚠️ **`Unit`** | The **only** `Unit::create` in `app/` is `OwnerUnitRequestService.php:217`, reached via `POST /app/unit-requests/{id}/approve` when an **external owner** submits a request and an operator approves it. ⚠️ **Two conditions block it on a self-provisioned tenant**: there is no owner to raise the request, and `approve()` resolves `RoomType::…->findOrFail($roomTypeId)` (`:211`) against a property that has no room types, so the required id cannot be supplied. ⚠️ **An operator cannot create a unit directly at all**; `/app/units` is a read-only listing (`UnitsList.php` has only `render()`). |

### GATED-UNREACHABLE — the self-referential guard

| subject | the condition |
|---|---|
| ⚠️ **`property_user` (P-7)** | `PropertyController.php:72-78` writes a `PropertyAssignment` **only** `if ($user->propertyAssignments()->exists())`. This is the **only** writer of that table in `app/` (rows otherwise exist solely in `MultiPropertyContextTest`), so the condition can never first become true. ⚠️ Consequence: the per-property allow-list can never be populated, and every back-office user resolves to the **"all properties"** branch of invariant #38. |

### Code that exists but has no caller

| subject | detail |
|---|---|
| ⚠️ **`OwnerStatement`** | Creation code **does** exist in `app/` — `OwnerStatementService::generateDraft()` at `:81`. But `generateDraft()` and `finalize()` have **zero callers in `app/` or `routes/`**: the only hit for either name is the method's own definition at `:77`. All other call sites are in `database/seeders/` and `tests/`. ⚠️ This is a distinct sub-class from "no code" and matters for the decision: the work is written, only unreachable. |

---

## §4 ⚠️ THE MONEY CHAIN — where it stops

| hop | classification | evidence |
|---|---|---|
| ① booking → `Reservation` | **REACHABLE** | storefront, operator and group paths all route through `AssignmentService` |
| ② charge → `folio_items` | **REACHABLE** | `FolioService` is used from 10 controllers/Livewire components |
| ③ ledger accrual | **REACHABLE** | `attributeReservation()` called from `BookingEngineService.php:234`, `ReservationCreateController.php:251`, `GroupBookingService.php:150`, plus the `owners:backfill-attribution` command |
| ④ **statement draft + finalisation** | ⚠️⚠️ **SEEDER-ONLY — THE CHAIN STOPS HERE** | the only `generateDraft(` / `->finalize(` hit in `app/`+`routes/` is the definition itself (`OwnerStatementService.php:77`) |
| ⑤ payout creation | **code wired, but starved** | `PayoutRunService.php:147` calls `createFromStatement()`, and `POST /app/payouts/run` reaches it — but it selects only `finalized` + locked statements (`:139-144`), which hop ④ never produces |
| ⑥ payout send | **REACHABLE — but offline** | the bound rail is `ManualPayoutRail`, which records a bank transfer the operator made outside the system |

> ⚠️ **A real tenant's money chain runs to hop ③ and stops.** Ledger entries accrue for ever; no statement is
> ever finalised; the payout engine has nothing to select. The demo's five paid statements exist because
> `DemoSeeder` finalised them.

---

## §5 ⚠️ PRIOR CONCLUSIONS THAT REST ON SEEDER-ONLY STATE

⚠️ **Flagged, not re-audited.** Each of these was verified against demo data, and the demo contains state a
real tenant cannot produce. They are not thereby *wrong* — the code they exercised is real — but they
established that a flow **works given that state**, not that a real tenant can reach it.

| conclusion | what it rested on |
|---|---|
| **The money-path certification** (displayed == charged, statement reconciliation, payout lifecycle) | seeded room types, rate plans, **finalised statements** and paid payouts |
| **The owner-portal audits** (statement, payouts, balance, documents, reports, tax centre) | finalised statements — none of which a tenant can produce |
| **The multi-property work** (#38, the property context, "pivot-or-all") | ⚠️ `property_user` rows exist only in tests; the **"pivot" branch is unreachable in production** (P-7) |
| **The channel-manager conclusions** | a seeded `Channel` and `ChannelMapping` |
| **Anything asserting the operator can configure taxes, fees, policies or add-ons** | seeded `Tax`, `AddOn`, `Amenity`, `PromoCode` — several are editable but not creatable |
| **Gate-4b's try-booking sandbox** | ⚠️ Honestly scoped already: it mirrors `seaside-demo` and says so, and it renders an explicit empty state when the demo property is absent. Listed for completeness. |

---

## §6 ⚠️ THE FOUNDER DECISIONS — prioritised by consequence, none picked

### ① Blocks a new tenant from taking a booking

**`RoomType`, `RatePlan`, `Unit`** — a `/signup` tenant reaches a property they cannot make sellable.

- **Build the path** — operator CRUD for room types, rate plans and units.
- **Accept the gap** — every new tenant must be onboarded by hand (someone runs a seeder or tinker for them). ⚠️ Consequence: self-serve signup is not self-serve, and `/signup` is live today.
- **Remove the feature** — withdraw self-serve signup until the paths exist.

### ② Silently produces no money

**Statement finalisation (hop ④).**

- **Build the path** — a route, command or scheduled job that calls `generateDraft()` / `finalize()`.
- **Accept the gap** — statements are finalised out-of-band by an operator with console access. ⚠️ Consequence: owner statements, payouts, the tax centre and the owner documents area are all inert for real tenants, while the docs describe them as working.
- **Remove the feature** — withdraw the owner-payout surfaces until a finaliser exists.

⚠️ Two adjacent facts, recorded in the funds-flow report and not re-litigated here: `POST /app/payouts/run`
bypasses `PAYOUT_ENGINE_ENABLED`, and the one-live-payout-per-statement rule has **no database constraint**.

### ③ Grants broader access than intended

**`property_user` (P-7).**

- **Build the path** — a UI to assign staff to properties, plus a way to establish the first pivot row.
- **Accept the gap** — every back-office user sees every property. ⚠️ Consequence: the documented
  per-property scoping does not exist, and the Gate-5 audit found a guide explaining how to use it.
- **Remove the feature** — drop the pivot-or-all rule and document org-wide access as the behaviour.

### ④ Features nobody can reach

**`Channel` / `ChannelMapping` · `Tax` (create) · `AddOn` · `Amenity` · `PromoCode` · `Building` · `Floor` ·
`AgreementAmendment`.**

- **Build the paths** · **Accept** (each stays demo-only) · **Remove** — ⚠️ a feature nobody can reach is a
  maintenance and claim liability, and Gate 5 found the documentation describing several of them as working.

---

## Provenance

Every classification was produced by the validated instrument **and** confirmed by hand-reading every
reference. ⚠️ Three independent mapping passes were then run and **each was adversarially re-checked by a
second reader whose explicit instruction was to FIND THE PATH THE AUDITOR MISSED**, searching shapes beyond
the original battery. Their verdict: **"No creation path was found for any entity claimed absent."** Every
overturn they produced was against a sub-claim not made in this report (reservation terminal statuses, a
bulk `units.owner_id` update, and a "RatePlan is read-only" assertion — this report says only that RatePlan
cannot be *created*, and explicitly notes that existing plans can be edited). ⚠️ The instrument required **three corrections** before any negative was trusted, each caught by
cross-checking against a feature known to exist — which is the method's own argument for itself.

**Read-only. Nothing was created, fixed, wired or altered. Money as seeded byte-identical:
929 / 10,912,000 / 0 `unit_rate_overrides` / 41 / 16 / 0.**

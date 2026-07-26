# RESUME — START HERE (fresh-session orientation index)

*Last consolidated 2026-07-27. This is the 60-second orientation for a new agent/session. It is a pointer, not a
copy — follow the links. If anything here conflicts with the repo, **trust the repo** and fix the docs.*

---

## 1. Read order (stop when you have what you need)

1. **THIS file** — orientation, creds, next action.
2. **`docs/PROJECT-STATE.md`** — the primary resume doc. The top "CURRENT STATE" block is authoritative; the
   **Invariants #1–53** list is the "what's proven" ledger; the "Deferred / outstanding register" is what NOT to
   build ad hoc.
3. **`docs/DECISIONS.md`** — the append-only dated "why" behind every major decision (newest entries at the end).
4. **`docs/SUPERADMIN-COMPLETENESS.md`** — the Super Admin roadmap (before-launch / soon / nice-to-have + phased plan).
5. **On demand:** `docs/GAP-ANALYSIS.md` (tenant-product roadmap) · `docs/GAP-SWEEP.md` · `docs/POLISH-SWEEP.md` ·
   `docs/SUPERADMIN-AUDIT.md` · `docs/design/DIVERGENCE-REGISTER.md` (prototype↔app design classification) ·
   `docs/EMAIL-PRODUCTION.md` + `docs/STRIPE-PRODUCTION.md` (the founder's cutover checklists) · `CLAUDE.md` (the
   working discipline). Per-area specs live under `docs/` and `docs/schema/` — read the relevant one before building.

## 2. One-paragraph state

**Vyrobel is a multi-tenant, AI-native hotel/apartment-management SaaS, and everything buildable is COMPLETE.** Three
tenant apps (operator `/app` · owner `/owner` · guest `/guest`) + a public storefront (`/book/{slug}`) + self-signup
(`/signup`) on a certified multi-tenant backend, plus the **Super Admin platform panel (`/admin`)** — all built,
styled, and browser-verified. Phases 0–7 (the whole tenant product) are done; Phase 8's two obtainable integrations
(**production email** + **live Stripe**, all three seams) are lit up in test-mode/code-complete, needing only the
founder's non-code cutovers. **Suite 1040 tests green · PHPStan L5 = 0 · Pint clean · `migrate:fresh --seed` clean ·
Invariants #1–53.** There is **no outstanding build** — the product is demo-ready end-to-end and the Super Admin panel
is audited (0 critical/0 P1) + production-ready.

## 3. Stack (locked)

Laravel 13 / PHP 8.4 · MySQL 8 / InnoDB · Livewire 4 + Blade + Tailwind 4 (Vite) · Redis + Horizon · ULID PKs ·
spatie **teams-mode** RBAC · shared-DB multi-tenancy via `organization_id` + `OrganizationScope`
(`runAsSuperAdmin` = the ONE sanctioned bypass). Integer minor-unit money (`*_minor` + `Money`). Pest 4 / PHPStan L5 /
Pint. Browser verification = the Playwright MCP driving `php artisan serve` (real, but not a re-runnable suite).

## 4. Run it / demo it

```
php artisan migrate:fresh --seed   # 2 demo properties, demo org on a real Essential subscription, demo admin
php artisan serve                  # http://127.0.0.1:8000
```
Prereqs for the suite: a genuine **MySQL 8** `vyrobel_test` DB (NOT MariaDB) + **Redis** running; then `php artisan test`.

## 5. Login credentials (all password `password`)

| Realm | URL | Email | Role |
|---|---|---|---|
| Operator back office | `/app` | `demo-staff@vyrobel-demo.test` | Hotel Manager |
| Operator (org admin) | `/app` | `demo-admin@vyrobel-demo.test` | Property Owner (org admin) |
| Owner portal | `/owner` | `demo-owner@vyrobel-demo.test` | External Owner (primary) |
| Guest portal | `/guest/login` | `demo.traveller@vyrobel-demo.test` | Booking-tied guest |
| **Platform admin** | `/admin` | `admin@vyrobel-demo.test` | Super Admin (**forced 2FA enrol on first login**) |

Public storefront (no login): `/book/vyrobel-demo`. Self-signup (provisions a new trial tenant): `/signup`.
(Also seeded: owner sub-users `demo-owner-accountant@` / `demo-owner-viewer@`; operator `demo-accountant@` /
`demo-housekeeper@`. Full detail in `database/seeders/DemoSeeder.php` + `PlatformAdminSeeder.php`.)

## 6. The next action

**There is no outstanding build.** The next decision is the founder's:

1. **Choose the launch model** — *free-trial / early-access* is launch-ready with the panel as-is; a *paid* launch
   needs only the already-scoped **Stripe go-live** + **suspension enforcement** (both in `docs/SUPERADMIN-COMPLETENESS.md`).
2. **Deploy** — the app runs locally on XAMPP; it needs production hosting / domain / HTTPS / MySQL / Redis-Horizon.
3. **The founder's cutovers** — email DNS + Postmark token (`docs/EMAIL-PRODUCTION.md`); the Stripe live-key flip
   (`docs/STRIPE-PRODUCTION.md`).

If building further, take the next item from the **`docs/SUPERADMIN-COMPLETENESS.md` roadmap** (Phase 1: billing sync ·
real revenue/invoices/refunds · suspension enforcement · re-point the Horizon gate to the `admin` guard ·
trial-adjust / cancel / manual customer create).

## 7. Working discipline (from `CLAUDE.md` — non-negotiable)

Audit-first (echo the plan before writing); STOP at every ⛔ gate; **no faked data ever** (flag demo data, never
stub-and-pretend); tests are real gates (report pass/fail honestly with citations); **extend, don't duplicate** (one
`App\Support\*` helper per concept — two `match`es drift); **mutation-prove** money/auth/isolation guards; static
checks can't see a rendered page, so **run the suite AND open it in a browser** before claiming a gate; commit per
gate. Tenancy, money (#17), refund bound (#27), and the authorization boundaries (#26/#30) are load-bearing — never
weaken them.

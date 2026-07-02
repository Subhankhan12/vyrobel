# Vyrobel — Claude Code Project Memory

Vyrobel is a multi-tenant, AI-native **hotel management SaaS platform** (PMS + channel manager + booking engine + guest/staff PWAs + owner portal + AI agents) for operators running a **mix** of hotels, aparthotels, serviced apartments, villas, and multi-unit rentals.

Keep this file lean and high-signal — it loads into context every session. Detailed specs are referenced by path (read them on demand), not pasted here.

## Stack (locked — do not change without an explicit decision recorded in docs/DECISIONS.md)
- **Laravel** (PHP 8.2+), **Livewire + Blade + Tailwind**, Vite for assets
- **MySQL 8 / InnoDB** — genuine MySQL (not XAMPP's bundled MariaDB). Use standard InnoDB features; no Postgres-specific constructs.
- **Queues:** `database` driver for Phase 1 → **Horizon on Redis** from Phase 2
- **RBAC:** `spatie/laravel-permission`, **teams mode** (team = organization)
- **Tests:** Pest · **Static analysis:** Larastan/PHPStan · **Style:** Laravel Pint · **E2E:** Playwright

## Non-negotiable conventions (apply to everything)
- **Tenancy:** every business table has `organization_id`; use the `BelongsToOrganization` trait (global scope + auto-fill + a guard that THROWS if a tenant query runs with no organization context, outside an explicit super-admin context). Never query tenant data unscoped.
- **Primary keys:** ULIDs on all tenant tables (`HasUlids`).
- **Money:** integer minor units (`*_minor` bigint) + ISO-4217 `currency`; use the `Money` cast/value object. NEVER use floats for money.
- **Time:** store UTC; compute guest-facing "today"/day logic in the property's `timezone`. Stay dates are `date`, half-open `[check_in, check_out)`.
- **PII:** `guests.email`/`phone` are encrypted casts. GDPR erase = `anonymize()` (strip PII + set `anonymized_at`) — NEVER soft-delete-and-keep PII.
- **One reservation, many channels:** a single `reservations` table; the `source` column distinguishes direct/OTA/walk-in/corporate/group. NEVER create parallel per-channel tables.
- **Single inventory write path:** ALL inventory-affecting writes go through `AssignmentService`. No other code inserts or mutates `reservation_units` or `availability` directly. On MySQL this service IS the no-double-booking guarantee (transaction + `SELECT … FOR UPDATE` on availability rows + overlap check).
- **Events:** every meaningful state change writes an `audit_events` row (who did what) AND a `domain_events` row (business event outbox).
- **Reuse, don't duplicate:** polymorphic `media` / `documents` / `notes` / `tags` are shared across modules. Extend existing services; never build a parallel structure for an existing concept.

## Workflow rules
- **Audit first:** at the start of a build step, inspect the real code and echo the plan before writing anything.
- **Gated phases:** STOP at every ⛔ gate and wait for approval. Never collapse steps.
- **No faked data, ever:** demo seed data must be clearly flagged as demo. If something isn't actually built, or a test isn't actually passing, say so plainly — never stub-and-pretend to look finished.
- **Tests are real gates:** the double-booking concurrency test and the tenant-isolation test MUST actually pass. Report pass/fail honestly, with file citations.
- **Commit per gate:** commit once at each passing gate with a clear message; never batch multiple gates into one commit.
- **Ask before installing** packages beyond those listed above.

## Commands
- Serve: `php artisan serve`
- Migrate: `php artisan migrate` · Rebuild + seed: `php artisan migrate:fresh --seed`
- Test: `php artisan test` (or `./vendor/bin/pest`)
- Style: `./vendor/bin/pint` · Static analysis: `./vendor/bin/phpstan analyse`
- Assets: `npm run dev` / `npm run build`
- Queue (Phase 2+): `php artisan horizon`

## Authoritative references — READ the relevant file before building in that area (do not assume)
- `docs/schema/phase-1.md` — Phase 1 column-level schema (source of truth for tables)
- `docs/CODING-STANDARDS.md` — code style, structure, testing patterns
- `docs/master-plan.md` — product vision, phases, buy-vs-build decisions
- `docs/DECISIONS.md` — running log of locked decisions (append here whenever we lock a new one)
- `docs/prompts/phase-1.md` — the current build prompt

(These are referenced, not auto-imported, to keep session context lean. Use `@path` import syntax only for short, always-needed snippets.)

## Current status
- **Phase 1 & Phase 2 both COMPLETE & certified** (Steps 0–9 each): multi-tenant core + the channel manager (Channex), Stripe payments, public booking engine, and staff layer — backend + unstyled contract stubs, all proven against **in-memory fakes** (no network). Suite: **84 tests / 509 assertions** green on MySQL; queue is **Redis/Horizon**, live.
- **⚠️ Two live-integration checkpoints remain OUTSTANDING** (never network-verified): Channex staging (real ARI/webhook shape + HMAC) and Stripe test-mode with real keys (real `StripeClient` + SDK webhook path). **NEXT ACTION: close them via `docs/prompts/live-integration-checkpoint.md`** (needs credentials) — else the design pass — then Phase 3.
- **On a new session, read `docs/PROJECT-STATE.md` first, then `docs/DECISIONS.md`** — they hold the resume snapshot, the invariants, and the deferred-items register.

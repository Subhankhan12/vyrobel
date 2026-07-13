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
- Dev email (demo): `mailpit` (SMTP `127.0.0.1:1025`, UI `http://127.0.0.1:8025`) + `.env` `MAIL_MAILER=smtp` — catches invite emails locally. **Prod needs a real provider** (Mailpit is dev/demo only).

## Authoritative references — READ the relevant file before building in that area (do not assume)
- `docs/schema/phase-1.md` — Phase 1 column-level schema (source of truth for tables)
- `docs/CODING-STANDARDS.md` — code style, structure, testing patterns
- `docs/master-plan.md` — product vision, phases, buy-vs-build decisions
- `docs/DECISIONS.md` — running log of locked decisions (append here whenever we lock a new one)
- `docs/prompts/phase-1.md` — the current build prompt

(These are referenced, not auto-imported, to keep session context lean. Use `@path` import syntax only for short, always-needed snippets.)

## Current status
- **THE PRODUCT IS COMPLETE & DEMOABLE END-TO-END.** Certified backend + full styled surface + real auth. Suite: **239 tests / 3372 assertions** green on MySQL; PHPStan L5, Pint clean; queue is **Redis/Horizon**, live. HEAD `b5bd52c`, working tree clean (only the untracked `docs/design/` reference assets remain uncommitted — deliberate). **Detail lives in `docs/PROJECT-STATE.md` + `docs/DECISIONS.md` — don't duplicate it here.**
- **Backend CERTIFIED:** Phase 1 (multi-tenant core), Phase 2 (channel manager Channex + Stripe payments *validated live test-mode 2026-07-03* + public booking engine + staff layer), Phase 3 · Module 1 (Owner Accounting — attribution → append-only `owner_ledger` → period-locked statements → payouts + owner-isolation portal). Backends are FINAL.
- **DESIGN PASS COMPLETE — 13 screens styled + browser-verified** (owner portal 4 · operator core 5 + Units · public booking 3, incl. the real checkout WRITE): three role design systems (owner / operator / public shells + `Formats*Data` traits); presentation only, backends/schema/guarantees unchanged; honesty rule + scoped-render/guarded-mutation discipline are invariants #19–20. Three **browser-verified user journeys** (public booking · operator ops · owner portal) — that pass found + fixed the two demo-data gaps (public slug, owner-accounting seeding; `docs/DECISIONS.md` 2026-07-12).
- **AUTH CHAPTER COMPLETE & SECURE** — login/logout + invites (owner + Hotel Manager + Accountant) + **password reset**. Real session auth fronts the app; **the dev-login shim is gone.** Invites: sha256-hashed single-use expiring tokened link → set hashed password → activate + auto-login (owner → `/owner`; staff → Membership + a **real, validated spatie role** → `/app`). Password reset on Laravel's Password broker, same posture (no-enum, active-only, single-use/expiring, hashed, remember-token rotated, session regen, rate-limited). **Dev email wired to Mailpit**; expired invites pruned daily (`invites:prune-expired`). **Production still needs a real mail provider.** Security stack is invariant #21; proven by `PhaseBEndToEndTest` + `StaffInviteTest` + `PasswordResetTest`.
- **Tier 1 finishing sweep DONE** (2026-07-13): password reset (above) · check-out now dirties the unit via the real service/event chain · scheduled expired-invite prune · UnitFactory made tenant-context-aware. These four are **removed from the deferred register**.
- **⚠️ TWO live-integration checkpoints remain OUTSTANDING** (deferred money/network rails — everything else is real + tested): **Channex staging** (ARI/webhook shape + HMAC) and **Stripe Connect payout rail** (`StripeConnectPayoutRail.send()` throws, `ManualPayoutRail` is the real path).
- **NEXT — a written, gated decision; NONE started (further building is lower-leverage than one customer conversation):** **(a) CUSTOMER VALIDATION — run Mailpit + demo the full loop to a real operator [RECOMMENDED]**, (b) demo polish, (c) a live checkpoint (Channex / Stripe Connect) when accounts exist, (d) a new design-only module (Unified Inbox) or in-product polish. (Mailpit + Manager/Accountant invites are DONE.)
- **On a new session, read `docs/PROJECT-STATE.md` first, then `docs/DECISIONS.md`** — they hold the resume snapshot, the invariants (#1–21), the deferred register, the styled-surface inventory, and the NEXT-ACTION fork.

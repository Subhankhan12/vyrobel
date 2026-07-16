# Vyrobel Coding Standards

## PHP / Laravel
- PSR-12 via Pint; run `./vendor/bin/pint` before committing.
- Strict types where practical; type all method signatures. Keep PHPStan (Larastan) green.
- Fat services, thin controllers/Livewire components. Business logic lives in services (e.g. `AssignmentService`, `FolioService`, `AvailabilityService`), never in controllers or Blade.
- One reservation, many channels: never branch schema by channel — branch behavior by `source`.
- All money via the `Money` value object (integer minor units + ISO-4217 currency). No floats, ever.
- All inventory writes go through `AssignmentService`. No exceptions.
- Every meaningful state change emits an `audit_events` row and a `domain_events` row via the shared writers.
- Tenancy: every business model uses the `BelongsToOrganization` trait; never query tenant data unscoped.

## Naming
- Models singular (`Reservation`), tables plural snake_case (`reservation_units`).
- ULID primary keys; `*_minor` for money columns; `*_at` for timestamps (stored UTC).

## Testing
- Pest. Feature tests for services and flows; unit tests for pure logic.
- Required, never-skipped: double-booking concurrency test; type-oversell test; tenant-isolation test; money-integrity test.
- Tests must actually pass — no skipped/xfail to look green. If it fails, report it.

## Git
- **COMMIT PER GATE, DIRECTLY ON `main`** — that is what this project actually does and what CLAUDE.md
  mandates: `git branch -a` shows only `main`, and every commit in the history (both security fixes, all
  five Wave 2 sub-gates) landed there. Conventional prefixes (feat/fix/chore/test/refactor/docs); one
  commit per passing gate, never batched.
  *(This line previously mandated feature branches — a workflow the repo has never used. Corrected
  2026-07-17 to match reality; if you WANT branches, decide it in `docs/DECISIONS.md` first.)*
- Commit `CLAUDE.md` and `docs/`; never commit `.env`, secrets, or `CLAUDE.local.md`.

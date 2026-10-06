# Vyrobel

A multi-tenant hotel management system: the operator back office (PMS, rates, housekeeping, maintenance, messages and team
chat), the owner portal, the guest portal, a public booking storefront and a channel manager. Live at
[vyrobel.com](https://vyrobel.com) — internal test organisations only, no customers yet.

## Stack

Laravel 13 · PHP 8.4 (8.4.1 or newer) · Livewire 4 · Tailwind 4 with Vite · MySQL 8.4 locally, MariaDB 11.8 on production ·
Redis locally · Pest 4 · PHPStan level 5 · Pint · spatie/laravel-permission (teams mode) · stripe-php (test mode only) ·
dompdf. Every tenant table carries `organization_id`; money is integer minor units; primary keys are ULIDs.

## Run it locally

```bash
composer install
npm ci && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed   # demo data — LOCAL ONLY, never on production
php artisan serve                  # then sign in at http://localhost:8000 (demo accounts: CLAUDE.md, "How to DEMO it")
```

## Tests and gates

- `php artisan test` — the full suite. It needs a `vyrobel_test` database and Redis, and it runs alone: one suite at a time.
  The count is whatever the run prints.
- `./vendor/bin/pint --test` · `./vendor/bin/phpstan analyse --memory-limit=1G`
- `VY_SEED_DATE=2026-09-01 php artisan migrate:fresh --seed --force && ops/money-as-seeded.sh` — the money gate (local only).
- `php tests/Mutations/run.php` — the mutation harness: every guard is proven by breaking it.
- GitHub runs the same gates on every push (`.github/workflows/ci.yml`); read the result with `gh run list`.

## Deploying

Only through `ops/release.sh` — stage, migrate (additive migrations only), swap, then `ops/public-checks.sh` — as written in
`docs/DEPLOYMENT.md` and `docs/memory/RULES.md`. Never `ops/deploy.sh` on the host.

## Where things are written down

- `CLAUDE.md` — the conventions and the hard rules.
- `docs/memory/` — the project's memory: `STATE.md` (where it stands now), `OPEN.md` (what is not finished, and who it
  waits on), `RULES.md`, `SYSTEMS.md` (the map) and `HISTORY.md` (every pass).
- `docs/DECISIONS.md` — every locked decision, dated.

No secret belongs in this repository, a commit, a command or its output.

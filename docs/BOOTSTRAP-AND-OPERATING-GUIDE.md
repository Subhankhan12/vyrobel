# Vyrobel — Project Bootstrap & Operating Guide

Everything you need to go from an empty folder to a running, tested Phase 1 foundation — plus how we operate as a full team (across every role) through the phases.

Environment assumed: **macOS + XAMPP**, project at `.../XAMPP/htdocs/vyrobel` (currently empty).

---

## 1. The memory & config files (what goes where)

Claude Code has **two** memory systems, both loaded at session start:

| File / system | Location | Committed? | Purpose |
|---|---|---|---|
| **Project CLAUDE.md** | `vyrobel/CLAUDE.md` | ✅ yes | Team conventions + locked stack. **The main one.** (Provided — drop it in the root.) |
| **Personal CLAUDE.md** | `~/.claude/CLAUDE.md` | no (your machine) | Your cross-project preferences (optional). |
| **Local CLAUDE.md** | `vyrobel/CLAUDE.local.md` | ❌ gitignore it | Personal notes for *this* project only. |
| **Nested CLAUDE.md** | `vyrobel/<subdir>/CLAUDE.md` | ✅ | Loads on demand when Claude works in that subtree (use later, e.g. PWA vs API). |
| **Auto memory** | machine-local memory folder | no | Notes Claude writes itself across sessions (v2.1.59+, on by default). Browse/toggle via `/memory`. |

**Rules of thumb:**
- Keep CLAUDE.md ~50–200 lines. Every line costs context budget each session. It's a behavior brief, not documentation.
- Put detailed specs in `docs/` and *reference them by path* in CLAUDE.md so Claude reads them on demand. Use the `@path/to/file` import syntax **only** for short, always-needed snippets (imports are pulled into context every session — importing a long doc bloats every session).
- CLAUDE.md is **guidance, not enforcement.** For things that must *always* happen (e.g. run Pint or tests before a commit), use a **hook** (see §6), not a CLAUDE.md sentence.
- Commands: `/init` drafts a CLAUDE.md from the repo (yours is empty, so use the provided one); `/memory` edits memory and toggles auto memory; `#` at the start of a line jots a quick rule mid-session.

---

## 2. Zero-to-Phase-1 sequence (exact steps)

**A. Verify your toolbox**
```bash
php -v          # need 8.2+  (check XAMPP's PHP, or use a newer PHP)
composer -V
node -v         # 18+ (20+ ideal)
git -v
claude --version   # for auto memory, want 2.1.59+
```

**B. Scaffold Laravel into the empty folder**
```bash
cd /Applications/XAMPP/htdocs/vyrobel
composer create-project laravel/laravel .   # the dot = install in place
```

**C. Create the database** — start **MySQL** in the XAMPP control panel, open phpMyAdmin (`http://localhost/phpmyadmin`), create a database named `vyrobel` with collation `utf8mb4_unicode_ci`.

**D. Configure `.env`** (in the project root)
```env
APP_NAME=Vyrobel
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vyrobel
DB_USERNAME=root
DB_PASSWORD=            # XAMPP default root password is empty

QUEUE_CONNECTION=database   # Redis+Horizon comes in Phase 2
```
Then: `php artisan key:generate`

**E. Initialise git**
```bash
git init
# ensure .gitignore includes: .env, /vendor, /node_modules, CLAUDE.local.md
git add .
git commit -m "chore: scaffold Laravel + project foundation"
```

**F. Create the docs structure and drop in the deliverables**
```
vyrobel/
├── CLAUDE.md                     ← the provided file
└── docs/
    ├── master-plan.md            ← the Master Plan doc
    ├── CODING-STANDARDS.md       ← §5 of this guide
    ├── DECISIONS.md              ← seed from §7
    ├── schema/
    │   └── phase-1.md            ← the Unified Schema doc (the prompt references THIS path)
    └── prompts/
        └── phase-1.md            ← the Phase 1 Claude Code prompt
```

**G. Save `CLAUDE.md`** to the repo root (provided).

**H. Open Claude Code and confirm memory loaded**
```bash
claude
/memory        # confirm vyrobel/CLAUDE.md appears in loaded files
```
(Optional: run `/init` now that Laravel exists — it can add detected commands. Keep the provided conventions.)

**I. Run Phase 1** — paste the Phase 1 prompt (from `docs/prompts/phase-1.md`) and work through its ⛔ gates. Step 0 is pure audit; it will report the repo state before writing anything.

---

## 3. Serving the app

Simplest for dev: **`php artisan serve`** → `http://localhost:8000`, using XAMPP's MySQL for the database. (No need to point Apache at `public/`.)

Optional upgrade: **Laravel Herd** (free, macOS-native) is a nicer Laravel dev environment than XAMPP if you hit friction — but XAMPP + `artisan serve` is perfectly fine to start, so don't switch mid-setup.

---

## 4. Environment gotchas (know these now)

- **Use genuine MySQL, not XAMPP's MariaDB:** XAMPP bundles MariaDB. Since you want real MySQL, install MySQL Community Server (`brew install mysql`) and run it on port 3306 — stop XAMPP's own database first so they don't clash. The bootstrap prompt does this and verifies the running engine actually reports as MySQL 8.
- **Redis for Horizon:** XAMPP has no Redis. Phase 1 runs queues on the `database` driver, so you don't need it yet. When Phase 2's channel sync arrives: `brew install redis`, start it, switch `QUEUE_CONNECTION=redis`, run `php artisan horizon`.
- **PHP version:** if XAMPP's bundled PHP is below 8.2, install a current PHP (e.g. via Homebrew) and use it for `composer`/`artisan`. The DB can still be XAMPP's.
- **Storage permissions:** if you hit write errors, `chmod -R 775 storage bootstrap/cache`.

---

## 5. Tooling setup (the QA + engineering baseline — do this early)

Install these right after scaffolding so quality is enforced from the first line, not retrofitted:

```bash
# Testing (Pest) — nicer syntax over PHPUnit; php artisan test runs it
composer require pestphp/pest --dev --with-all-dependencies
php artisan pest:install

# Static analysis (Larastan / PHPStan) — catches type & logic bugs before runtime
composer require larastan/larastan --dev

# Code style (Pint) — ships with Laravel; zero-config PSR-12
./vendor/bin/pint

# E2E (Playwright) — used by the Phase 1 integrity UI test (Step 8)
npm init playwright@latest
```

Create a `phpstan.neon` at level 5–6 to start (raise over time). Aim for these as your standing quality bar:
- **Pint** clean (no style diffs) before every commit.
- **PHPStan** green at the chosen level.
- **Pest** suite green — and specifically the two make-or-break tests never skipped: the **double-booking concurrency** test and the **tenant-isolation** test.

---

## 6. The `.claude/` folder (project-level config — mostly later, but know it exists)

Beyond CLAUDE.md, a `.claude/` directory in the repo can hold:
- **`settings.json`** — project settings (permissions, tool config), committed for the team.
- **Custom slash commands** — reusable prompts (e.g. a `/new-module` command that runs your standard audit-first checklist).
- **Hooks** — shell commands that run at fixed lifecycle events (e.g. *PreToolUse* / pre-commit). **This is how you enforce rules**, since CLAUDE.md is only guidance. Good early candidates once we're rolling: run Pint on edited files; block a commit if the Pest suite fails.
- **Subagents** — specialized agents with their own focused context (useful later for, say, a dedicated test-writer or reviewer).

Don't build these now — just know that "make Claude *always* do X" = a hook, not a CLAUDE.md line. We'll add a couple of enforcement hooks once Phase 1 has a test suite to protect.

---

## 7. Seed your `docs/DECISIONS.md` with what's already locked

A running decision log keeps a long, gated project honest — anyone (including future-you and Claude) can see *what* was decided and *why*. Start it with:

```markdown
# Vyrobel — Decision Log

- **Product name:** Vyrobel — an independent hotel management SaaS platform.
- **Wedge / ICP:** the hybrid operator (hotel + apartment + villa + multi-unit) with real owner accounting; SMB beachhead in under-served geographies. Not competing head-on with Mews/Cloudbeds on breadth.
- **Architecture:** all-in-one, single unified data model (the "semantic layer" that makes AI agents work natively).
- **Database:** MySQL 8 / InnoDB — genuine MySQL (not MariaDB); no Postgres-specific features.
- **Multi-tenancy:** shared database, `organization_id` column + global scope + tenant guard.
- **Primary keys:** ULID.
- **RBAC:** spatie/laravel-permission, teams mode (team = organization).
- **Double-booking prevention:** two-level (room-type counts + physical-unit non-overlap), enforced in a single guarded `AssignmentService` (app-level locking, since MySQL has no exclusion constraint).
- **Buy-vs-build:** RENT the channel manager (Channex), payments (Stripe + Channex PCI proxy), messaging transport (Meta WhatsApp API / Twilio / SendGrid), smart-lock APIs. BUILD the data model, AI agent layer, hybrid experience, website builder, PWAs, automation builder.
- **Build order:** Phase 1 foundation → Phase 2 distribution/payments → Phase 3 differentiators (website, inbox, owner accounting, AI) → Phase 4 intelligence → Phase 5 platform/moat.

(Append new decisions with a date as we lock them.)
```

---

## 8. The `docs/CODING-STANDARDS.md` starter (save this as that file)

```markdown
# Vyrobel Coding Standards

## PHP / Laravel
- PSR-12 via Pint; run `./vendor/bin/pint` before committing.
- Strict types where practical; type all method signatures. Keep PHPStan green.
- Fat services, thin controllers/Livewire components. Business logic lives in services (e.g. AssignmentService, FolioService, AvailabilityService), never in controllers or Blade.
- One reservation, many channels: never branch schema by channel — branch behavior by `source`.
- All money via the Money value object (integer minor units). No floats, ever.
- All inventory writes go through AssignmentService. No exceptions.
- Every state change emits audit_events + domain_events via the shared writers.

## Naming
- Models singular (`Reservation`), tables plural snake_case (`reservation_units`).
- ULID keys; `*_minor` for money columns; `*_at` for timestamps (UTC).

## Testing
- Pest. Feature tests for services and flows; unit tests for pure logic.
- Required, never-skipped: double-booking concurrency test; type-oversell test; tenant-isolation test; money-integrity test.
- Tests must actually pass — no skipped/xfail to look green. If it fails, report it.

## Git
- Feature branches off `main`; small, focused commits with conventional prefixes (feat/fix/chore/test/refactor).
- Commit CLAUDE.md and docs; never commit `.env`, secrets, or CLAUDE.local.md.
```

---

## 9. How we operate as a full team (role by role)

You asked for CEO / developer / designer / engineer / SEO / senior-QA and every other role. Here's the honest breakdown of which "hat" is active **now** vs **later** — because a good team knows what to defer as much as what to build.

| Role | Active now (Phase 1)? | What it does |
|---|---|---|
| **CEO / Product** | Continuous | Holds the phase gates; protects the wedge; says "don't build ahead." Maintains DECISIONS.md and success metrics. |
| **Architect** | ✅ Now | Owns the unified data model, conventions, buy-vs-build, the single-write-path rule. |
| **Backend Engineer** | ✅ Now | Builds Phase 1 via the gated Claude Code prompt: migrations, models, services. |
| **DevOps / Security** | ✅ Now | Env & secrets hygiene, .gitignore, PCI-safe design (tokens only), backups; CI/CD from Phase 2. |
| **Senior QA** | ✅ Now | Test suite from day one; the make-or-break integrity tests; enforcement hooks; "tests must really pass." |
| **Designer** | ⏳ Phase 3 | The Claude Design → stub-view integration workflow. Phase 1 builds *unstyled* stub views exposing data contracts, so design drops in cleanly later. |
| **SEO Specialist** | ⏳ Phase 3 | Belongs to the guest-facing website builder. Premature now — but the master plan already bakes the *fields* for it (meta, JSON-LD/schema, sitemaps, hreflang for multi-language, OG images) into that module, so it's ready when we get there. |
| **Revenue / Data** | ⏳ Phase 4 | AI pricing, KPIs, forecasting — after the core earns. |

Practical rhythm: build strictly phase-by-phase through gated Claude Code prompts, log decisions as we lock them, keep the quality bar (Pint + PHPStan + Pest green, integrity tests never skipped), and defer the design/SEO/revenue hats until their phase — while pre-baking the hooks for them in the schema so nothing needs a rewrite.

---

## 10. Master checklist (empty folder → Phase 1 running)

- [ ] Toolbox verified (php 8.2+, composer, node 18+, git, claude 2.1.59+)
- [ ] `composer create-project laravel/laravel .` into the empty folder
- [ ] `vyrobel` database created (utf8mb4) in phpMyAdmin
- [ ] `.env` configured for MySQL + `php artisan key:generate`
- [ ] `git init` + first commit; `.gitignore` includes CLAUDE.local.md
- [ ] Tooling installed (Pest, Larastan, Pint, Playwright); `phpstan.neon` at level 5
- [ ] `docs/` created with master-plan, schema/phase-1, prompts/phase-1, CODING-STANDARDS, DECISIONS
- [ ] `CLAUDE.md` saved to repo root
- [ ] `claude` opened; `/memory` confirms CLAUDE.md loaded
- [ ] Phase 1 prompt pasted; Step 0 audit reviewed and approved
- [ ] Work through Phase 1 gates; integrity tests green before calling Phase 1 done

When Phase 1 is in and the integrity tests pass, tell me and I'll write the Phase 2 master prompt (Channex + Stripe + booking engine + staff PWA) the same way.

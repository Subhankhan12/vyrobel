# Vyrobel — Decision Log

A running record of locked decisions — what was decided and why — so the project stays honest across gated phases. Append new decisions with a date as we lock them.

## Locked as of 2 July 2026

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

## New decisions

<!-- Add entries below, most recent first, each with a date. Example:
### YYYY-MM-DD
- **Decision:** … — **Why:** … -->

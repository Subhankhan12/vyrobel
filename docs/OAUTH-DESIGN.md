# Vyrobel — OAuth2 Authorization Server (scoped, owner-authorized, tenant-isolated) · DESIGN

**Status:** PHASE 0 — investigation + design (read-only). Presented for approval before any build.
**Date:** 2026-07-29
**⚠️ SECURITY feature.** A third-party token grants access to owner/tenant data. Non-negotiables: **scoped** (least-privilege, read-first) · **owner-consented** (a real consent screen showing exactly the requested scopes) · **revocable** (immediate) · **audited** (#20) · **tenant + owner double-gated** (a token sees ONLY that owner's data, ONLY the granted scope — proven both ways). A token can NEVER over-grant (exceed the owner's own capabilities) or cross tenants.

---

## 1. What this makes real

The owner Security page lists **"Connected apps"** as an honest **"Coming soon"** pill (`owner-security.blade.php:273`) — there is **no OAuth backend anywhere** today (no Passport, no `oauth_*` tables, no token concept; confirmed by three sweeps). This build introduces a real OAuth2 authorization server (Laravel Passport) so a third-party app can request **scoped** access on an owner's behalf, the owner **authorizes/revokes**, and "Connected apps" becomes a real list + revoke.

**De-risked:** `composer require laravel/passport --dry-run` resolves **v13.7.5** cleanly on Laravel 13 (league/oauth2-server 9.4.1). Passport is installable + additive.

---

## 2. What already exists (REUSE — do not rebuild)

| Piece | Where | Role in the design |
|---|---|---|
| Session guards `web` / `guest` / `admin` | `config/auth.php:42` | Untouched; we ADD an `api` (passport) guard alongside |
| `User` (ULID PK, spatie `HasRoles`, memberships) | `app/Models/User.php` | The token subject; add `Passport\HasApiTokens` |
| `TenantContext` (`set`/`forOrganization`/`runAsSuperAdmin`) + `OrganizationScope` (fail-closed) | `app/Support/Tenancy/*` | The API middleware re-establishes tenant from the token's owner→org |
| `OwnerContext` (`activate`/`ownerIds`/`can`/`role`) + `OwnerScope` (active+empty → `1=0`) | `app/Support/Owner/*` | The API middleware activates the owner scope for the token's owner |
| `SetOwnerScopeForPortal` (owner resolution: primary `Owner.user_id` + `OwnerUser` sub-users) | `app/Http/Middleware/SetOwnerScopeForPortal.php` | The exact template for the token→owner resolution |
| `OwnerTeamRole` caps (`CAP_FINANCIALS`/`CAP_CALENDAR`/…) + `EnsureOwnerCapability` | `app/Support/Owner/OwnerTeamRole.php` | The token's scope is intersected with the owner-role caps (no over-grant) |
| The owner-scoped READ shapes: `OwnerBookingsReader` (name-only #28), `OwnerPortalService::statements()`/`units()` | `app/Support/Owner/OwnerBookingsReader.php`, `app/Services/OwnerPortalService.php` | The API returns these SAME safe fields (anti-drift, no PII) |
| `AuditEventRecorder::record()` (user-as-subject) | `app/Support/Events/AuditEventRecorder.php` | Every authorize/revoke audited (#20), no morph-map change needed |
| The Security page revoke pattern (self-resolve + `current_password` re-auth + audit + `throttle`) | `OwnerSecurityController::revokeTrustedDevice` | The exact template for "revoke a connected app" |
| `bootstrap/app.php` `withRouting` + `shouldRenderJsonWhen(api/*)` | `bootstrap/app.php` | Add `api: routes/api.php`; JSON errors already primed |

**The engine orchestrates existing scoping — it never invents a new isolation mechanism.** Owner isolation is the certified `OwnerScope`; tenant isolation is the certified `OrganizationScope`; capability limits are the certified `OwnerTeamRole`. OAuth adds a *third* gate (the token scope) ON TOP.

---

## 3. The scope model (least-privilege, READ-FIRST)

Three read scopes, each mapping to real owner-scoped data AND an owner-role capability (so a token can never exceed the owner's own access):

| Scope | Grants read of | Maps to owner cap | Roles that can grant it | Safe fields returned |
|---|---|---|---|---|
| `read:bookings` | the owner's bookings | `CAP_CALENDAR` (`calendar.view`) | co-owner, view-only | reference, guest **NAME only** (#28), unit label(s), stay dates/nights, source, status, owner-net |
| `read:statements` | the owner's statements | `CAP_FINANCIALS` (`financials.view`) | **all** owner roles | id, period, status, currency, `net_payable_minor` (+ breakdown `*_minor`) |
| `read:units` | the owner's units | `CAP_CALENDAR` (`calendar.view`) | co-owner, view-only | id, code, name, unit_kind, max_occupancy, is_active, room-type label |

**⚠️ Excluded by construction (never in any scope):** guest email/phone (bookings are name-only #28, and the shape has no contact field at all); the units' `door_code` / `wifi_ssid` / `wifi_password` / `arrival_notes` (encrypted arrival secrets, #33) — a `read:units` token returns the physical-unit facts only, never an arrival secret.

**Write scopes:** NOT built (no real third-party write use case; a write would need `CAP_ACTIONS`, co-owner-only, and far more care). The scope catalog is designed so a future `write:*` scope slots in with the same cap-intersection gate. **Client-credentials grant:** NOT built (no machine use case — all access is owner-consented via authorization-code).

The scope catalog is declared once via `Passport::tokensCan([...])` in `AppServiceProvider::boot()`; the human descriptions drive the consent screen (so the owner sees exactly what each scope means).

---

## 4. The authorization flow (authorization-code + PKCE)

```
third-party app ──(1) redirect owner to───►  GET /oauth/authorize?client_id&redirect_uri
                                             &response_type=code&scope=read:bookings read:units
                                             &code_challenge&code_challenge_method=S256
                                                    │
                                    Vyrobel: owner must be logged in (web guard) AND owner-linked
                                                    ▼
                                        ┌───────────────────────────────┐
                                        │  BRANDED CONSENT SCREEN        │
                                        │  "<App> wants to access:"      │
                                        │   • Your bookings (read-only)  │  ← EXACTLY the requested
                                        │   • Your units (read-only)     │    scopes, human-described
                                        │  [ Deny ]        [ Authorize ] │
                                        └───────────────────────────────┘
                                          Deny → no token.  Authorize → audited, auth code issued.
                                                    │
   app ──(2) POST /oauth/token (code + PKCE verifier + client)──► access_token (+ refresh_token),
                                                                  carrying ONLY the granted scopes
```

- **PKCE** for public clients (SPAs/mobile — no client secret); confidential clients (server apps) use a hashed secret (`Passport::hashClientSecrets()`). Passport supports both on the same authorize/token endpoints.
- The consent screen is a **published, branded blade** (warm-glass, the owner design language) replacing Passport's default Vue view — it lists the app name + **exactly** the requested scopes (no over-grant surprise), and the owner must actively **Authorize**.
- **Consent-time cap filter:** the authorize screen only offers scopes the logged-in owner's role can back (a `read:bookings` request from an accountant-only owner is shown as unavailable) — belt to the API's suspenders (§6).
- **Audited (#20):** an approved authorization records `owner.connected_app_authorized` (app, scopes); a denial records nothing sensitive. Token lifetimes: short access token + a refresh token (configurable via `Passport::tokensExpireIn` / `refreshTokensExpireIn`).

---

## 5. Client registration (platform-admin-managed — least surface)

Third-party OAuth clients are **registered by a platform admin** (the `admin` realm), NOT open self-serve — open client registration is an abuse vector we deliberately avoid. A client carries: name, redirect URIs, `confidential`|`public` (PKCE), and the allowed scopes. Registration is via a platform-admin action (a controller/command over the Passport client model). **Self-serve developer registration is deferred** (designed, not built). The demo seeds one clearly-labelled **example** client so the flow is demoable end-to-end.

---

## 6. The scoped, tenant-isolated API (⚠️ the DOUBLE-GATE — the security crux)

New `routes/api.php` (registered via `bootstrap/app.php` `withRouting(api:)`, prefix `/api`). Each owner read endpoint runs THREE gates:

```
GET /api/owner/bookings
   │
   ├─ auth:api                    ── Passport authenticates the TOKEN → resolves its User (401 if invalid/revoked)
   ├─ ResolveApiOwnerContext      ── NEW middleware: token's User → owner(s) + org (mirrors SetOwnerScopeForPortal,
   │                                 resolved under runAsSuperAdmin), then TenantContext::set(org) +
   │                                 setPermissionsTeamId(org) + OwnerContext::activate(ownerIds, role, isPrimary).
   │                                 ⛔ 403 if the User is not an owner (empty owner set → fail-closed anyway).
   │                                 ⛔ 403 if the User's owners span >1 org (a token is single-tenant by design).
   ├─ scope:read:bookings         ── Passport CheckScopes: the token MUST carry the scope (403 otherwise)
   └─ EnsureApiScopeCapability    ── NEW: the scope→cap map (read:bookings→calendar.view) must be a cap the
                                     resolved owner ROLE holds (OwnerContext::can) — a token can NEVER exceed
                                     the owner's own access (403 otherwise). ← the "no over-grant" guarantee.
   ▼
   Controller reads through OwnerBookingsReader / OwnerPortalService — the OwnerScope (active for THIS owner) +
   OrganizationScope (active for THIS org) mean the query returns ONLY this owner's rows in this tenant.
   Returns the SAFE fields only (name-only #28, no arrival secrets).
```

**The two isolations, both fail-closed — mutation-proven in Phase 3:**
- **Scope isolation:** a `read:bookings` token hitting `/api/owner/statements` → the statements route requires `scope:read:statements` → **403** (wrong scope). Proven both directions.
- **Tenant/owner isolation:** the token's `OwnerContext` is owner `O` in org `G`; the `OwnerScope`+`OrganizationScope` restrict every read to `O`'s rows in `G`. A token can NEVER see another owner's or another tenant's data — the scopes fail closed (empty owner set → `1=0`; missing tenant → throws). Proven by seeding a second owner/org and asserting the token sees only its own.
- **No over-grant:** the scope→cap gate refuses a token whose scope exceeds the bound owner's role (e.g. an accountant-role token can't carry `read:bookings`). Proven.

---

## 7. Revoke (immediate)

- The owner's Security "Connected apps" section lists the apps **that user** has authorized — grouped from Passport's `oauth_access_tokens` (their `user_id`, not revoked), by client: **app name · scopes granted · authorized date · last used**.
- **Revoke** → a self-only, re-authed (`current_password`) controller POST that revokes the client's tokens for that user (Passport `TokenRepository::revokeAccessToken` + revoke the linked refresh tokens), audited `owner.connected_app_revoked` (user-as-subject, no morph-map change). `throttle:10,1`, matching the trusted-device/passkey pattern.
- **Immediate:** a revoked access token fails `auth:api` (Passport checks the `revoked` flag on every request) → **401**. Mutation-proven: authorize → token works → revoke → the same token is rejected.

---

## 8. Passport-on-Laravel-13 / ULID specifics (Phase 1 install notes)

- **ULID `user_id`:** publish Passport migrations (`vendor:publish --tag=passport-migrations`) and set the `user_id` columns on `oauth_access_tokens` / `oauth_auth_codes` (and personal-access) to `char(26)` (ULID) — the `User` PK is a ULID, not an int. Passport client ids are UUIDs (independent — fine).
- **Encryption keys:** `php artisan passport:keys` generates the OAuth signing keys — an env artifact like `APP_KEY` (gitignored; generated per env / committed test keys for CI). Documented in the go-live/env notes.
- **Registration points** (all additive, in the established seams): `api` guard in `config/auth.php`; `HasApiTokens` on `User`; `Passport::tokensCan(...)` + lifetimes + `hashClientSecrets()` in `AppServiceProvider::boot()` (next to `Auth::provider('guest-eloquent', …)`); `api: routes/api.php` in `bootstrap/app.php`; the `scope`/`scopes` middleware aliases in `withMiddleware`.
- **Security advisories:** the dry-run flagged advisories on 2 transitive packages — Phase 1 runs `composer audit` and updates/verifies before committing (not a design blocker; a diligence step).
- **DISTURB NOTHING:** the `api` guard is only ever invoked explicitly (`auth:api`); the default guard stays `web`; the session realms are untouched. The green suite is the proof.

---

## 9. Surfacing — "Connected apps" (Coming soon → REAL)

Remove `'Connected apps'` from the Security "Coming soon" grid (`owner-security.blade.php:273`) and add a real `vy-card` "Connected apps" section (after Login approval, before Your data), matching the trusted-devices list markup: per app — **name** (bold), **scopes** (small pills) · **authorized {date}** · **last used {when}**, and a two-step **Revoke → password → Confirm** form. Empty state: an honest "No connected apps — third-party apps you authorize will appear here." **Self-only** (each user manages the apps they authorized), NOT capability-gated (consistent with the per-user Security page).

---

## 10. Phase plan (gated; per-gate commit; suite green + PHPStan L5 + Pint throughout)

- **PHASE 1 — Passport + client/scope model.** Install Passport (additive; ULID migrations; keys); `api` guard; `HasApiTokens`; `Passport::tokensCan` (the 3 read scopes); the scope→cap map; platform-admin client registration; a demo example client. **Prove:** suite green (nothing disturbed); the scope catalog is defined; a client registers; a personal-access/auth flow issues a token carrying declared scopes.
- **PHASE 2 — authorization-code + PKCE + consent screen.** The branded consent blade (shows exactly the requested scopes); approve/deny; PKCE for public clients; audit on authorize. **Mutation-prove:** a denied consent issues NO token; an approved consent issues a token with ONLY the requested scopes; PKCE verifier required for a public client.
- **PHASE 3 — scoped, tenant-isolated API + revoke.** `routes/api.php` with the 3 double-gated read endpoints (`auth:api` + `ResolveApiOwnerContext` + `scope:*` + `EnsureApiScopeCapability`); the safe read shapes; the revoke path. **Mutation-prove BOTH isolations fail closed:** wrong-scope → 403; cross-owner/cross-tenant → sees nothing; over-grant scope → 403; **revoke → the token is immediately rejected (401)**.
- **PHASE 4 — Security "Connected apps" real + consolidate.** The real list + scopes + revoke on the Security page; the consent flow reachable. Consolidate `OAUTH-DESIGN.md` (built) + `DECISIONS.md` + `DIVERGENCE-REGISTER.md` (Security connected-apps → real) + `PROJECT-STATE.md` + the invariant list; bump HEAD. Browser-verify (authorize a test app → it appears with its scopes → revoke → its token is rejected). Push all. **STOP** with a report.

---

## 11. Standing guarantees held throughout

SECURITY-grade rigor: Laravel Passport (authorization-code + **PKCE**), **least-privilege READ-first** scopes, **owner-consented** via a real consent screen showing exactly the requested scopes, **revocable** (immediate), **audited** (#20). ⚠️ The API is **double-gated** — the token's SCOPE **and** the owner's TENANT+OWNER scope — and **both isolations are mutation-proven to fail closed** (wrong-scope rejected; cross-tenant/cross-owner rejected); a token can NEVER over-grant (more than the owner's role) or cross tenants; revoke is immediate (mutation-proven). Owner/tenant-scoped (#28); guest data name-only, no arrival secrets. **Additive — DISTURB NOTHING** (the green suite is the proof). Reuse the certified `OwnerScope`/`OrganizationScope`/`OwnerTeamRole` (never a new isolation mechanism). Client registration is platform-admin-managed (least surface); write scopes + client-credentials + self-serve registration are designed-but-deferred (no real use case yet), never faked.

<?php

use App\Http\Controllers\Auth\AcceptInviteController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BookingEngineController;
use App\Http\Controllers\ChannexWebhookController;
use App\Http\Controllers\FrontDeskActionController;
use App\Http\Controllers\HousekeepingActionController;
use App\Http\Controllers\Operator\FolioPdfController;
use App\Http\Controllers\Operator\GroupBookingController;
use App\Http\Controllers\Operator\GuestActionController;
use App\Http\Controllers\Operator\InboxActionController;
use App\Http\Controllers\Operator\InviteOwnerController;
use App\Http\Controllers\Operator\MaintenanceActionController;
use App\Http\Controllers\Operator\MaintenanceSettingsController;
use App\Http\Controllers\Operator\OperatorProfileController;
use App\Http\Controllers\Operator\PayoutRunActionController;
use App\Http\Controllers\Operator\RateActionController;
use App\Http\Controllers\Operator\ReservationCreateController;
use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Http\Controllers\Operator\RoleAssignmentController;
use App\Http\Controllers\Operator\RoomTypePhotoController;
use App\Http\Controllers\Operator\SettingsActionController;
use App\Http\Controllers\Owner\OwnerBlockController;
use App\Http\Controllers\Owner\OwnerBookingSummaryPdfController;
use App\Http\Controllers\Owner\OwnerMessageActionController;
use App\Http\Controllers\Owner\OwnerProfileController;
use App\Http\Controllers\Owner\OwnerRemittancePdfController;
use App\Http\Controllers\Owner\OwnerStatementPdfController;
use App\Http\Controllers\ReservationFolioActionController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Http\Middleware\RequireTwoFactorPending;
use App\Http\Middleware\ResolvePublicProperty;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Livewire\FrontDesk;
use App\Livewire\HousekeepingBoard;
use App\Livewire\InboxList;
use App\Livewire\InboxThread;
use App\Livewire\MaintenanceList;
use App\Livewire\MaintenanceTicketDetail;
use App\Livewire\Operator\GroupBooking;
use App\Livewire\Operator\GuestProfile;
use App\Livewire\Operator\GuestsList;
use App\Livewire\Operator\NewReservation;
use App\Livewire\Operator\OperatorDashboard;
use App\Livewire\Operator\OperatorMaintenanceCosts;
use App\Livewire\Operator\OperatorMaintenanceSettings;
use App\Livewire\Operator\OperatorOwnerDetail;
use App\Livewire\Operator\OperatorOwners;
use App\Livewire\Operator\OperatorProfile;
use App\Livewire\Operator\OperatorRoles;
use App\Livewire\Operator\OperatorSearch;
use App\Livewire\Operator\OperatorSecurity;
use App\Livewire\Operator\OperatorSettings;
use App\Livewire\Operator\PayoutRun;
use App\Livewire\Operator\RatesGrid;
use App\Livewire\Operator\ReservationRefund;
use App\Livewire\Operator\ReservationsList;
use App\Livewire\Owner\OwnerAgreement;
use App\Livewire\Owner\OwnerArchive;
use App\Livewire\Owner\OwnerBalance;
use App\Livewire\Owner\OwnerBlockCalendar;
use App\Livewire\Owner\OwnerBookingDetail;
use App\Livewire\Owner\OwnerBookings;
use App\Livewire\Owner\OwnerDashboard;
use App\Livewire\Owner\OwnerDocuments;
use App\Livewire\Owner\OwnerExpenses;
use App\Livewire\Owner\OwnerMessages;
use App\Livewire\Owner\OwnerMessageThread;
use App\Livewire\Owner\OwnerMyUnits;
use App\Livewire\Owner\OwnerPayouts;
use App\Livewire\Owner\OwnerSettings;
use App\Livewire\Owner\OwnerStatementView;
use App\Livewire\Owner\OwnerUnitDetail;
use App\Livewire\ReservationDetail;
use App\Livewire\ReservationRack;
use App\Livewire\UnitsList;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Shared Vyrobel login (operator + owner) — real, session-based auth (Auth Phase A). The login POST is
 * CSRF-protected (web group; NOT in the CSRF-except list) and rate-limited per (email, IP) in
 * LoginRequest. Logout is a CSRF-protected POST behind `auth`. `/app/*` + `/owner/*` require `auth`
 * (redirect here when unauthenticated); `/book/*` stays public.
 */
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
 * 2FA login CHALLENGE (Auth · Step 2) — reachable ONLY in the "2FA pending" state (correct password given,
 * not yet logged in) via RequireTwoFactorPending. That pending marker is a plain session key, NOT the auth
 * guard, so it grants no authenticated access — /app + /owner reject a pending user by construction. The POST
 * verifies a TOTP code OR a single-use recovery code and is rate-limited in the controller (per pending user
 * + IP); CSRF-protected (web group). Session regenerated on success (fixation protection).
 */
Route::middleware(RequireTwoFactorPending::class)->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.login');
});

/*
 * PUBLIC forgot / reset password (Auth Phase B · finishing sweep). Laravel Password broker — tokened,
 * single-use, expiring tokens. NO enumeration (the request-link endpoint always responds generically;
 * ACTIVE users only), rate-limited against abuse, CSRF-protected (web group). On reset: hashed password,
 * remember-token rotated, session regenerated, signed in + routed by role.
 */
Route::middleware('throttle:6,1')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

/*
 * PUBLIC accept-invite / set-password (Auth Phase B · Step 2). No auth — the invitee has no usable
 * password yet; the sha256-hashed single-use token in the URL is the credential. Rate-limited (per IP)
 * against token guessing; CSRF-protected POST (web group). Invalid/expired/spent → a generic page.
 */
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/invite/{token}', [AcceptInviteController::class, 'show'])->name('invite.accept');
    Route::post('/invite/{token}', [AcceptInviteController::class, 'store']);
});

/*
 * Public, unauthenticated INBOUND Channex webhook (Step 4). Signature-authenticated, CSRF-exempt
 * (see bootstrap/app.php). {channel} is our channel ULID; the controller resolves it under a
 * super-admin context then sets the tenant context — no route-model binding (that would trip the
 * tenant scope before context is set).
 */
Route::post('/webhooks/channex/{channel}', ChannexWebhookController::class)->name('webhooks.channex');

/*
 * Public, unauthenticated INBOUND Stripe webhook (Step 6). Signature-authenticated (Stripe's real
 * scheme), CSRF-exempt. Resolves the payment (hence org) under super-admin then sets context —
 * no route params, the event body carries the payment_intent id.
 */
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

/*
 * Public, unauthenticated BOOKING ENGINE (Step 7) — backend + unstyled contract stub. Tenant/
 * property context is set by ResolvePublicProperty (real context, never a guard bypass) from the
 * {property} slug; every action re-loads it tenant-scoped. Rate-limited (throttle:booking) against
 * scraping/abuse. POST endpoints are CSRF-exempt (JSON API; see bootstrap/app.php). Styled UI is
 * the deferred design pass.
 */
Route::prefix('book/{property}')
    ->middleware([ResolvePublicProperty::class, 'throttle:booking'])
    ->name('booking.')
    ->group(function () {
        Route::get('/', [BookingEngineController::class, 'browse'])->name('browse');
        // Design pass · Booking Screen 2 — public room detail (read-only; property-scoped → cross-property 404).
        Route::get('/rooms/{roomType}', [BookingEngineController::class, 'room'])->name('room');
        // Design pass · Booking Screen 3 — styled checkout. GET review (read-only) + CSRF-protected POST
        // WRITE that books THROUGH AssignmentService + FolioService (never a parallel path); PRG to the
        // styled confirmation. `book/{property}/checkout` is NOT in the CSRF-except list (see bootstrap).
        Route::get('/checkout', [BookingEngineController::class, 'checkout'])->name('checkout');
        Route::post('/checkout', [BookingEngineController::class, 'reserve'])->name('reserve');
        Route::get('/reservation/{quote}', [BookingEngineController::class, 'reservation'])->name('reservation');
        // JSON API (machine clients) — the real final backend contract, CSRF-exempt by path (bootstrap).
        Route::get('/search', [BookingEngineController::class, 'search'])->name('search');
        Route::post('/quote', [BookingEngineController::class, 'quote'])->name('quote');
        Route::post('/book', [BookingEngineController::class, 'book'])->name('book');
        Route::get('/confirmation/{quote}', [BookingEngineController::class, 'confirmation'])->name('confirmation');
    });

/*
 * THE OPERATOR APP (/app) — `auth` + the BACK-OFFICE GATE.
 *
 * `auth` gives us a user and lets SetTenantForWebRequest resolve the active organization (tenant
 * isolation intact). {@see EnsureBackOfficeAccess} then enforces the operator/owner boundary: only a
 * user holding a `config('authorization.back_office_roles')` role IN THE ACTIVE ORG may enter. This
 * closes the critical hole where `auth` alone let an owner — who necessarily holds an active Membership,
 * because that is the only way their org resolves — walk into the whole back office, and (since
 * OwnerScope is inactive off-portal) read EVERY owner's financials at /app/owners/{owner}.
 * See docs/DECISIONS.md 2026-07-16. The gate is on the GROUP, so it covers every route below —
 * including any added later.
 */
Route::middleware(['auth', EnsureBackOfficeAccess::class])->prefix('app')->name('app.')->group(function () {
    // Design pass · Operator Screen 5 — operator dashboard (new tenant-scoped overview page).
    Route::get('/dashboard', OperatorDashboard::class)->name('dashboard');

    // GLOBAL SEARCH (Phase 1 · Sub-gate 2) — cross-entity search (reservations/guests/units/owners), the query
    // on a GET (scope-safe reads). Gated on `reservations.view` (the operations read baseline); per-entity
    // results follow the viewer's abilities inside the component. Tenant-scoped; NOT availability search.
    Route::get('/search', OperatorSearch::class)->middleware('can:reservations.view')->name('search');
    // Auth Phase B · Step 4 — operator Owners screen + the invite-owner write (POST → controller → PRG).
    Route::get('/owners', OperatorOwners::class)->name('owners');
    Route::post('/owners/invite', [InviteOwnerController::class, 'store'])
        ->middleware(['throttle:10,1', 'can:users.manage'])->name('owners.invite');
    // Wave 1 · Sub-gate 3 — the operator's TENANT-scoped lens on ONE owner (units/agreement/
    // statements/payouts/balance). NOT owner-scoped: OwnerScope is inactive off-portal, so this
    // deliberately reads the whole owner relationship the operator manages, within their own org
    // (Owner is BelongsToOrganization → a cross-org id 404s).
    Route::get('/owners/{owner}', OperatorOwnerDetail::class)->name('owners.show');

    // ROLES & ACCESS (Phase 1 · Sub-gate 3) — VIEW the fixed catalog (roles + read-only permissions + members)
    // and ASSIGN a role to a member. Gated on `users.manage` (only an admin manages roles — the same gate that
    // closed the owner-invite escalation, #30). The role→permission catalog is CODE-DEFINED + read-only; only
    // the user↔role assignment is written, and RoleAssignmentService bounds it by the privilege-subset rule so
    // no one can grant a role with more access than their own.
    Route::get('/roles', OperatorRoles::class)->middleware('can:users.manage')->name('roles');
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])->middleware('can:users.manage')->name('roles.assign');
    Route::post('/roles/revoke', [RoleAssignmentController::class, 'revoke'])->middleware('can:users.manage')->name('roles.revoke');
    // Wave 1 · Sub-gate 4 — the operator PAYOUT RUN (tenant-scoped): finalized statements ready to pay
    // → REAL payouts through the certified OwnerPayoutService (ManualPayoutRail is the working rail;
    // the Stripe-Connect rail stays a config-gated skeleton that THROWS — never faked). The writes use
    // the operator-mutation pattern: POST → controller → service → PRG (CSRF + tenant re-scoped).
    Route::get('/payouts/run', PayoutRun::class)->name('payouts.run');
    Route::post('/payouts/create', [PayoutRunActionController::class, 'create'])
        ->middleware('can:payouts.manage')->name('payouts.create');
    Route::post('/payouts/pay', [PayoutRunActionController::class, 'pay'])
        ->middleware('can:payouts.manage')->name('payouts.pay');
    Route::get('/rack', ReservationRack::class)->name('rack');
    Route::get('/units', UnitsList::class)->name('units');

    // Rates & availability (Wave 4) — the READ grid + the guarded rate/restriction write, which is the
    // FIRST production caller of RateAndRestrictionService (setRate/setRestriction). PER-ROOM-TYPE (the
    // design's per-unit grid can't be backed by the per-rate-plan tables — docs/WAVE4-INVENTORY STRUCT-1),
    // tenant-scoped, integer money parsed server-side. GET gated on `rates.view` (Housekeeping/Maintenance/
    // Marketing/Guest Support hold none → the nav shows them "Soon", never a 403); POST gated on
    // `rates.manage` — deliberately NOT Front Desk or Reservation Agent (invariant #30, separation of duties;
    // RatesGrid gates the editor off the SAME permission so the UI never offers a write the route refuses).
    Route::get('/rates', RatesGrid::class)->middleware('can:rates.view')->name('rates');
    Route::post('/rates', [RateActionController::class, 'apply'])->middleware('can:rates.manage')->name('rates.update');

    // Settings (Wave 4) — ONE coherent settings surface (Operator + Property settings de-conflicted). Only
    // the BACKED columns are written (properties / property_policies / organizations); unbacked design fields
    // are stubbed "Soon", never a fake save. The page + property/policy writers are gated `properties.manage`
    // (Property Owner / Group Admin); the ORG writer is additionally gated `settings.manage` (Property Owner)
    // — per-section separation of duties, NOT Front Desk. Both permissions already exist in the catalog.
    Route::get('/settings', OperatorSettings::class)->middleware('can:properties.manage')->name('settings');
    Route::post('/settings/property', [SettingsActionController::class, 'updateProperty'])->middleware('can:properties.manage')->name('settings.property');
    Route::post('/settings/policies', [SettingsActionController::class, 'updatePolicies'])->middleware('can:properties.manage')->name('settings.policies');
    Route::post('/settings/organization', [SettingsActionController::class, 'updateOrganization'])->middleware('can:settings.manage')->name('settings.organization');

    // Change password (Auth) — the operator Security page + its guarded write. Reachable by EVERY back-office
    // user (auth + #26, NO fine-grained gate — unlike /app/settings, which is manager-gated) so anyone can
    // change their OWN password. The write resolves the user from auth() (never a request id), VERIFIES the
    // current password, and reuses User::changePasswordTo (the shared reset hash + remember-token-rotate
    // path). Rate-limited like the rest of the auth chapter.
    Route::get('/security', OperatorSecurity::class)->name('security');
    Route::post('/security/password', [ChangePasswordController::class, 'operator'])->middleware('throttle:6,1')->name('security.password');

    // Operator PROFILE (Phase 1 · Sub-gate 5) — the logged-in operator's OWN name + work email. Like /security,
    // behind auth + #26 with NO fine-grained gate (every back-office user has a profile). The write is SELF-ONLY:
    // the controller resolves $request->user() (never a request id), so there is no id to tamper with to reach
    // another account. Only the genuinely-backed fields persist; unbacked design fields are omitted, not faked.
    Route::get('/profile', OperatorProfile::class)->name('profile');
    Route::post('/profile', [OperatorProfileController::class, 'update'])->name('profile.update');

    // Two-factor ENROLLMENT (self-service; the login challenge is separate). Real TOTP (google2fa) — enable →
    // confirm-code (anti-lockout) → one-time recovery codes; regenerate; disable (re-auth). Self-only
    // (auth()-resolved). Confirm/disable rate-limited. Behind #26 like the rest of /app.
    Route::post('/security/2fa/enable', [TwoFactorController::class, 'enable'])->name('security.2fa.enable');
    Route::post('/security/2fa/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:6,1')->name('security.2fa.confirm');
    Route::post('/security/2fa/recovery-codes', [TwoFactorController::class, 'regenerate'])->name('security.2fa.regenerate');
    Route::post('/security/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:6,1')->name('security.2fa.disable');
    // Unified Inbox — UNSTYLED operator contract stubs (thread list + thread view; styled screen is a
    // later design pass). Tenant-scoped; the view resolves {thread} via a scoped findOrFail → cross-org 404.
    Route::get('/inbox', InboxList::class)->name('inbox');
    Route::get('/inbox/{thread}', InboxThread::class)->name('inbox.show');
    // Inbox writes — REAL reply + lifecycle via POST → controller → InboxService → redirect (PRG).
    // Cross-org {thread} 404s; a deferred channel leaves an honest failed record + clean flash (never 500).
    Route::post('/inbox/{thread}/reply', [InboxActionController::class, 'reply'])->name('inbox.reply');
    Route::post('/inbox/{thread}/assign', [InboxActionController::class, 'assign'])->name('inbox.assign');
    Route::post('/inbox/{thread}/close', [InboxActionController::class, 'close'])->name('inbox.close');
    Route::post('/inbox/{thread}/reopen', [InboxActionController::class, 'reopen'])->name('inbox.reopen');
    Route::post('/inbox/{thread}/snooze', [InboxActionController::class, 'snooze'])->name('inbox.snooze');
    Route::post('/inbox/{thread}/link-reservation', [InboxActionController::class, 'linkReservation'])->name('inbox.link-reservation');
    // Maintenance module — styled work-order board + detail (design pass · Maintenance UI). Tenant-scoped;
    // the detail resolves {ticket} via a scoped findOrFail in render() → cross-org 404.
    Route::get('/maintenance', MaintenanceList::class)->name('maintenance');
    // Maintenance COSTS (analytics) + SETTINGS (Phase 1 · Sub-gate 4) — declared BEFORE `{ticket}` so "costs"/
    // "settings" are not captured as a ticket id. Gated on `maintenance.manage`. Costs is read-only real
    // aggregates; Settings persists typed columns (SLA / category-assignee / toggles) — never a toast-only save.
    Route::get('/maintenance/costs', OperatorMaintenanceCosts::class)->middleware('can:maintenance.manage')->name('maintenance.costs');
    Route::get('/maintenance/settings', OperatorMaintenanceSettings::class)->middleware('can:maintenance.manage')->name('maintenance.settings');
    Route::post('/maintenance/settings', [MaintenanceSettingsController::class, 'update'])->middleware('can:maintenance.manage')->name('maintenance.settings.update');
    Route::get('/maintenance/{ticket}', MaintenanceTicketDetail::class)->name('maintenance.show');
    // Maintenance writes — REAL open + assign via POST → controller → MaintenanceService → redirect (PRG).
    // A foreign unit_id / cross-org {ticket} 404s; a guarded illegal transition flashes cleanly (never 500).
    Route::post('/maintenance', [MaintenanceActionController::class, 'store'])->name('maintenance.store');
    Route::post('/maintenance/{ticket}/assign', [MaintenanceActionController::class, 'assign'])->name('maintenance.assign');
    // Lifecycle writes from the styled ticket detail — each offered only when the real state allows it;
    // the service is still the authority (illegal transition → clean flash, never 500). Cross-org → 404.
    Route::post('/maintenance/{ticket}/start', [MaintenanceActionController::class, 'start'])->name('maintenance.start');
    Route::post('/maintenance/{ticket}/resolve', [MaintenanceActionController::class, 'resolve'])->name('maintenance.resolve');
    Route::post('/maintenance/{ticket}/cancel', [MaintenanceActionController::class, 'cancel'])->name('maintenance.cancel');
    // Demo-readiness · Part A — operator room-type PHOTO management (secure upload; the operator-mutation
    // pattern POST→controller→PRG). {roomType}/{photo} are string ids resolved tenant-scoped → cross-org 404.
    Route::post('/room-types/{roomType}/photos', [RoomTypePhotoController::class, 'store'])->name('room-types.photos.store');
    Route::post('/room-types/{roomType}/photos/{photo}/cover', [RoomTypePhotoController::class, 'setCover'])->name('room-types.photos.cover');
    Route::post('/room-types/{roomType}/photos/{photo}/move', [RoomTypePhotoController::class, 'move'])->name('room-types.photos.move');
    Route::delete('/room-types/{roomType}/photos/{photo}', [RoomTypePhotoController::class, 'destroy'])->name('room-types.photos.destroy');
    // Wave 2 · Sub-gate 1 — the operator RESERVATIONS LIST (tenant-scoped, read-only). Declared BEFORE
    // the {reservation} detail so the literal path wins. Stage filters come from the shared
    // App\Support\Reservations\ReservationStage — the same resolver the badge uses, so a chip can never
    // disagree with what it filters on.
    Route::get('/reservations', ReservationsList::class)->name('reservations');

    // New reservation (Wave 4) — the READ screen + the guarded booking write. MUST precede the
    // `{reservation}` show route so "create" is not captured as an id. The write is the certified
    // AssignmentService::createBooking (single path, no double-booking); gated on `reservations.create`
    // (Front Desk / Reservation Agent / Hotel Manager / Group Admin / Property Owner). An optional MANUAL
    // payment inside the controller additionally checks `payments.record`. Never a raw PAN (GUARD 3).
    Route::get('/reservations/create', NewReservation::class)->middleware('can:reservations.create')->name('reservations.create');
    Route::post('/reservations/create', [ReservationCreateController::class, 'store'])->middleware('can:reservations.create')->name('reservations.store');

    // GROUP BOOKING (Phase 1 · Sub-gate 1) — a block of rooms created ATOMICALLY through the single write path
    // (AssignmentService::createGroupBooking — all-or-nothing, no phantom events). Same `reservations.create`
    // gate as a single booking; the dates ride the GET (scope-safe availability), the block rides the POST.
    Route::get('/group-booking', GroupBooking::class)->middleware('can:reservations.create')->name('group-booking');
    Route::post('/group-booking', [GroupBookingController::class, 'store'])->middleware('can:reservations.create')->name('group-booking.store');

    Route::get('/reservations/{reservation}', ReservationDetail::class)->name('reservations.show');
    // Demo-readiness · Part B — operator folio PDF. TENANT-scoped (string id → findOrFail → cross-org 404).
    // Real append-only ledger; reconciles to the cent; streams application/pdf.
    Route::get('/reservations/{reservation}/folio.pdf', [FolioPdfController::class, 'download'])->name('reservations.folio-pdf');
    // Wave 2 · Sub-gate 3 — the dedicated REFUND screen. Read-only; the write reuses the existing
    // `reservations.refund` POST below (now amount-aware). The screen exists to RESPECT the #27 cumulative
    // guard at the UI: it shows the real remaining-refundable per payment, caps the input at it, and offers
    // nothing once remaining = 0 — so the button cannot invite a rejection. The service stays the authority.
    // Gated on the same permission as the POST it exists to perform (2026-07-17). It is a GET, but its ONLY
    // purpose is refunding: leaving it open would let an unpermitted role open a refund form that 403s on
    // submit — precisely the "UI must not invite a rejection" failure #27 warns about, just one click later.
    Route::get('/reservations/{reservation}/refund', ReservationRefund::class)
        ->middleware('can:payments.refund')->name('reservations.refund-screen');
    // Wave 2 · Sub-gate 4 — the operator GUESTS LIST (the CRM). Tenant-scoped, read-only.
    // ⚠️ This is the PII-visible surface (locked decision 4): guest email/phone are decrypted for display
    // HERE and nowhere else — the front desk, the reservations list and the inbox stay name-only.
    // "Total billed" comes from the shared App\Support\Guest\GuestSpend and the status badge from the shared
    // App\Support\Reservations\ReservationStage, so this page and the guest profile cannot disagree.
    Route::get('/guests', GuestsList::class)->name('guests');
    // Wave 2 · Sub-gate 5 — the guest PROFILE + its REAL writes. The design's "Save changes" fired a toast
    // and persisted nothing; these POSTs go through GuestService (the single path that records the audit +
    // domain events). {guest} is a plain string resolved tenant-scoped in the component/controller (never
    // RMB) → cross-org 404. Contact fields are encrypted casts — the cast does the encrypting.
    Route::get('/guests/{guest}', GuestProfile::class)->name('guests.show');
    Route::post('/guests/{guest}', [GuestActionController::class, 'updateProfile'])->name('guests.update');
    Route::post('/guests/{guest}/notes', [GuestActionController::class, 'addNote'])->name('guests.notes.store');
    Route::post('/guests/{guest}/tags', [GuestActionController::class, 'addTag'])->name('guests.tags.store');
    Route::delete('/guests/{guest}/tags', [GuestActionController::class, 'removeTag'])->name('guests.tags.destroy');
    // ⚠️ GDPR ERASURE (2026-07-17) — the trigger `Guest::anonymize()` never had. It was called from NOWHERE:
    // the erase was correct and tested, and no operator could run it, so a data-subject request could not
    // actually be served. Gated on `can:guests.erase` (Property Owner / Group Admin only — invariant #30):
    // running the house does not include erasing a person. IRREVERSIBLE, so the controller verifies a TYPED
    // confirmation server-side — a client dialog is bypassable by anyone who can POST.
    Route::post('/guests/{guest}/anonymize', [GuestActionController::class, 'anonymize'])
        ->middleware('can:guests.erase')->name('guests.anonymize');
    // Design pass · Operator Screen 4 — REAL append-only folio actions via POST → controller →
    // FolioService → redirect (PRG). {reservation} is a string param resolved tenant-scoped in the
    // controller (never RMB) → cross-org 404. A refund is a NEW offsetting entry (never an edit).
    // Wave 2 · Sub-gate 2 — the LIFECYCLE transitions behind the terminal-state panels (cancel / mark
    // no-show). POST → controller → AssignmentService (the single guarded inventory write path, which
    // frees `sold`, writes reservation_status_history and emits the domain event) → PRG.
    //
    // ⚠️ THE STATUS GUARD LIVES IN THE CONTROLLER, and that is NOT the usual pattern — read before moving
    // it. `AssignmentService::terminate()` has NO status guard: re-cancelling an already-cancelled
    // reservation does not throw, it silently OVERWRITES `cancelled_at`/`cancellation_reason` (destroying
    // the provenance the terminal panel renders) and appends a junk `cancelled → cancelled` row to the
    // append-only audit trail. Proven empirically — see docs/DECISIONS.md 2026-07-16. So
    // ReservationLifecycleController::CANCELLABLE / ::NO_SHOWABLE are load-bearing, not cosmetic, and the
    // component gates its buttons off those same constants so the UI cannot offer what the write refuses.
    // The proper fix is a guard inside terminate() itself (a service change — recorded as outstanding).
    // The controller's `catch (RuntimeException)` is still real: releaseLine() → lockAvailability() can
    // throw inside the transaction, and the rollback is total.
    Route::post('/reservations/{reservation}/cancel', [ReservationLifecycleController::class, 'cancel'])
        ->middleware('can:reservations.cancel')->name('reservations.cancel');
    Route::post('/reservations/{reservation}/no-show', [ReservationLifecycleController::class, 'markNoShow'])
        ->middleware('can:reservations.cancel')->name('reservations.no-show');
    Route::post('/reservations/{reservation}/payments', [ReservationFolioActionController::class, 'recordPayment'])
        ->middleware('can:payments.record')->name('reservations.payment');
    Route::post('/reservations/{reservation}/refunds', [ReservationFolioActionController::class, 'refund'])
        ->middleware('can:payments.refund')->name('reservations.refund');
    Route::get('/housekeeping', HousekeepingBoard::class)->name('housekeeping');
    // Design pass · Operator Screen 3 — REAL housekeeping status advance via POST → action → redirect
    // (PRG). {task} is a string param resolved tenant-scoped in the controller (never RMB) → cross-org 404.
    Route::post('/housekeeping/{task}/status', [HousekeepingActionController::class, 'updateStatus'])->name('housekeeping.status');
    // Phase 2 Step 8 — front-desk staff surface (arrivals/departures/in-house + action contracts).
    Route::get('/front-desk', FrontDesk::class)->name('front-desk');
    // Design pass · Operator Screen 2 — REAL check-in/out via POST → action → redirect (PRG). The
    // {reservation} is a string param resolved tenant-scoped in the controller (never RMB), so the
    // mutation re-runs the tenant middleware and cross-org ids 404.
    Route::post('/front-desk/{reservation}/check-in', [FrontDeskActionController::class, 'checkIn'])->name('front-desk.check-in');
    Route::post('/front-desk/{reservation}/check-out', [FrontDeskActionController::class, 'checkOut'])->name('front-desk.check-out');
});

/*
 * Phase 3 · Module 1 Step 6 — OWNER PORTAL (unstyled contract stubs). `auth` + SetTenantForWebRequest
 * (web group) set the user + org; SetOwnerScopeForPortal then activates the owner scope (row-level,
 * within the tenant) so an owner sees ONLY their own units/statements/payouts and a non-linked user
 * fails closed. Typed models are resolved owner+tenant-scoped in render() (never RMB props). Styled
 * owner PWA is the design pass.
 */
Route::middleware(['auth', SetOwnerScopeForPortal::class])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', OwnerDashboard::class)->name('dashboard');
    // WAVE 3 — the OWNER INBOX (owner ↔ the operator's staff). OWNER-scoped by OwnerScope: MessageThread
    // gained `owner_id` + BelongsToOwner, so a cross-owner thread AND every operator↔guest thread
    // (owner_id NULL) 404 here — the two-inbox boundary falls out of the scope, not out of a predicate.
    // The reply is a REAL message via InboxService::postOwnerMessage() (inbound = into the org), never a
    // fake send. {thread} is a plain string resolved in the component/controller (never RMB).
    Route::get('/messages', OwnerMessages::class)->name('messages');
    Route::get('/messages/{thread}', OwnerMessageThread::class)->name('messages.show');
    Route::post('/messages', [OwnerMessageActionController::class, 'start'])->name('messages.start');
    Route::post('/messages/{thread}', [OwnerMessageActionController::class, 'reply'])->name('messages.reply');
    Route::get('/statements/{statement}', OwnerStatementView::class)->name('statements.show');
    // Demo-readiness · Part B — branded owner-statement PDF. OWNER-scoped (string id → findOrFail under
    // OwnerScope → cross-owner 404, fail-closed for non-linked). Real owner_ledger figures; reconciles to
    // the cent; no invented lines. Streams application/pdf.
    Route::get('/statements/{statement}/pdf', [OwnerStatementPdfController::class, 'download'])->name('statements.pdf');
    // Wave 1 · Sub-gate 2 — two more owner PDFs on the SAME owner-scoped pattern (string id →
    // findOrFail under OwnerScope → cross-owner 404; fail-closed for a non-linked user). Both reuse
    // PdfService + the shared MoneyFormatter, so they reconcile to the screens to the cent.
    Route::get('/statements/{statement}/bookings', [OwnerBookingSummaryPdfController::class, 'download'])->name('statements.bookings');
    Route::get('/payouts/{payout}/remittance', [OwnerRemittancePdfController::class, 'download'])->name('payouts.remittance');
    // Design pass · Screen 3 — owner payouts (owner-scoped; new page for the certified Payout backend).
    Route::get('/payouts', OwnerPayouts::class)->name('payouts');
    // Design pass · Screen 4 — owner units + owner-scoped occupancy calendar (new owner-scoped page).
    Route::get('/units', OwnerMyUnits::class)->name('units');
    // Wave 1 · Sub-gate 3 — ONE of the owner's units. Owner-scoped: a non-owned/cross-owner unit id
    // 404s under OwnerScope. Occupancy is THIS-unit/THIS-owner (never room-type-wide).
    Route::get('/units/{unit}', OwnerUnitDetail::class)->name('units.show');
    // Phase 2 · Sub-gate 1 — the owner's bookings across their OWN units + one booking's detail. Owner-scoped
    // THROUGH the units: reservations carry no owner_id, so the set is derived from the owner-scoped units and
    // {booking} is REJECTED (404) unless it touches one this owner owns (never trusts the request id). Read-only;
    // guest NAME only (no contact — invariant #28). Filters ride GET so the owner middleware re-runs.
    Route::get('/bookings', OwnerBookings::class)->name('bookings');
    Route::get('/bookings/{booking}', OwnerBookingDetail::class)->name('bookings.show');
    // Phase 2 · Sub-gate 2 — the owner Block Calendar. Reads are owner-scoped through the unit; the WRITES go
    // through OwnerBlockService → the certified AssignmentService (single inventory write path). The unit is
    // resolved from the owner's OWN units (never the request id) — a foreign unit id is 403 — and blockInventory
    // enforces non-overlap (a booked/blocked date is rejected by the real assertUnitFree rule).
    Route::get('/calendar', OwnerBlockCalendar::class)->name('calendar');
    Route::post('/calendar/block', [OwnerBlockController::class, 'store'])->name('calendar.block');
    Route::delete('/calendar/blocks/{block}', [OwnerBlockController::class, 'destroy'])->name('calendar.release');
    // Wave 1 · Sub-gate 1 — the owner-accounting long-tail (all read-only, owner-scoped, over the
    // CERTIFIED owner-accounting backend; presentation only, no service/schema change).
    Route::get('/balance', OwnerBalance::class)->name('balance');
    Route::get('/expenses', OwnerExpenses::class)->name('expenses');
    Route::get('/agreement', OwnerAgreement::class)->name('agreement');
    Route::get('/archive', OwnerArchive::class)->name('archive');
    // Wave 1 · Sub-gate 4 — the owner's document library: an owner-scoped aggregation over the REAL
    // generatable PDFs (statement · remittance · booking summary). No document is invented.
    Route::get('/documents', OwnerDocuments::class)->name('documents');

    // Wave 4 — owner SELF-PROFILE. The owner edits their OWN contact details (name + encrypted
    // email/phone). ⚠️ Owner is BelongsToOrganization, NOT BelongsToOwner, so OwnerScope does not scope it:
    // the write resolves the owner by an EXPLICIT `where('user_id', auth id)` (no owner id in the request),
    // so an owner can only ever edit their own row. The payout account/IBAN, notification prefs, team and 2FA
    // are net-new subsystems, stubbed "Soon".
    Route::get('/settings', OwnerSettings::class)->name('settings');
    Route::post('/settings', [OwnerProfileController::class, 'update'])->name('settings.update');
    // Change password (Auth) — owner-side write on the owner Settings page. Owner resolved from auth() (never
    // a request id), current password verified, shared reset hash+rotate path. Rate-limited like the rest of auth.
    Route::post('/settings/password', [ChangePasswordController::class, 'owner'])->middleware('throttle:6,1')->name('settings.password');
    // Two-factor ENROLLMENT (owner side) — same shared TwoFactorController, self-only, back() to /owner/settings.
    Route::post('/settings/2fa/enable', [TwoFactorController::class, 'enable'])->name('settings.2fa.enable');
    Route::post('/settings/2fa/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:6,1')->name('settings.2fa.confirm');
    Route::post('/settings/2fa/recovery-codes', [TwoFactorController::class, 'regenerate'])->name('settings.2fa.regenerate');
    Route::post('/settings/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:6,1')->name('settings.2fa.disable');
});

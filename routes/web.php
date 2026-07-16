<?php

use App\Http\Controllers\Auth\AcceptInviteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BookingEngineController;
use App\Http\Controllers\ChannexWebhookController;
use App\Http\Controllers\FrontDeskActionController;
use App\Http\Controllers\HousekeepingActionController;
use App\Http\Controllers\Operator\FolioPdfController;
use App\Http\Controllers\Operator\InboxActionController;
use App\Http\Controllers\Operator\InviteOwnerController;
use App\Http\Controllers\Operator\MaintenanceActionController;
use App\Http\Controllers\Operator\RoomTypePhotoController;
use App\Http\Controllers\Owner\OwnerStatementPdfController;
use App\Http\Controllers\ReservationFolioActionController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\ResolvePublicProperty;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Livewire\FrontDesk;
use App\Livewire\HousekeepingBoard;
use App\Livewire\InboxList;
use App\Livewire\InboxThread;
use App\Livewire\MaintenanceList;
use App\Livewire\MaintenanceTicketDetail;
use App\Livewire\Operator\OperatorDashboard;
use App\Livewire\Operator\OperatorOwners;
use App\Livewire\Owner\OwnerAgreement;
use App\Livewire\Owner\OwnerArchive;
use App\Livewire\Owner\OwnerBalance;
use App\Livewire\Owner\OwnerDashboard;
use App\Livewire\Owner\OwnerExpenses;
use App\Livewire\Owner\OwnerMyUnits;
use App\Livewire\Owner\OwnerPayouts;
use App\Livewire\Owner\OwnerStatementView;
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
 * Phase 1 CONTRACT-STUB pages (unstyled). Read-only Livewire components that expose the data
 * shapes for the later design pass. Behind `auth` so the SetTenantForWebRequest middleware can
 * resolve the active organization from the authenticated user (tenant isolation intact).
 */
Route::middleware('auth')->prefix('app')->name('app.')->group(function () {
    // Design pass · Operator Screen 5 — operator dashboard (new tenant-scoped overview page).
    Route::get('/dashboard', OperatorDashboard::class)->name('dashboard');
    // Auth Phase B · Step 4 — operator Owners screen + the invite-owner write (POST → controller → PRG).
    Route::get('/owners', OperatorOwners::class)->name('owners');
    Route::post('/owners/invite', [InviteOwnerController::class, 'store'])->middleware('throttle:10,1')->name('owners.invite');
    Route::get('/rack', ReservationRack::class)->name('rack');
    Route::get('/units', UnitsList::class)->name('units');
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
    Route::get('/reservations/{reservation}', ReservationDetail::class)->name('reservations.show');
    // Demo-readiness · Part B — operator folio PDF. TENANT-scoped (string id → findOrFail → cross-org 404).
    // Real append-only ledger; reconciles to the cent; streams application/pdf.
    Route::get('/reservations/{reservation}/folio.pdf', [FolioPdfController::class, 'download'])->name('reservations.folio-pdf');
    // Design pass · Operator Screen 4 — REAL append-only folio actions via POST → controller →
    // FolioService → redirect (PRG). {reservation} is a string param resolved tenant-scoped in the
    // controller (never RMB) → cross-org 404. A refund is a NEW offsetting entry (never an edit).
    Route::post('/reservations/{reservation}/payments', [ReservationFolioActionController::class, 'recordPayment'])->name('reservations.payment');
    Route::post('/reservations/{reservation}/refunds', [ReservationFolioActionController::class, 'refund'])->name('reservations.refund');
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
    Route::get('/statements/{statement}', OwnerStatementView::class)->name('statements.show');
    // Demo-readiness · Part B — branded owner-statement PDF. OWNER-scoped (string id → findOrFail under
    // OwnerScope → cross-owner 404, fail-closed for non-linked). Real owner_ledger figures; reconciles to
    // the cent; no invented lines. Streams application/pdf.
    Route::get('/statements/{statement}/pdf', [OwnerStatementPdfController::class, 'download'])->name('statements.pdf');
    // Design pass · Screen 3 — owner payouts (owner-scoped; new page for the certified Payout backend).
    Route::get('/payouts', OwnerPayouts::class)->name('payouts');
    // Design pass · Screen 4 — owner units + owner-scoped occupancy calendar (new owner-scoped page).
    Route::get('/units', OwnerMyUnits::class)->name('units');
    // Wave 1 · Sub-gate 1 — the owner-accounting long-tail (all read-only, owner-scoped, over the
    // CERTIFIED owner-accounting backend; presentation only, no service/schema change).
    Route::get('/balance', OwnerBalance::class)->name('balance');
    Route::get('/expenses', OwnerExpenses::class)->name('expenses');
    Route::get('/agreement', OwnerAgreement::class)->name('agreement');
    Route::get('/archive', OwnerArchive::class)->name('archive');
});

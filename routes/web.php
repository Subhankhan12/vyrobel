<?php

use App\Http\Controllers\BookingEngineController;
use App\Http\Controllers\ChannexWebhookController;
use App\Http\Controllers\FrontDeskActionController;
use App\Http\Controllers\HousekeepingActionController;
use App\Http\Controllers\ReservationFolioActionController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\ResolvePublicProperty;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Livewire\FrontDesk;
use App\Livewire\HousekeepingBoard;
use App\Livewire\Operator\OperatorDashboard;
use App\Livewire\Owner\OwnerDashboard;
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
    Route::get('/rack', ReservationRack::class)->name('rack');
    Route::get('/units', UnitsList::class)->name('units');
    Route::get('/reservations/{reservation}', ReservationDetail::class)->name('reservations.show');
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
    // Design pass · Screen 3 — owner payouts (owner-scoped; new page for the certified Payout backend).
    Route::get('/payouts', OwnerPayouts::class)->name('payouts');
    // Design pass · Screen 4 — owner units + owner-scoped occupancy calendar (new owner-scoped page).
    Route::get('/units', OwnerMyUnits::class)->name('units');
});

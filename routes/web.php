<?php

use App\Http\Controllers\BookingEngineController;
use App\Http\Controllers\ChannexWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\ResolvePublicProperty;
use App\Livewire\FrontDesk;
use App\Livewire\HousekeepingBoard;
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
        Route::get('/', [BookingEngineController::class, 'stub'])->name('stub');
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
    Route::get('/rack', ReservationRack::class)->name('rack');
    Route::get('/units', UnitsList::class)->name('units');
    Route::get('/reservations/{reservation}', ReservationDetail::class)->name('reservations.show');
    Route::get('/housekeeping', HousekeepingBoard::class)->name('housekeeping');
    // Phase 2 Step 8 — front-desk staff surface (arrivals/departures/in-house + action contracts).
    Route::get('/front-desk', FrontDesk::class)->name('front-desk');
});

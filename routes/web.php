<?php

use App\Http\Controllers\ChannexWebhookController;
use App\Http\Controllers\StripeWebhookController;
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
 * Phase 1 CONTRACT-STUB pages (unstyled). Read-only Livewire components that expose the data
 * shapes for the later design pass. Behind `auth` so the SetTenantForWebRequest middleware can
 * resolve the active organization from the authenticated user (tenant isolation intact).
 */
Route::middleware('auth')->prefix('app')->name('app.')->group(function () {
    Route::get('/rack', ReservationRack::class)->name('rack');
    Route::get('/units', UnitsList::class)->name('units');
    Route::get('/reservations/{reservation}', ReservationDetail::class)->name('reservations.show');
    Route::get('/housekeeping', HousekeepingBoard::class)->name('housekeeping');
});

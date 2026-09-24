<?php

declare(strict_types=1);

use App\Http\Controllers\Api\OwnerApiController;
use App\Http\Middleware\EnsureOwnerApiScope;
use App\Http\Middleware\RefuseSuspendedOrganization;
use App\Http\Middleware\ResolveApiOwnerContext;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| OAuth2 scoped, owner + tenant-isolated READ API (Wave D · Passport)
|--------------------------------------------------------------------------
|
| Every endpoint is TRIPLE-GATED:
|   1. auth:api               — a valid, non-revoked Passport access token (401 otherwise).
|   2. ResolveApiOwnerContext — the token's user → owner + org; sets Tenant + Owner context (403 if not an
|                               owner, or if it straddles orgs). The OwnerScope + OrganizationScope then make
|                               EVERY read return ONLY this owner's rows in this tenant.
|   3. EnsureOwnerApiScope:<scope> — the token must carry the scope AND the owner's role must back it (403
|                               otherwise) — a token can never over-grant beyond the owner's own access.
|
| READ-ONLY, least-privilege. Guest data is NAME-only (#28); no arrival secrets (#33).
*/
// ⚠️ ADMIN 01 · PHASE 2 — a token whose organisation is suspended or archived is refused (403), after the owner
//    context resolves the organisation it belongs to.
Route::middleware(['auth:api', ResolveApiOwnerContext::class, RefuseSuspendedOrganization::class])->prefix('owner')->name('api.owner.')->group(function () {
    Route::get('/bookings', [OwnerApiController::class, 'bookings'])
        ->middleware(EnsureOwnerApiScope::class.':read:bookings')->name('bookings');

    Route::get('/statements', [OwnerApiController::class, 'statements'])
        ->middleware(EnsureOwnerApiScope::class.':read:statements')->name('statements');

    Route::get('/units', [OwnerApiController::class, 'units'])
        ->middleware(EnsureOwnerApiScope::class.':read:units')->name('units');
});

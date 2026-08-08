<?php

declare(strict_types=1);

use App\Services\OwnerInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 3: expiries that were TRUE but never stated.
 *
 * Both windows are real; neither was told to the user in the place they needed it, so someone returning to
 * a dead link had no way to know why. Sourced from config / the service constant rather than written out,
 * so the sentence cannot drift from the setting that governs it.
 */
it('tells the user how long a password-reset link lasts, from the real config', function () {
    expect(config('auth.passwords.users.expire'))->toBe(60);

    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('valid for 60 minutes');
});

it('states the invite expiry from the service constant, not a hardcoded number', function () {
    expect(OwnerInviteService::EXPIRY_DAYS)->toBe(7);

    $blade = file_get_contents(resource_path('views/emails/owner-invite.blade.php'));

    expect($blade)->toContain('OwnerInviteService::EXPIRY_DAYS')
        ->and($blade)->not->toContain('expires in 7 days');
});

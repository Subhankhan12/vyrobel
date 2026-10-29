<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// I18N-01 (2026-10-29) — the console speaks its admin's own language. Its words were keys, but a platform admin had no column,
// so the console printed English for everyone (found by ops/i18n/english-left.php on the first German render).

beforeEach(fn () => config(['app.available_locales' => ['en', 'de']]));

it('saves the admin\'s language and shows the console in it', function () {
    $admin = platformAdmin(['name' => 'Console Reader']);
    $german = __('admin/account/index.profile', [], 'de');
    expect($german)->not->toBe('Profile');   // a German word exists to see

    $this->actingAs($admin, 'admin')->get(route('admin.account.edit'))->assertOk()->assertSee('Profile')->assertSee('name="locale"', false);

    $this->actingAs($admin, 'admin')->put(route('admin.account.profile'), ['name' => 'Console Reader', 'locale' => 'de'])
        ->assertRedirect(route('admin.account.edit'));
    expect($admin->fresh()->locale)->toBe('de');

    $this->actingAs($admin->fresh(), 'admin')->get(route('admin.account.edit'))->assertOk()->assertSee($german);
})->group('security');

it('refuses a language nobody may pick, and leaves English for an admin who never chose', function () {
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->put(route('admin.account.profile'), ['name' => 'X', 'locale' => 'klingon'])->assertRedirect();
    expect($admin->fresh()->locale)->toBeNull()
        ->and(sessionErrorKeys())->toContain('locale');

    $this->actingAs($admin->fresh(), 'admin')->get(route('admin.account.edit'))->assertOk()->assertSee('Profile');
})->group('security');

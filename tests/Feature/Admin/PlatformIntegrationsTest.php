<?php

declare(strict_types=1);

use App\Models\PlatformAdminAction;
use App\Models\PlatformIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// SUPER ADMIN · BUILD 2 · GATE 2 — Integrations: encrypted master credentials, never echoed, audited.

it('lists the channel catalog', function () {
    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.integrations.index'))
        ->assertOk()
        ->assertSee('Channex')
        ->assertSee('Booking.com')
        ->assertSee('How customers connect');   // the self-connect model is documented
})->group('security');

it('saves a master credential ENCRYPTED at rest (the plaintext is never on disk) and AUDITS it', function () {
    $this->actingAs(platformAdmin(), 'admin')
        ->put(route('admin.integrations.update', 'channex'), ['api_key' => 'sk_channex_SECRET_123', 'is_enabled' => '1'])
        ->assertRedirect(route('admin.integrations.index'));

    $integration = PlatformIntegration::query()->where('channel', 'channex')->first();
    expect($integration)->not->toBeNull();
    expect($integration->isConfigured())->toBeTrue();
    expect($integration->credentials['api_key'])->toBe('sk_channex_SECRET_123');   // decrypts via the cast
    expect($integration->is_enabled)->toBeTrue();

    // 🔒 The raw DB column is CIPHERTEXT — the plaintext secret never lands on disk.
    $raw = (string) DB::table('platform_integrations')->where('channel', 'channex')->value('credentials');
    expect($raw)->not->toContain('sk_channex_SECRET_123');
    expect(strlen($raw))->toBeGreaterThan(20);

    // 🔒 The audit records the channel, NEVER the secret.
    $action = PlatformAdminAction::query()->where('action', 'platform.integration.updated')->first();
    expect($action)->not->toBeNull();
    expect(json_encode($action->meta))->not->toContain('sk_channex_SECRET_123');
})->group('security');

it('NEVER echoes the stored secret back on the edit screen (write-only / masked)', function () {
    PlatformIntegration::create(['channel' => 'channex', 'credentials' => ['api_key' => 'sk_channex_SECRET_123'], 'status' => 'configured']);

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.integrations.edit', 'channex'))
        ->assertOk()
        ->assertDontSee('sk_channex_SECRET_123')   // 🔒 the secret is never rendered
        ->assertSee('configured');                 // only its presence is shown
})->group('security');

it('rotates write-only — a blank field keeps the existing credential', function () {
    PlatformIntegration::create(['channel' => 'channex', 'credentials' => ['api_key' => 'OLD_KEY'], 'status' => 'configured']);

    // Submit with a blank api_key (just toggling enabled) → the existing key is kept.
    $this->actingAs(platformAdmin(), 'admin')
        ->put(route('admin.integrations.update', 'channex'), ['api_key' => '', 'is_enabled' => '1']);
    expect(PlatformIntegration::query()->where('channel', 'channex')->first()->credentials['api_key'])->toBe('OLD_KEY');

    // Submit a new key → rotated.
    $this->actingAs(platformAdmin(), 'admin')
        ->put(route('admin.integrations.update', 'channex'), ['api_key' => 'NEW_KEY']);
    expect(PlatformIntegration::query()->where('channel', 'channex')->first()->credentials['api_key'])->toBe('NEW_KEY');
})->group('security');

it('the test-connection is an honest STUB (marks tested; the real sync is account-gated) + audited', function () {
    PlatformIntegration::create(['channel' => 'channex', 'credentials' => ['api_key' => 'k'], 'status' => 'configured']);

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.integrations.test', 'channex'))
        ->assertRedirect(route('admin.integrations.index'))
        ->assertSessionHas('admin_notice', fn ($m) => str_contains($m, 'account-gated'));   // honest label

    $integration = PlatformIntegration::query()->where('channel', 'channex')->first();
    expect($integration->status)->toBe('tested');
    expect($integration->last_tested_at)->not->toBeNull();
    expect(PlatformAdminAction::query()->where('action', 'platform.integration.tested')->exists())->toBeTrue();
})->group('security');

it('rejects an unknown channel (404)', function () {
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.integrations.edit', 'myspace'))->assertNotFound();
})->group('security');

it('a tenant user cannot reach any Integrations route', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s);

    $this->actingAs($operator)->get(route('admin.integrations.index'))->assertRedirect(route('admin.login'));
    $this->actingAs($operator)->put(route('admin.integrations.update', 'channex'), ['api_key' => 'x'])->assertRedirect(route('admin.login'));
    expect(PlatformIntegration::query()->count())->toBe(0);   // nothing written by the tenant user
})->group('security');

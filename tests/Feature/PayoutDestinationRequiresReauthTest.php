<?php

use App\Models\PayoutMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ══ AUDIT FOLLOW-UP 2026-08-30 — RE-AUTHENTICATE BEFORE MOVING WHERE THE MONEY GOES ══════════════════
//
// ⚠️ All three payout-destination actions were capability-gated ONLY. A hijacked owner session could
// redirect the destination IMMEDIATELY, with no password and without the slower email takeover (H-3).
// REPRODUCED before the fix: added an attacker account and set it default, both 302, no password.
//
// ⚠️ ALL THREE ARE PROVEN SEPARATELY, AND THAT IS THE POINT. An attacker who can only ADD an account has
// achieved NOTHING; one who can SET-DEFAULT has achieved EVERYTHING; and REMOVE deletes the genuine
// account so whatever remains becomes the destination. A single test over "the controller" would have
// left the actual attack open.
//
// ⚠️ THE BARE `current_password` — config/auth.php defines web · guest · admin and NO `owner` guard, and
// /owner runs on plain `auth`. A guard-qualified rule would silently match nothing (the H-3 finding).
//
// ⚠️ MEASUREMENT NOTE: PayoutMethod uses SoftDeletes. A raw COUNT(*) counts a removed row as live and made
// a working removal look refused during the live reproduction. These tests count through the model.

/** An owner with the payout capability, a known password, and one existing default account. */
function payoutReauthActor(): array
{
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->changePasswordTo('secret-Passw0rd!');

    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    $existing = PayoutMethod::create([
        'owner_id' => $ownerId, 'label' => 'Main', 'account_holder' => 'Real Owner',
        'iban' => 'GB33BUKB20201555555555', 'iban_last4' => '5555', 'iban_country' => 'GB',
        'currency' => 'EUR', 'is_default' => true,
    ]);

    return ['user' => $user->fresh(), 'ownerId' => $ownerId, 'existing' => $existing];
}

function addPayload(array $extra = []): array
{
    return array_merge([
        'account_holder' => 'Attacker', 'iban' => 'GB33BUKB20201555555555', 'currency' => 'EUR',
    ], $extra);
}

// ── 1. ⛔ ADD — refused without the password, accepted with it ───────────────────────────────────────
it('refuses to add a payout account without the current password', function () {
    $c = payoutReauthActor();

    $this->actingAs($c['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payout-method.add'), addPayload())
        ->assertSessionHasErrors('current_password');

    expect(PayoutMethod::withoutGlobalScopes()->where('account_holder', 'Attacker')->count())->toBe(0);
});

it('adds a payout account with the correct password', function () {
    $c = payoutReauthActor();

    $this->actingAs($c['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payout-method.add'), addPayload(['current_password' => 'secret-Passw0rd!', 'account_holder' => 'Second']))
        ->assertSessionHasNoErrors();

    expect(PayoutMethod::withoutGlobalScopes()->where('account_holder', 'Second')->count())->toBe(1);
});

// ── 2. ⛔ SET DEFAULT — THE ACTUAL REDIRECT ─────────────────────────────────────────────────────────
it('refuses to change the default payout account without the current password', function () {
    $c = payoutReauthActor();
    $second = PayoutMethod::create([
        'owner_id' => $c['ownerId'], 'label' => 'Other', 'account_holder' => 'Other',
        'iban' => 'GB33BUKB20201555555555', 'iban_last4' => '9999', 'iban_country' => 'GB',
        'currency' => 'EUR', 'is_default' => false,
    ]);

    $this->actingAs($c['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payout-method.default', $second->id))
        ->assertSessionHasErrors('current_password');

    expect((bool) $c['existing']->fresh()->is_default)->toBeTrue();   // the money still goes to the real one
    expect((bool) $second->fresh()->is_default)->toBeFalse();
});

it('changes the default payout account with the correct password', function () {
    $c = payoutReauthActor();
    $second = PayoutMethod::create([
        'owner_id' => $c['ownerId'], 'label' => 'Other', 'account_holder' => 'Other',
        'iban' => 'GB33BUKB20201555555555', 'iban_last4' => '9999', 'iban_country' => 'GB',
        'currency' => 'EUR', 'is_default' => false,
    ]);

    $this->actingAs($c['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payout-method.default', $second->id), ['current_password' => 'secret-Passw0rd!'])
        ->assertSessionHasNoErrors();

    expect((bool) $second->fresh()->is_default)->toBeTrue();
});

// ── 3. ⛔ REMOVE — deleting the genuine account redirects the money too ─────────────────────────────
it('refuses to remove a payout account without the current password', function () {
    $c = payoutReauthActor();

    $this->actingAs($c['user'])->from(route('owner.payout-method'))
        ->delete(route('owner.payout-method.remove', $c['existing']->id))
        ->assertSessionHasErrors('current_password');

    expect($c['existing']->fresh()->trashed())->toBeFalse();   // ⚠️ SoftDeletes — a raw count would lie
});

it('removes a payout account with the correct password', function () {
    $c = payoutReauthActor();

    $this->actingAs($c['user'])->from(route('owner.payout-method'))
        ->delete(route('owner.payout-method.remove', $c['existing']->id), ['current_password' => 'secret-Passw0rd!'])
        ->assertSessionHasNoErrors();

    expect(PayoutMethod::withoutGlobalScopes()->withTrashed()->find($c['existing']->id)->trashed())->toBeTrue();
});

// ── 3b. ⛔ NO EXISTENCE ORACLE — ownership answers BEFORE identity ──────────────────────────────────
// ⚠️ ORDER IS LOAD-BEARING: capability (route middleware) → OWNERSHIP → identity. A cross-owner attempt must
// give the SAME answer whether or not a password was sent. Password-first would make a validation error mean
// "that account exists and belongs to someone" — an existence oracle the attacker did not previously have.
it('returns the same not-found for a cross-owner method with AND without a password', function () {
    $a = payoutReauthActor();

    // A second owner in the same org, with their own account.
    $sB = seedScenario(1);
    $userB = ownerRoleUser($sB, 0);
    $ownerB = (string) $sB['units'][0]->fresh()->owner_id;
    $foreign = PayoutMethod::create([
        'owner_id' => $ownerB, 'label' => 'Theirs', 'account_holder' => 'Someone Else',
        'iban' => 'GB33BUKB20201555555555', 'iban_last4' => '7777', 'iban_country' => 'GB',
        'currency' => 'EUR', 'is_default' => true,
    ]);

    // NO password → 404, not a validation error.
    $this->actingAs($a['user'])
        ->post(route('owner.payout-method.default', $foreign->id))
        ->assertNotFound();

    // CORRECT password → still 404. Same answer, so the response reveals nothing about existence.
    $this->actingAs($a['user'])
        ->post(route('owner.payout-method.default', $foreign->id), ['current_password' => 'secret-Passw0rd!'])
        ->assertNotFound();

    // And removal behaves identically.
    $this->actingAs($a['user'])
        ->delete(route('owner.payout-method.remove', $foreign->id), ['current_password' => 'secret-Passw0rd!'])
        ->assertNotFound();

    expect($foreign->fresh()->trashed())->toBeFalse();
    expect((bool) $foreign->fresh()->is_default)->toBeTrue();
});

// ── 4. the form carries the field — a rule with no field is a feature that always fails ─────────────
it('renders a password field for every payout-destination action', function () {
    $c = payoutReauthActor();
    PayoutMethod::create([
        'owner_id' => $c['ownerId'], 'label' => 'Other', 'account_holder' => 'Other',
        'iban' => 'GB33BUKB20201555555555', 'iban_last4' => '9999', 'iban_country' => 'GB',
        'currency' => 'EUR', 'is_default' => false,
    ]);

    $html = $this->actingAs($c['user'])->get(route('owner.payout-method'))->assertOk()->getContent();

    // add + set-default + remove all need somewhere to put it.
    expect(substr_count($html, 'name="current_password"'))->toBeGreaterThanOrEqual(3);
});

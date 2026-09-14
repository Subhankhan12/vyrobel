<?php

use App\Models\PayoutMethod;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

/*
 * A SECRET MUST NEVER BE FLASHED INTO THE SESSION (audit 2026-08-07 · F6-3).
 *
 * On a validation failure Laravel flashes ALL old input so the form can repopulate. The app declared no
 * exclusions, so only the framework default (password fields) applied — and the owner payout form's IBAN and
 * account-holder name were written in PLAINTEXT to the `sessions` table. Reproduced against the raw row
 * before the fix:
 *
 *     "account_holder":"Ada Lovelace SecretHolder","iban":"PT50000000000000000000999"
 *
 * `payout_methods.iban` is an ENCRYPTED column precisely because an IBAN is sensitive. Flashing it in the
 * clear defeats that control, and the session store is not encrypted. It is this codebase's own rule one
 * layer over: an encrypted column is undone the moment its value is copied somewhere plaintext.
 */

it('does not flash the IBAN or account holder after a failed payout-method submit', function () {
    $owner = ownerRoleUser(seedScenario(1));

    // A syntactically plausible IBAN that FAILS the real mod-97 checksum, so validation rejects it and the
    // old-input flash is what we are inspecting.
    $response = $this->actingAs($owner)->post('/owner/payout-method', [
        'current_password' => 'password',   // ⚠️ FIXTURE ONLY — payout-destination re-auth
        'account_holder' => 'Ada Lovelace SecretHolder',
        'iban' => 'PT50000000000000000000999',
        'label' => 'Main',
        'currency' => 'EUR',   // supplied so the IBAN checksum is the ONLY reason this is rejected
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();   // the rejection itself still works — UX preserved

    $flashed = Session::get('_old_input', []);

    expect($flashed)->not->toHaveKey('iban');
    expect($flashed)->not->toHaveKey('account_holder');

    // Nothing anywhere in the flashed payload spells either secret — a rename or a nested copy would fail here.
    $blob = json_encode($flashed, JSON_THROW_ON_ERROR);
    expect($blob)->not->toContain('PT50000000000000000000999');
    expect($blob)->not->toContain('SecretHolder');

    // The non-sensitive field IS still flashed, so the form genuinely repopulates what it safely can.
    expect($flashed)->toHaveKey('label');
});

it('still stores a valid IBAN, and stores it ENCRYPTED at rest', function () {
    $owner = ownerRoleUser(seedScenario(1));

    // A real mod-97-valid IBAN (the same fixture OwnerPayoutMethodTest proves valid).
    $this->actingAs($owner)->post('/owner/payout-method', [
        'current_password' => 'password',   // ⚠️ FIXTURE ONLY — payout-destination re-auth
        'account_holder' => 'Ada Lovelace',
        'iban' => 'GB82WEST12345698765432',
        'label' => 'HardeningCheck',
        'currency' => 'EUR',
    ])->assertRedirect();

    $row = DB::table('payout_methods')->orderByDesc('created_at')->first();

    expect($row)->not->toBeNull();

    // ⚠️ The point of this gate is that #28 is STRENGTHENED, never weakened: the value still persists, and it
    // still persists as CIPHERTEXT. Read the RAW column, bypassing the cast.
    expect((string) $row->iban)->not->toContain('GB82WEST12345698765432');
    expect(strlen((string) $row->iban))->toBeGreaterThan(40);   // an encrypted envelope, not a 25-char IBAN

    // …and it still decrypts back through the model, so the feature works.
    expect((string) PayoutMethod::query()->findOrFail($row->id)->iban)->toBe('GB82WEST12345698765432');
});

it('declares the other secret-bearing field names too', function () {
    // Declared globally rather than per-controller so it covers forms not yet written. If a new banking or
    // credential field is added, it belongs in this list.
    $dontFlash = app(ExceptionHandler::class);
    $reflection = new ReflectionClass($dontFlash);

    while ($reflection && ! $reflection->hasProperty('dontFlash')) {
        $reflection = $reflection->getParentClass();
    }

    $property = $reflection->getProperty('dontFlash');
    $property->setAccessible(true);
    $declared = $property->getValue($dontFlash);

    foreach (['password', 'iban', 'account_holder', 'tax_id', 'api_key', 'secret', 'two_factor_code'] as $field) {
        expect($declared)->toContain($field);
    }
});

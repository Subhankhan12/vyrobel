<?php

use App\Models\Membership;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Support\Payouts\Iban;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// OWNER PAYOUT METHOD (Phase 2 · Sub-gate 5) — DATA CAPTURE for the owner's bank account(s).
//   • add persists; the full IBAN is ENCRYPTED at rest (assert ciphertext) and MASKED in the UI (never rendered);
//   • the first account is the default; set-default + remove (promoting the next) work;
//   • SELF-ONLY — an owner can't read/set-default/remove another owner's method (owner-scoped resolve → 404);
//   • a bad IBAN is rejected by the real ISO mod-97 check; the live rail is honestly deferred (no fake transfer).

// A widely-used valid example IBAN (passes ISO 7064 mod-97) — last four "5432".
const VALID_IBAN = 'GB82WEST12345698765432';
const VALID_IBAN_2 = 'DE89370400440532013000';

/** A linked owner-portal user owning unit[$index]. */
if (! function_exists('pmOwner')) {
    function pmOwner(array $s, int $index, string $name): array
    {
        $unit = $s['units'][$index];
        $owner = ownUnit($unit);
        $user = User::factory()->create(['name' => $name]);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return ['user' => $user, 'owner' => $owner];
    }
}

/** Add a payout method for the acting owner through the route. */
function addMethod($test, User $user, string $iban, string $holder = 'Amara Okafor', ?string $label = null)
{
    return $test->actingAs($user)->post('/owner/payout-method', array_filter([
        'account_holder' => $holder, 'iban' => $iban, 'currency' => 'EUR', 'label' => $label,
    ], fn ($v) => $v !== null));
}

// ── 0. The IBAN checksum is REAL ─────────────────────────────────────────────────────────────────
it('validates the IBAN checksum from its structure', function () {
    expect(Iban::isValid(VALID_IBAN))->toBeTrue()
        ->and(Iban::isValid(VALID_IBAN_2))->toBeTrue()
        ->and(Iban::isValid('GB00WEST12345698765432'))->toBeFalse()   // wrong check digits
        ->and(Iban::isValid('not-an-iban'))->toBeFalse()
        ->and(Iban::last4(VALID_IBAN))->toBe('5432')
        ->and(Iban::country(VALID_IBAN))->toBe('GB');
});

// ── 1. Add persists; IBAN ENCRYPTED at rest; MASKED in the UI (never the full number) ─────────────
it('stores the IBAN encrypted and only ever shows it masked', function () {
    $s = seedScenario(1);
    $a = pmOwner($s, 0, 'Amara Okafor');

    addMethod($this, $a['user'], VALID_IBAN, 'Amara Okafor', 'Revolut Business')
        ->assertRedirect(route('owner.payout-method'))->assertSessionHas('payout_method_notice');

    $method = PayoutMethod::query()->where('owner_id', $a['owner']->id)->firstOrFail();
    // Decrypts back to the normalized IBAN…
    expect((string) $method->iban)->toBe(VALID_IBAN)
        ->and($method->is_default)->toBeTrue()          // first account → default
        ->and((string) $method->iban_last4)->toBe('5432')
        ->and((string) $method->iban_country)->toBe('GB');
    // …but the RAW column is ciphertext, never the plaintext IBAN.
    $raw = (string) DB::table('payout_methods')->where('id', $method->id)->value('iban');
    expect($raw)->not->toBe(VALID_IBAN)->and($raw)->not->toContain('5698');   // no middle digits leak

    // The screen shows the MASK, never the full number.
    $this->actingAs($a['user'])->get('/owner/payout-method')
        ->assertOk()
        ->assertSee('···· 5432')
        ->assertSee('Revolut Business')
        ->assertDontSee(VALID_IBAN);
});

// ── 2. First is default; set-default switches it ──────────────────────────────────────────────────
it('makes the first account default and switches the default on request', function () {
    $s = seedScenario(1);
    $a = pmOwner($s, 0, 'Amara Okafor');

    addMethod($this, $a['user'], VALID_IBAN);
    addMethod($this, $a['user'], VALID_IBAN_2);

    $first = PayoutMethod::query()->where('iban_last4', '5432')->firstOrFail();
    $second = PayoutMethod::query()->where('iban_last4', '3000')->firstOrFail();
    expect($first->is_default)->toBeTrue()->and($second->is_default)->toBeFalse();

    $this->actingAs($a['user'])->post(route('owner.payout-method.default', $second->id))
        ->assertRedirect()->assertSessionHas('payout_method_notice');

    expect($second->fresh()->is_default)->toBeTrue()->and($first->fresh()->is_default)->toBeFalse();
});

// ── 3. SELF-ONLY — an owner can't touch another owner's method (read + set-default + remove) ──────
it('never lets an owner read or manage another owner’s method', function () {
    $s = seedScenario(2);
    $a = pmOwner($s, 0, 'Amara Okafor');
    $b = pmOwner($s, 1, 'Bruno Vale');

    addMethod($this, $a['user'], VALID_IBAN, 'Amara Okafor', 'Amara Bank');
    addMethod($this, $b['user'], VALID_IBAN_2, 'Bruno Vale', 'Bruno Bank');
    $bMethod = PayoutMethod::query()->where('owner_id', $b['owner']->id)->firstOrFail();

    // A never sees B's account…
    $this->actingAs($a['user'])->get('/owner/payout-method')->assertOk()->assertDontSee('Bruno Bank');

    // …and cannot set-default or remove it by id (owner-scoped resolve → 404), leaving it untouched.
    $this->actingAs($a['user'])->post(route('owner.payout-method.default', $bMethod->id))->assertNotFound();
    $this->actingAs($a['user'])->delete(route('owner.payout-method.remove', $bMethod->id))->assertNotFound();

    expect($bMethod->fresh()->is_default)->toBeTrue()                                       // still B's default…
        ->and(PayoutMethod::withoutOwnerScope()->whereKey($bMethod->id)->exists())->toBeTrue();   // …still present
});

// ── 4. A bad IBAN is rejected, nothing stored ─────────────────────────────────────────────────────
it('rejects an invalid IBAN without storing it', function () {
    $s = seedScenario(1);
    $a = pmOwner($s, 0, 'Amara Okafor');

    addMethod($this, $a['user'], 'GB00WEST12345698765432')   // wrong check digits
        ->assertRedirect()->assertSessionHasErrors('iban');

    expect(PayoutMethod::query()->where('owner_id', $a['owner']->id)->exists())->toBeFalse();
});

// ── 5. Remove promotes the next account to default ────────────────────────────────────────────────
it('promotes the next account to default when the default is removed', function () {
    $s = seedScenario(1);
    $a = pmOwner($s, 0, 'Amara Okafor');

    addMethod($this, $a['user'], VALID_IBAN);      // default
    addMethod($this, $a['user'], VALID_IBAN_2);
    $default = PayoutMethod::query()->where('iban_last4', '5432')->firstOrFail();
    $other = PayoutMethod::query()->where('iban_last4', '3000')->firstOrFail();

    $this->actingAs($a['user'])->delete(route('owner.payout-method.remove', $default->id))
        ->assertRedirect()->assertSessionHas('payout_method_notice');

    expect(PayoutMethod::query()->whereKey($default->id)->exists())->toBeFalse()   // soft-deleted → gone
        ->and($other->fresh()->is_default)->toBeTrue();                            // promoted
});

// ── 6. Non-linked user fails closed; the live rail is honestly deferred ───────────────────────────
it('fails closed for a non-linked user and defers the live rail honestly', function () {
    $s = seedScenario(1);
    $a = pmOwner($s, 0, 'Amara Okafor');

    // The honest deferred-rail message, and NO fabricated verification state.
    $this->actingAs($a['user'])->get('/owner/payout-method')
        ->assertOk()
        ->assertSee('Automated bank transfers are coming soon')
        ->assertDontSee('Verified')
        ->assertDontSee('micro-deposit');

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    $this->actingAs($stranger)->get('/owner/payout-method')->assertOk()->assertSee('No owner account is linked');
});

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
        // ⚠️ FIXTURE ONLY: changing a payout destination now re-authenticates (audit follow-up 2026-08-30).
        // The factory default password.
        'current_password' => 'password',
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

    $this->actingAs($a['user'])->post(route('owner.payout-method.default', $second->id), ['current_password' => 'password'])
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

    $this->actingAs($a['user'])->delete(route('owner.payout-method.remove', $default->id), ['current_password' => 'password'])
        ->assertRedirect()->assertSessionHas('payout_method_notice');

    expect(PayoutMethod::query()->whereKey($default->id)->exists())->toBeFalse()   // soft-deleted → gone
        ->and($other->fresh()->is_default)->toBeTrue();                            // promoted
});

// ── 6. Non-linked user fails closed; the live rail is honestly deferred ───────────────────────────
it('fails closed for a non-linked user and defers the live rail honestly', function () {
    $s = seedScenario(1);
    $a = pmOwner($s, 0, 'Amara Okafor');

    // Phase 8 — the live Stripe Connect rail: a real "Connect Stripe" onboarding. Verification is honestly
    // labelled as pending the LIVE banking connection — NEVER a fabricated ACTIVE verification (no fake
    // "Verified" badge, no "Verifying"/"deposits sent" micro-deposit flow).
    $this->actingAs($a['user'])->get('/owner/payout-method')
        ->assertOk()
        ->assertSee('Connect Stripe')
        ->assertSee(route('owner.payout-method.connect'), false)
        ->assertSee('live banking connection')       // the honest pending-live label
        ->assertDontSee('Verifying')
        ->assertDontSee('deposits sent');

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    $this->actingAs($stranger)->get('/owner/payout-method')->assertOk()->assertSee('No owner account is linked');
});

// ── Exact-match: the rebuilt page renders the real account + schedule + tax form + payouts-go-to ───
it('renders the real account (masked), the schedule, the tax form and the payouts-go-to rail', function () {
    $s = seedScenario(1);
    $a = pmOwner($s, 0, 'Amara Okafor');
    addMethod($this, $a['user'], VALID_IBAN, 'Amara Okafor', 'Revolut Business');

    $this->actingAs($a['user'])->get('/owner/payout-method')
        ->assertOk()
        ->assertSee('Revolut Business')
        ->assertSee('···· 5432')                     // masked IBAN (#28)
        ->assertDontSee(VALID_IBAN)                  // never the full number
        ->assertSee('Payout schedule')               // the shared preferences store
        ->assertSee('Tax &amp; remittance details', false)   // the inline tax form
        ->assertSee(route('owner.payouts.preferences'))      // schedule → the real prefs route
        ->assertSee(route('owner.tax.save'))                 // tax details → the shared write path
        ->assertSee('Payouts go to')                 // the dark default-account rail
        // ⚠️ NO fabricated verification badge ON A RENDERED ACCOUNT. The sibling guard forbade "Verifying"
        // but not "Verified" — the stronger claim — and it sat in a test with NO account, so it could never
        // have caught a badge in the account row (empty-fixture vacuity, proven by mutation). `payout_methods`
        // has no verification column at all, so any such badge is pure fabrication.
        ->assertDontSee('Verified');
});

// ── Exact-match: the inline tax form writes the REAL Owner fields via the shared owner.tax.save path ──
it('saves tax & remittance details from the payout-method page via the shared write', function () {
    $s = seedScenario(1);
    $a = pmOwner($s, 0, 'Amara Okafor');

    // The inline form's action is owner.tax.save (co-owner via $capActions); the co-owner can submit it.
    $this->actingAs($a['user'])->post(route('owner.tax.save'), ['current_password' => 'password',
        'type' => 'company', 'legal_name' => 'Seaside Group Ltd',
        'tax_id' => '501234567', 'vat_number' => 'PT501234567', 'tax_country' => 'Portugal',
    ])->assertRedirect();

    $owner = $a['owner']->fresh();
    expect((string) $owner->legal_name)->toBe('Seaside Group Ltd')
        ->and((string) $owner->type)->toBe('company')
        ->and((string) $owner->tax_id)->toBe('501234567')        // decrypts back (encrypted at rest, #28)
        ->and($owner->hasTaxDetails())->toBeTrue();              // a valid tax ID releases the hold
});

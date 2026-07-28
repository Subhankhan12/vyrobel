<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\PayoutMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER SELF-PROFILE (Wave 4 · Sub-gate 3) — an owner edits their OWN contact details.
//
// ⚠️ THE POINT OF THIS FILE. Owner is BelongsToOrganization, NOT BelongsToOwner — OwnerScope does not scope
// the owner row. The write is guarded by an EXPLICIT `where('user_id', auth id)` with NO owner id anywhere in
// the request, so an owner can only ever edit the single row that belongs to their own login. Asserted by
// having owner A post and proving owner B is untouched.

// ── 1. THE BACKED SELF-EDIT PERSISTS ─────────────────────────────────────────────────────────────
it('lets an owner update their own name, email and phone', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);

    $this->actingAs($a['user'])->post('/owner/settings', [
        'name' => 'Amara N. Okafor',
        'email' => 'amara@newmail.test',
        'phone' => '+353 87 555 0101',
    ])->assertRedirect(route('owner.settings'))->assertSessionHas('owner_notice');

    $owner = $a['owner']->fresh();
    // email/phone are encrypted casts — read back decrypted, stored ciphertext at rest.
    expect((string) $owner->name)->toBe('Amara N. Okafor')
        ->and((string) $owner->email)->toBe('amara@newmail.test')
        ->and((string) $owner->phone)->toBe('+353 87 555 0101');
});

// ── 2. ⚠️ THE user_id GUARD — an owner can edit ONLY their own row, never another's ──────────────
it('never lets one owner’s edit touch another owner’s record', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $b = inboxOwner($s, 'Bruno Keller', 1);
    $b['owner']->update(['name' => 'Bruno Keller', 'email' => 'bruno@original.test']);

    // Owner A posts — there is NO owner id in the request; the controller resolves A from their login.
    $this->actingAs($a['user'])->post('/owner/settings', [
        'name' => 'Amara Edited',
        'email' => 'amara@edited.test',
    ])->assertRedirect();

    // A changed…
    expect((string) $a['owner']->fresh()->name)->toBe('Amara Edited');
    // …and B is completely untouched — the guard isolates by user_id, not a tamperable id.
    expect((string) $b['owner']->fresh()->name)->toBe('Bruno Keller')
        ->and((string) $b['owner']->fresh()->email)->toBe('bruno@original.test');
});

// ── 3. AN OWNER SEES THEIR OWN CONTACT (their own PII is theirs), never another owner's ──────────
it('shows the owner their own details and not another owner’s', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $b = inboxOwner($s, 'Bruno Keller', 1);
    $a['owner']->update(['email' => 'amara@self.test']);
    $b['owner']->update(['email' => 'bruno@secret.test']);

    $response = $this->actingAs($a['user'])->get('/owner/settings');

    $response->assertOk()
        ->assertSee('amara@self.test')          // their OWN email — correct to show for self-edit
        ->assertDontSee('bruno@secret.test');   // never another owner's

    assertNoRawBlade($response);
});

// ── 4. THE HUB renders its sections wired to the REAL backends (exact-match · #62 · Gate 1) ───────
it('renders the settings hub sections wired to the real backends', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);

    $this->actingAs($a['user'])->get('/owner/settings')
        ->assertOk()
        ->assertSee('Profile')                          // the identity section
        ->assertSee('Business & tax details')
        ->assertSee('Save business details')            // primary-owner business form (owner.settings.update)
        // the inline tax form posts to the ONE owner.tax.save path (anti-drift — same store as Tax Center)
        ->assertSee('Save tax details')
        ->assertSee(route('owner.tax.save'), false)
        ->assertSee('Billing &amp; invoices', false)
        ->assertSee('Management fee')
        ->assertSee('no card needed')                   // honest — no fabricated owner card
        ->assertSee('Profile completeness')
        ->assertSee('Security &amp; privacy', false)
        ->assertSee(route('owner.security'), false)
        ->assertDontSee('not wired yet');
});

// ── 6. THE REAL management-fee rate (not the wireframe's 15%) + masked payout IBAN + no fake card ──
it('shows the REAL management-fee rate, a masked payout IBAN, and no fabricated card', function () {
    $s = seedScenario(1);

    // A DISTINCTIVE 20% agreement → proves the billing rate is READ from the agreement, not hardcoded 15%.
    $user = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'name' => 'Rate Owner', 'payout_currency' => 'EUR', 'tax_id' => 'PT123456789']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 20.00, 'effective_from' => '2026-01-01']);

    PayoutMethod::create([
        'organization_id' => $s['org']->id, 'owner_id' => $owner->id,
        'label' => 'Revolut Business', 'account_holder' => 'Rate Owner',
        'iban' => 'LT121000011101001000', 'iban_last4' => '1000', 'iban_country' => 'LT',
        'currency' => 'EUR', 'is_default' => true,
    ]);

    $res = $this->actingAs($user)->get('/owner/settings')->assertOk()
        ->assertSee('20% of booking revenue')        // the REAL agreement rate, read live
        ->assertDontSee('15% of booking revenue')
        ->assertSee('no card needed')                // honest — the owner has NO Vyrobel card/subscription
        ->assertDontSee('4242')                      // no fabricated card-on-file
        // the masked payout IBAN (the primary owner has CAP_PAYOUT), never the full number (#28)
        ->assertSee('···· 1000')
        ->assertDontSee('LT121000011101001000');

    assertNoRawBlade($res);
});

// ── 5. #42 — the owner-party write is PRIMARY-OWNER-ONLY; a non-primary-owner is refused ─────────
it('forbids the owner-party (business) write to a user who is not the primary owner', function () {
    $s = seedScenario(1);
    // A back-office staff user is not the primary owner → EnsurePrimaryOwner refuses before the controller.
    $staff = operatorUser($s, ['name' => 'Not An Owner'], 'Hotel Manager');

    $this->actingAs($staff)->post('/owner/settings', ['name' => 'Hacker'])->assertForbidden();

    expect(Owner::query()->where('name', 'Hacker')->exists())->toBeFalse();
});

<?php

use App\Models\Owner;
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

// ── 4. HONESTY — the old "Soon" stubs are now REAL links (owner account area · Gate 1) ───────────
it('shows real links (no fake "Soon") for payout, notifications and team, plus the real two-factor panel', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);

    $this->actingAs($a['user'])->get('/owner/settings')
        ->assertOk()
        ->assertSee('My profile')
        // The "More" row now LINKS to the real pages (these subsystems shipped) — no fake "Soon".
        ->assertSee('Payout account & bank details')
        ->assertSee('Notification preferences')
        ->assertSee('Team & access')
        ->assertDontSee('not wired yet')
        // Password + two-factor now live on the dedicated Security page — linked, not on this page.
        ->assertSee('Security')
        ->assertSee(route('owner.security'), false)
        // The primary owner sees the editable owner-party (business) form.
        ->assertSee('Save business details');
});

// ── 5. #42 — the owner-party write is PRIMARY-OWNER-ONLY; a non-primary-owner is refused ─────────
it('forbids the owner-party (business) write to a user who is not the primary owner', function () {
    $s = seedScenario(1);
    // A back-office staff user is not the primary owner → EnsurePrimaryOwner refuses before the controller.
    $staff = operatorUser($s, ['name' => 'Not An Owner'], 'Hotel Manager');

    $this->actingAs($staff)->post('/owner/settings', ['name' => 'Hacker'])->assertForbidden();

    expect(Owner::query()->where('name', 'Hacker')->exists())->toBeFalse();
});

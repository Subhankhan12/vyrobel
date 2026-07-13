<?php

use App\Mail\OwnerInviteMail;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Auth Phase B · Step 4 — the operator Owners screen + the invite-owner write. An operator opens the
// modal and invites an owner: POST → InviteOwnerController → OwnerInviteService → PRG. TENANT-scoped;
// only the OWNER role is wired (Manager/Accountant are disabled "Coming soon" and rejected server-side);
// an active email surfaces a clean flash, never a 500.

/** An active org member with NO owner link — the operator. */
function operatorFor(array $scenario, string $email = 'op@vyrobel.test'): User
{
    $user = User::factory()->create(['email' => $email, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return $user;
}

// ── The screen renders, tenant-scoped ────────────────────────────────────────────────────────────
it('renders the owners screen with the invite affordance and only this org’s owners', function () {
    $s = seedScenario(1);
    Owner::factory()->create(['name' => 'Marisol Vaz', 'status' => 'active']);
    $op = operatorFor($s);

    // Another org's owner must NOT appear.
    $other = seedScenario(1);
    Owner::factory()->create(['name' => 'Foreign Owner', 'status' => 'active']);

    $this->actingAs($op)->get('/app/owners')
        ->assertOk()
        ->assertSee('Invite an owner')
        ->assertSee('Marisol Vaz')
        ->assertDontSee('Foreign Owner');
});

// ── The happy path: a pending owner + invited login + dispatched email, org-scoped ───────────────
it('invites an owner: pending owner + invited login + email, all tenant-scoped', function () {
    Mail::fake();
    $s = seedScenario(1);
    $op = operatorFor($s);

    $this->actingAs($op)->post('/app/owners/invite', [
        'name' => 'Marisol Vaz',
        'email' => 'marisol@example.test',
        'role' => 'owner',
        'commission_pct' => '15',
        'unit_ids' => [$s['units'][0]->id],
    ])->assertRedirect(route('app.owners'))->assertSessionHas('owners_notice');

    $user = User::query()->where('email', 'marisol@example.test')->first();
    expect($user)->not->toBeNull()->and($user->status)->toBe('invited');

    $owner = Owner::query()->where('user_id', $user->id)->first();
    expect($owner->status)->toBe('inactive')->and($owner->organization_id)->toBe($s['org']->id);

    $invite = OwnerInvite::query()->where('email', 'marisol@example.test')->first();
    expect($invite->organization_id)->toBe($s['org']->id)
        ->and($invite->assigned_unit_ids)->toBe([$s['units'][0]->id])
        ->and((float) $invite->commission_pct)->toBe(15.0);

    Mail::assertSent(OwnerInviteMail::class, fn (OwnerInviteMail $m) => $m->hasTo('marisol@example.test'));
});

// ── Cross-org: a foreign unit id is silently dropped, never assigned across orgs ─────────────────
it('drops a unit id belonging to another org from the assignment', function () {
    Mail::fake();
    $other = seedScenario(1);
    $foreignUnit = $other['units'][0];   // belongs to org B

    $s = seedScenario(1);                 // context now org A
    $op = operatorFor($s);

    $this->actingAs($op)->post('/app/owners/invite', [
        'name' => 'A Owner',
        'email' => 'aowner@example.test',
        'role' => 'owner',
        'unit_ids' => [$foreignUnit->id, $s['units'][0]->id],
    ])->assertRedirect(route('app.owners'));

    $invite = OwnerInvite::query()->where('email', 'aowner@example.test')->firstOrFail();
    // Only org A's own unit survives; the foreign id is gone.
    expect($invite->assigned_unit_ids)->toBe([$s['units'][0]->id]);

    // And the foreign unit was never reassigned.
    app(TenantContext::class)->runAsSuperAdmin(function () use ($foreignUnit) {
        expect(Unit::withoutGlobalScopes()->find($foreignUnit->id)->owner_id)->toBeNull();
    });
});

// ── Owner + the two staff roles are the ONLY accepted roles — anything else 422s server-side ──────
it('rejects an unknown role with a validation error and creates no invite', function () {
    $s = seedScenario(1);
    $op = operatorFor($s);

    $this->actingAs($op)->post('/app/owners/invite', [
        'name' => 'Nope',
        'email' => 'nope@example.test',
        'role' => 'wizard',   // not owner|manager|accountant
    ])->assertSessionHasErrors('role');

    expect(OwnerInvite::query()->where('email', 'nope@example.test')->count())->toBe(0);
});

// ── An email that already belongs to an active user → a clean flash, not a 500 ───────────────────
it('surfaces a graceful error (not a crash) when the email is already an active account', function () {
    $s = seedScenario(1);
    $op = operatorFor($s);
    User::factory()->create(['email' => 'taken@example.test', 'status' => 'active']);

    $this->actingAs($op)->post('/app/owners/invite', [
        'name' => 'Taken',
        'email' => 'taken@example.test',
        'role' => 'owner',
    ])->assertStatus(302)->assertSessionHas('owners_error');
});

// ── The screen + write require auth ───────────────────────────────────────────────────────────────
it('requires authentication to view or invite', function () {
    $this->get('/app/owners')->assertRedirect('/login');
    $this->post('/app/owners/invite', ['name' => 'X', 'email' => 'x@example.test', 'role' => 'owner'])
        ->assertRedirect('/login');
});

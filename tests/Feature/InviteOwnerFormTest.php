<?php

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Models\Membership;
use App\Models\OwnerInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// INVITE OWNER — the FORM (exact-match #80). The modal + flow are the REAL, certified invite (name/email/role/
// units/commission → OwnerInviteService), gated operator-admin (can:users.manage). This file defends the
// GATE-1 additions: the PRG "Invite sent" flash carries the real email + expiry (drives the confirmation view),
// and the "Preview the invite email" route renders the REAL Mailable + is operator-admin gated.

/** An operator-admin who can invite (users.manage) — the minimal targeted grant, not a full provision. */
if (! function_exists('inviteAdmin')) {
    function inviteAdmin(array $scenario, string $email = 'invite-admin@vyrobel.test'): User
    {
        $user = User::factory()->create(['email' => $email, 'status' => 'active']);
        Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

        $registrar = app(PermissionRegistrar::class);
        $prev = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($scenario['org']->id);
        try {
            $role = Role::findOrCreate('Property Owner', (string) config('authorization.guard'));
            Permission::findOrCreate('users.manage', (string) config('authorization.guard'));
            $role->givePermissionTo('users.manage');
            $user->assignRole('Property Owner');
        } finally {
            $registrar->setPermissionsTeamId($prev);
        }

        return $user->fresh() ?? $user;
    }
}

// ── The invite PRG flashes the real email + expiry for the "Invite sent" confirmation view ─────────
it('sends an owner invite and renders the exact "Invite sent" confirmation with the real email + expiry', function () {
    emailIsBeingSent();   // D-MAIL-CLAIMS: this path SAYS an email was sent, so email must be sent
    $s = seedScenario(1);
    app(OrganizationRoleProvisioner::class)->provision($s['org']);
    $op = inviteAdmin($s);

    $this->actingAs($op)->post('/app/owners/invite', ['current_password' => 'password',
        'name' => 'Marisol Vega',
        'email' => 'marisol@example.test',
        'role' => 'owner',
        'commission_pct' => '18',
        'unit_ids' => [$s['units'][0]->id],
    ])
        ->assertRedirect(route('app.owners'))
        ->assertSessionHas('owners_notice')
        // The structured flash that drives the exact-match "sent" view.
        ->assertSessionHas('owner_invite_sent');

    $sent = session('owner_invite_sent');
    expect($sent['email'])->toBe('marisol@example.test')
        ->and($sent['expires'])->not->toBeNull();   // the real 7-day expiry, formatted

    // The redirected owners page renders the "Invite sent" confirmation with the real invited email.
    $this->actingAs($op)->get('/app/owners')
        ->assertOk()
        ->assertSee('Invite sent')
        ->assertSee('marisol@example.test');
});

// ── "Preview the invite email" renders the REAL Mailable, operator-admin gated ─────────────────────
it('previews the real invite email for an operator-admin, role-appropriate, without sending or writing', function () {
    Mail::fake();
    $s = seedScenario(1);
    app(OrganizationRoleProvisioner::class)->provision($s['org']);
    $op = inviteAdmin($s);

    // Owner preview → the real template with the set-password CTA + the owner-portal copy.
    $this->actingAs($op)->get('/app/owners/invite/preview')
        ->assertOk()
        ->assertSee('Set your password')
        ->assertSee('owner portal');

    // Accountant preview → the role-appropriate copy.
    $this->actingAs($op)->get('/app/owners/invite/preview?role=accountant')
        ->assertOk()
        ->assertSee('Set your password')
        ->assertSee('Accountant');

    // A preview NEVER sends and NEVER writes an invite record.
    Mail::assertNothingSent();
    expect(OwnerInvite::query()->count())->toBe(0);
});

it('refuses the invite email preview to a non-admin operator (users.manage gate)', function () {
    $s = seedScenario(1);
    // A back-office member WITHOUT users.manage (Hotel Manager) — passes /app, fails the invite gate.
    $mgr = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($mgr)->get('/app/owners/invite/preview')->assertForbidden();
});

<?php

use App\Models\Membership;
use App\Models\Unit;
use App\Models\UnitRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER ADD-UNIT REQUEST (Phase 2 · Sub-gate 6) — an owner REQUESTS a unit; they never create billable
// inventory. The request persists owner-scoped + self-only, and is visible to the operator (who creates the
// real unit as their own action, not built here).

/** A linked owner-portal user owning unit[$index]. */
if (! function_exists('urOwner')) {
    function urOwner(array $s, int $index, string $name): array
    {
        $unit = $s['units'][$index];
        $owner = ownUnit($unit);
        $user = User::factory()->create(['name' => $name]);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return ['user' => $user, 'owner' => $owner];
    }
}

// ── 1. Submitting persists a PENDING, owner-scoped request (and creates NO unit) ──────────────────
it('files a pending unit request for the owner and creates no unit', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');
    $unitsBefore = Unit::query()->count();

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'notes' => 'Two-bed near the marina.',
    ])->assertRedirect(route('owner.add-unit'))->assertSessionHas('unit_request_notice');

    $request = UnitRequest::query()->where('owner_id', $a['owner']->id)->firstOrFail();
    expect((string) $request->requested_name)->toBe('Villa Marisol')
        ->and((string) $request->requested_kind)->toBe('villa')
        ->and((string) $request->status)->toBe('pending')
        ->and(Unit::query()->count())->toBe($unitsBefore);   // NO inventory created

    // The owner sees it on their own screen.
    $this->actingAs($a['user'])->get('/owner/add-unit')->assertOk()->assertSee('Villa Marisol')->assertSee('Pending review');
});

// ── 2. Owner-scoped — an owner sees only their OWN requests ───────────────────────────────────────
it('shows an owner only their own requests', function () {
    $s = seedScenario(2);
    $a = urOwner($s, 0, 'Amara Okafor');
    $b = urOwner($s, 1, 'Bruno Vale');

    $this->actingAs($a['user'])->post('/owner/add-unit', ['requested_name' => 'Amara Loft', 'requested_kind' => 'apartment']);
    $this->actingAs($b['user'])->post('/owner/add-unit', ['requested_name' => 'Bruno Cabin', 'requested_kind' => 'room']);

    $this->actingAs($a['user'])->get('/owner/add-unit')
        ->assertOk()->assertSee('Amara Loft')->assertDontSee('Bruno Cabin');
});

// ── 3. Operator-VISIBLE, gated on units.view ──────────────────────────────────────────────────────
it('shows the request to an operator and gates the screen on units.view', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');
    $this->actingAs($a['user'])->post('/owner/add-unit', ['requested_name' => 'Villa Operatorseen', 'requested_kind' => 'villa']);

    // A Hotel Manager (has units.view) sees the pending request…
    $manager = operatorUser($s, [], 'Hotel Manager');
    $this->actingAs($manager)->get('/app/unit-requests')
        ->assertOk()->assertSee('Villa Operatorseen')->assertSee('Pending');

    // …an Accountant (no units.view) is refused.
    $accountant = operatorUser($s, [], 'Accountant');
    $this->actingAs($accountant)->get('/app/unit-requests')->assertForbidden();
});

// ── 4. Self-only — filed against the actor; a non-linked user can't submit ────────────────────────
it('is self-only — a non-linked user cannot file a request', function () {
    $s = seedScenario(1);
    urOwner($s, 0, 'Amara Okafor');

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->post('/owner/add-unit', ['requested_name' => 'Ghost Unit', 'requested_kind' => 'room'])
        ->assertNotFound();   // no linked owner → nothing to file against
    expect(UnitRequest::query()->where('requested_name', 'Ghost Unit')->exists())->toBeFalse();

    $this->actingAs($stranger)->get('/owner/add-unit')->assertOk()->assertSee('No owner account is linked');
});

// ── 5. An invalid (non-enum) kind is rejected ─────────────────────────────────────────────────────
it('rejects a requested kind outside the real unit enum', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/add-unit', ['requested_name' => 'Studio X', 'requested_kind' => 'studio'])
        ->assertRedirect()->assertSessionHasErrors('requested_kind');

    expect(UnitRequest::query()->where('owner_id', $a['owner']->id)->exists())->toBeFalse();
});

// ── 6. Help renders honestly — the real message route, no faked ticket queue ──────────────────────
it('renders a static Help page with the real message route and no faked tickets', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->get('/owner/help')
        ->assertOk()
        ->assertSee('Message your manager')          // the REAL owner↔operator inbox
        ->assertSee('Owner FAQs')
        ->assertDontSee('support request')           // no faked ticket queue
        ->assertDontSee('VYR-');                     // no fabricated ticket numbers
});

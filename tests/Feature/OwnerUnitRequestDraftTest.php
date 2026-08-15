<?php

use App\Livewire\Operator\OperatorUnitRequests;
use App\Models\Amenity;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\UnitRequest;
use App\Models\User;
use App\Services\OwnerUnitRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// DO9-2 — save an Add-Unit request as a DRAFT + resume it.
//
// ⚠️ THE LOAD-BEARING PROPERTY IS ISOLATION: a draft is the owner's own unfinished work and must never reach
// the operator. `OperatorUnitRequests` had NO status filter at all, so this is the test that would have caught
// the leak. Per DO3-3's M1 lesson it is asserted against the OPERATOR COMPONENT ITSELF (and the raw scope),
// not through some owner-side path that another filter already protects.
//
// Counts are raw `DB::table` reads so a scoping bug can never hide a row from the assertion meant to catch it.

/** A linked owner (co-owner/primary → CAP_ACTIONS) in the seeded org. */
function draftOwner(array $s, int $unitIndex = 0): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][$unitIndex]->update(['owner_id' => $owner->id]);

    return compact('user', 'owner');
}

/** Raw count of this owner's request rows, by status — independent of every model scope. */
function rawRequestCount(string $ownerId, ?string $status = null): int
{
    $q = DB::table('unit_requests')->where('owner_id', $ownerId)->whereNull('deleted_at');

    return (int) ($status === null ? $q->count() : $q->where('status', $status)->count());
}

// ── 1. A PARTIAL, incomplete wizard saves as a draft — no full-validation error ───────────────────
it('saves an incomplete wizard as a draft without demanding the required fields', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);

    // No name and no kind — exactly what "submit or lose it" used to throw away.
    $this->actingAs($a['user'])
        ->post(route('owner.add-unit.draft'), ['location' => 'Lagos', 'notes' => 'Half-finished thought'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(rawRequestCount($a['owner']->id, 'draft'))->toBe(1)
        ->and(rawRequestCount($a['owner']->id, 'pending'))->toBe(0);

    $row = DB::table('unit_requests')->where('owner_id', $a['owner']->id)->first();
    expect($row->requested_name)->toBeNull()          // the schema genuinely allows it now
        ->and($row->location)->toBe('Lagos')
        ->and($row->notes)->toBe('Half-finished thought');
});

// ── 2. ⚠️⚠️ ISOLATION — a draft NEVER reaches the operator (asserted on the operator's own component) ──
it('keeps a draft out of the operator review queue and its counts', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Secret Half-Finished Villa',
        'requested_kind' => 'villa',
    ])->assertRedirect();

    // A FILED request too, so the test proves the queue still works rather than passing on an empty list.
    $this->actingAs($a['user'])->post(route('owner.add-unit.submit'), [
        'requested_name' => 'Properly Filed Villa',
        'requested_kind' => 'villa',
    ])->assertRedirect();

    expect(rawRequestCount($a['owner']->id, 'draft'))->toBe(1)
        ->and(rawRequestCount($a['owner']->id, 'pending'))->toBe(1);

    // ⚠️ The OPERATOR's own screen, rendered as an operator — the surface that would leak.
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s);

    Livewire::actingAs($operator)->test(OperatorUnitRequests::class)
        ->assertSee('Properly Filed Villa')
        ->assertDontSee('Secret Half-Finished Villa');

    // ⚠️ And the scope directly (DO3-3's M1 lesson — do not let one filter vouch for another).
    $filed = UnitRequest::withoutGlobalScopes()->filed()->pluck('requested_name')->all();
    expect($filed)->toContain('Properly Filed Villa')
        ->and($filed)->not->toContain('Secret Half-Finished Villa');
});

// ── 3. RESUME pre-fills the wizard from the draft (including DO9-1's notes) ───────────────────────
it('pre-fills the wizard from a saved draft when resuming', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);
    $amenity = Amenity::query()->first();

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Villa Marisol',
        'requested_kind' => 'villa',
        'location' => 'Cascais',
        'street_address' => '12 Rua Azul',
        'bedrooms' => 3, 'bathrooms' => 2, 'sleeps' => 6, 'min_nights' => 4,
        'nightly_rate' => '180', 'cleaning_fee' => '45.50',
        'notes' => 'Sea view from the terrace',
        'amenities' => $amenity === null ? [] : [$amenity->id],
    ])->assertRedirect();

    $draft = UnitRequest::query()->where('owner_id', $a['owner']->id)->first();

    $this->actingAs($a['user'])->get(route('owner.add-unit', ['draft' => $draft->id]))
        ->assertOk()
        ->assertSee('Villa Marisol')
        ->assertSee('Cascais')
        ->assertSee('12 Rua Azul')
        ->assertSee('Sea view from the terrace')     // DO9-1's notes survive the round trip
        // ⚠️ Blade's @js emits SINGLE-quoted JS literals, so the pre-filled money must be asserted in that
        // exact form — a double-quoted needle silently misses and the test would pass for the wrong reason.
        ->assertSee("nightly: '180'", false)          // money back as plain typed text, never re-formatted
        ->assertSee("cleaning: '45.50'", false)
        ->assertSee('Nothing has been sent', false)  // and the state is stated honestly
        ->assertSee($draft->id, false);              // the draft_id hidden field, so submit promotes THIS row
});

// ── 4. Resume → SUBMIT promotes the SAME row (a transition, not a duplicate) → operator-visible ───
it('promotes the resumed draft to a filed request instead of creating a second one', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'notes' => 'Sea view',
    ])->assertRedirect();

    $draft = UnitRequest::query()->where('owner_id', $a['owner']->id)->first();

    $this->actingAs($a['user'])->post(route('owner.add-unit.submit'), [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'notes' => 'Sea view',
        'draft_id' => $draft->id,
    ])->assertRedirect();

    // ONE row, promoted — not a draft plus a duplicate request.
    expect(rawRequestCount($a['owner']->id))->toBe(1)
        ->and(rawRequestCount($a['owner']->id, 'draft'))->toBe(0)
        ->and(rawRequestCount($a['owner']->id, 'pending'))->toBe(1)
        ->and(DB::table('unit_requests')->where('id', $draft->id)->value('status'))->toBe('pending')
        ->and(DB::table('unit_requests')->where('id', $draft->id)->value('notes'))->toBe('Sea view');

    // Now — and only now — the operator sees it.
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s);
    Livewire::actingAs($operator)->test(OperatorUnitRequests::class)->assertSee('Villa Marisol');
});

// ── 5. ⚠️ SUBMIT STILL DEMANDS THE FULL SET — an incomplete draft cannot slip through ─────────────
it('rejects a submit of an incomplete draft, so the relaxation is draft-only', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), ['location' => 'Lagos'])->assertRedirect();
    $draft = UnitRequest::query()->where('owner_id', $a['owner']->id)->first();

    // Resume + submit while still missing the required name → REJECTED at submit.
    $this->actingAs($a['user'])
        ->post(route('owner.add-unit.submit'), ['location' => 'Lagos', 'draft_id' => $draft->id])
        ->assertSessionHasErrors('requested_name');

    // Still a draft; nothing filed, nothing on the operator's desk.
    expect(rawRequestCount($a['owner']->id, 'draft'))->toBe(1)
        ->and(rawRequestCount($a['owner']->id, 'pending'))->toBe(0);
});

// ── 6. 🔒 RBAC — an owner cannot resume or overwrite ANOTHER owner's draft ────────────────────────
it('refuses to resume or write another owner’s draft', function () {
    $s = seedScenario(2);
    $a = draftOwner($s, 0);
    $b = draftOwner($s, 1);

    $this->actingAs($b['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Owner B private draft', 'requested_kind' => 'villa',
    ])->assertRedirect();
    $bDraft = UnitRequest::withoutGlobalScopes()->where('owner_id', $b['owner']->id)->first();

    // Owner A resuming B's draft id → the wizard renders empty, never B's content.
    $this->actingAs($a['user'])->get(route('owner.add-unit', ['draft' => $bDraft->id]))
        ->assertOk()
        ->assertDontSee('Owner B private draft');

    // Owner A submitting WITH B's draft id → files A's own new request; B's draft is untouched.
    $this->actingAs($a['user'])->post(route('owner.add-unit.submit'), [
        'requested_name' => 'Owner A request', 'requested_kind' => 'villa', 'draft_id' => $bDraft->id,
    ])->assertRedirect();

    expect(DB::table('unit_requests')->where('id', $bDraft->id)->value('status'))->toBe('draft')
        ->and(DB::table('unit_requests')->where('id', $bDraft->id)->value('requested_name'))->toBe('Owner B private draft')
        ->and(DB::table('unit_requests')->where('id', $bDraft->id)->value('owner_id'))->toBe($b['owner']->id)
        ->and(rawRequestCount($a['owner']->id, 'pending'))->toBe(1);
});

// ── 7. ⚠️ A draft id cannot be used to overwrite an ALREADY-FILED request ─────────────────────────
// findDraft() pins status='draft' — without it, a resumed submit could be aimed at a request the operator
// has already seen (or approved) and rewrite it from the owner's side.
it('cannot rewrite a filed request by passing its id as a draft', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.submit'), [
        'requested_name' => 'Already filed', 'requested_kind' => 'villa',
    ])->assertRedirect();
    $filed = UnitRequest::query()->where('owner_id', $a['owner']->id)->first();

    $this->actingAs($a['user'])->post(route('owner.add-unit.submit'), [
        'requested_name' => 'Rewritten', 'requested_kind' => 'room', 'draft_id' => $filed->id,
    ])->assertRedirect();

    expect(DB::table('unit_requests')->where('id', $filed->id)->value('requested_name'))->toBe('Already filed')
        ->and(rawRequestCount($a['owner']->id))->toBe(2);   // a NEW request, not a rewrite
});

// ── 8. The owner's own list shows the draft, labelled honestly, and offers Continue ───────────────
it('shows the owner their draft as not-sent, with a resume link', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa',
    ])->assertRedirect();
    $draft = UnitRequest::query()->where('owner_id', $a['owner']->id)->first();

    $this->actingAs($a['user'])->get(route('owner.add-unit'))
        ->assertOk()
        ->assertSee('Villa Marisol')
        ->assertSee('Draft — not sent', false)   // ⚠️ NOT "Pending review" — the default arm would have lied
        ->assertSee('Continue')
        ->assertSee(route('owner.add-unit', ['draft' => $draft->id]), false);
});

// ── 9. An unnamed draft is labelled, not rendered blank ───────────────────────────────────────────
it('labels a nameless draft rather than showing an empty row', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), ['location' => 'Lagos'])->assertRedirect();

    $this->actingAs($a['user'])->get(route('owner.add-unit'))->assertOk()->assertSee('Untitled draft');
});

// ── 10. Re-saving a draft UPDATES it (one row) and syncs amenities down as well as up ─────────────
it('updates the same draft row on a second save and reflects removed amenities', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);
    $amenity = Amenity::query()->first();

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'First pass', 'requested_kind' => 'villa',
        'amenities' => $amenity === null ? [] : [$amenity->id],
    ])->assertRedirect();
    $draft = UnitRequest::query()->where('owner_id', $a['owner']->id)->first();

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Second pass', 'requested_kind' => 'room', 'draft_id' => $draft->id,
    ])->assertRedirect();

    expect(rawRequestCount($a['owner']->id))->toBe(1)
        ->and(DB::table('unit_requests')->where('id', $draft->id)->value('requested_name'))->toBe('Second pass')
        ->and(DB::table('unit_requests')->where('id', $draft->id)->value('requested_kind'))->toBe('room')
        ->and($draft->fresh()->amenities()->count())->toBe(0);   // unticked chips really come off
});

// ── 11. ⚠️ The relaxation is REQUIREDNESS only — a draft must still be well-formed ────────────────
it('still enforces format and bound rules on a draft save', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Fine', 'requested_kind' => 'not_a_real_kind',
    ])->assertSessionHasErrors('requested_kind');

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'notes' => str_repeat('x', 2001),
    ])->assertSessionHasErrors('notes');

    expect(rawRequestCount($a['owner']->id))->toBe(0);   // neither malformed draft was stored
});

// ── 12. The operator cannot ACTION a draft either (the guard beneath the list) ────────────────────
it('refuses to approve or decline a draft at the service layer', function () {
    $s = seedScenario(1);
    $a = draftOwner($s);
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Draft', 'requested_kind' => 'villa',
    ])->assertRedirect();
    $draft = UnitRequest::withoutGlobalScopes()->where('owner_id', $a['owner']->id)->first();

    $service = app(OwnerUnitRequestService::class);
    expect(fn () => $service->decline($draft, $operator, null))->toThrow(RuntimeException::class);

    expect(DB::table('unit_requests')->where('id', $draft->id)->value('status'))->toBe('draft');
});

// ── 13. The filed-status set is pinned, and the operator query reads it ───────────────────────────
it('pins the filed statuses and keeps draft out of them', function () {
    // ⚠️ This test owns ONE fact: a DRAFT is never filed. The exact contents of the set are pinned once, in
    // `OwnerUnitRequestWithdrawTest` — pinning the same constant in two files means every legitimate addition
    // (DO9-3's `withdrawn` was the first) reds a test that has no opinion about it (§N.8f, applied to guards).
    expect(UnitRequest::FILED_STATUSES)->toContain('pending')
        ->and(UnitRequest::FILED_STATUSES)->not->toContain('draft');

    // The operator screen goes through the scope rather than writing its own status list.
    $source = file_get_contents(app_path('Livewire/Operator/OperatorUnitRequests.php'));
    expect($source)->toContain('->filed()');
});

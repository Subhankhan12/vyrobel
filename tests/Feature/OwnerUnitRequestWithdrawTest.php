<?php

use App\Livewire\Operator\OperatorUnitRequests;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\UnitRequest;
use App\Models\User;
use App\Services\OwnerUnitRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// DO9-3 — the owner withdraws a request they already filed (pending → withdrawn).
//
// ⚠️ THE LOAD-BEARING PROPERTY IS THE RACE: the owner's page is a SNAPSHOT, so a Withdraw click can arrive
// after the operator has already approved and created a real unit. Retracting then would tell the owner their
// request was cancelled while a live, bookable unit existed for it. Tests 3–5 force exactly that.
//
// Statuses are read with raw `DB::table` queries so a scoping bug can never hide the row the assertion is
// meant to catch, and the state-gate is asserted at the SERVICE as well as through HTTP (DO3-3's M1 lesson:
// a non-HTTP caller must be gated too).

/** A linked owner (co-owner/primary → CAP_ACTIONS) in the seeded org. */
function withdrawOwner(array $s, int $unitIndex = 0): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][$unitIndex]->update(['owner_id' => $owner->id]);

    return compact('user', 'owner');
}

/**
 * File a real PENDING request through the real submit path (never a hand-built row).
 *
 * ⚠️ THE TRAILING GET IS LOAD-BEARING, NOT TIDINESS. Submitting flashes `unit_request_done`, and the very
 * next render shows the "done" CONFIRMATION screen instead of the wizard — so a test that renders the page
 * immediately after filing is looking at a branch with no requests list at all, and any assertion about a
 * status chip fails for a reason that has nothing to do with the thing under test. Consuming the flash here
 * leaves every caller on the ordinary wizard page (§N.27.6 — a fixture must construct its case).
 */
function filedRequest(array $a, string $name = 'Villa Marisol'): UnitRequest
{
    test()->actingAs($a['user'])->post(route('owner.add-unit.submit'), [
        'requested_name' => $name, 'requested_kind' => 'villa',
    ])->assertRedirect();

    test()->actingAs($a['user'])->get(route('owner.add-unit'))->assertOk();   // burn the done-flash

    $row = UnitRequest::withoutGlobalScopes()->where('owner_id', $a['owner']->id)->where('requested_name', $name)->first();
    expect($row->status)->toBe('pending');

    return $row;
}

function rawStatus(string $id): ?string
{
    return DB::table('unit_requests')->where('id', $id)->value('status');
}

// ── 1. A pending request withdraws, and the row SURVIVES (a soft transition, not a delete) ───────
it('withdraws a pending request without deleting it', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);
    $req = filedRequest($a);

    $this->actingAs($a['user'])
        ->post(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]))
        ->assertRedirect();

    expect(rawStatus($req->id))->toBe('withdrawn')
        // ⚠️ The record stays — the audit trail must still show what was asked for and that it was retracted.
        ->and(DB::table('unit_requests')->where('id', $req->id)->whereNull('deleted_at')->count())->toBe(1)
        ->and(DB::table('unit_requests')->where('id', $req->id)->value('requested_name'))->toBe('Villa Marisol');

    expect(DB::table('audit_events')->where('action', 'owner.unit_request_withdrawn')->count())->toBe(1);
});

// ── 2. It leaves the operator's ACTIONABLE queue (visible, labelled, not actionable) ─────────────
it('drops a withdrawn request out of the operator’s pending queue while keeping the record', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);
    $req = filedRequest($a);
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s);

    // Before: it is genuinely on the operator's desk and counted.
    Livewire::actingAs($operator)->test(OperatorUnitRequests::class)
        ->assertSee('Villa Marisol')
        ->assertViewHas('pending', 1);

    $this->actingAs($a['user'])->post(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]))->assertRedirect();

    // After: still visible (it WAS filed — hiding it would delete a row from under a mid-review operator),
    // but out of the pending count and carrying no actions.
    Livewire::actingAs($operator)->test(OperatorUnitRequests::class)
        ->assertSee('Villa Marisol')
        ->assertSee('Withdrawn')
        ->assertViewHas('pending', 0);
});

// ── 3. ⚠️⚠️ THE RACE — the operator got there first; withdraw is REFUSED, nothing is retracted ───
it('refuses to withdraw a request the operator has already approved', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);
    $req = filedRequest($a);

    // The operator approved while the owner's page still showed "Pending review".
    DB::table('unit_requests')->where('id', $req->id)->update(['status' => 'approved']);

    $this->actingAs($a['user'])
        ->post(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]))
        ->assertRedirect()
        ->assertSessionHas('unit_request_withdraw_error');

    // ⚠️ Unchanged. A retraction here would cancel a request that already has a live unit behind it.
    expect(rawStatus($req->id))->toBe('approved');

    // ⚠️ PER-LAYER — the SERVICE refuses on its own, so a non-HTTP caller is gated too (DO3-3's M1).
    expect(fn () => app(OwnerUnitRequestService::class)->withdraw($req->fresh()))->toThrow(RuntimeException::class);
    expect(rawStatus($req->id))->toBe('approved');
});

// ── 3b. ⚠️⚠️ THE ACTUAL RACE SHAPE — a STALE in-memory model, overtaken between load and act ─────
// Test 3 proves the gate but NOT that it reads the LOCKED row: over HTTP the controller re-loads the request
// fresh, so the caller's copy is never stale and a gate reading `$request->status` would pass it too.
// The real race is: something loads the model while it is pending, the operator approves microseconds later,
// and the withdraw then acts on a copy that no longer reflects the database. Only re-reading UNDER THE LOCK
// catches that — so this is the test that pins the re-check itself.
it('decides on the locked row, not on the caller’s stale copy', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);
    $req = filedRequest($a);

    // Loaded while pending — exactly what a controller holds a moment before acting.
    $stale = UnitRequest::withoutGlobalScopes()->findOrFail($req->id);
    expect($stale->status)->toBe('pending');

    // The operator gets there first, after the load.
    DB::table('unit_requests')->where('id', $req->id)->update(['status' => 'approved']);
    expect($stale->status)->toBe('pending');   // the in-memory copy still says otherwise

    expect(fn () => app(OwnerUnitRequestService::class)->withdraw($stale))->toThrow(RuntimeException::class);

    // ⚠️ Not retracted. Withdrawing here would cancel a request that already has a live unit behind it.
    expect(rawStatus($req->id))->toBe('approved')
        ->and(DB::table('audit_events')->where('action', 'owner.unit_request_withdrawn')->count())->toBe(0);
});

// ── 4. ⚠️ The refusal names the REAL winning state, read from the row ────────────────────────────
it('tells the owner which state actually won the race', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);

    foreach (['approved' => 'already approved', 'declined' => 'already reviewed'] as $status => $needle) {
        $req = filedRequest($a, 'Villa '.$status);
        DB::table('unit_requests')->where('id', $req->id)->update(['status' => $status]);

        $this->actingAs($a['user'])
            ->post(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]))
            ->assertRedirect();

        expect(session('unit_request_withdraw_error'))->toContain($needle);
        expect(rawStatus($req->id))->toBe($status);
    }
});

// ── 5. ⚠️ STATE-GATED — every non-pending state is refused, server-side ──────────────────────────
it('refuses to withdraw anything that is not pending', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);
    $req = filedRequest($a);
    $svc = app(OwnerUnitRequestService::class);

    foreach (['approved', 'declined', 'withdrawn', 'draft'] as $status) {
        DB::table('unit_requests')->where('id', $req->id)->update(['status' => $status]);

        // Server-authoritative: the POST is refused even though the UI would never have shown the control.
        $this->actingAs($a['user'])
            ->post(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]))
            ->assertRedirect()
            ->assertSessionHas('unit_request_withdraw_error');

        expect(fn () => $svc->withdraw($req->fresh()))->toThrow(RuntimeException::class);
        expect(rawStatus($req->id))->toBe($status);   // untouched in every case
    }
});

// ── 6. A second withdraw is refused, not a silent no-op that reports success ─────────────────────
it('refuses a second withdraw of an already-withdrawn request', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);
    $req = filedRequest($a);

    $this->actingAs($a['user'])->post(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]))
        ->assertRedirect()->assertSessionHas('unit_request_withdrawn');

    $this->actingAs($a['user'])->post(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]))
        ->assertRedirect()->assertSessionHas('unit_request_withdraw_error');

    expect(rawStatus($req->id))->toBe('withdrawn')
        ->and(DB::table('audit_events')->where('action', 'owner.unit_request_withdrawn')->count())->toBe(1);
});

// ── 7. 🔒 RBAC — an owner cannot withdraw ANOTHER owner's request ────────────────────────────────
it('404s a cross-owner withdraw and leaves the request pending', function () {
    $s = seedScenario(2);
    $a = withdrawOwner($s, 0);
    $b = withdrawOwner($s, 1);
    $bReq = filedRequest($b, 'Owner B villa');

    $this->actingAs($a['user'])
        ->post(route('owner.add-unit.withdraw', ['unitRequest' => $bReq->id]))
        ->assertNotFound();

    // Raw-query: B's request is untouched (a scoped read could not prove this).
    expect(rawStatus($bReq->id))->toBe('pending')
        ->and(DB::table('audit_events')->where('action', 'owner.unit_request_withdrawn')->count())->toBe(0);
});

// ── 8. ⚠️ THE LABEL — a withdrawn request must not read as "Pending review" ──────────────────────
it('labels a withdrawn request as Withdrawn and offers no further action', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);
    $req = filedRequest($a);

    // ⚠️ THE NEEDLE IS THE STATUS CHIP, NOT THE BARE WORDS. The wizard's live-preview card ALWAYS carries a
    // "Pending review" badge (it describes the unit being drafted, not this request), so a page-wide
    // assertDontSee('Pending review') can never pass and would have failed for a reason unrelated to the
    // label under test. The chip's own closing markup pins the request row's label exactly.
    $chip = fn (string $label): string => '<span class="vy-dot"></span>'.$label.'</span>';

    // Pending → the control is offered and the chip says so.
    $this->actingAs($a['user'])->get(route('owner.add-unit'))
        ->assertOk()
        ->assertSee('vy-au-withdraw', false)
        ->assertSee($chip('Pending review'), false);

    $this->actingAs($a['user'])->post(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]))->assertRedirect();

    // Withdrawn → labelled honestly, and the control is gone.
    $this->actingAs($a['user'])->get(route('owner.add-unit'))
        ->assertOk()
        ->assertSee($chip('Withdrawn'), false)
        ->assertDontSee($chip('Pending review'), false)                              // ⚠️ the default-arm mislabel
        ->assertDontSee(route('owner.add-unit.withdraw', ['unitRequest' => $req->id]), false);
});

// ── 9. A DRAFT is never offered a Withdraw control (it was never filed) ──────────────────────────
it('offers no withdraw on a draft, which is resumed rather than retracted', function () {
    $s = seedScenario(1);
    $a = withdrawOwner($s);

    $this->actingAs($a['user'])->post(route('owner.add-unit.draft'), [
        'requested_name' => 'Half-finished', 'requested_kind' => 'villa',
    ])->assertRedirect();

    // ⚠️ THE ABSENCE IS ASSERTED ON THE CONTROL, NOT THE WORD. The shared owner layout carries a CSS COMMENT
    // explaining the `.vy-au-withdraw` rule — and CSS comments SHIP (§N.15.3), so assertDontSee('Withdraw')
    // fails on prose about the button rather than on the button. Read what RENDERS, not what is written
    // about it.
    $this->actingAs($a['user'])->get(route('owner.add-unit'))
        ->assertOk()
        ->assertSee('Draft — not sent', false)
        ->assertSee('Continue')
        ->assertDontSee('class="vy-au-withdraw"', false)
        ->assertDontSee(route('owner.add-unit.withdraw', ['unitRequest' => 'x']), false);
});

// ── 10. The withdrawn state is filed (visible to the operator), and the set is pinned ────────────
it('pins withdrawn as a filed status and keeps draft out', function () {
    expect(UnitRequest::FILED_STATUSES)->toBe(['pending', 'approved', 'declined', 'withdrawn'])
        ->and(UnitRequest::FILED_STATUSES)->not->toContain('draft');

    // The decision lives under a lock in the service, not in the controller that happens to call it.
    $source = file_get_contents(app_path('Services/OwnerUnitRequestService.php'));
    expect($source)->toContain('lockForUpdate');
});

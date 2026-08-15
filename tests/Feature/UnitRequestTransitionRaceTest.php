<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\Unit;
use App\Models\UnitRequest;
use App\Models\User;
use App\Services\OwnerUnitRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// DO9-3b — the REVERSE race. DO9-3 made withdraw() safe from the owner's side; approve()/decline() checked
// `pending` on the CALLER'S COPY, outside any lock (decline had no transaction at all), so an operator
// approve already in flight could land on a request the owner had just withdrawn — and create a REAL,
// BOOKABLE UNIT for a retracted request. That side-effect is what makes this worse than a stale status.
//
// ⚠️ EVERY RACE TEST HERE USES A GENUINELY STALE IN-MEMORY MODEL, and that is deliberate. DO9-3's mutation
// "read $request->status instead of $locked->status" passed ALL its tests, because over HTTP the controller
// re-loads the row fresh so the caller's copy is never stale. A test that goes through HTTP proves the GATE
// but not that it reads the LOCKED ROW. These load the model while pending, change the database underneath,
// and then act — the only shape that can tell the two apart.

/** An operator + an owner with one PENDING request, plus the property/room type an approval needs. */
function raceFixture(): array
{
    $s = seedScenario(1);
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s);

    $ownerUser = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $ownerUser->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $ownerUser->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);

    $request = UnitRequest::create([
        'organization_id' => $s['org']->id,
        'owner_id' => $owner->id,
        'requested_name' => 'Casa Contested',
        'requested_kind' => 'villa',
        'bedrooms' => 2, 'bathrooms' => 1, 'sleeps' => 4,
        'status' => 'pending',
    ]);

    expect($request->status)->toBe('pending');   // the fixture CONSTRUCTED its case (§N.27.6)

    return [
        's' => $s, 'operator' => $operator, 'owner' => $owner, 'ownerUser' => $ownerUser,
        'request' => $request,
        'propertyId' => (string) $s['property']->id,
        'roomTypeId' => (string) $s['units'][0]->room_type_id,
    ];
}

/** Units for this property, counted raw — independent of every scope, so an orphan cannot hide. */
function rawUnitCount(string $propertyId): int
{
    return (int) DB::table('units')->where('property_id', $propertyId)->whereNull('deleted_at')->count();
}

// ── 1. ⚠️⚠️ THE REVERSE RACE — approve lands after a withdrawal → REFUSED, and NO UNIT IS CREATED ──
it('refuses an in-flight approve of a request the owner withdrew, and creates no unit', function () {
    $f = raceFixture();
    $before = rawUnitCount($f['propertyId']);

    // The operator loaded the request while it was pending — this is their in-memory copy.
    $stale = UnitRequest::withoutGlobalScopes()->findOrFail($f['request']->id);
    expect($stale->status)->toBe('pending');

    // The owner withdraws in the meantime. The operator's copy still says "pending".
    app(OwnerUnitRequestService::class)->withdraw($f['request']->fresh());
    expect(DB::table('unit_requests')->where('id', $f['request']->id)->value('status'))->toBe('withdrawn')
        ->and($stale->status)->toBe('pending');

    expect(fn () => app(OwnerUnitRequestService::class)
        ->approve($stale, $f['operator'], $f['propertyId'], $f['roomTypeId']))
        ->toThrow(RuntimeException::class);

    // ⚠️ THE LOAD-BEARING ASSERTION: no unit exists for a retracted request, and the status held.
    expect(rawUnitCount($f['propertyId']))->toBe($before)
        ->and(DB::table('unit_requests')->where('id', $f['request']->id)->value('status'))->toBe('withdrawn')
        ->and(DB::table('unit_requests')->where('id', $f['request']->id)->value('created_unit_id'))->toBeNull();
});

// ── 2. ⚠️ The same for DECLINE — a withdrawn request cannot be declined out from under the owner ──
it('refuses an in-flight decline of a request the owner withdrew', function () {
    $f = raceFixture();

    $stale = UnitRequest::withoutGlobalScopes()->findOrFail($f['request']->id);
    app(OwnerUnitRequestService::class)->withdraw($f['request']->fresh());

    expect(fn () => app(OwnerUnitRequestService::class)->decline($stale, $f['operator'], 'too late'))
        ->toThrow(RuntimeException::class);

    expect(DB::table('unit_requests')->where('id', $f['request']->id)->value('status'))->toBe('withdrawn')
        ->and(DB::table('unit_requests')->where('id', $f['request']->id)->value('review_note'))->toBeNull();
});

// ── 3. ⚠️ TWO OPERATORS — the second approve is refused, and only ONE unit exists ────────────────
it('creates exactly one unit when two approves race the same request', function () {
    $f = raceFixture();
    $before = rawUnitCount($f['propertyId']);
    $svc = app(OwnerUnitRequestService::class);

    // Both operators hold a copy loaded while the request was pending.
    $staleA = UnitRequest::withoutGlobalScopes()->findOrFail($f['request']->id);
    $staleB = UnitRequest::withoutGlobalScopes()->findOrFail($f['request']->id);

    $svc->approve($staleA, $f['operator'], $f['propertyId'], $f['roomTypeId']);

    expect(fn () => $svc->approve($staleB, $f['operator'], $f['propertyId'], $f['roomTypeId']))
        ->toThrow(RuntimeException::class);

    // ⚠️ ONE unit, not two — a second would be duplicate inventory billed to the operator.
    expect(rawUnitCount($f['propertyId']))->toBe($before + 1);
});

// ── 4. ⚠️ A decline racing an approve — whichever confirms `pending` first wins, once ─────────────
it('refuses a decline once the request has already been approved', function () {
    $f = raceFixture();
    $svc = app(OwnerUnitRequestService::class);

    $stale = UnitRequest::withoutGlobalScopes()->findOrFail($f['request']->id);
    $svc->approve($f['request']->fresh(), $f['operator'], $f['propertyId'], $f['roomTypeId']);

    expect(fn () => $svc->decline($stale, $f['operator'], 'changed my mind'))->toThrow(RuntimeException::class);

    expect(DB::table('unit_requests')->where('id', $f['request']->id)->value('status'))->toBe('approved');
});

// ── 5. THE NORMAL CASE IS UNCHANGED — approve still creates the unit and stamps the request ──────
it('still approves a pending request into a real unit, exactly as before', function () {
    $f = raceFixture();
    $before = rawUnitCount($f['propertyId']);

    $unit = app(OwnerUnitRequestService::class)
        ->approve($f['request'], $f['operator'], $f['propertyId'], $f['roomTypeId'], 'looks good');

    $row = DB::table('unit_requests')->where('id', $f['request']->id)->first();

    expect($unit)->toBeInstanceOf(Unit::class)
        ->and($unit->name)->toBe('Casa Contested')
        ->and((string) $unit->owner_id)->toBe((string) $f['owner']->id)
        ->and($unit->max_occupancy)->toBe(4)                 // built from the request's own sleeps
        ->and(rawUnitCount($f['propertyId']))->toBe($before + 1)
        ->and($row->status)->toBe('approved')
        ->and($row->created_unit_id)->toBe((string) $unit->id)
        ->and($row->review_note)->toBe('looks good')
        ->and($row->reviewed_by)->toBe((string) $f['operator']->id)
        ->and($row->reviewed_at)->not->toBeNull();
});

// ── 6. THE NORMAL CASE IS UNCHANGED — decline still stamps the request and creates nothing ───────
it('still declines a pending request without creating inventory', function () {
    $f = raceFixture();
    $before = rawUnitCount($f['propertyId']);

    app(OwnerUnitRequestService::class)->decline($f['request'], $f['operator'], 'not this year');

    $row = DB::table('unit_requests')->where('id', $f['request']->id)->first();

    expect($row->status)->toBe('declined')
        ->and($row->review_note)->toBe('not this year')
        ->and($row->reviewed_by)->toBe((string) $f['operator']->id)
        ->and(rawUnitCount($f['propertyId']))->toBe($before);   // no inventory from a decline
});

// ── 7. A DRAFT can never be approved or declined (it was never filed) ────────────────────────────
it('refuses to approve or decline a draft', function () {
    $f = raceFixture();
    $svc = app(OwnerUnitRequestService::class);
    DB::table('unit_requests')->where('id', $f['request']->id)->update(['status' => 'draft']);
    $before = rawUnitCount($f['propertyId']);

    expect(fn () => $svc->approve($f['request']->fresh(), $f['operator'], $f['propertyId'], $f['roomTypeId']))
        ->toThrow(RuntimeException::class);
    expect(fn () => $svc->decline($f['request']->fresh(), $f['operator']))->toThrow(RuntimeException::class);

    expect(rawUnitCount($f['propertyId']))->toBe($before)
        ->and(DB::table('unit_requests')->where('id', $f['request']->id)->value('status'))->toBe('draft');
});

// ── 8. ⚠️ The gate is ONE mechanism, and it reads the LOCKED row ─────────────────────────────────
it('routes all three transitions through one locked, re-checked helper', function () {
    // ⚠️ TOKENISED, COMMENTS STRIPPED (§N.27.5). A naive substr_count over the raw file counts this
    // service's own DOCBLOCKS — which explain the lock at length — and reports 3 locks where the code has
    // one. A source guard must read what RUNS, not what is written about it. (Caught by this very test.)
    $code = '';
    foreach (token_get_all(file_get_contents(app_path('Services/OwnerUnitRequestService.php'))) as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= $token[1];
        } else {
            $code .= $token;
        }
    }

    // One lock + one re-check, shared — not three drifting copies (§N.8f).
    expect(substr_count($code, 'lockForUpdate'))->toBe(1)
        ->and(substr_count($code, 'transitionFromPending('))->toBe(4);   // 1 definition + 3 callers

    // ⚠️ The re-check reads the LOCKED row. Reading the caller's copy is the mutation that passed every
    // test in DO9-3 — pinning the expression keeps the distinction from silently reverting.
    expect($code)->toContain("\$locked->status !== 'pending'")
        ->and($code)->not->toContain("\$request->status !== 'pending'");
});

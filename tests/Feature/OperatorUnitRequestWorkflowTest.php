<?php

use App\Models\Owner;
use App\Models\Unit;
use App\Models\UnitRequest;
use App\Notifications\Owner\UnitRequestReviewedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// #104 GATE 1 — the operator Unit-Requests review is now OPERATIONAL end-to-end: Approve creates the REAL unit
// (the app's first unit-creation writer), Decline notifies the owner. Both gated `can:units.manage` (#20/#30),
// audited, and the owner always learns the outcome (the feedback loop the audit found missing).

/** A pending add-unit request filed by a real owner in the scenario's org. */
function pendingUnitRequest(array $s): UnitRequest
{
    $ownerUser = ownerRoleUser($s);                       // creates an Owner linked to a user, owning units[0]
    $owner = Owner::query()->where('user_id', $ownerUser->id)->firstOrFail();

    return UnitRequest::create([
        'organization_id' => $s['org']->id,
        'owner_id' => $owner->id,
        'requested_name' => 'Sea View 2B',
        'requested_kind' => 'apartment',
        'sleeps' => 4,
        'requested_rate_minor' => 12000,
        'currency' => 'EUR',
        'status' => 'pending',
    ]);
}

it('approves a request by creating the real unit, assigning the owner, and notifying them', function () {
    Notification::fake();
    $s = seedScenario(1);
    $req = pendingUnitRequest($s);
    $ownerUser = $req->owner->user;
    $manager = operatorUser($s, [], 'Hotel Manager');    // holds units.manage

    $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->post(route('app.unit-requests.approve', $req->id), [
            'property_id' => $s['property']->id,
            'room_type_id' => $s['roomType']->id,
            'note' => 'Welcome aboard',
        ])->assertRedirect(route('app.unit-requests'));

    // A REAL unit now exists, under the chosen property/room-type, assigned to the requesting owner, active.
    $unit = Unit::query()->where('name', 'Sea View 2B')->first();
    expect($unit)->not->toBeNull()
        ->and($unit->property_id)->toBe($s['property']->id)
        ->and($unit->room_type_id)->toBe($s['roomType']->id)
        ->and((string) $unit->owner_id)->toBe((string) $req->owner_id)
        ->and($unit->unit_kind)->toBe('apartment')
        ->and($unit->max_occupancy)->toBe(4)
        ->and((bool) $unit->is_active)->toBeTrue();

    // The request is stamped fulfilled + linked to what it became; the owner is notified.
    $req->refresh();
    expect($req->status)->toBe('approved')
        ->and((string) $req->created_unit_id)->toBe((string) $unit->id)
        ->and((string) $req->reviewed_by)->toBe((string) $manager->id);
    Notification::assertSentTo($ownerUser, UnitRequestReviewedNotification::class);
});

it('declines a request without creating a unit, and notifies the owner', function () {
    Notification::fake();
    $s = seedScenario(1);
    $req = pendingUnitRequest($s);
    $ownerUser = $req->owner->user;
    $manager = operatorUser($s, [], 'Hotel Manager');
    $unitsBefore = Unit::query()->count();

    $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->post(route('app.unit-requests.decline', $req->id), ['note' => 'Not a fit right now'])
        ->assertRedirect(route('app.unit-requests'));

    expect(Unit::query()->count())->toBe($unitsBefore);   // NO inventory created
    $req->refresh();
    expect($req->status)->toBe('declined')
        ->and($req->review_note)->toBe('Not a fit right now')
        ->and($req->created_unit_id)->toBeNull();
    Notification::assertSentTo($ownerUser, UnitRequestReviewedNotification::class);
});

it('403s a role without units.manage and never creates a unit', function () {
    $s = seedScenario(1);
    $req = pendingUnitRequest($s);
    $frontDesk = operatorUser($s, [], 'Front Desk');       // lacks units.manage
    expect($frontDesk->can('units.manage'))->toBeFalse();

    $this->actingAs($frontDesk)->withSession(['current_property_id' => $s['property']->id])
        ->post(route('app.unit-requests.approve', $req->id), [
            'property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id,
        ])->assertForbidden();

    expect(Unit::query()->where('name', 'Sea View 2B')->exists())->toBeFalse();
    expect($req->fresh()->status)->toBe('pending');
});

it('refuses to re-review an already-reviewed request (bounded transition)', function () {
    $s = seedScenario(1);
    $req = pendingUnitRequest($s);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $session = ['current_property_id' => $s['property']->id];

    $this->actingAs($manager)->withSession($session)
        ->post(route('app.unit-requests.decline', $req->id), [])->assertRedirect();
    expect($req->fresh()->status)->toBe('declined');

    // A second review (approve) on the now-declined request is refused — status is unchanged, no unit created.
    $this->actingAs($manager)->withSession($session)
        ->post(route('app.unit-requests.approve', $req->id), [
            'property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id,
        ])->assertRedirect();
    expect($req->fresh()->status)->toBe('declined')
        ->and(Unit::query()->where('name', 'Sea View 2B')->exists())->toBeFalse();
});

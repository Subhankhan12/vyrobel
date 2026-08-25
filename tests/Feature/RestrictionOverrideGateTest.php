<?php

declare(strict_types=1);

use App\Exceptions\Booking\RestrictionOverrideForbiddenException;
use App\Exceptions\Booking\RestrictionsViolationException;
use App\Models\AuditEvent;
use App\Models\Restriction;
use App\Services\AssignmentService;
use App\Support\Booking\RestrictionOverrideReason;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ THE ACCEPTANCE TEST FOR THIS PASS: removing a role's `reservations.restriction_override` grant must
 * turn a test RED. Last pass the permission was declared and consulted NOWHERE, so that mutation broke
 * nothing and the harness correctly reported "NO TEST COVERS THIS".
 *
 * ⚠️ The gate lives where the override is CONSUMED (`AssignmentService::occupy()`), not on a route — so it
 * covers the operator create path, group bookings AND the public storefront at once.
 */
function gateRestrict(array $s, string $date, array $flags): void
{
    Restriction::query()->updateOrCreate(
        ['room_type_id' => (string) $s['roomType']->id, 'rate_plan_id' => null, 'date' => $date],
        $flags + ['organization_id' => (string) $s['org']->id],
    );
}

function gateBook(array $s, string $in, string $out, array $override = [])
{
    return app(AssignmentService::class)->createBooking([
        'organization_id' => (string) $s['org']->id,
        'property_id' => (string) $s['property']->id,
        'guest_id' => (string) $s['guest']->id,
        'currency' => 'EUR',
        'check_in' => $in,
        'check_out' => $out,
        'adults' => 1,
    ], [[
        'room_type_id' => (string) $s['roomType']->id,
        'unit_id' => (string) $s['units'][0]->id,
        'rate_plan_id' => (string) $s['ratePlan']->id,
        'check_in' => $in,
        'check_out' => $out,
        'price_minor' => 10000,
    ] + $override]);
}

it('⚠️⚠️ THE ACCEPTANCE TEST — a permitted role CAN override', function () {
    $s = seedScenario(1);
    gateRestrict($s, '2026-09-01', ['min_stay' => 7]);

    // ⚠️ Front Desk holds the grant. If that grant is removed, this test must go RED — that is the whole
    //    point of this pass, and it did not happen last pass.
    $this->actingAs(operatorUser($s, [], 'Front Desk'));

    expect(gateBook($s, '2026-09-01', '2026-09-02', [
        'restriction_override_reason' => RestrictionOverrideReason::FILLS_A_GAP,
    ])->exists)->toBeTrue();
});

it('⚠️⚠️ A ROLE WITHOUT THE GRANT CANNOT override — Reservation Agent can create but not finish', function () {
    $s = seedScenario(1);
    gateRestrict($s, '2026-09-01', ['min_stay' => 7]);

    $this->actingAs(operatorUser($s, [], 'Reservation Agent'));

    expect(fn () => gateBook($s, '2026-09-01', '2026-09-02', [
        'restriction_override_reason' => RestrictionOverrideReason::FILLS_A_GAP,
    ]))->toThrow(RestrictionOverrideForbiddenException::class);
});

it('⚠️⚠️ A GUEST CAN NEVER OVERRIDE — even with a crafted override reason, and no audit row is written', function () {
    $s = seedScenario(1);
    gateRestrict($s, '2026-09-01', ['min_stay' => 7]);

    // No authenticated operator at all — the public storefront's position.
    expect(fn () => gateBook($s, '2026-09-01', '2026-09-02', [
        'restriction_override_reason' => RestrictionOverrideReason::FILLS_A_GAP,
    ]))->toThrow(RestrictionOverrideForbiddenException::class);

    // ⚠️ Refused BEFORE the audit is written — no row may record an act that was not permitted.
    expect(AuditEvent::query()->where('action', 'reservation.restriction_overridden')->count())->toBe(0);
});

it('⚠️⚠️ SEEN == WAIVED — a breach set that changed since the refusal is REFUSED AGAIN', function () {
    $s = seedScenario(1);
    gateRestrict($s, '2026-09-01', ['min_stay' => 7]);

    // The operator was shown only the minimum stay…
    $seen = [['restriction' => 'min_stay', 'date' => '2026-09-01', 'value' => 7]];

    // …and someone closed the date to arrivals in the meantime.
    gateRestrict($s, '2026-09-01', ['min_stay' => 7, 'closed_to_arrival' => true]);

    // ⚠️ actingAs LAST: writing a tenant-scoped model resets the spatie team context, and a null team makes
    //    `can()` fail SILENTLY to false — the documented footgun, hit here for real.
    $this->actingAs(operatorUser($s, [], 'Front Desk'));

    expect(fn () => gateBook($s, '2026-09-01', '2026-09-02', [
        'restriction_override_reason' => RestrictionOverrideReason::FILLS_A_GAP,
        'restriction_override_seen' => $seen,
    ]))->toThrow(RestrictionsViolationException::class);
});

it('SEEN == WAIVED — an UNCHANGED set goes through', function () {
    $s = seedScenario(1);
    gateRestrict($s, '2026-09-01', ['min_stay' => 7]);
    $this->actingAs(operatorUser($s, [], 'Front Desk'));

    expect(gateBook($s, '2026-09-01', '2026-09-02', [
        'restriction_override_reason' => RestrictionOverrideReason::FILLS_A_GAP,
        'restriction_override_seen' => [['restriction' => 'min_stay', 'date' => '2026-09-01', 'value' => 7]],
    ])->exists)->toBeTrue();
});

it('⚠️ ZERO MOVEMENT — with no override, a breaching booking is still refused and nothing is audited', function () {
    $s = seedScenario(1);
    gateRestrict($s, '2026-09-01', ['min_stay' => 7]);
    $this->actingAs(operatorUser($s, [], 'Front Desk'));

    expect(fn () => gateBook($s, '2026-09-01', '2026-09-02'))
        ->toThrow(RestrictionsViolationException::class);

    expect(AuditEvent::query()->where('action', 'reservation.restriction_overridden')->count())->toBe(0);
});

it('⚠️ THE OVERRIDE IS OFFERED ONLY AFTER A REFUSAL — the form carries no override field on a first attempt', function () {
    $s = seedScenario(1);

    $html = $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get(route('app.reservations.create'))->assertOk()->getContent();

    // ⚠️ A pre-tickable override gets ticked from habit and the block stops firing entirely.
    expect(str_contains($html, 'restriction_override_reason'))->toBeFalse();
});

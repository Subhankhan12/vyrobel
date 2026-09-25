<?php

declare(strict_types=1);

use App\Exceptions\Booking\RestrictionsViolationException;
use App\Models\AuditEvent;
use App\Models\Restriction;
use App\Services\AssignmentService;
use App\Support\Booking\RestrictionOverrideReason;
use App\Support\Booking\RestrictionOverrideStats;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ THE OVERRIDE MUST BE INFORMED. A booking can breach several restrictions at once, so every breach is
 * collected and presented — a first-failure-wins refusal is a BLANK CHEQUE, waiving a stop-sell the operator
 * never saw. And the audit records EVERY waiver: naming one of two is worse than none, because it reads as
 * precise.
 */
function ovRestrict(array $s, string $date, array $flags): void
{
    Restriction::query()->updateOrCreate(
        ['room_type_id' => (string) $s['roomType']->id, 'rate_plan_id' => null, 'date' => $date],
        $flags + ['organization_id' => (string) $s['org']->id],
    );
}

function ovBook(array $s, string $in, string $out, ?string $reason = null, ?string $note = null)
{
    // ⚠️ The override is now GATED at the point it is consumed, so these must act as a permitted operator.
    //    Before this pass the permission was checked nowhere and they passed unauthenticated — which is
    //    exactly the defect that pass closed. ⚠️ Set AFTER any tenant-scoped write: a null spatie team makes
    //    `can()` fail silently to false.
    if ($reason !== null) {
        auth()->login(operatorUser($s, [], 'Front Desk'));
    }

    $line = [
        'room_type_id' => (string) $s['roomType']->id,
        'unit_id' => (string) $s['units'][0]->id,
        'rate_plan_id' => (string) $s['ratePlan']->id,
        'check_in' => $in,
        'check_out' => $out,
        'price_minor' => 10000,
    ];
    if ($reason !== null) {
        $line['restriction_override_reason'] = $reason;
        $line['restriction_override_note'] = $note;
    }

    return app(AssignmentService::class)->createBooking([
        'organization_id' => (string) $s['org']->id,
        'property_id' => (string) $s['property']->id,
        'lead_guest_id' => (string) $s['guest']->id,
        'currency' => 'EUR',
        'check_in' => $in,
        'check_out' => $out,
        'adults' => 1,
    ], [$line]);
}

it('⚠️⚠️ EVERY BREACH IS COLLECTED — not first-failure-wins', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7, 'closed_to_arrival' => true]);
    ovRestrict($s, '2026-09-02', ['stop_sell' => true]);

    try {
        ovBook($s, '2026-09-01', '2026-09-03');
        $this->fail('the booking should have been refused');
    } catch (RestrictionsViolationException $e) {
        $kinds = array_column($e->asRecords(), 'restriction');
        sort($kinds);

        // ⚠️ Three separate breaches, all reported. A first-failure check would name only min_stay.
        expect($kinds)->toBe(['closed_to_arrival', 'min_stay', 'stop_sell'])
            ->and($e->getMessage())->toContain('3 restrictions');
    }
});

it('⚠️⚠️ THE AUDIT RECORDS EVERY WAIVER, with its date and value', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7, 'closed_to_arrival' => true]);
    ovRestrict($s, '2026-09-02', ['stop_sell' => true]);

    ovBook($s, '2026-09-01', '2026-09-03', RestrictionOverrideReason::FILLS_A_GAP);

    $row = AuditEvent::query()->where('action', 'reservation.restriction_overridden')->firstOrFail();
    $waived = $row->after['waived'];

    expect($row->after['waived_count'])->toBe(3)
        ->and(array_column($waived, 'restriction'))->toContain('min_stay', 'closed_to_arrival', 'stop_sell')
        ->and(collect($waived)->firstWhere('restriction', 'min_stay')['value'])->toBe(7)
        ->and(collect($waived)->firstWhere('restriction', 'stop_sell')['date'])->toBe('2026-09-02');
});

it('⚠️ MIN_STAY BOUNDARY — exactly 7 BOOKS', function () {
    // ⚠️ Separate scenarios per side: sharing one would have the first booking occupy the unit, and the
    //    second would fail on AVAILABILITY before the restriction was ever consulted — a green test that
    //    proves nothing about the boundary.
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7]);

    expect(ovBook($s, '2026-09-01', '2026-09-08')->exists)->toBeTrue();
});

it('⚠️ MIN_STAY BOUNDARY — 6 is REFUSED', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7]);

    expect(fn () => ovBook($s, '2026-09-01', '2026-09-07'))
        ->toThrow(RestrictionsViolationException::class);
});

it('⚠️ MAX_STAY BOUNDARY — exactly 7 BOOKS', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['max_stay' => 7]);

    expect(ovBook($s, '2026-09-01', '2026-09-08')->exists)->toBeTrue();
});

it('⚠️ MAX_STAY BOUNDARY — 8 is REFUSED', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['max_stay' => 7]);

    expect(fn () => ovBook($s, '2026-09-01', '2026-09-09'))
        ->toThrow(RestrictionsViolationException::class);
});

it('⚠️ MAX_STAY is judged on the ARRIVAL date — a maximum on a later night does not govern', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-03', ['max_stay' => 1]);

    expect(ovBook($s, '2026-09-01', '2026-09-06')->exists)->toBeTrue();
});

it('⚠️ ZERO MOVEMENT — no restriction set, behaviour unchanged', function () {
    $s = seedScenario(1);
    expect(ovBook($s, '2026-09-01', '2026-09-02')->exists)->toBeTrue();
});

it('⚠️ ZERO MOVEMENT — no override used, a breaching booking is still refused', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7]);

    expect(fn () => ovBook($s, '2026-09-01', '2026-09-02'))
        ->toThrow(RestrictionsViolationException::class);

    // ⚠️ And no audit row is written when nothing was overridden.
    expect(AuditEvent::query()->where('action', 'reservation.restriction_overridden')->count())->toBe(0);
});

it('⚠️ A FREE-TEXT reason is REFUSED', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7]);

    expect(fn () => ovBook($s, '2026-09-01', '2026-09-02', 'the guest was nice'))
        ->toThrow(InvalidArgumentException::class);
});

it('⚠️ "other" REQUIRES a note and is counted separately', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7]);

    expect(fn () => ovBook($s, '2026-09-01', '2026-09-02', RestrictionOverrideReason::OTHER))
        ->toThrow(InvalidArgumentException::class);

    ovBook($s, '2026-09-01', '2026-09-02', RestrictionOverrideReason::OTHER, 'contracted crew rate');

    expect(RestrictionOverrideStats::since()['by_reason'])->toHaveKey(RestrictionOverrideReason::OTHER);
});

it('⚠️ THE OVERRIDE RATE is answerable, by reason AND by restriction', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7, 'closed_to_arrival' => true]);

    ovBook($s, '2026-09-01', '2026-09-02', RestrictionOverrideReason::REGULAR_GUEST);
    $stats = RestrictionOverrideStats::since();

    // ⚠️ One override, TWO waivers — a per-booking count alone would hide the second.
    expect($stats['overrides'])->toBe(1)
        ->and($stats['waivers'])->toBe(2)
        ->and($stats['by_restriction'])->toHaveKeys(['min_stay', 'closed_to_arrival']);
});

it('⚠️ IT IS AN ACT, NOT A STATE — nothing persists on the reservation', function () {
    $s = seedScenario(1);
    ovRestrict($s, '2026-09-01', ['min_stay' => 7]);

    $r = ovBook($s, '2026-09-01', '2026-09-02', RestrictionOverrideReason::FILLS_A_GAP);

    foreach ($r->refresh()->getAttributes() as $column => $value) {
        expect(str_contains($column, 'override'))->toBeFalse("reservation carries a standing override column: {$column}");
    }
});

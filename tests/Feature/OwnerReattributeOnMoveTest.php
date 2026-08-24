<?php

declare(strict_types=1);

use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\ReservationUnit;
use App\Services\AssignmentService;
use App\Services\OwnerAttributionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️ MONEY PATH — the owner attribution must follow the unit. The defect was silently wrong in BOTH
 * directions: one owner overpaid, another underpaid, and neither could notice.
 *
 * ⚠️ Money is MOVED between owners, never created or destroyed — the signed-sum assertions below are the
 * arithmetic that pins that.
 */
function reattrScenario(): array
{
    $s = seedScenario(2);
    $ownerA = ownUnit($s['units'][0], ['commission_pct' => 18]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    return [$s, $ownerA, $reservation];
}

function reattrNetFor(string $ownerId, string $reservationId): int
{
    return (int) OwnerLedgerEntry::query()
        ->where('reservation_id', $reservationId)
        ->where('owner_id', $ownerId)
        ->get()
        ->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
}

function reattrLedgerSum(string $reservationId): int
{
    return (int) OwnerLedgerEntry::query()
        ->where('reservation_id', $reservationId)
        ->get()
        ->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
}

it('SAME OWNER → different unit is a PROVEN NO-OP — not a reversal plus an identical re-post', function () {
    [$s, $ownerA, $reservation] = reattrScenario();
    // The SAME owner on both units — ownUnit() always makes a new one, so assign directly.
    $s['units'][1]->update(['owner_id' => $ownerA->id]);

    $before = OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count();
    $netBefore = reattrNetFor((string) $ownerA->id, (string) $reservation->id);

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id);

    // ⚠️ NOT ONE NEW ROW. Churn on an append-only ledger is visible to the owner.
    expect(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count())->toBe($before)
        ->and(reattrNetFor((string) $ownerA->id, (string) $reservation->id))->toBe($netBefore);
});

it('OWNER A → OWNER B before arrival — A fully reversed, B fully attributed, sum preserved', function () {
    [$s, $ownerA, $reservation] = reattrScenario();
    $ownerB = ownUnit($s['units'][1], ['commission_pct' => 18]);

    $netABefore = reattrNetFor((string) $ownerA->id, (string) $reservation->id);
    $sumBefore = reattrLedgerSum((string) $reservation->id);
    expect($netABefore)->toBeGreaterThan(0);

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id);

    // ⚠️ A is reversed to exactly zero — a PARTIAL reversal is the failure the whole-transaction rule exists
    //    to prevent, so this asserts the net, not merely that a reversal row appeared.
    expect(reattrNetFor((string) $ownerA->id, (string) $reservation->id))->toBe(0)
        ->and(reattrNetFor((string) $ownerB->id, (string) $reservation->id))->toBe($netABefore)
        ->and(reattrLedgerSum((string) $reservation->id))->toBe($sumBefore);
});

it('OWNER → OPERATOR-OWNED unit takes the owner share to ZERO', function () {
    [$s, $ownerA, $reservation] = reattrScenario();
    // units[1] is left un-owned → operator-owned.

    expect(reattrNetFor((string) $ownerA->id, (string) $reservation->id))->toBeGreaterThan(0);

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id);

    expect(reattrNetFor((string) $ownerA->id, (string) $reservation->id))->toBe(0)
        ->and(reattrLedgerSum((string) $reservation->id))->toBe(0);
});

it('OPERATOR-OWNED → OWNER gives an owner earnings where there were none', function () {
    $s = seedScenario(2);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // units[0] un-owned
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    $ownerB = ownUnit($s['units'][1], ['commission_pct' => 18]);
    expect(reattrNetFor((string) $ownerB->id, (string) $reservation->id))->toBe(0);

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id);

    expect(reattrNetFor((string) $ownerB->id, (string) $reservation->id))->toBeGreaterThan(0);
});

it('an ALREADY-ARRIVED booking is left alone — the split date is not invented', function () {
    [$s, $ownerA, $reservation] = reattrScenario();
    $ownerB = ownUnit($s['units'][1], ['commission_pct' => 18]);

    $reservation->forceFill(['status' => 'checked_in'])->save();
    $netABefore = reattrNetFor((string) $ownerA->id, (string) $reservation->id);
    $rowsBefore = OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count();

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id);

    // ⚠️ Unchanged, deliberately: re-attributing the WHOLE stay would be as wrong as leaving it, just in
    //    the other direction, and no recorded fact says when the guest actually moved.
    expect(reattrNetFor((string) $ownerA->id, (string) $reservation->id))->toBe($netABefore)
        ->and(reattrNetFor((string) $ownerB->id, (string) $reservation->id))->toBe(0)
        ->and(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count())->toBe($rowsBefore);
});

it('IDEMPOTENT — moving back and forth does not double-post', function () {
    [$s, $ownerA, $reservation] = reattrScenario();
    $ownerB = ownUnit($s['units'][1], ['commission_pct' => 18]);
    $sumBefore = reattrLedgerSum((string) $reservation->id);

    $svc = app(AssignmentService::class);
    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    $line = $svc->assignUnit($line, (string) $s['units'][1]->id);
    $svc->assignUnit($line, (string) $s['units'][0]->id);

    // Back where it started: A holds the money again, B is flat, and the total never moved.
    expect(reattrNetFor((string) $ownerB->id, (string) $reservation->id))->toBe(0)
        ->and(reattrNetFor((string) $ownerA->id, (string) $reservation->id))->toBe($sumBefore)
        ->and(reattrLedgerSum((string) $reservation->id))->toBe($sumBefore);
});

it('a LOCKED period is never rewritten — the reversal carries forward as a prior-period adjustment', function () {
    [$s, $ownerA, $reservation] = reattrScenario();
    $ownerB = ownUnit($s['units'][1], ['commission_pct' => 18]);

    $serviceDate = OwnerLedgerEntry::query()
        ->where('reservation_id', $reservation->id)
        ->where('owner_id', $ownerA->id)
        ->value('service_date');

    // Seal A's period THROUGH that service date — the owner has been shown this statement.
    OwnerStatement::query()->create([
        'organization_id' => $reservation->organization_id,
        'owner_id' => $ownerA->id,
        'period_start' => CarbonImmutable::parse($serviceDate)->startOfMonth()->toDateString(),
        'period_end' => CarbonImmutable::parse($serviceDate)->endOfMonth()->toDateString(),
        'currency' => 'EUR',
        'source_currency' => 'EUR',
        'status' => 'finalized',
        'locked' => true,
    ]);

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id);

    $reversals = OwnerLedgerEntry::query()
        ->where('reservation_id', $reservation->id)
        ->where('owner_id', $ownerA->id)
        ->whereNotNull('reverses_id')
        ->get();

    // ⚠️ Every reversal into the sealed period is flagged to carry forward — the sealed statement's own
    //    figures are never touched, which is the whole point of the lock.
    expect($reversals)->not->toBeEmpty()
        ->and($reversals->every(fn ($r): bool => $r->prior_period_adjustment === true))->toBeTrue();

    // And A still nets to zero for this booking overall.
    expect(reattrNetFor((string) $ownerA->id, (string) $reservation->id))->toBe(0);
});

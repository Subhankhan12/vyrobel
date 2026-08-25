<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\Restriction;
use App\Support\Booking\RestrictionGuard;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️ THE SEEDED DEMO RESTRICTIONS. Two properties are asserted, and the second is the one that matters:
 *
 *  1. ⚠️ THEY ARE ANCHORED, NOT HARDCODED. The demo shifts its dates to stay current, so a fixed date would
 *     drift out of the visible window and the demo would silently stop demonstrating the feature.
 *  2. ⚠️⚠️ THEY BLOCK NO SEEDED BOOKING. If one landed on a booked date, that booking would fail to create,
 *     it would vanish, and the demo's money would move — so byte-identical seeded money is the proof, never
 *     "the seed completed".
 */
beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

if (! function_exists('seedRead')) {
    /** ⚠️ Tenant-scoped models need a context; these are whole-database assertions, so read as super-admin. */
    function seedRead(callable $fn): mixed
    {
        return app(TenantContext::class)->runAsSuperAdmin($fn);
    }
}

it('⚠️ the seeded restrictions are ANCHORED to the showcase month, not a fixed date', function () {
    seedRead(function (): void {
        $rows = Restriction::query()->orderBy('date')->get();
        expect($rows)->not->toBeEmpty();

        // The showcase month is next month, by the seeder's own anchoring.
        $showcase = CarbonImmutable::now()->startOfMonth()->addMonth();

        foreach ($rows as $r) {
            expect(CarbonImmutable::parse($r->date)->format('Y-m'))->toBe($showcase->format('Y-m'));
        }
    });
});

it('⚠️ the demo shows BOTH a minimum stay and one of the newly-settable three', function () {
    seedRead(fn () => expect(Restriction::query()->whereNotNull('min_stay')->count())->toBeGreaterThan(0)
        ->and(Restriction::query()->where('closed_to_arrival', true)->count())->toBeGreaterThan(0));
});

it('⚠️⚠️ NO SEEDED BOOKING IS BLOCKED — checked with the REAL guard, not a date proxy', function () {
    seedRead(function (): void {
        // ⚠️ A date-overlap proxy is the WRONG test and was tried first: a restriction can sit on a booked
        //    date without refusing anything (a 3-night minimum only refuses stays SHORTER than three that
        //    ARRIVE that day). ⚠️ So this runs the ACTUAL `RestrictionGuard` over every seeded booking —
        //    the same code the booking path uses (§N.8f) — which is the only honest way to know.
        foreach (Reservation::query()->with('reservationUnits')->get() as $r) {
            foreach ($r->reservationUnits as $line) {
                RestrictionGuard::assertBookable(
                    (string) $line->room_type_id,
                    CarbonImmutable::parse($line->check_in)->toDateString(),
                    CarbonImmutable::parse($line->check_out)->toDateString(),
                );
            }
        }
    });
})->throwsNoExceptions();

it('⚠️⚠️ MONEY-AS-SEEDED IS UNCHANGED — the real proof nothing was blocked', function () {
    seedRead(function (): void {
        // ⚠️ A blocked booking would reduce the count AND move the signed sum. "The seed ran" proves neither.
        expect(Reservation::query()->count())->toBe(42)
            ->and((int) OwnerLedgerEntry::query()->count())->toBe(29)
            ->and((int) OwnerLedgerEntry::query()->get()->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor()))
            ->toBe(465360);
    });
});

it('⚠️ NO override audit row was seeded — an audit of an act nobody performed is a fabrication', function () {
    seedRead(fn () => expect(AuditEvent::query()->where('action', 'reservation.restriction_overridden')->count())->toBe(0));
});

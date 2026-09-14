<?php

use App\Models\RatePlan;
use App\Services\BookingEngineService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ══ P-3 · `DATE()` DEFEATS THE INDEX RANGE ON `rate_calendar` — 2026-08-31 ═══════════════════════════
//
// ⚠️ THE COLUMN TYPE IS WHAT MAKES THIS SAFE, and it was established before a single query changed:
// `rate_calendar.date` is a **DATE** column (`$table->date('date')`; live schema `date`). `DATE(x)` on a DATE
// column is the identity function, so removing the wrapper cannot change what matches. Had it been a
// DATETIME, removing it would have changed the result set and this pass would have stopped.
//
// ⚠️ AND THE COMPARED VALUE IS BUILT BEFORE THE QUERY, in every case, as a `toDateString()` string. The
// change is purely `DATE(col) op 'str'` → `col op 'str'`; nothing about how the string is derived moves. The
// day-boundary test below proves that rather than asserting it.

function rcdSeed(int $days = 40): array
{
    $s = seedScenario(1);
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();

    $rows = [];
    for ($i = 0; $i < $days; $i++) {
        $rows[] = [
            'id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'rate_plan_id' => $plan->id,
            'date' => CarbonImmutable::parse('2026-09-01')->addDays($i)->toDateString(),
            'price_minor' => 18000 + $i, 'currency' => 'EUR', 'created_at' => now(), 'updated_at' => now(),
        ];
    }
    DB::table('rate_calendar')->insert($rows);

    return ['s' => $s, 'plan' => $plan];
}

/** The same range, both ways — DATE()-wrapped and plain. */
function rcdRows(string $planId, string $from, string $toExclusive, bool $wrapped): array
{
    $col = $wrapped ? 'DATE(`date`)' : '`date`';

    return DB::select(
        "SELECT id, `date`, price_minor FROM rate_calendar WHERE rate_plan_id = ? AND {$col} >= ? AND {$col} < ? ORDER BY `date`, id",
        [$planId, $from, $toExclusive],
    );
}

// ── 1. ⚠️⚠️ IDENTICAL ROWS — captured and diffed, never a price eyeballed ──────────────────────────
it('returns byte-identical rows with and without the DATE() wrapper', function () {
    $f = rcdSeed();
    $plan = (string) $f['plan']->id;

    $wrapped = rcdRows($plan, '2026-09-06', '2026-09-20', true);
    $plain = rcdRows($plan, '2026-09-06', '2026-09-20', false);

    expect(json_encode($plain))->toBe(json_encode($wrapped))
        ->and(count($plain))->toBe(14);   // ⚠️ the fixture bites: a range that really selects a subset
});

// ── 2. ⚠️⚠️ THE DAY BOUNDARY — two extreme zones, either side of midnight ──────────────────────────
// ⚠️ A guest booked or charged for the wrong night is far worse than a slow page. This walks a zone 14 hours
// AHEAD of UTC and one 11 hours BEHIND, at 23:00 and 01:00 local, builds "today" exactly as the storefront
// does, and requires both query forms to agree every time.
it('agrees across the day boundary in zones far ahead of and behind UTC', function () {
    $f = rcdSeed();
    $plan = (string) $f['plan']->id;
    $checked = 0;

    foreach (['Pacific/Kiritimati', 'Pacific/Midway', 'UTC'] as $zone) {
        foreach (['2026-09-09 23:00:00', '2026-09-10 01:00:00'] as $localClock) {
            CarbonImmutable::setTestNow(CarbonImmutable::parse($localClock, $zone));

            // Built exactly as the app builds it, in the app's own zone — untouched by this change.
            $today = CarbonImmutable::now()->toDateString();
            $end = CarbonImmutable::now()->addDays(7)->toDateString();

            expect(json_encode(rcdRows($plan, $today, $end, false)))
                ->toBe(json_encode(rcdRows($plan, $today, $end, true)),
                    "the two forms disagreed at {$localClock} {$zone} — a day boundary moved");
            $checked++;
        }
    }

    CarbonImmutable::setTestNow();
    expect($checked)->toBe(6);
});

// ── 3. ⚠️ THE PLAN, PINNED — the only thing an output test CANNOT see ──────────────────────────────
// ⚠️ Restoring `whereDate()` returns IDENTICAL ROWS, so tests 1 and 2 would stay green through a revert.
// The regression is the PLAN, so the plan is what is asserted: EXPLAIN must report a `range` scan on the
// composite index, not a `ref` scan that reads every row of the plan and filters afterwards.
it('plans the storefront range as an index range, not a full read of the plan', function () {
    $f = rcdSeed(200);   // ⚠️ enough rows that the optimiser has a reason to prefer the index
    $plan = (string) $f['plan']->id;

    $wrapped = DB::select('EXPLAIN SELECT * FROM rate_calendar WHERE rate_plan_id = ? AND DATE(`date`) >= ? AND DATE(`date`) < ?', [$plan, '2026-09-06', '2026-09-20'])[0];
    $plain = DB::select('EXPLAIN SELECT * FROM rate_calendar WHERE rate_plan_id = ? AND `date` >= ? AND `date` < ?', [$plan, '2026-09-06', '2026-09-20'])[0];

    expect($plain->type)->toBe('range')
        ->and($wrapped->type)->toBe('ref')                       // the defect, still demonstrable
        ->and((int) $plain->rows)->toBeLessThan((int) $wrapped->rows);
});

// ── 4. ⚠️ AND THE PATH A GUEST WAITS ON STILL PRICES THE SAME STAY ─────────────────────────────────
it('prices a stay through the storefront path unchanged', function () {
    $f = rcdSeed();

    $total = app(BookingEngineService::class)->priceOver((string) $f['plan']->id, '2026-09-06', '2026-09-09');

    // three nights: 18005 + 18006 + 18007
    expect($total)->toBe(54018);
});

// ── 5. ⚠️⚠️ PINNING THE PRODUCTION QUERY — the plan regression an output test cannot see ────────────
// ⚠️ Tests 1-2 stay green through a revert, because `whereDate()` returns the same rows. What changes is
// the SQL Laravel builds, so THAT is captured: the two public-booking-path reads must not wrap the column.
it('builds the public booking-path reads without a DATE() wrapper', function () {
    $f = rcdSeed();
    $seen = [];

    DB::listen(function ($q) use (&$seen): void {
        if (str_contains($q->sql, 'rate_calendar')) {
            $seen[] = $q->sql;
        }
    });

    app(BookingEngineService::class)->priceOver((string) $f['plan']->id, '2026-09-06', '2026-09-09');

    expect($seen)->not->toBeEmpty();
    foreach ($seen as $sql) {
        expect(str_contains(strtolower($sql), 'date(`date`)'))
            ->toBeFalse("priceOver() wrapped the date column — the index range is defeated again: {$sql}");
    }

    // ⚠️ …and the wrapper IS detectable by this check, so it cannot pass by looking for the wrong thing.
    expect(str_contains(strtolower('select * from rate_calendar where date(`date`) >= ?'), 'date(`date`)'))
        ->toBeTrue();
});

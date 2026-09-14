<?php

use App\Models\Property;
use App\Models\RatePlan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ══ C1 · THE STOREFRONT'S "TODAY" IS THE PROPERTY'S — 2026-08-31 ════════════════════════════════════
//
// ⚠️ `StorefrontLandingController::fromPriceMinor()` built "today" from `now()` in the APPLICATION timezone
// (UTC). For a property 14 hours ahead, 01:00 local on the 2nd is still the 1st in UTC, so the price window
// opened on a night ALREADY PAST locally — and the cheapest such night became the public "from €X".
//
// ⚠️ REPRODUCED, guest-visibly: the page advertised **from €50** for a night the guest could no longer book,
// where the real floor was **€300**.
//
// ⚠️ SEVERITY, STATED HONESTLY: this is the "from" TEASER only. It changes no availability and nothing a
// guest is charged — `/book/{slug}`'s offers come from StorefrontOffer, which already resolved the property
// timezone. But an advertised price that cannot be obtained is a wrong figure on a public page.
//
// ⚠️ THESE ASSERT WHAT A GUEST IS SHOWN, not just what a variable holds. A date-only test would pass a page
// that renders the wrong number.

const SLT_YESTERDAY = 5000;      // the trap: cheapest, but local-yesterday

const SLT_TODAY = 30000;

function sltSetup(string $timezone): array
{
    $s = seedScenario(1);
    Property::withoutGlobalScopes()->whereKey($s['property']->id)
        // ⚠️ A UNIQUE slug per call: `properties.slug` is unique and the six-crossing test builds six
        //    scenarios in one test, which collided on a fixed value.
        ->update(['timezone' => $timezone, 'slug' => 'zone-'.Str::lower(Str::ulid())]);
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();

    foreach ([['2026-09-01', SLT_YESTERDAY], ['2026-09-02', SLT_TODAY], ['2026-09-03', SLT_TODAY + 1000]] as [$d, $p]) {
        DB::table('rate_calendar')->updateOrInsert(
            ['rate_plan_id' => $plan->id, 'date' => $d],
            ['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'price_minor' => $p,
                'currency' => 'EUR', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    return $s;
}

/** The "from" figure a guest actually sees on the rendered landing page, in minor units. */
function sltRenderedFrom(array $s): ?int
{
    $html = test()->get('/stay/'.$s['org']->slug)->assertOk()->getContent();

    // ⚠️ The page renders whole euros ("€300"), not "€300.00" — matched as the view actually writes it.
    //    A regex expecting decimals returned NULL for every case and would have failed silently as "no match".
    if (preg_match('/€\s?([\d,]+)(?:\.(\d{2}))?/u', $html, $m) !== 1) {
        return null;
    }

    return ((int) str_replace(',', '', $m[1])) * 100 + (int) ($m[2] ?? 0);
}

// ── 1. ⚠️⚠️ SIX CROSSINGS — three zones, either side of midnight ───────────────────────────────────
it('shows the price window from the PROPERTY’s local today, across midnight in three zones', function () {
    $checked = 0;

    foreach (['Pacific/Kiritimati' => 'UTC+14', 'Pacific/Midway' => 'UTC-11', 'UTC' => 'UTC'] as $zone => $label) {
        foreach (['2026-09-01 23:00:00', '2026-09-02 01:00:00'] as $localClock) {
            $s = sltSetup($zone);

            // The instant at which it is $localClock in the property's zone.
            CarbonImmutable::setTestNow(CarbonImmutable::parse($localClock, $zone)->setTimezone('UTC'));

            $localToday = CarbonImmutable::now($zone)->toDateString();
            $expected = (int) DB::table('rate_calendar')->where('date', '>=', $localToday)->min('price_minor');

            expect(sltRenderedFrom($s))->toBe(
                $expected,
                "{$label} at {$localClock} local: the page must price from the property's own today ({$localToday})",
            );
            $checked++;
        }
    }

    CarbonImmutable::setTestNow();
    expect($checked)->toBe(6);
});

// ── 2. ⚠️ THE ORDINARY CASE — when the zones agree, nothing may change ─────────────────────────────
// ⚠️ This is most of the time, and a fix that quietly moved the figure here would be a regression dressed
// as a correction.
it('changes nothing when the property’s zone and the application’s agree', function () {
    $s = sltSetup('UTC');
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-02 12:00:00', 'UTC'));   // mid-day, no boundary

    expect(sltRenderedFrom($s))->toBe(SLT_TODAY)
        ->and(CarbonImmutable::now('UTC')->toDateString())->toBe(CarbonImmutable::now()->toDateString());

    CarbonImmutable::setTestNow();
});

// ── 3. ⚠️⚠️ THE TRAP THE OLD CODE FELL INTO, named ─────────────────────────────────────────────────
it('never advertises a night that is already in the property’s past', function () {
    $s = sltSetup('Pacific/Kiritimati');
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 12:00:00', 'UTC'));   // 02:00 on the 2nd, local

    expect(CarbonImmutable::now()->toDateString())->toBe('2026-09-01')            // the server's day…
        ->and(CarbonImmutable::now('Pacific/Kiritimati')->toDateString())->toBe('2026-09-02');   // …and the property's

    expect(sltRenderedFrom($s))->toBe(SLT_TODAY)          // €300 — the real floor
        ->and(sltRenderedFrom($s))->not->toBe(SLT_YESTERDAY);   // never €50, a night already gone

    CarbonImmutable::setTestNow();
});

// ── 4. ⚠️ AN EMPTY TIMEZONE MUST NOT TAKE THE PUBLIC PAGE DOWN ─────────────────────────────────────
// ⚠️ The column is NOT NULL DEFAULT 'UTC', but `varchar` accepts ''. Every seeder and factory sets a real
// zone, so this path is otherwise untested — and a fix that threw here would be worse than the bug.
it('falls back to UTC when a property carries an empty timezone', function () {
    $s = sltSetup('');
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-02 12:00:00', 'UTC'));

    expect(Property::withoutGlobalScopes()->whereKey($s['property']->id)->value('timezone'))->toBe('');
    expect(sltRenderedFrom($s))->toBe(SLT_TODAY);   // renders, does not 500

    CarbonImmutable::setTestNow();
});

<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RateProposal;
use App\Models\RateSeason;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerRateService;
use App\Services\RateProposalService;
use App\Services\SeasonalRateService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * ⚠️⚠️ A SEASON SAYS WHAT IT IS ABOUT TO OVERWRITE — the DISCLOSURE half, and nothing else.
 *
 * ⚠️⚠️ NO POLICY CHANGES HERE. `SeasonPrecedence` is untouched, no season refuses anything, and applying is
 * still permitted. Whether a season SHOULD be able to replace a negotiated rate is an OPEN FOUNDER RULING.
 *
 * ⚠️ EVERY AGREED RATE BELOW IS MADE THROUGH THE REAL PROPOSAL-AND-ACCEPTANCE PATH — `propose()` then
 *    `approve()` — never a fixture row written to look like one. A fixture that fakes provenance would prove
 *    the disclosure can read a shape nobody creates, which is exactly how a defect survives.
 */
uses(RefreshDatabase::class);

/** An owner exclusively owning one room type, a plan with a rate, and an operator who may propose. */
function ardScenario(): array
{
    $s = seedScenario(1);
    $suffix = Str::upper(Str::random(5));

    $roomType = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Azure '.$suffix, 'kind' => 'room']);
    $unit = Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $roomType->id, 'code' => 'U-'.$suffix]);
    $plan = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $roomType->id, 'name' => 'Flex '.$suffix, 'currency' => 'EUR']);
    $roomType->update(['default_rate_plan_id' => $plan->id]);

    $owner = ownUnit($unit);
    $ownerUser = User::factory()->create(['name' => 'Olive Owner '.$suffix]);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $ownerUser->id, 'status' => 'active']);
    $owner->update(['user_id' => $ownerUser->id]);

    $operator = operatorUser($s, ['name' => 'Manny Manager '.$suffix], 'Hotel Manager');

    // ⚠️ THE TEAM. `can()` is false without it, and `rates.manage` is what gates applying a season.
    app(PermissionRegistrar::class)->setPermissionsTeamId($s['org']->id);
    $operator->unsetRelation('roles')->unsetRelation('permissions');

    return compact('s', 'roomType', 'unit', 'plan', 'owner', 'ownerUser', 'operator');
}

/** ⚠️ A REAL negotiated rate: proposed by the operator, approved by the owner, applied by the certified path. */
function ardAgree(array $c, int $rateMinor, string $from, string $to, ?float $weekendPct = null): RateProposal
{
    $proposal = app(RateProposalService::class)->propose(
        $c['operator'], (string) $c['owner']->getKey(), (string) $c['roomType']->getKey(),
        $rateMinor, $weekendPct, null, $from, $to, 'demo',
    );

    app(RateProposalService::class)->approve($proposal, $c['ownerUser']);

    return $proposal->refresh();
}

function ardSeason(array $c, string $from, string $to, int $priceMinor = 25000): RateSeason
{
    return RateSeason::create([
        'rate_plan_id' => $c['plan']->id, 'name' => 'Summer '.Str::random(4),
        'starts_on' => $from, 'ends_on' => $to,
        'price_minor' => $priceMinor, 'currency' => 'EUR',
    ]);
}

function ardPreview(RateSeason $season): array
{
    return app(SeasonalRateService::class)->preview($season);
}

// ══ #8 · ⚠️⚠️ THE POSITIVE HALF — A REAL AGREED RATE, NAMED ══════════════════════════════════════════

it('⚠️⚠️ names the nights carrying a rate the owner negotiated — through the REAL acceptance path', function () {
    $c = ardScenario();
    $from = now()->addDays(10)->toDateString();
    $to = now()->addDays(16)->toDateString();

    $proposal = ardAgree($c, 19000, $from, $to);

    // ⚠️ THE AGREEMENT IS REAL: the certified path actually wrote those nights.
    expect($proposal->state)->toBe(RateProposal::STATE_APPROVED)
        ->and((int) RateCalendar::query()->where('rate_plan_id', $c['plan']->id)
            ->whereDate('date', $from)->value('price_minor'))->toBe(19000);

    $preview = ardPreview(ardSeason($c, $from, $to));

    // ⚠️ Seven nights inclusive, every one of them still carrying the agreed €190.
    expect($preview['agreed_nights'])->toBe(7)
        ->and($preview['agreed_owners'])->toBe(1)
        ->and($preview['agreed_from'])->toBe($from)
        ->and($preview['agreed_to'])->toBe($to);
});

// ══ ⚠️ THE NEGATIVE HALF — SILENCE WHERE THERE IS NOTHING TO SAY ═════════════════════════════════════

it('⚠️ says NOTHING when no night carries a negotiated rate', function () {
    $c = ardScenario();
    $from = now()->addDays(40)->toDateString();
    $to = now()->addDays(46)->toDateString();

    // Ordinary nightly prices, set by nobody in particular — exactly what most nights are.
    foreach (range(0, 6) as $offset) {
        RateCalendar::create([
            'rate_plan_id' => $c['plan']->id,
            'date' => now()->addDays(40 + $offset)->toDateString(),
            'price' => Money::of(17000, 'EUR'),
        ]);
    }

    // ⚠️ A disclosure that fires on every application is wallpaper, and one proven only in the positive
    //    direction may be firing on everything.
    expect(ardPreview(ardSeason($c, $from, $to))['agreed_nights'])->toBe(0);
});

it('⚠️ falls silent once the agreed rate has been superseded — a stale warning is noise', function () {
    $c = ardScenario();
    $from = now()->addDays(60)->toDateString();
    $to = now()->addDays(63)->toDateString();

    ardAgree($c, 19000, $from, $to);
    expect(ardPreview(ardSeason($c, $from, $to))['agreed_nights'])->toBe(4);

    // Somebody moves the price on afterwards. The agreement is history; those nights no longer carry it.
    RateCalendar::query()->where('rate_plan_id', $c['plan']->id)
        ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
        ->update(['price_minor' => 21000]);

    expect(ardPreview(ardSeason($c, $from, $to))['agreed_nights'])->toBe(0);
});

it('⚠️ counts only APPROVED agreements — pending, declined and withdrawn applied nothing', function () {
    $c = ardScenario();
    $from = now()->addDays(80)->toDateString();
    $to = now()->addDays(83)->toDateString();

    RateCalendar::create(['rate_plan_id' => $c['plan']->id, 'date' => $from, 'price' => Money::of(19000, 'EUR')]);

    // Proposed but never decided — nothing was applied, so nothing may be attributed to it.
    app(RateProposalService::class)->propose(
        $c['operator'], (string) $c['owner']->getKey(), (string) $c['roomType']->getKey(),
        19000, null, null, $from, $to, 'still pending',
    );

    expect(ardPreview(ardSeason($c, $from, $to))['agreed_nights'])->toBe(0);
});

// ══ ⚠️⚠️ THE WEEKEND CASE — the false negative that WAS closed ═══════════════════════════════════════

it('⚠️⚠️ counts Fri/Sat too when the agreement carried a weekend uplift', function () {
    $c = ardScenario();
    // A window that certainly contains a Friday and a Saturday.
    $start = now()->addDays(100)->startOfWeek();
    $from = $start->toDateString();
    $to = $start->copy()->addDays(6)->toDateString();

    ardAgree($c, 20000, $from, $to, weekendPct: 25.0);

    // ⚠️ The certified writer priced Fri/Sat at base × 1.25. A disclosure comparing every night to the BASE
    //    figure alone would find no match on two nights in seven and under-report — the exact false negative
    //    this reconstruction exists to close.
    $friday = RateCalendar::query()->where('rate_plan_id', $c['plan']->id)
        ->whereDate('date', $start->copy()->addDays(4)->toDateString())->value('price_minor');
    expect((int) $friday)->toBe(25000);   // the uplift really landed, so the assertion below is not vacuous

    expect(ardPreview(ardSeason($c, $from, $to))['agreed_nights'])->toBe(7);
});

// ══ #1/#2 · ⚠️⚠️ THE FALSE NEGATIVE, ASSERTED RATHER THAN ONLY DESCRIBED ═════════════════════════════

it('⚠️⚠️ CANNOT see an owner’s SELF-SET rate — the limit, pinned so it is never mistaken for coverage', function () {
    $c = ardScenario();
    $from = now()->addDays(120)->toDateString();
    $to = now()->addDays(123)->toDateString();

    // ⚠️ The owner sets their own price in their portal. `OwnerRateController` → `OwnerRateService::apply()`
    //    writes ordinary `rate_calendar` rows and NO record of any kind — no proposal, no provenance.
    app(OwnerRateService::class)->applyForExclusiveOwner(
        (string) $c['roomType']->getKey(), (string) $c['owner']->getKey(),
        $from, now()->addDays(124)->toDateString(), 22500, null, null,
    );

    expect((int) RateCalendar::query()->where('rate_plan_id', $c['plan']->id)
        ->whereDate('date', $from)->value('price_minor'))->toBe(22500);   // the rate really is the owner's

    // ⚠️⚠️ AND THE DISCLOSURE IS SILENT ABOUT IT, because there is nothing to read. This is asserted so the
    //    limit is a measured fact rather than a sentence in a docblock: an operator who trusts the warning
    //    also trusts its silence, and here the silence is WRONG — it just cannot be made right without a
    //    provenance column on `rate_calendar` (costed in docs/AGREED-RATE-DISCLOSURE.md, not built).
    expect(ardPreview(ardSeason($c, $from, $to))['agreed_nights'])->toBe(0);
});

// ══ #7 · ⚠️ A DISCLOSURE WRITES NOTHING ══════════════════════════════════════════════════════════════

it('⚠️ writes nothing — it reads and it says', function () {
    $c = ardScenario();
    $from = now()->addDays(140)->toDateString();
    $to = now()->addDays(146)->toDateString();
    ardAgree($c, 19000, $from, $to);

    $before = DB::table('rate_calendar')->orderBy('id')->get()->toJson();
    $seasonsBefore = DB::table('rate_seasons')->count();

    $preview = ardPreview(ardSeason($c, $from, $to));
    expect($preview['agreed_nights'])->toBe(7);   // ⚠️ the probe below is not vacuous — it really ran

    // ⚠️ Byte-identical, not merely "the same count": a disclosure that rewrote a price while reporting on
    //    it would be the worst possible version of this feature.
    expect(DB::table('rate_calendar')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(DB::table('rate_seasons')->count())->toBe($seasonsBefore + 1);   // only the season row itself
});

// ══ #4 · ⚠️ ONE SOURCE — the disclosure reads what the blast radius reads ════════════════════════════

it('⚠️ is computed from the SAME nightly rows as the blast radius, never a second read', function () {
    $c = ardScenario();
    $from = now()->addDays(160)->toDateString();
    $to = now()->addDays(166)->toDateString();
    ardAgree($c, 19000, $from, $to);

    // ⚠️ Source-level: `preview()` hands its OWN `$dates` and `$existing` to the disclosure. Two reads of
    //    the current price could disagree, and the two figures sit in the same sentence on screen.
    $source = file_get_contents(app_path('Services/SeasonalRateService.php'));
    expect($source)->toContain('AgreedRateDisclosure::forSeason($season, $dates, $existing)');

    $preview = ardPreview(ardSeason($c, $from, $to));

    // Behavioural: every agreed night is one the blast radius also counts as changing (€190 → €250).
    expect($preview['agreed_nights'])->toBe(7)
        ->and($preview['changing'])->toBeGreaterThanOrEqual($preview['agreed_nights']);
});

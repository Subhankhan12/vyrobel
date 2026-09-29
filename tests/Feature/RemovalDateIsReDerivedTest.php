<?php

use App\Models\AuditEvent;
use App\Models\Property;
use App\Services\ChannelConnectionService;
use App\Services\PropertyRemovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ F-57 (founder ruling, 2026-09-19) — THE REMOVAL DATE MEANS "AFTER THE LAST STAY" WHEN IT ACTS.
 *
 * ⚠️ `schedule()` computed `removal_effective_at` from the stays that existed at that moment, and nothing
 * recomputed it. So a booking taken the next day outran it and `processDue()` archived a property over a
 * confirmed stay. The daily pass now RE-DERIVES from the stays as they stand and DEFERS rather than archives.
 *
 * ⚠️⚠️ THE TWO PROPERTIES THE RULING BUYS ARE ASSERTED SEPARATELY, because each can hold while the other
 * fails: the GUEST IS NEVER ARCHIVED OVER (case 1) and the OPERATOR IS NEVER BLOCKED (case 2 — scheduling
 * still works, and a property with nothing ahead of it still archives on its date).
 *
 * ⚠️⚠️ EVERY ASSERTION ABOUT WHETHER A PROPERTY SURVIVED READS THE RAW ROW AND ITS `deleted_at`. FIX 43's
 * mutation went GREEN because `withoutGlobalScopes()` strips the SOFT-DELETE scope along with the tenant
 * scope and hands back a soft-deleted row — an Eloquent result cannot answer this question.
 */

/** Scheduled, due, and holding no stay beyond today — the state `processDue()` acts on. */
function rdrDueProperty(array $s): Property
{
    $property = $s['property'];
    secondProperty($s);   // ⚠️ 2026-09-29 — an organisation can no longer remove its LAST property; this one gets a second
    app(PropertyRemovalService::class)->schedule($property->fresh(), 'ops@example.test');

    $property->fresh()->forceFill(['removal_effective_at' => now()->subHour()])->save();

    return $property->fresh();
}

/** The raw row — no Eloquent scope can soften this answer. */
function rdrRow(string $propertyId): object
{
    return DB::table('properties')->where('id', $propertyId)->first(['id', 'deleted_at', 'removal_effective_at']);
}

function rdrRun(): int
{
    return app(PropertyRemovalService::class)->processDue(app(ChannelConnectionService::class));
}

// ── 1. ⚠️⚠️ THE GUEST IS NEVER ARCHIVED OVER ────────────────────────────────────────────────────────
it('⚠️⚠️ F-57 — defers the archive when a stay reaches beyond the scheduled date', function () {
    $s = seedScenario(1);
    $property = rdrDueProperty($s);

    // ⚠️ A REAL booking made AFTER scheduling, through the certified path, departing well beyond the date.
    $stay = book($s);
    $stay->forceFill([
        'check_in' => now()->addDays(20)->toDateString(),
        'check_out' => now()->addDays(23)->toDateString(),
        'status' => 'confirmed',
    ])->save();

    $archived = rdrRun();

    // ⚠️ THE PROPERTY'S RAW ROW — still live. Not "a status", not a log line.
    $row = rdrRow((string) $property->getKey());
    expect($archived)->toBe(0, 'the archive ran anyway');
    expect($row->deleted_at)->toBeNull('a confirmed stay was archived over — F-57 has returned');

    // ⚠️ AND THE STAY IS UNTOUCHED — deferred, never cancelled to make the archive possible.
    expect(DB::table('reservations')->where('id', $stay->getKey())->value('status'))->toBe('confirmed');
});

// ── 2. ⚠️⚠️ THE POSITIVE CONTROL — AND IT CAN GENUINELY FAIL ────────────────────────────────────────
/**
 * ⚠️⚠️ WITHOUT THIS, "NOTHING EVER ARCHIVES" READS IDENTICALLY TO "THE DEFERRAL WORKS". A property with
 * nothing ahead of it must still archive on its date, or the fix has simply broken removal.
 */
it('⚠️⚠️ still archives a property with no stay ahead of it — the control', function () {
    $s = seedScenario(1);
    $property = rdrDueProperty($s);

    // ⚠️ THE DOOR IS OPEN: assert there really is no stay beyond today, so this cannot pass by accident.
    $ahead = DB::table('reservations')
        ->where('property_id', $property->getKey())
        ->whereNotIn('status', ['cancelled', 'no_show'])
        ->whereDate('check_out', '>=', now()->toDateString())
        ->count();
    expect($ahead)->toBe(0, 'the fixture has a stay ahead of it; the control proves nothing');

    $archived = rdrRun();

    expect($archived)->toBe(1);
    expect(rdrRow((string) $property->getKey())->deleted_at)->not->toBeNull('the property did not archive');
});

// ── 3. ⚠️⚠️ THE STORED DATE BECOMES TRUE, WHICH IS THE WHOLE VISIBILITY STORY ───────────────────────
/**
 * ⚠️⚠️ 🔬 `removal_effective_at` is read by exactly ONE surface — the operator's property Settings page,
 * which renders *"Removal is scheduled — the property stays live until the last checkout on X."* Writing the
 * new date forward makes that existing sentence true again, which is how a deferral becomes VISIBLE without
 * this pass building a surface it had no ruling for.
 */
it('⚠️⚠️ writes the new date forward, so the operator’s existing sentence stays true', function () {
    $s = seedScenario(1);
    $property = rdrDueProperty($s);
    $before = rdrRow((string) $property->getKey())->removal_effective_at;

    $stay = book($s);
    $stay->forceFill([
        'check_in' => now()->addDays(20)->toDateString(),
        'check_out' => now()->addDays(23)->toDateString(),
        'status' => 'confirmed',
    ])->save();

    rdrRun();

    $after = rdrRow((string) $property->getKey())->removal_effective_at;

    expect($after)->not->toBe($before, 'the stored date was left stale — it is both unread and wrong');
    expect(substr((string) $after, 0, 10))->toBe(now()->addDays(23)->toDateString(),
        'the new date is not the last checkout');
});

// ── 4. ⚠️⚠️ NOBODY PRESSED ANYTHING, SO THE DEFERRAL IS AUDITED AS `system` ─────────────────────────
/**
 * ⚠️ F-9, F-17 and F-22 were each "a write with no actor". A deferral is a decision the product makes on its
 * own at 02:00, and it records what it did and why — from which date, to which date.
 */
it('⚠️⚠️ records the deferral, with system as the actor and both dates', function () {
    $s = seedScenario(1);
    $property = rdrDueProperty($s);
    $was = rdrRow((string) $property->getKey())->removal_effective_at;

    $stay = book($s);
    $stay->forceFill([
        'check_in' => now()->addDays(20)->toDateString(),
        'check_out' => now()->addDays(23)->toDateString(),
        'status' => 'confirmed',
    ])->save();

    rdrRun();

    $row = AuditEvent::withoutGlobalScopes()->where('action', 'property.removal_deferred')->sole();

    expect((string) $row->actor_type)->toBe('system', 'a deferral must not be attributed to a person');
    expect($row->actor_id)->toBeNull();
    expect($row->subject_id)->toBe((string) $property->getKey());
    expect($row->before['removal_effective_at'])->toBe($was);
    expect(substr((string) $row->after['removal_effective_at'], 0, 10))->toBe(now()->addDays(23)->toDateString());
    expect($row->after['reason'])->toContain('beyond the scheduled date');
});

// ── 5. ⚠️⚠️ ONE DEFINITION OF "THE LAST STAY", AND IT IS A DENY-LIST ───────────────────────────────
/**
 * ⚠️⚠️ `schedule()` and `processDue()` must agree, because they are the SAME rule — a second computation of
 * one concept is how three permission censuses drifted apart (FIX 42). This drives BOTH and requires the same
 * answer for a status the deny-list does not name.
 *
 * ⚠️ `reservations.status` has SEVEN values; the rule excludes `cancelled` and `no_show`, so FIVE count —
 * including `enquiry` and `tentative`. An unconverted ENQUIRY therefore holds a property open, which is the
 * inherited rule and errs toward never archiving over somebody.
 */
it('⚠️⚠️ counts an ENQUIRY as a stay — the same deny-list on both paths', function () {
    $s = seedScenario(1);
    $property = rdrDueProperty($s);

    $stay = book($s);
    $stay->forceFill([
        'check_in' => now()->addDays(10)->toDateString(),
        'check_out' => now()->addDays(12)->toDateString(),
        'status' => 'enquiry',
    ])->save();

    // ⚠️ THE EXECUTION PATH defers on it.
    expect(rdrRun())->toBe(0, 'an enquiry did not hold the property open');
    expect(rdrRow((string) $property->getKey())->deleted_at)->toBeNull();

    // ⚠️ AND THE SCHEDULING PATH agrees — the same rule, asked at the other moment.
    app(PropertyRemovalService::class)->schedule($property->fresh(), 'ops@example.test');
    expect(substr((string) rdrRow((string) $property->getKey())->removal_effective_at, 0, 10))
        ->toBe(now()->addDays(12)->toDateString(), 'schedule() and processDue() disagree about what counts');
});

// ── 6. ⚠️ A CANCELLED STAY DOES NOT HOLD IT OPEN — the deny-list is real, not decorative ───────────
it('⚠️ ignores a cancelled stay, so the deny-list is doing work', function () {
    $s = seedScenario(1);
    $property = rdrDueProperty($s);

    $stay = book($s);
    $stay->forceFill([
        'check_in' => now()->addDays(10)->toDateString(),
        'check_out' => now()->addDays(12)->toDateString(),
        'status' => 'cancelled',
    ])->save();

    expect(rdrRun())->toBe(1, 'a cancelled stay deferred the archive');
    expect(rdrRow((string) $property->getKey())->deleted_at)->not->toBeNull();
});

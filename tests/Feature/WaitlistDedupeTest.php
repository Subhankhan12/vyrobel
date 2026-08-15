<?php

/*
 * DO-CORRECTNESS-1 · FIX 3 — A WAITLIST DOUBLE-SUBMIT WRITES ONE ROW, NOT TWO (2026-08-15).
 *
 * ⚠️ THE DEFECT, AND ITS SIBLING IS THE PROOF IT WAS AN OMISSION. `guest_wishlist_items` and
 * `guest_waitlist_entries` were created in the SAME migration (2026_07_22_120000). The wishlist got
 * `unique(['guest_id','room_type_id'])` and a `firstOrCreate()` service; the waitlist got two plain indexes
 * and a bare `create()`. `GuestSavedTest` even asserts the wishlist's idempotency ("saves … idempotently") —
 * the waitlist had no such test, and no such behaviour. A double-click wrote two identical rows.
 *
 * ⚠️ THE TRAP THIS FIX HAD TO AVOID, VERIFIED ON THIS MySQL RATHER THAN ASSUMED: **a UNIQUE index does not
 * constrain rows where any indexed column is NULL.** And this table's design comment says *"Exact dates OR a
 * flexible month. One of the two is set"* — so EVERY row carries a NULL among `check_in`/`check_out`/
 * `flexible_month`. A unique index over those nullable columns would have been **completely inert**: present
 * in the schema, visible in review, never firing once. A guard that does not guard is worse than a missing
 * one, so the identity is carried by a STORED GENERATED column that can never be NULL.
 *
 * ⚠️ AND THE SIBLING'S COLUMNS WOULD HAVE BEEN WRONG HERE. Wishlist identity is (guest, room type). A guest
 * waiting on the same room type for 3–7 Sep AND for 20–24 Sep has made two genuinely different requests, so
 * the window belongs in the identity — blind-copying the sibling would have silently discarded the second.
 *
 * ⚠️ VERIFIED OFF THE DEMO PATH. No demo data double-submits, so no page could ever have shown this; the
 * condition has to be forced. Both layers are exercised here — the service, and the DB backstop underneath it.
 */

use App\Models\Guest;
use App\Models\GuestWaitlistEntry;
use App\Models\RoomType;
use App\Services\GuestWishlistService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL and has collided in this suite before. */
function fix3Guest(Guest $guest): Guest
{
    $guest->update(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada.waitlist@example.test']);
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    return $guest->fresh();
}

it('⚠️ FIX 3 — a double-submit yields ONE waitlist row (the service layer)', function () {
    $s = seedScenario(1);
    $guest = fix3Guest($s['guest']);
    $service = app(GuestWishlistService::class);

    $payload = ['check_in' => '2026-09-20', 'check_out' => '2026-09-23', 'notify_similar' => true];

    $first = $service->joinWaitlist($guest, $s['roomType'], $payload);
    $second = $service->joinWaitlist($guest, $s['roomType'], $payload);

    expect(GuestWaitlistEntry::withoutGlobalScopes()->where('guest_id', $guest->getKey())->count())->toBe(1)
        // ⚠️ It must RETURN the existing row, not silently swallow the second call — the caller redirects on it.
        ->and($second->getKey())->toBe($first->getKey());

    // ⚠️ AND IT MUST NOT SILENTLY REFRESH THE PLACE IN THE QUEUE. Reviving a lapsed entry is
    // `reconfirmWaitlist()`'s job; a double-submit that quietly extended the expiry would trade one wrong
    // write for another.
    expect($second->expires_at->equalTo($first->expires_at))->toBeTrue();
});

it('⚠️ FIX 3 — the DB backstop rejects a raw duplicate, INCLUDING the all-NULL-dates case', function () {
    $s = seedScenario(1);
    $guest = fix3Guest($s['guest']);

    // ⚠️ THE CASE A NAIVE INDEX WOULD HAVE MISSED. Flexible month set → both date columns NULL → under a
    // plain `unique(guest_id, room_type_id, check_in, check_out, flexible_month)` MySQL would happily insert
    // this twice. This is the reason the generated column exists, asserted rather than described.
    $row = fn (): array => [
        'id' => (string) Str::ulid(),
        'organization_id' => $s['org']->id,
        'guest_id' => $guest->getKey(),
        'room_type_id' => $s['roomType']->id,
        'check_in' => null,
        'check_out' => null,
        'flexible_month' => '2026-10',
        'notify_similar' => 0,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('guest_waitlist_entries')->insert($row());   // non-vacuity: the first one really does insert

    expect(fn () => DB::table('guest_waitlist_entries')->insert($row()))
        ->toThrow(QueryException::class);

    expect(GuestWaitlistEntry::withoutGlobalScopes()->where('guest_id', $guest->getKey())->count())->toBe(1);
});

it('⚠️ FIX 3 — two DIFFERENT date windows stay two entries (the sibling\'s columns would have collapsed them)', function () {
    $s = seedScenario(1);
    $guest = fix3Guest($s['guest']);
    $service = app(GuestWishlistService::class);

    $service->joinWaitlist($guest, $s['roomType'], ['check_in' => '2026-09-03', 'check_out' => '2026-09-07']);
    $service->joinWaitlist($guest, $s['roomType'], ['check_in' => '2026-09-20', 'check_out' => '2026-09-24']);
    $service->joinWaitlist($guest, $s['roomType'], ['flexible_month' => '2026-11']);

    // ⚠️ THE OVER-CONSTRAINT GUARD. If someone later "simplifies" the identity to (guest, room type) — the
    // sibling's shape — this drops to 1 and a real guest request is silently discarded. That failure mode is
    // quieter than the duplicate this pass fixed, so it gets its own test.
    expect(GuestWaitlistEntry::withoutGlobalScopes()->where('guest_id', $guest->getKey())->count())->toBe(3);
});

it('⚠️ FIX 3 — a second guest is unaffected, and a second room type is a distinct request', function () {
    // Scope check: the identity must not be so broad it blocks a different guest or a different room type.
    $s = seedScenario(1);
    $guest = fix3Guest($s['guest']);
    $service = app(GuestWishlistService::class);

    $other = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Suite', 'kind' => 'room']);

    $service->joinWaitlist($guest, $s['roomType'], ['flexible_month' => '2026-12']);
    $service->joinWaitlist($guest, $other, ['flexible_month' => '2026-12']);

    expect(GuestWaitlistEntry::withoutGlobalScopes()->where('guest_id', $guest->getKey())->count())->toBe(2);
});

it('⚠️ FIX 3 — the constraint and its generated column are REALLY in the schema, and the migration de-dupes first', function () {
    // ⚠️ READS THE LIVE SCHEMA, not the migration file — a migration that silently no-ops (or that was run
    // against the wrong database, which happened while building this fix) would otherwise pass unnoticed.
    $create = (array) DB::selectOne('SHOW CREATE TABLE guest_waitlist_entries');
    $sql = (string) ($create['Create Table'] ?? '');

    expect($sql)->toContain('guest_waitlist_entries_identity_unique')
        ->and($sql)->toContain('`window_key`')
        ->and(strtoupper($sql))->toContain('GENERATED ALWAYS AS')
        ->and(strtoupper($sql))->toContain('STORED');

    // ⚠️ AND THE MIGRATION MUST DE-DUPE BEFORE CONSTRAINING. Adding a unique index to a table that already
    // holds duplicates throws — and it would throw in PRODUCTION, on real guest data, not here. The earliest
    // row is the keeper (it carries the guest's original `confirmed_at`, i.e. the place they actually earned).
    $migration = (string) file_get_contents(
        database_path('migrations/2026_08_21_000000_add_unique_identity_to_guest_waitlist_entries.php')
    );
    expect($migration)->toContain('->delete()')
        ->and($migration)->toContain("orderBy('created_at')")
        ->and($migration)->toContain('dropUnique')          // down() reverses cleanly
        ->and($migration)->toContain('dropColumn');
});

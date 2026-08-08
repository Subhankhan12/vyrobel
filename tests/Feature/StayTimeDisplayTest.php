<?php

use App\Support\StayTime;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * THE OWNER BOOKING DETAIL INVENTED ITS STAY TIMES — AND ONE OF THEM WAS WRONG (audit 2026-08-07 · GATE 6).
 *
 * Two separate defects sat on top of each other:
 *
 *  1. A CORRUPTED FORMAT STRING. The timeline ran `format('D j M · 3:00 PM')` — in PHP's date format `P`
 *     is the UTC offset and `M` the month, so the literal was eaten:
 *
 *         'D j M · 3:00 PM'  →  "Sat 5 Sep · 3:00 +00:00Sep"
 *
 *  2. HARDCODED TIMES, one of them simply FALSE. The hero card printed "from 3:00 PM" and "by 10:00 AM"
 *     while `properties.check_in_time` / `check_out_time` hold the real values — 15:00 and 11:00 for the
 *     demo properties. So the check-in hour was accidentally right and the CHECK-OUT HOUR WAS WRONG BY AN
 *     HOUR, told to an owner as fact. A fabricated figure that happens to match is the kind that survives
 *     review (#39); its neighbour that doesn't match is the one that gets someone's guest thrown out early.
 *
 * Passing a REAL time through a REAL format fixes both at once — there is no literal left to corrupt.
 * An unset column yields null so the caller OMITS the line rather than inventing an hour.
 */

it('formats a stored time without corrupting it', function () {
    expect(StayTime::display('15:00:00'))->toBe('3:00 PM')
        ->and(StayTime::display('11:00:00'))->toBe('11:00 AM')
        ->and(StayTime::display('09:30:00'))->toBe('9:30 AM')
        ->and(StayTime::display('15:00'))->toBe('3:00 PM')
        ->and(StayTime::display('15:00:00', 'H:i'))->toBe('15:00');

    // ⚠️ The exact corruption that shipped — no output may ever contain a UTC offset.
    expect(StayTime::display('15:00:00'))->not->toContain('+00:00');
});

it('returns NULL for an unset time so the caller omits the line', function () {
    expect(StayTime::display(null))->toBeNull()
        ->and(StayTime::display(''))->toBeNull()
        ->and(StayTime::display('   '))->toBeNull()
        ->and(StayTime::display('not a time'))->toBeNull()
        ->and(StayTime::labelled(null))->toBeNull();
});

it('prefixes only when there is a real time to prefix', function () {
    expect(StayTime::labelled('15:00:00'))->toBe('from 3:00 PM')
        ->and(StayTime::labelled('11:00:00', 'by '))->toBe('by 11:00 AM');
});

it('has no hardcoded stay time left in the owner booking detail', function () {
    // The defect was invisible to every static check — it was a string literal that looked like data.
    $view = file_get_contents(resource_path('views/livewire/owner/booking-detail.blade.php'));
    $component = file_get_contents(app_path('Livewire/Owner/OwnerBookingDetail.php'));

    expect($view)->not->toContain('from 3:00 PM')->not->toContain('by 10:00 AM');
    // The CALL form, not the literal — the docblock above the fix quotes the old string deliberately,
    // as the record of what went wrong, and that reference must not fail its own regression test.
    expect($component)->not->toContain("->format('D j M · 3:00 PM')");

    // …and it reads the property's real columns instead.
    expect($component)->toContain('check_in_time')->toContain('check_out_time');
});

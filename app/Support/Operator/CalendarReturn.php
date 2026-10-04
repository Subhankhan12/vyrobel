<?php

declare(strict_types=1);

namespace App\Support\Operator;

use Illuminate\Http\Request;

/**
 * BACK INTO THE CALENDAR — where a write sent from the Calendar returns (2026-10-04, the founder: "it is not possible to do
 * reservation" there). A price set or a season applied from its New reservation dialog comes back INTO the dialog on the same
 * stay; a booking made from the dialog, or one dragged to another night, comes back to the Calendar as the person had it.
 *
 * ⚠️ `return` is a fixed token, never a URL (an open redirect), and each part of the way back is checked or left out: real
 * dates, 7/14/30, an id's shape, 1–12 adults, a known source. The Calendar re-checks the filter and the unit against what it has.
 * ⚠️⚠️ NEVER THE GUEST'S NAME: it stays in the browser tab — `kept=1` asks the dialog to bring it back from there (#28, the rule
 * its "Check rack" follows since 2026-10-04).
 */
final class CalendarReturn
{
    /** `return` when a write is sent from the Calendar. */
    public const TOKEN = 'calendar';

    /** The sources a booking from the Calendar may carry — named as the full form names them. */
    public const SOURCES = ['direct' => 'Direct', 'walk_in' => 'Walk-in', 'corporate' => 'Corporate', 'agent' => 'Travel agent'];

    /** How many nights the Calendar may show at once. */
    public const RANGES = [7, 14, 30];

    public static function wanted(Request $request): bool
    {
        return $request->input('return') === self::TOKEN;
    }

    /** The Calendar as the person had it: its first night, 7/14/30 and the room-type filter. */
    public static function window(Request $request): string
    {
        return route('app.calendar', self::windowParams($request));
    }

    /** Back into the New reservation dialog, on the same stay and with the unit that was clicked. */
    public static function dialog(Request $request): string
    {
        $adults = filter_var($request->input('return_adults'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $source = $request->input('return_source');

        return route('app.calendar', self::windowParams($request) + array_filter([
            'new' => 1,
            'ci' => self::date($request->input('return_check_in')),
            'co' => self::date($request->input('return_check_out')),
            'adults' => $adults === false ? null : $adults,
            'source' => is_string($source) && array_key_exists($source, self::SOURCES) ? $source : null,
            'unit' => self::id($request->input('return_unit')),
            'kept' => 1,
        ], fn ($v): bool => $v !== null));
    }

    /** @return array<string, string|int> */
    private static function windowParams(Request $request): array
    {
        $days = filter_var($request->input('return_days'), FILTER_VALIDATE_INT);

        return array_filter([
            'start' => self::date($request->input('return_start')),
            'days' => in_array($days, self::RANGES, true) ? $days : null,
            'rt' => self::id($request->input('return_rt')),
        ], fn ($v): bool => $v !== null);
    }

    /** A Y-m-d that is a real calendar date ("2026-02-31" is not 3 March), or null. */
    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }

    /** An id's shape (a ULID) — the Calendar checks the id itself against what it has. */
    private static function id(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[0-9A-Za-z]{26}$/', $value) === 1 ? $value : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Unit;

use App\Http\Controllers\Operator\InventoryActionController;

/**
 * UNIT CODES, SEVERAL AT ONCE (2026-10-05, the founder: "Add inventory section design and functionality is not good. It is
 * so confusing") — what the Add units field and the Add room type dialog's "Units" field accept: one code ("101"), a list
 * ("101, 102, 105" — commas, semicolons or new lines) and ranges ("101-110", "A1-A12", "01-09"), mixed. 📖 Before, every
 * unit was its own dialog: forty rooms were forty dialogs. ONE parser, read by {@see InventoryActionController}; the
 * page's script mirrors it only to preview what will be added — the server's answer is the one that counts.
 *
 * ⚠️ A RANGE IS ONLY A RANGE WHEN IT CAN BE NOTHING ELSE: digits on both sides of the dash, the same prefix (or none) on
 * both, and a larger second number. "A-101", "Villa-2" and "12-3" stay single codes — and the preview shows which.
 * ⚠️ A range is COUNTED before it is expanded, so "1-99999999" is refused for its size and never built in memory.
 * ⚠️ Duplicates are compared without case, as the database's unique index (property, code) compares them.
 */
final class UnitCodes
{
    /** The most one add may create — a floor of a hotel, not a typo that makes thousands of rooms. */
    public const MAX = 100;

    /** The longest code the forms have ever taken. */
    public const MAX_LENGTH = 60;

    /** prefix · first number · dash · the same prefix again, or none · last number */
    private const RANGE = '/^(.*?)(\d{1,9})\s*[-–—]\s*(?:\1)?(\d{1,9})$/u';

    /**
     * @return array{codes: list<string>, error: ?string}
     */
    public static function parse(string $input): array
    {
        $specs = [];
        $total = 0;
        foreach (preg_split('/[,;\r\n]+/u', $input) ?: [] as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $spec = self::spec($part);
            $total += $spec['count'];
            $specs[] = $spec;
        }

        if ($specs === []) {
            return ['codes' => [], 'error' => 'Enter at least one unit code.'];
        }
        if ($total > self::MAX) {
            return ['codes' => [], 'error' => 'That is '.number_format($total).' units — add at most '.self::MAX.' at a time.'];
        }

        $codes = [];
        $seen = [];
        foreach ($specs as $spec) {
            foreach (self::expand($spec) as $code) {
                if (mb_strlen($code) > self::MAX_LENGTH) {
                    return ['codes' => [], 'error' => '“'.mb_substr($code, 0, 24).'…” is longer than '.self::MAX_LENGTH.' characters.'];
                }
                $key = mb_strtolower($code);
                if (isset($seen[$key])) {
                    return ['codes' => [], 'error' => $code.' is listed twice.'];
                }
                $seen[$key] = true;
                $codes[] = $code;
            }
        }

        return ['codes' => $codes, 'error' => null];
    }

    /**
     * The codes as a short phrase — consecutive numbers with one prefix read as a run: "101–103, 105, A1–A4".
     *
     * @param  list<string>  $codes
     */
    public static function summary(array $codes): string
    {
        $parts = [];
        $run = null;   // [prefix, width, start, end, first]
        foreach ($codes as $code) {
            $m = [];
            $numbered = preg_match('/^(.*?)(\d{1,9})$/u', $code, $m) === 1;
            if ($numbered && $run !== null && $m[1] === $run[0] && (int) $m[2] === $run[3] + 1
                && self::format($m[1], (int) $m[2], $run[1]) === $code) {
                $run[3]++;

                continue;
            }
            if ($run !== null) {
                $parts[] = self::runText($run);
            }
            $run = $numbered
                ? [$m[1], self::padWidth($m[2]), (int) $m[2], (int) $m[2], $code]
                : null;
            if (! $numbered) {
                $parts[] = $code;
            }
        }
        if ($run !== null) {
            $parts[] = self::runText($run);
        }

        $shown = array_slice($parts, 0, 6);
        $more = count($parts) - count($shown);

        return implode(', ', $shown).($more > 0 ? ' and '.$more.' more' : '');
    }

    /** @return array{part: string, prefix: string, from: int, to: int, width: int, count: int} */
    private static function spec(string $part): array
    {
        $m = [];
        if (preg_match(self::RANGE, $part, $m) === 1 && (int) $m[3] > (int) $m[2]) {
            return ['part' => $part, 'prefix' => $m[1], 'from' => (int) $m[2], 'to' => (int) $m[3],
                'width' => self::padWidth($m[2]), 'count' => (int) $m[3] - (int) $m[2] + 1];
        }

        return ['part' => $part, 'prefix' => '', 'from' => 0, 'to' => 0, 'width' => 0, 'count' => 1];
    }

    /**
     * @param  array{part: string, prefix: string, from: int, to: int, width: int, count: int}  $spec
     * @return list<string>
     */
    private static function expand(array $spec): array
    {
        if ($spec['count'] === 1 && $spec['to'] === 0) {
            return [$spec['part']];
        }

        $out = [];
        for ($n = $spec['from']; $n <= $spec['to']; $n++) {
            $out[] = self::format($spec['prefix'], $n, $spec['width']);
        }

        return $out;
    }

    /** "01" keeps its width through the range (01 … 12); "1" does not (1 … 12). */
    private static function padWidth(string $digits): int
    {
        return strlen($digits) > 1 && $digits[0] === '0' ? strlen($digits) : 0;
    }

    private static function format(string $prefix, int $n, int $width): string
    {
        return $prefix.($width > 0 ? str_pad((string) $n, $width, '0', STR_PAD_LEFT) : (string) $n);
    }

    /** @param  array{0: string, 1: int, 2: int, 3: int, 4: string}  $run */
    private static function runText(array $run): string
    {
        $last = self::format($run[0], $run[3], $run[1]);

        return match (true) {
            $run[3] === $run[2] => $run[4],
            $run[3] === $run[2] + 1 => $run[4].', '.$last,
            default => $run[4].'–'.$last,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Export;

/**
 * The ONE CSV formula-injection guard for every operator export (anti-drift — two copies WILL drift). A CSV
 * cell whose text begins with `=`, `+`, `-`, `@` or a tab is a formula/DDE payload when opened in Excel /
 * LibreOffice / Sheets (e.g. `=cmd|'/c calc'!A1`, `=HYPERLINK(...)`); we neutralise it by quote-prefixing so
 * the spreadsheet treats it as literal text, and we strip CR/LF so a field can't break the row structure.
 *
 * Apply {@see field()} to EVERY free-text (user-controllable) column written to a CSV — titles, names, unit
 * codes, notes. Numeric / enum / date columns are safe as-is but harmless to wrap.
 */
final class CsvSafe
{
    public static function field(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);

        return preg_match('/^[=+\-@\t]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * Neutralise an ENTIRE row — the form every export should use.
     *
     * Added 2026-08-08 (audit L1-02 / F-2b-05). Per-cell `field()` calls were the original contract, and the
     * result was predictable: of twelve CSV emitters only two used this class, three carried byte-identical
     * private copies, and all seven owner-realm exports sanitised NOTHING. Sanitising a whole row removes the
     * per-column judgement call that made it easy to miss one — and adding a column to an export can no
     * longer quietly add an unsanitised one.
     *
     * Non-strings (ints, floats, null) are cast then passed through unchanged in practice: a number can never
     * match the formula prefixes. `field()` remains public for the rare single-value case.
     *
     * @param  array<int, string|int|float|null>  $cells
     * @return array<int, string>
     */
    public static function row(array $cells): array
    {
        return array_map(static fn ($cell): string => self::field((string) $cell), $cells);
    }
}

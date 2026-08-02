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
}

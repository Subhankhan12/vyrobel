<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Support\Money\MoneyFormatter;
use Closure;
use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * D4-1 — THE ONE .xlsx idiom for this codebase. Every workbook export goes through here, so "how a money cell
 * is written" has a single definition and two surfaces cannot disagree about it (§N.8f).
 *
 * ⚠️ THE WHOLE POINT OF A WORKBOOK OVER A CSV IS THAT ITS CELLS ARE TYPED. A CSV is text: "€1,234.56" is the
 * only thing it can carry, and a spreadsheet opening it may or may not guess a number back. A workbook that
 * writes that same STRING is strictly worse than the CSV — it looks right and then refuses to SUM, which is
 * the one thing a person opens a workbook to do. So:
 *
 * 🔢 MONEY IS A NUMBER, FORMATTED — never a formatted string. The cell VALUE is the real amount (minor units
 *    ÷ 100) and the currency symbol lives in the cell's number FORMAT. It therefore displays exactly what
 *    {@see MoneyFormatter::display()} shows on screen (same symbol, same grouping, same 2 decimals) while
 *    still summing, averaging and charting. ⚠️ NO RE-PRICING HAPPENS HERE — the integer minor amount handed
 *    in is the one the screen renders (VAT-inclusive on the flat view, §G.1); this changes its TYPE, never
 *    its value.
 * 📅 DATES ARE DATE CELLS — a real date serial, so a spreadsheet can sort and group by them chronologically.
 *    A stringified "Sep" sorts alphabetically, which puts April first.
 * 📊 PERCENTAGES ARE PERCENT CELLS — the value is the fraction, the "%" lives in the format, so it displays
 *    as the page shows it and still averages correctly.
 *
 * ⚠️ STREAMING, DELIBERATELY. {@see Writer} writes each row straight to the file on disk and releases it —
 * memory stays flat regardless of row count, so a large export cannot OOM the request. Rows are therefore
 * produced INSIDE the callback (pull them in chunks if the set is big); never assemble the whole sheet in an
 * array first, which would reintroduce exactly the ceiling this avoids.
 *
 * ⚠️ THE TEMP FILE ALWAYS GOES. It is deleted after the response is sent on the happy path, and unlinked
 * explicitly if the build throws — a half-written workbook is never left behind and never served.
 */
final class Workbook
{
    /** Two decimals + thousands grouping, matching {@see MoneyFormatter::display()}'s shape. */
    private const MONEY_DECIMALS = '#,##0.00';

    public const DATE_FORMAT = 'yyyy-mm-dd';

    public const MONTH_FORMAT = 'mmm yyyy';

    public const PERCENT_FORMAT = '0%';

    /**
     * Build a workbook and hand it back as a download.
     *
     * ⚠️ The callback receives the live streaming {@see Writer}: add sheets and rows to it. Whatever it writes
     * is already on disk by the time it returns.
     */
    public static function download(string $filename, Closure $build): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'vy-xlsx-');
        if ($tmp === false) {
            throw new RuntimeException('Could not create a temporary file for the workbook.');
        }

        $writer = new Writer;
        $writer->openToFile($tmp);

        try {
            $build($writer);
            $writer->close();
        } catch (\Throwable $e) {
            // Never leave (or serve) a half-written workbook.
            try {
                $writer->close();
            } catch (\Throwable) {
                // The build already failed; closing is best-effort.
            }
            @unlink($tmp);

            throw $e;
        }

        return response()
            ->download($tmp, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);   // ⚠️ the temp file goes with the response, always
    }

    /**
     * 🔢 A money cell: NUMERIC value + a currency number format.
     *
     * ⚠️ `$minor` is the same integer the screen renders — this types it, it does not re-price it (§G.1).
     */
    public static function money(int $minor, string $currency): Cell
    {
        return Cell::fromValue($minor / 100, (new Style)->withFormat(self::moneyFormat($currency)));
    }

    /** The number format that renders a value the way {@see MoneyFormatter::display()} writes it. */
    public static function moneyFormat(string $currency): string
    {
        // The symbol is quoted so Excel treats it as a literal prefix rather than a locale currency token.
        return '"'.MoneyFormatter::symbol($currency).'"'.self::MONEY_DECIMALS;
    }

    /** 📅 A real date cell (sortable, groupable) — never the rendered label. */
    public static function date(DateTimeInterface $date, string $format = self::DATE_FORMAT): Cell
    {
        return Cell::fromValue($date, (new Style)->withFormat($format));
    }

    /**
     * 📊 A percent cell from a WHOLE-NUMBER percentage (the form the screen holds: 38 → "38%").
     *
     * The stored value is the fraction, so averaging a column behaves; the format supplies the sign.
     */
    public static function percent(int $whole): Cell
    {
        return Cell::fromValue($whole / 100, (new Style)->withFormat(self::PERCENT_FORMAT));
    }

    /**
     * A sheet name Excel accepts (I18N-01): at most 31 characters and none of \ / ? * : [ ], and not starting or ending with
     * an apostrophe. The English names already fit, so they pass through unchanged; a longer translation is cut, not refused —
     * an invalid name would throw mid-download.
     */
    public static function sheetName(string $name): string
    {
        $clean = trim((string) preg_replace('~[\\\\/?*:\[\]]+~u', ' ', $name), " '");

        return mb_substr($clean === '' ? 'Sheet' : $clean, 0, 31);
    }

    /** A bold heading row (a title or a column header). */
    public static function headingRow(array $values): Row
    {
        $bold = (new Style)->withFontBold(true);

        return new Row(array_map(fn ($v): Cell => Cell::fromValue($v, $bold), $values));
    }

    /** A plain row of already-built cells or scalar values. */
    public static function row(array $cells): Row
    {
        return new Row(array_map(fn ($c): Cell => $c instanceof Cell ? $c : Cell::fromValue($c), $cells));
    }
}

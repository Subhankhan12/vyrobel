<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\OwnerLedgerEntry;
use App\Support\Money\MoneyFormatter;
use App\Support\Payouts\RailLabel;

/**
 * THE ONE rule that turns `owner_ledger` rows into an owner-attributed NET and its gross→net breakdown —
 * shared by the owner Unit Detail, the owner Bookings list and the owner Booking Detail (Phase 2 · Sub-gate 1).
 * Same discipline as {@see OwnerOccupancy} / {@see MoneyFormatter} /
 * {@see RailLabel}: "Net to you" is the single figure an owner is most likely to dispute,
 * so the several surfaces that show it MUST derive it identically or they will drift.
 *
 * THE DEFINITION (canonical, unchanged): net = Σ {@see OwnerLedgerEntry::signedMinor()} — credit +, debit −.
 * There is no stored "net" column; `amount_minor` is always positive and `direction` carries the sign. A
 * reversal is an ordinary mirror row with the direction flipped (invariant #16), so summing signedMinor nets a
 * reversed pair to 0 automatically — no version filtering, no hand-picked credit/debit lists.
 *
 * ⚠️ THIS IS A PURE TRANSFORM — it never queries. The CALLER decides WHICH rows (this unit's, this owner's,
 * this reservation's) and hands them in; owner isolation lives in that query (owner-scoped {@see OwnerLedgerEntry}
 * + owned-unit resolution), never here. Sums are ALWAYS kept per currency — nothing is summed or converted
 * across currencies (invariant #17).
 */
final class OwnerLedgerAttribution
{
    /**
     * The canonical entry_type order for the breakdown — these EIGHT values ARE the enum. Ordering only:
     * nothing is added, dropped, or re-signed by listing them.
     */
    public const TYPE_ORDER = [
        'room_revenue', 'cleaning', 'addon', 'adjustment',
        'commission', 'management_fee', 'ota_cost', 'expense',
    ];

    /**
     * Per-currency net (minor units) over the given rows: currency → Σ signedMinor. ksort'd for a
     * deterministic currency order. This is THE net primitive — every per-booking / per-unit "Net to you"
     * figure comes through here.
     *
     * @param  iterable<OwnerLedgerEntry>  $entries
     * @return array<string, int>
     */
    public static function netPerCurrency(iterable $entries): array
    {
        $net = [];

        foreach ($entries as $entry) {
            $currency = (string) $entry->currency;
            $net[$currency] = ($net[$currency] ?? 0) + $entry->signedMinor();
        }

        ksort($net);

        return $net;
    }

    /**
     * Per-currency net rendered as display strings, in currency order — the list-cell form of
     * {@see netPerCurrency()}. Empty rows → `[]` (the caller renders "Not yet attributed", never a fake €0).
     *
     * @param  iterable<OwnerLedgerEntry>  $entries
     * @return list<string>
     */
    public static function netDisplays(iterable $entries): array
    {
        $out = [];

        foreach (self::netPerCurrency($entries) as $currency => $minor) {
            $out[] = MoneyFormatter::display($minor, $currency);
        }

        return $out;
    }

    /**
     * The gross→net breakdown, bucketed PER CURRENCY then PER entry_type (each bucket Σ signedMinor), split
     * into credits / deductions by each bucket's own sign so Credited + Deductions = Net EXACTLY, by
     * construction, whatever the rows are. This is the shape the owner Unit Detail and Booking Detail views
     * consume verbatim.
     *
     * @param  iterable<OwnerLedgerEntry>  $entries
     * @return list<array{currency: string, net_minor: int, net_display: string, credited_display: string, deducted_display: string, credits: list<array{label: string, display: string}>, deductions: list<array{label: string, display: string}>}>
     */
    public static function breakdown(iterable $entries): array
    {
        /** @var array<string, array<string, int>> $buckets */
        $buckets = [];

        foreach ($entries as $entry) {
            $currency = (string) $entry->currency;
            $type = (string) $entry->entry_type;
            $buckets[$currency][$type] = ($buckets[$currency][$type] ?? 0) + $entry->signedMinor();
        }

        ksort($buckets);   // deterministic currency order

        $blocks = [];

        foreach ($buckets as $currency => $byType) {
            $credits = [];
            $deductions = [];
            $creditedMinor = 0;
            $deductedMinor = 0;

            foreach (self::orderedTypes($byType) as $type) {
                $minor = $byType[$type];
                $row = [
                    'label' => self::entryTypeLabel($type),
                    'display' => MoneyFormatter::display($minor, $currency, true),
                ];

                if ($minor < 0) {
                    $deductions[] = $row;
                    $deductedMinor += $minor;
                } else {
                    $credits[] = $row;
                    $creditedMinor += $minor;
                }
            }

            $netMinor = $creditedMinor + $deductedMinor;   // deducted is already negative

            $blocks[] = [
                'currency' => $currency,
                'net_minor' => $netMinor,
                'net_display' => MoneyFormatter::display($netMinor, $currency),
                'credited_display' => MoneyFormatter::display($creditedMinor, $currency, true),
                'deducted_display' => MoneyFormatter::display($deductedMinor, $currency, true),
                'credits' => $credits,
                'deductions' => $deductions,
            ];
        }

        return $blocks;
    }

    /** entry_type enum → an owner-facing label. The 8 arms ARE the enum; nothing else is invented. */
    public static function entryTypeLabel(string $type): string
    {
        return match ($type) {
            'room_revenue' => 'Room revenue',
            'commission' => 'Commission',
            'management_fee' => 'Management fee',
            'ota_cost' => 'OTA cost',
            'cleaning' => 'Cleaning',
            'addon' => 'Add-on',
            'expense' => 'Expense',
            'adjustment' => 'Adjustment',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /**
     * The entry types present, in the canonical enum order. An unrecognised type (there should be none) is
     * appended rather than dropped — a row never vanishes from a total it is part of.
     *
     * @param  array<string, int>  $byType
     * @return list<string>
     */
    private static function orderedTypes(array $byType): array
    {
        $known = array_values(array_filter(
            self::TYPE_ORDER,
            fn (string $type): bool => array_key_exists($type, $byType),
        ));

        $unknown = array_values(array_diff(array_keys($byType), self::TYPE_ORDER));
        sort($unknown);

        return array_merge($known, $unknown);
    }
}

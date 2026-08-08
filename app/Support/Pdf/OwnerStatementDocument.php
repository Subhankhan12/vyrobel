<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Organization;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\PayoutMethod;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\OwnerStatementBreakdown;
use App\Support\Payouts\Iban;

/**
 * Builds the OWNER STATEMENT PDF view data from the REAL, already-finalized statement — rebuilt to the decoded
 * "Owner Statement PDF" wireframe (a print letterhead document). Pure + read-only: it never touches the
 * owner-accounting services/schema.
 *
 * RECONCILES (invariant #17): the breakdown rows are the SAME `owner_statement_lines` the screen reads and they sum
 * to the `net` line; the Performance-by-unit table comes from the ONE shared {@see OwnerStatementBreakdown} (which
 * the page uses too), so Σ(per-unit gross) == the statement `gross_minor` — the two surfaces cannot drift. Occupancy
 * / nights / ADR are presentation (never summed into money). NO guest data appears (a statement carries none, #28);
 * the "Payment & ops fees" line is a REAL engine bucket (Wave C · GATE 4) that flows through these same lines — €0
 * and omitted until a live-payment writer posts a fee, never fabricated (#39). The masked IBAN is shown ONLY when the exporter
 * is a payout-capable role (CAP_PAYOUT) — an accountant export never leaks the account number; the estimated payout
 * DATE stays refused (no field).
 */
final class OwnerStatementDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function data(OwnerStatement $statement, bool $canSeePayout = false): array
    {
        $currency = (string) $statement->currency;
        $org = Organization::query()->whereKey($statement->organization_id)->first();
        $company = OperatorLetterhead::registeredName($org instanceof Organization ? $org : null);

        $gross = (int) $statement->gross_minor;

        // The REAL breakdown lines (same rows the screen shows) + a truthful DETAIL sublabel per line.
        $breakdown = OwnerStatementBreakdown::forStatement($statement);
        $lines = OwnerStatementLine::query()
            ->where('owner_statement_id', $statement->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (OwnerStatementLine $l): array => [
                'description' => (string) $l->description,
                'detail' => self::lineDetail((string) $l->line_type, (int) $l->amount_minor, $gross, $breakdown),
                'amount_display' => MoneyFormatter::display((int) $l->amount_minor, (string) $l->currency, signed: $l->line_type !== 'net'),
                'is_negative' => (int) $l->amount_minor < 0,
                'is_net' => $l->line_type === 'net',
                'unverified' => (bool) $l->unverified,
            ])
            ->all();

        // REAL owner-scoped stats over the statement period — from the SAME shared breakdown as the by-unit table
        // (headline occupancy == the by-unit Total row; nights + ADR = gross ÷ nights). Never a fabricated %.
        // Gross/nights/ADR are the SOURCE-currency figures the breakdown computes (the per-unit rows are formatted
        // in it too) — label them with the source currency, NOT the payout currency, so an FX statement's by-unit
        // total + ADR agree with the per-unit rows (the net headline stays the payout currency, post-FX).
        $sourceCurrency = (string) $breakdown['currency'];
        $nights = (int) $breakdown['total_nights'];
        $grossTotal = (int) $breakdown['total_gross_minor'];
        $adrMinor = $nights > 0 ? (int) round($grossTotal / $nights) : 0;

        // The masked payout IBAN — payout-capable exporter only (co-owner); an accountant export omits it (#28).
        // Guard the last4 (a method persisted without it would otherwise mask to an empty "···· ").
        $method = $canSeePayout ? PayoutMethod::query()->orderByDesc('is_default')->first() : null;
        $ibanMasked = ($method instanceof PayoutMethod && filled($method->iban_last4))
            ? Iban::masked((string) $method->iban_last4)
            : null;

        $commissionPct = ($gross !== 0 && (int) $statement->commission_minor !== 0)
            ? (int) round(abs((int) $statement->commission_minor) / $gross * 100)
            : null;

        $issued = $statement->finalized_at !== null
            ? $statement->finalized_at->format('j M Y')
            : $statement->period_end->format('j M Y');

        return [
            // Letterhead / identity
            'company' => $company,
            'country' => $org instanceof Organization ? (string) ($org->country ?? '') : '',
            'billing_email' => $org instanceof Organization ? (string) ($org->billing_email ?? '') : '',
            'doc_ref' => 'OS-'.$statement->period_start->format('Y-m'),
            'sequence_no' => $statement->sequence_no,

            // Title / subtitle
            'period_label' => $statement->period_start->format('F Y'),
            'owner_name' => (string) ($statement->owner->name ?? 'Owner'),
            // The owner's REAL registered address (GATE 3) — the recipient block. Empty parts are dropped, so an
            // owner who hasn't set an address simply shows none (never a fabricated line, #39).
            'owner_address_lines' => self::addressLines(is_array($statement->owner?->address) ? $statement->owner->address : []),
            'unit_count' => (int) $breakdown['unit_count'],
            'issued' => $issued,

            // Net-payable headline + stats
            'currency' => $currency,
            'net_display' => MoneyFormatter::display((int) $statement->net_payable_minor, $currency),
            'status_label' => $statement->status === 'paid' ? 'Paid' : 'Payout pending',
            'iban_masked' => $ibanMasked,
            'occupancy_pct' => (int) $breakdown['total_occupancy_pct'],
            'nights' => $nights,
            'adr_display' => MoneyFormatter::display($adrMinor, $sourceCurrency),

            // Breakdown + by unit
            'lines' => $lines,
            'units' => $breakdown['available'] ? $breakdown['units'] : [],
            'unit_total' => [
                'nights' => $nights,
                'occupancy_pct' => (int) $breakdown['total_occupancy_pct'],
                'gross_display' => MoneyFormatter::display($grossTotal, $sourceCurrency),
            ],

            // Payout note
            'commission_pct' => $commissionPct,
            'has_unverified' => (bool) $statement->has_unverified,

            // FX shown ONLY when the statement genuinely converts currencies (operator-supplied rate, never a float).
            'fx' => ((string) $statement->source_currency !== $currency)
                ? ['source' => (string) $statement->source_currency, 'rate' => (string) $statement->fx_rate, 'src' => (string) $statement->fx_source]
                : null,
        ];
    }

    /**
     * The owner's registered address as printable lines: "street" then "postcode city" then "country". Blank
     * parts are dropped; an empty address yields no lines. Presentation only — never summed into money.
     *
     * @param  array<string, mixed>  $address
     * @return list<string>
     */
    private static function addressLines(array $address): array
    {
        $street = trim((string) ($address['street'] ?? ''));
        $cityLine = trim(trim((string) ($address['postcode'] ?? '')).' '.trim((string) ($address['city'] ?? '')));
        $country = trim((string) ($address['country'] ?? ''));

        return array_values(array_filter([$street, $cityLine, $country], fn (string $v): bool => $v !== ''));
    }

    /**
     * The line's truthful DETAIL column (the wireframe's middle column): gross → "N nights · M units";
     * commission / OTA → "X% of gross …". Null for the net line + fixed components. Derived from real figures.
     *
     * @param  array<string, mixed>  $breakdown
     */
    private static function lineDetail(string $lineType, int $amount, int $gross, array $breakdown): ?string
    {
        if ($lineType === 'gross') {
            if (($breakdown['available'] ?? false) !== true) {
                return null;
            }
            $nights = (int) $breakdown['total_nights'];
            $units = (int) $breakdown['unit_count'];

            return $nights.' '.($nights === 1 ? 'night' : 'nights').' · '.$units.' '.($units === 1 ? 'unit' : 'units');
        }

        if ($gross === 0 || ! in_array($lineType, ['commission', 'ota_cost'], true)) {
            return null;
        }

        $pct = (int) round(abs($amount) / $gross * 100);

        return $pct.'% of '.($lineType === 'commission' ? 'gross room revenue' : 'gross revenue');
    }
}

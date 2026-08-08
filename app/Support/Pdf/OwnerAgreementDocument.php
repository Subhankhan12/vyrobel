<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\AgreementAmendment;
use App\Models\ManagementAgreement;
use App\Models\Organization;
use App\Models\Owner;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\OwnerAgreementTerms;

/**
 * Builds the OWNER AGREEMENT / AMENDMENT PDF view data (Owner Agreement exact-match #82). Pure +
 * read-only; mirrors {@see OwnerRemittanceDocument} (letterhead + real fields + honest omissions).
 *
 * HONESTY (#19/#39) — every element is a real column or a value DERIVED from one; a document has no
 * "Soon", so an unset field is OMITTED, never invented.
 *   • REAL     — commission_pct, management fee, payout schedule/currency, effective dates, status,
 *                term/notice/auto-renew (when set), per-villa commission (the same resolveFor the
 *                attribution engine uses, #17); the amendment's version/title/summary/effective/terms and
 *                its real acknowledgment record (who/when).
 *   • DERIVED  — the operator VAT/country letterhead (real billing_company/vat/country when set).
 *   • OMITTED  — any unset term (row dropped); per-villa CLEANING (no per-unit cleaning rate modelled →
 *                "At cost", never €90); a legally-binding e-signature (this is an in-portal ACKNOWLEDGMENT,
 *                honestly framed — never a claim of legal signature validity).
 *   • NO GUEST PII — owner surface; no guest identity is reachable here.
 */
final class OwnerAgreementDocument
{
    /**
     * The base agreement summary PDF.
     *
     * @param  list<string>  $ownerIds
     * @return array<string, mixed>
     */
    public static function agreement(ManagementAgreement $a, Owner $owner, array $ownerIds): array
    {
        $org = self::org((string) $a->organization_id);

        $feeMinor = $a->management_fee_minor;
        $feeCurrency = $a->management_fee_currency;
        $fee = ($feeMinor !== null && is_string($feeCurrency) && strlen($feeCurrency) === 3)
            ? MoneyFormatter::display((int) $feeMinor, $feeCurrency)
            : null;

        $payoutCurrency = is_string($a->payout_currency) && $a->payout_currency !== ''
            ? $a->payout_currency
            : ($owner->payout_currency !== '' ? $owner->payout_currency : null);

        return [
            'operator' => self::operatorName($org),
            'operator_addr' => self::operatorAddr($org),
            'owner_name' => (string) ($owner->legal_name ?: ($owner->name ?? 'Owner')),
            'doc_ref' => 'MA-'.$a->effective_from->format('Y'),
            'commission' => $a->commission_pct === null ? null : self::pct((float) $a->commission_pct),
            'scope' => $a->unit_id === null ? 'Portfolio-wide' : 'Unit-specific',
            'status' => ucfirst((string) $a->status),
            'started' => $a->effective_from->format('j M Y'),
            'runs_until' => $a->effective_to?->format('j M Y'),          // null → "Open-ended" in the view
            'fee' => $fee,
            'fee_interval' => self::interval($a->management_fee_interval),
            'schedule' => self::interval($a->payout_schedule),
            'payout_currency' => $payoutCurrency,
            'term' => $a->term_months !== null ? $a->term_months.' months' : null,
            'notice' => $a->notice_days !== null ? $a->notice_days.' days' : null,
            'auto_renews' => $a->auto_renews,                            // null → row omitted
            'villas' => OwnerAgreementTerms::perVilla($ownerIds, $a->effective_from->toDateString() < now()->toDateString() ? now()->toDateString() : $a->effective_from->toDateString()),
            'generated_on' => now()->format('j M Y'),
        ];
    }

    /**
     * A single amendment PDF — its content + the real acknowledgment record (when acknowledged).
     *
     * @return array<string, mixed>
     */
    public static function amendment(AgreementAmendment $amendment, Owner $owner): array
    {
        $org = self::org((string) $amendment->organization_id);
        $amendment->loadMissing('acknowledgment.acknowledgedBy');
        $ack = $amendment->acknowledgment;

        return [
            'operator' => self::operatorName($org),
            'operator_addr' => self::operatorAddr($org),
            'owner_name' => (string) ($owner->legal_name ?: ($owner->name ?? 'Owner')),
            'doc_ref' => 'AM-'.$amendment->effective_date->format('Y'),
            'version' => (string) $amendment->version,
            'title' => (string) $amendment->title,
            'summary' => $amendment->summary !== null ? (string) $amendment->summary : null,
            'effective_date' => $amendment->effective_date->format('j M Y'),
            'changes' => self::changes(is_array($amendment->new_terms) ? $amendment->new_terms : []),
            'acknowledged_by' => $ack?->acknowledgedBy?->name,
            'acknowledged_at' => $ack?->acknowledged_at?->format('j M Y'),
            'generated_on' => now()->format('j M Y'),
        ];
    }

    /**
     * Turn the raw `new_terms` json into human rows — real values only; keys we don't recognise are
     * skipped rather than dumped.
     *
     * @param  array<string, mixed>  $terms
     * @return list<array{label: string, value: string}>
     */
    private static function changes(array $terms): array
    {
        $rows = [];

        if (isset($terms['commission_pct'])) {
            $rows[] = ['label' => 'Management fee', 'value' => self::pct((float) $terms['commission_pct']).' of booking revenue'];
        }
        if (isset($terms['management_fee_minor'], $terms['management_fee_currency'])) {
            $rows[] = ['label' => 'Fixed fee', 'value' => MoneyFormatter::display((int) $terms['management_fee_minor'], (string) $terms['management_fee_currency'])];
        }
        if (isset($terms['payout_schedule']) && self::interval($terms['payout_schedule']) !== null) {
            $rows[] = ['label' => 'Payout schedule', 'value' => (string) self::interval($terms['payout_schedule'])];
        }
        if (isset($terms['term_months'])) {
            $rows[] = ['label' => 'Term', 'value' => ((int) $terms['term_months']).' months'];
        }
        if (isset($terms['notice_days'])) {
            $rows[] = ['label' => 'Notice period', 'value' => ((int) $terms['notice_days']).' days'];
        }

        return $rows;
    }

    private static function org(string $id): Organization
    {
        return Organization::query()->whereKey($id)->firstOrNew(['id' => $id]);
    }

    private static function operatorName(Organization $org): string
    {
        return OperatorLetterhead::registeredName($org);
    }

    private static function operatorAddr(Organization $org): string
    {
        return OperatorLetterhead::line($org);
    }

    private static function interval(mixed $value): ?string
    {
        return match ($value) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            default => null,
        };
    }

    private static function pct(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2), '0'), '.').'%';
    }
}

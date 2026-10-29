<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Organization;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Support\Money\MoneyFormatter;
use App\Support\Payouts\RailLabel;
use Illuminate\Support\Carbon;

/**
 * Builds the OWNER REMITTANCE ADVICE PDF view data for ONE payout (Wave 1 · Sub-gate 2). Pure +
 * read-only — it never touches the certified owner-accounting services/schema, and mirrors
 * {@see OwnerStatementDocument}: the breakdown is the REAL `owner_statement_lines` of the payout's
 * source statement (the same rows the owner portal reads), rendered through the shared MoneyFormatter,
 * so the advice reconciles to the screen to the character — not merely to the cent.
 *
 * HONESTY (invariant #19) — every element is a certified read or a value DERIVED from one; the design's
 * fiction is OMITTED, because a document has no "Soon": it states a real fact or stays silent.
 *   • REAL     — amount / currency / status / rail / paid_at / external_ref / failure_reason (payout);
 *                sequence_no / period / fx (statement); the statement's component + net lines (commission
 *                at the REAL agreement rate — it IS the certified statement line, never a hardcoded %).
 *   • DERIVED  — the amount LABEL and the rail/status words (from the real enums); the operator VAT/country
 *                LETTERHEAD (real `billing_company`/`billing_vat_id`/`country`, shown only when set); the
 *                masked payout IBAN (the owner's real `iban_last4` via the shared Iban::masked, passed in
 *                CAP_PAYOUT-gated by the controller — #28, never the full number, omitted otherwise); the
 *                `RA-YYYY-MM` reference (period-derived, like the booking-summary's BS-YYYY-MM).
 *   • OMITTED  — an ESTIMATED settle date (there is no estimate field — `paid_on` shows ONLY once the payout
 *                is genuinely paid, never a projection), a "Scheduled" status (not in the enum) and a "SEPA"
 *                claim (the rail word comes from RailLabel). Reference / Paid-on / Failure / FX / IBAN rows
 *                appear ONLY when the underlying field is genuinely set.
 *   • NO GUEST PII — owner surfaces show no guest names (locked precedent); statement lines are bucket
 *                labels, so no guest identity can reach this document.
 */
final class OwnerRemittanceDocument
{
    /**
     * @param  string|null  $ibanMasked  the owner's masked payout IBAN — resolved CAP_PAYOUT-gated by the caller
     *                                   ({@see OwnerNextPayout}); null for a non-payout-capable viewer (#28).
     * @return array<string, mixed>
     */
    public static function data(Payout $payout, ?string $ibanMasked = null): array
    {
        $currency = (string) $payout->currency;
        $status = (string) $payout->status;

        // Letterhead — the operator's REAL registered identity (same rule as the statement/booking-summary PDFs):
        // VAT + country shown ONLY when set; the wireframe's street/city is not modelled, so only the country.
        $org = Organization::query()->whereKey($payout->organization_id)->firstOrNew(['id' => $payout->organization_id]);
        $addr = OperatorLetterhead::line($org);   // the ONE letterhead rule (shared)

        // Owner-scoped relation off the already-scoped payout. `owner_statement_id` is a NOT NULL FK, so
        // this resolves for every payout the owner can see; still guarded, so a missing statement OMITS
        // its rows rather than inventing them.
        $statement = $payout->statement;

        $lines = $statement === null ? [] : OwnerStatementLine::query()
            ->where('owner_statement_id', $statement->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (OwnerStatementLine $l): array => [
                'description' => $l->label(),
                'amount_display' => MoneyFormatter::display((int) $l->amount_minor, (string) $l->currency, signed: $l->line_type !== 'net'),
                'is_negative' => (int) $l->amount_minor < 0,
                'is_net' => $l->line_type === 'net',
                'unverified' => (bool) $l->unverified,
            ])
            ->all();

        return [
            'operator' => OperatorLetterhead::registeredName($org),
            'operator_addr' => $addr,
            // A period-derived remittance reference (RA-YYYY-MM), like the booking-summary's BS-YYYY-MM — derived,
            // never fabricated; the accompanied statement is cross-referenced by its REAL sequence_no.
            'doc_ref' => $statement !== null ? 'RA-'.$statement->period_start->format('Y-m') : null,
            // The owner's masked payout IBAN — passed in CAP_PAYOUT-gated; null → the row is omitted (#28). Never full.
            'iban_masked' => $ibanMasked,
            // The payee is the owner's REGISTERED legal name when set (from the Tax & remittance details), else
            // their display name — so the remittance advice prints the same payee the owner captured.
            'owner_name' => (string) ($payout->owner?->legal_name ?: ($payout->owner->name ?? __('php/support/pdf/owner_remittance_document.owner_fallback'))),
            'currency' => $currency,

            // ── The payout — REAL columns; the enums supply every word ──────────────────────────────
            'amount_display' => MoneyFormatter::display((int) $payout->amount_minor, $currency),
            // DERIVED from the real status: a pending/failed payout has NOT been paid, so the document
            // never claims it was.
            'amount_label' => match ($status) {
                'paid' => __('php/support/pdf/owner_remittance_document.amount_net_paid'),
                'reversed' => __('php/support/pdf/owner_remittance_document.amount_net_reversed'),
                default => __('php/support/pdf/owner_remittance_document.amount_net_payable'),
            },
            'status_label' => self::statusLabel($status),
            // The status enum (pending/paid/failed/reversed) IS the tone vocabulary — the blade styles a
            // pill per real state, so an invented state cannot be rendered.
            'status_tone' => $status,
            // The SHARED rail map (App\Support\Payouts\RailLabel) — the same one the owner Payouts screen
            // renders, so the document and the screen can never disagree about how a payout was sent.
            'method' => RailLabel::for((string) $payout->rail),
            // A real settlement date exists ONLY once the payout is paid — never an estimate.
            'paid_on' => ($status === 'paid' && $payout->paid_at !== null)
                ? Carbon::parse($payout->paid_at)->translatedFormat('j M Y')
                : null,
            // The operator's own bank reference, when they attached one. Never generated here.
            'reference' => (is_string($payout->external_ref) && $payout->external_ref !== '') ? $payout->external_ref : null,
            'failure_reason' => ($status === 'failed' && is_string($payout->failure_reason) && $payout->failure_reason !== '')
                ? $payout->failure_reason
                : null,

            // ── The source statement — the REAL reconciling breakdown ───────────────────────────────
            'sequence_no' => $statement?->sequence_no,
            'period_label' => $statement?->period_start->translatedFormat('F Y'),
            'period_range' => $statement === null
                ? null
                : $statement->period_start->translatedFormat('j M Y').' – '.$statement->period_end->translatedFormat('j M Y'),
            'lines' => $lines,
            'has_unverified' => $statement !== null && (bool) $statement->has_unverified,
            // FX shown ONLY when the statement genuinely converts currencies (source ≠ payout currency;
            // operator-supplied rate, never a silent float) — same rule as the statement PDF/screen.
            'fx' => ($statement !== null && (string) $statement->source_currency !== (string) $statement->currency)
                ? ['source' => (string) $statement->source_currency, 'rate' => (string) $statement->fx_rate, 'src' => (string) $statement->fx_source]
                : null,
        ];
    }

    /** The payout `status` enum → the word on the status pill (the tone stays the code). */
    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('php/support/pdf/owner_remittance_document.status_pending'),
            'held' => __('php/support/pdf/owner_remittance_document.status_held'),
            'processing' => __('php/support/pdf/owner_remittance_document.status_processing'),
            'paid' => __('php/support/pdf/owner_remittance_document.status_paid'),
            'failed' => __('php/support/pdf/owner_remittance_document.status_failed'),
            'reversed' => __('php/support/pdf/owner_remittance_document.status_reversed'),
            default => ucfirst($status),
        };
    }
}

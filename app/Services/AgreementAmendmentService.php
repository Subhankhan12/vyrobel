<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgreementAmendment;
use App\Models\AgreementAmendmentAcknowledgment;
use App\Models\ManagementAgreement;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Support\Facades\DB;

/**
 * The REAL amendment-acknowledgment flow (Owner Agreement exact-match #82) — the buildable replacement for
 * the wireframe's fake "Review & sign" e-signature.
 *
 * The owner ACKNOWLEDGES a published {@see AgreementAmendment} (a real, audited record of consent — WHO,
 * WHEN, WHICH version). Acknowledging APPLIES the new terms via the CERTIFIED effective-dating that
 * {@see ManagementAgreement} already trusts (the attribution engine keys off "the agreement in effect on
 * that night"): the current portfolio-wide agreement is bounded at effective_date − 1 and a SUPERSEDING
 * agreement is created from effective_date carrying the amendment's `new_terms`. History is never
 * rewritten — nights before the effective date keep the old terms, nights on/after use the new; because
 * the model's `effectiveOn` scope is date-keyed, the change becomes "in effect" automatically when the
 * date arrives, with no scheduler.
 *
 * ⚠️ Amendments should be PUBLISHED with a forward effective_date (an operator concern) so no
 * already-attributed night is re-bucketed. This service honours the stated effective_date verbatim rather
 * than silently shifting it.
 *
 * ⚠️ In-portal ACKNOWLEDGMENT, NOT a legally-binding e-signature — that is honest-deferred to real e-sig
 * infrastructure. The record is real; the wording never claims legal signature validity.
 */
class AgreementAmendmentService
{
    /** The agreement fields an amendment's `new_terms` may change. */
    private const TERM_KEYS = [
        'commission_pct',
        'management_fee_minor',
        'management_fee_currency',
        'management_fee_interval',
        'payout_currency',
        'payout_schedule',
        'term_months',
        'notice_days',
        'auto_renews',
        'attribution_rules',
    ];

    public function __construct(
        private readonly AuditEventRecorder $audit,
    ) {}

    /**
     * Record the owner's acknowledgment of an amendment and apply its terms. Idempotent: an already-applied
     * amendment returns its existing acknowledgment untouched (a re-submit is never a second consent).
     */
    public function acknowledge(AgreementAmendment $amendment, User $actor): AgreementAmendmentAcknowledgment
    {
        // Idempotent — a double-submit (or a retry) records once. The unique index backs this at the DB.
        $existing = $amendment->acknowledgment;
        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($amendment, $actor): AgreementAmendmentAcknowledgment {
            $ack = AgreementAmendmentAcknowledgment::create([
                'organization_id' => $amendment->organization_id,
                'amendment_id' => $amendment->id,
                'owner_id' => $amendment->owner_id,
                'acknowledged_by' => $actor->getKey(),
                'acknowledged_version' => (string) $amendment->version,
                'acknowledged_at' => now(),
            ]);

            $this->applyTerms($amendment);

            $amendment->update(['status' => AgreementAmendment::STATUS_APPLIED]);

            // #20 — WHO acknowledged WHAT; the version is recorded, never the changed values verbatim.
            $this->audit->record(
                'owner.agreement.amendment_acknowledged',
                $amendment,
                null,
                ['version' => (string) $amendment->version, 'effective_date' => $amendment->effective_date->toDateString()],
                'user',
                (string) $actor->getKey(),
                (string) $amendment->organization_id,
            );

            return $ack;
        });
    }

    /**
     * Apply the amendment's new terms via effective-dating. Bounds the current portfolio-wide agreement at
     * effective_date − 1 and creates a superseding one from effective_date. A `new_terms` of null/empty is
     * a no-op (the amendment carried no term change — it is still a real acknowledged document).
     */
    private function applyTerms(AgreementAmendment $amendment): void
    {
        $terms = is_array($amendment->new_terms) ? $amendment->new_terms : [];
        $terms = array_intersect_key($terms, array_flip(self::TERM_KEYS));

        if ($terms === []) {
            return;
        }

        $effectiveFrom = $amendment->effective_date;

        // The portfolio-wide agreement in effect on the effective date is the one being superseded
        // (per-unit overrides are their own effective-dated rows and are untouched — most-specific-wins).
        $current = ManagementAgreement::query()
            ->where('owner_id', $amendment->owner_id)
            ->whereNull('unit_id')
            ->effectiveOn($effectiveFrom)
            ->orderByDesc('effective_from')
            ->first();

        // Clone the current fields (so unchanged terms carry forward), then override with the amendment.
        $base = $current instanceof ManagementAgreement
            ? $current->only(self::TERM_KEYS)
            : [];

        ManagementAgreement::create(array_merge($base, $terms, [
            'organization_id' => $amendment->organization_id,
            'owner_id' => $amendment->owner_id,
            'unit_id' => null,
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
            'status' => 'active',
        ]));

        // Bound the superseded agreement the day before the new terms take effect — never delete it, so
        // attribution for past nights is preserved (append-only history, not a rewrite).
        if ($current instanceof ManagementAgreement) {
            $current->update(['effective_to' => $effectiveFrom->copy()->subDay()]);
        }
    }
}

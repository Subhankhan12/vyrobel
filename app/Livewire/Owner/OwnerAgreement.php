<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\AgreementAmendment;
use App\Models\ManagementAgreement;
use App\Models\Organization;
use App\Models\Owner;
use App\Models\OwnerExpense;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerAttributionService;
use App\Support\Owner\OwnerAgreementTerms;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Tenancy\TenantContext;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Agreement (Wave 1 · Sub-gate 1) — the owner's READ-ONLY view of the commercial terms the
 * operator has set for them: the agreement in effect today, any per-unit overrides, and the earlier /
 * upcoming terms. Styled from the catalogued "Owner Agreement" design. Presentation only: NO change to
 * {@see ManagementAgreement}, the attribution engine, schema, or guarantees. No route-model-bound props
 * — every read happens in render() under the active owner+tenant scope. The owner can NEVER edit terms.
 *
 * ⚠️ THE ISOLATION-CRITICAL BIT — `management_agreements` is **NOT** owner-scoped. The model carries
 * `BelongsToOrganization` only, so {@see OwnerScope} does NOT protect it: inside one tenant a naive
 * `ManagementAgreement::all()` would hand this owner EVERY other owner's commission and fees. The same
 * trap applies to `owners`. Both are therefore scoped EXPLICITLY here via
 * {@see OwnerContext::ownerIds()}, and fail closed (no query at all) when the portal scope is inactive
 * or resolves to an empty owner set. `units` IS owner-scoped ({@see Unit} uses BelongsToOwner), so the
 * unit lookup is already restricted.
 *
 * BelongsToOwner is deliberately NOT added to the model: {@see OwnerAttributionService}
 * resolves agreements OUTSIDE owner context (operator, CLI, queue), where a global scope would fail
 * closed and break the certified backend. Explicit scoping in this component is the correct fix.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — commission_pct, management fee (minor+currency) + interval, payout schedule, payout
 *                currency, scope (unit_id null = portfolio-wide, else the owner-scoped unit),
 *                effective_from / effective_to, status, and the attribution_rules OVERRIDES verbatim.
 *   • DERIVED  — "in effect today" comes from the model's CERTIFIED `effectiveOn` scope (never a
 *                re-implemented date predicate); the hero picks the portfolio-wide agreement in effect,
 *                and everything else in effect is listed beside it; the effective payout currency is
 *                agreement.payout_currency ?? owners.payout_currency (labelled with its source).
 *   • NULLS    — every nullable column renders honestly: no commission → "Not set" (NEVER "0%"), no
 *                fee → "Not set", effective_to null → "Open-ended", no overrides → "Standard
 *                attribution". A figure is never invented to fill a slot.
 *   • STATUS   — the REAL enum ONLY (active | ended). The design's "Amendment ready to sign" /
 *                "2026 amendment signed" states DO NOT EXIST and are never rendered. Rows not in
 *                effect carry a factual DATE note ("Starts …" / "Ran to …") — never an invented status.
 *   • NO BACKEND — e-signature / amendment / "Review & sign" / "Preview" / "Auto-renews" / term +
 *                notice period: OMITTED entirely (no model, no signing state, no column). "Download
 *                agreement" (no stored signed PDF exists) and "Message your manager" (no owner
 *                messaging surface): disabled "Soon", never a fake link or document.
 *   • CURRENCY — no total is computed anywhere on this screen (terms are rendered per agreement), so
 *                no cross-currency sum exists; the fee is always shown in its OWN currency (#17).
 */
#[Layout('components.layouts.owner')]
class OwnerAgreement extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $ownerIds = $this->scopedOwnerIds();
        $today = Carbon::today();

        // ⚠️ EXPLICIT owner scoping — OwnerScope does not cover this table (see the class docblock).
        // An empty owner set runs NO query at all: the screen fails closed to its empty state.
        /** @var Collection<int, ManagementAgreement> $all */
        $all = $ownerIds === []
            ? new Collection
            : ManagementAgreement::query()
                ->whereIn('owner_id', $ownerIds)
                ->orderByDesc('effective_from')
                ->orderByDesc('created_at')
                ->get();

        // "In effect today" via the model's CERTIFIED effectiveOn scope (status=active AND
        // effective_from <= today <= effective_to|null) — the date predicate is never duplicated here.
        $currentIds = $ownerIds === []
            ? []
            : ManagementAgreement::query()
                ->whereIn('owner_id', $ownerIds)
                ->effectiveOn($today)
                ->pluck('id')
                ->map(fn (mixed $id): string => (string) $id)
                ->all();

        $current = $all->filter(fn (ManagementAgreement $a): bool => in_array((string) $a->id, $currentIds, true))->values();

        // The portfolio-wide agreement in effect headlines; per-unit agreements are overrides on top of
        // it (most-specific-wins is the attribution engine's job, not this screen's).
        $primary = $current->first(fn (ManagementAgreement $a): bool => $a->unit_id === null) ?? $current->first();
        $primaryId = $primary instanceof ManagementAgreement ? (string) $primary->id : null;

        $units = $this->unitLabels($all);
        $owners = $this->ownerRecords($ownerIds);
        $multiOwner = count($ownerIds) > 1;

        // The primary owner id for capability + per-owner reads (the headlined agreement's owner).
        $primaryOwnerId = $primary instanceof ManagementAgreement ? (string) $primary->owner_id : ($ownerIds[0] ?? null);
        $context = app(OwnerContext::class);

        return view('livewire.owner.agreement', [
            'viewer' => $this->viewer(),
            'active' => $primary instanceof ManagementAgreement ? $this->card($primary, $units, $owners, $multiOwner) : null,
            'overrides' => $primary instanceof ManagementAgreement ? $this->attributionOverrides($primary) : null,
            'currentCount' => $current->count(),
            'others' => $this->rows(
                $current->reject(fn (ManagementAgreement $a): bool => (string) $a->id === $primaryId),
                $units, $owners, $multiOwner, $today,
            ),
            'history' => $this->rows(
                $all->reject(fn (ManagementAgreement $a): bool => in_array((string) $a->id, $currentIds, true)),
                $units, $owners, $multiOwner, $today,
            ),
            // ── Owner Agreement exact-match (#82) ──────────────────────────────────────────────────
            'operatorName' => $this->operatorName(),
            'amendment' => $this->amendmentCard($ownerIds),
            'feeCovers' => $primary instanceof ManagementAgreement ? $this->feeCovers($ownerIds, $primary) : null,
            'villas' => OwnerAgreementTerms::perVilla($ownerIds, $today->toDateString()),
            'keyTerms' => $primary instanceof ManagementAgreement ? $this->keyTerms($primary) : [],
            'documents' => $this->documents($ownerIds),
            // Capability flags — the acknowledgment is CAP_ACTIONS (co-owner); PDFs are CAP_EXPORTS.
            'canAcknowledge' => $primaryOwnerId !== null && $context->canOn($primaryOwnerId, OwnerTeamRole::CAP_ACTIONS),
            'canExport' => $primaryOwnerId !== null && $context->canOn($primaryOwnerId, OwnerTeamRole::CAP_EXPORTS),
        ]);
    }

    /** The operator/organisation name for the header copy ("Your terms with {operator}"). */
    private function operatorName(): string
    {
        $id = app(TenantContext::class)->id();
        $name = $id === null ? null : Organization::query()->whereKey($id)->value('name');

        return is_string($name) && $name !== '' ? $name : 'your operator';
    }

    /**
     * The single amendment to surface (the wireframe's toggling card): the PENDING one awaiting
     * acknowledgment if any, else the most recently APPLIED one. Owner-scoped EXPLICITLY (the table
     * carries no OwnerScope); null when the owner has no amendments.
     *
     * @param  list<string>  $ownerIds
     * @return array<string, mixed>|null
     */
    private function amendmentCard(array $ownerIds): ?array
    {
        if ($ownerIds === []) {
            return null;
        }

        $pending = AgreementAmendment::query()
            ->whereIn('owner_id', $ownerIds)
            ->where('status', AgreementAmendment::STATUS_PUBLISHED)
            ->orderBy('effective_date')
            ->first();

        $amendment = $pending ?? AgreementAmendment::query()
            ->whereIn('owner_id', $ownerIds)
            ->where('status', AgreementAmendment::STATUS_APPLIED)
            ->with('acknowledgment.acknowledgedBy')
            ->orderByDesc('effective_date')
            ->first();

        if (! $amendment instanceof AgreementAmendment) {
            return null;
        }

        $amendment->loadMissing('acknowledgment.acknowledgedBy');
        $ack = $amendment->acknowledgment;

        return [
            'id' => (string) $amendment->id,
            'needs' => $amendment->needsAcknowledgment(),
            'version' => (string) $amendment->version,
            'title' => (string) $amendment->title,
            'summary' => $amendment->summary !== null ? (string) $amendment->summary : null,
            'effective_display' => $amendment->effective_date->format('j M Y'),
            'acknowledged_by' => $ack?->acknowledgedBy?->name,
            'acknowledged_display' => $ack?->acknowledged_at?->format('j M Y'),
            'changes' => $this->amendmentChanges(is_array($amendment->new_terms) ? $amendment->new_terms : []),
        ];
    }

    /**
     * Human rows for an amendment's `new_terms` (Preview) — real values only.
     *
     * @param  array<string, mixed>  $terms
     * @return list<array{label: string, value: string}>
     */
    private function amendmentChanges(array $terms): array
    {
        $rows = [];

        if (isset($terms['commission_pct'])) {
            $rows[] = ['label' => 'Management fee', 'value' => $this->pct((float) $terms['commission_pct']).' of booking revenue'];
        }
        if (isset($terms['payout_schedule'])) {
            $label = $this->intervalLabel($terms['payout_schedule']);
            if ($label !== null) {
                $rows[] = ['label' => 'Payout schedule', 'value' => $label];
            }
        }
        if (isset($terms['term_months'])) {
            $rows[] = ['label' => 'Term', 'value' => ((int) $terms['term_months']).' months'];
        }
        if (isset($terms['notice_days'])) {
            $rows[] = ['label' => 'Notice period', 'value' => ((int) $terms['notice_days']).' days'];
        }

        return $rows;
    }

    /**
     * "What your fee covers" — INCLUDED is honest descriptive copy of what full-service management is (a
     * generic service description, NOT a per-owner fabricated term). AT-COST is REAL: the distinct
     * deductible expense categories that genuinely bill the owner (reconciling with the Expenses page);
     * honest empty when none have been charged.
     *
     * @param  list<string>  $ownerIds
     * @return array{included: list<string>, addons: list<string>}
     */
    private function feeCovers(array $ownerIds, ManagementAgreement $primary): array
    {
        $included = [
            'Listing & channel distribution',
            'Guest communications & support',
            'Housekeeping coordination',
            'Pricing & revenue management',
            'Statements & payouts',
            'Damage & incident handling',
        ];

        // REAL at-cost add-ons = the distinct categories of deductible expenses actually charged to this
        // owner (the same rows the Expenses page shows). Never a fabricated "Professional photography".
        $addons = $ownerIds === []
            ? []
            : OwnerExpense::query()
                ->whereIn('owner_id', $ownerIds)
                ->where('deductible', true)
                ->pluck('category')
                ->filter(fn ($c): bool => is_string($c) && $c !== '')
                ->unique()
                ->map(fn (string $c): string => Str::headline($c))
                ->values()
                ->all();

        return ['included' => $included, 'addons' => $addons];
    }

    /**
     * Key terms — the REAL fields of the agreement in effect, each rendered only when set. Owner-stays and
     * cancellations are NOT modelled → never fabricated ("30 nights fee-free" / "per your policy" omitted).
     *
     * @return list<array{label: string, value: string}>
     */
    private function keyTerms(ManagementAgreement $a): array
    {
        $rows = [];

        $schedule = $this->intervalLabel($a->payout_schedule);
        if ($schedule !== null) {
            $rows[] = ['label' => 'Payout schedule', 'value' => $schedule];
        }
        // A fixed periodic management fee is real data the wireframe has no slot for (its mock has none) —
        // surfaced honestly when set, never fabricated when null.
        $fee = $this->feeDisplay($a);
        if ($fee !== null) {
            $interval = $this->intervalLabel($a->management_fee_interval);
            $rows[] = ['label' => 'Management fee', 'value' => $fee.($interval !== null ? ' · '.$interval : '')];
        }
        if ($a->notice_days !== null) {
            $rows[] = ['label' => 'Notice period', 'value' => $a->notice_days.' days, either side'];
        }
        if ($a->term_months !== null) {
            $rows[] = ['label' => 'Term', 'value' => $a->term_months.' months'];
        }
        if ($a->auto_renews !== null) {
            $rows[] = ['label' => 'Renewal', 'value' => $a->auto_renews ? 'Auto-renewing' : 'Does not auto-renew'];
        }

        return $rows;
    }

    /**
     * The Documents list — the base agreement PDF + one row PER real amendment (with its real
     * acknowledgment date, or "Awaiting acknowledgment"). Owner-scoped explicitly. The PDFs are
     * CAP_EXPORTS-gated on the route; the links are shown to any viewer but 403 without the capability
     * (the @if in the view hides them for a view-only sub-user).
     *
     * @param  list<string>  $ownerIds
     * @return array<string, mixed>
     */
    private function documents(array $ownerIds): array
    {
        $amendments = $ownerIds === []
            ? collect()
            : AgreementAmendment::query()
                ->whereIn('owner_id', $ownerIds)
                ->visible()
                ->with('acknowledgment')
                ->orderByDesc('effective_date')
                ->get()
                ->map(fn (AgreementAmendment $m): array => [
                    'id' => (string) $m->id,
                    'label' => (string) $m->version.' amendment',
                    'meta' => $m->acknowledgment !== null
                        ? 'Acknowledged '.$m->acknowledgment->acknowledged_at?->format('j M Y')
                        : 'Awaiting acknowledgment',
                ])
                ->all();

        return ['amendments' => $amendments];
    }

    /** Trim a decimal percent to its shortest form: 18.00 → "18%" — via the ONE shared formatter. */
    private function pct(float $value): string
    {
        return (string) OwnerCommissionRate::display($value);
    }

    /**
     * The owner(s) the portal scope resolved for this request. FAIL-CLOSED: an inactive scope
     * (operator / CLI / queue — where OwnerScope does not constrain) yields NO ids, so this screen
     * shows nothing rather than every owner's commercial terms.
     *
     * @return list<string>
     */
    private function scopedOwnerIds(): array
    {
        $context = app(OwnerContext::class);

        return $context->active() ? $context->ownerIds() : [];
    }

    /**
     * The full presentation payload for one agreement.
     *
     * @param  array<string, string>  $units
     * @param  array<string, Owner>  $owners
     * @return array<string, mixed>
     */
    private function card(ManagementAgreement $a, array $units, array $owners, bool $multiOwner): array
    {
        $owner = $owners[(string) $a->owner_id] ?? null;
        $fee = $this->feeDisplay($a);
        $currency = $this->payoutCurrency($a, $owner);

        return [
            'commission_display' => $this->commissionDisplay($a),
            'fee_display' => $fee,
            'fee_interval' => $fee === null ? null : $this->intervalLabel($a->management_fee_interval),
            'schedule' => $this->intervalLabel($a->payout_schedule),
            'payout_currency' => $currency['code'],
            'payout_currency_source' => $currency['source'],
            'scope_label' => $this->scopeLabel($a, $units),
            'from_display' => $this->dateDisplay($a->effective_from),
            'to_display' => $this->dateDisplay($a->effective_to),
            'status_meta' => $this->agreementStatusMeta((string) $a->status),
            'owner_label' => $multiOwner && $owner instanceof Owner ? (string) $owner->name : null,
            // Additive contractual terms (#82) — each honest: null → the row is omitted, never fabricated.
            'term_display' => $a->term_months !== null ? $a->term_months.' months' : null,
            'notice_display' => $a->notice_days !== null ? $a->notice_days.' days' : null,
            'auto_renews' => $a->auto_renews,
            'unit_count' => $this->ownerUnitCount((string) $a->owner_id),
        ];
    }

    /** The count of units this owner has under management — the real "manages N villas" figure. */
    private function ownerUnitCount(string $ownerId): int
    {
        return Unit::query()->where('owner_id', $ownerId)->count();
    }

    /**
     * Table rows for the agreements beside the hero (other terms in effect) and for history.
     *
     * @param  Collection<int, ManagementAgreement>  $set
     * @param  array<string, string>  $units
     * @param  array<string, Owner>  $owners
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $set, array $units, array $owners, bool $multiOwner, Carbon $today): array
    {
        return $set->map(function (ManagementAgreement $a) use ($units, $owners, $multiOwner, $today): array {
            $owner = $owners[(string) $a->owner_id] ?? null;

            return [
                'scope_label' => $this->scopeLabel($a, $units),
                'owner_label' => $multiOwner && $owner instanceof Owner ? (string) $owner->name : null,
                'from_display' => $this->dateDisplay($a->effective_from),
                'to_display' => $this->dateDisplay($a->effective_to),
                'timing_note' => $this->timingNote($a, $today),
                'commission_display' => $this->commissionDisplay($a),
                'fee_display' => $this->feeDisplay($a),
                'status_meta' => $this->agreementStatusMeta((string) $a->status),
            ];
        })->values()->all();
    }

    /**
     * `unit_id` null = the terms cover the whole portfolio; set = that unit only. The unit is resolved
     * through {@see Unit}, which IS owner-scoped — an id that no longer resolves to one of the viewer's
     * units is labelled generically rather than named.
     *
     * @param  array<string, string>  $units
     */
    private function scopeLabel(ManagementAgreement $a, array $units): string
    {
        if ($a->unit_id === null) {
            return 'Portfolio-wide';
        }

        return $units[(string) $a->unit_id] ?? 'Unit-specific';
    }

    /**
     * The REAL commission, e.g. "15.00%" — the `decimal:2` cast already renders the stored scale, so
     * the figure is never re-rounded here. A null column → null → the view says "Not set", NEVER "0%".
     */
    /**
     * ⚠️ Via the SHARED formatter (wireframe-parity WAVE 3). This used to cast the decimal straight to a
     * string and append the unit — and `commission_pct` is a DB decimal, so it printed "18.00%" while
     * {@see pct()} below printed "18%" for the same agreement. One page, one legally-meaningful rate,
     * written two ways.
     */
    private function commissionDisplay(ManagementAgreement $a): ?string
    {
        return OwnerCommissionRate::display($a->commission_pct);
    }

    /**
     * The REAL enum label for a fee interval / payout schedule — 'monthly'|'quarterly'|'yearly' only.
     * Anything else (or null) → null → "Not set"; a schedule is never invented to fill the row.
     */
    private function intervalLabel(mixed $value): ?string
    {
        return match ($value) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            default => null,
        };
    }

    /** The REAL fixed management fee in its OWN currency (money(), never a float). Null → "Not set". */
    private function feeDisplay(ManagementAgreement $a): ?string
    {
        $minor = $a->management_fee_minor;
        $currency = $a->management_fee_currency;

        if ($minor === null || ! is_string($currency) || strlen($currency) !== 3) {
            return null;
        }

        return $this->money((int) $minor, $currency);
    }

    /**
     * DERIVED: the currency the owner is paid in = the agreement override if set, else the owner's
     * profile default. The source is always labelled so the figure is never mistaken for the other.
     *
     * @return array{code: string|null, source: string|null}
     */
    private function payoutCurrency(ManagementAgreement $a, ?Owner $owner): array
    {
        $onAgreement = is_string($a->payout_currency) && $a->payout_currency !== '' ? $a->payout_currency : null;

        if ($onAgreement !== null) {
            return ['code' => $onAgreement, 'source' => 'Set on this agreement'];
        }

        // owners.payout_currency is NOT NULL in the schema — only an empty value needs guarding.
        $default = $owner instanceof Owner && $owner->payout_currency !== ''
            ? $owner->payout_currency
            : null;

        return ['code' => $default, 'source' => $default === null ? null : 'From your owner profile'];
    }

    /**
     * `attribution_rules` holds OVERRIDES ONLY (a missing key = the engine's documented default), so
     * only the keys actually stored are listed. Null/empty → null → the view says "Standard
     * attribution"; a default is never presented as if it were an agreed term.
     *
     * @return list<array{label: string, value: string}>|null
     */
    private function attributionOverrides(ManagementAgreement $a): ?array
    {
        $rules = $a->attribution_rules;

        if (! is_array($rules) || $rules === []) {
            return null;
        }

        $rows = [];

        foreach ($rules as $key => $value) {
            $rows[] = ['label' => $this->ruleLabel((string) $key), 'value' => $this->ruleValue($value)];
        }

        return $rows;
    }

    private function ruleLabel(string $key): string
    {
        return (string) preg_replace('/\bOta\b/', 'OTA', Str::headline($key));
    }

    private function ruleValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            return Str::headline((string) $value);
        }

        // ⚠️ A LIST OF VALUES IS A LIST, NOT JSON (audit 2026-08-07 · GATE 6). This fell straight through
        // to json_encode, so an owner reading the commercial terms of their own agreement saw the literal
        // `["other"]` — the real stored value of `non_deductible_categories`, rendered as a debug dump on
        // a legal document. Each element goes through the same headline treatment a scalar gets, so a list
        // reads exactly like the single value it would have been.
        if (is_array($value) && array_is_list($value) && $value !== [] && ! array_filter($value, fn ($v): bool => ! is_scalar($v))) {
            return implode(', ', array_map(fn (mixed $v): string => Str::headline((string) $v), $value));
        }

        // Anything genuinely structured (a nested object) has no honest one-line rendering; say so rather
        // than dumping JSON at the owner.
        return $value === [] || $value === null ? '—' : 'See agreement document';
    }

    /**
     * Factual DATE context for an agreement that is not in effect today — real columns only. A phase is
     * NEVER rendered as a status: `status` has exactly two values (active | ended) and inventing a
     * third ("Upcoming"/"Past") would be a fabricated state.
     */
    private function timingNote(ManagementAgreement $a, Carbon $today): ?string
    {
        $from = $this->asDate($a->effective_from);
        $to = $this->asDate($a->effective_to);

        if ($from !== null && $from->gt($today)) {
            return 'Starts '.$from->format('j M Y');
        }

        if ($to !== null && $to->lt($today)) {
            return 'Ran to '.$to->format('j M Y');
        }

        return null;
    }

    /**
     * The REAL status enum only — active | ended. An unexpected value is echoed, never re-badged.
     *
     * @return array{label: string, tone: string}
     */
    private function agreementStatusMeta(string $status): array
    {
        return match ($status) {
            'active' => ['label' => 'Active', 'tone' => 'paid'],
            'ended' => ['label' => 'Ended', 'tone' => 'draft'],
            default => ['label' => ucfirst($status), 'tone' => 'draft'],
        };
    }

    /**
     * `units` IS owner-scoped (BelongsToOwner), so this read is already restricted to the viewer's own
     * units — no explicit whereIn needed beyond the id filter.
     *
     * @param  Collection<int, ManagementAgreement>  $agreements
     * @return array<string, string>
     */
    private function unitLabels(Collection $agreements): array
    {
        $unitIds = $agreements
            ->map(fn (ManagementAgreement $a): mixed => $a->unit_id)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($unitIds === []) {
            return [];
        }

        return Unit::query()
            ->whereIn('id', $unitIds)
            ->get()
            ->mapWithKeys(function (Unit $u): array {
                $name = (string) $u->name;

                return [(string) $u->id => (string) $u->code.($name !== '' ? ' · '.$name : '')];
            })
            ->all();
    }

    /**
     * ⚠️ `owners` carries the TENANT scope only (no BelongsToOwner) — the same trap as
     * `management_agreements`. The explicit whereIn on the resolved portal ids is what keeps this to
     * the viewer's own owner record(s).
     *
     * @param  list<string>  $ownerIds
     * @return array<string, Owner>
     */
    private function ownerRecords(array $ownerIds): array
    {
        if ($ownerIds === []) {
            return [];
        }

        return Owner::query()->whereIn('id', $ownerIds)->get()->keyBy('id')->all();
    }

    private function dateDisplay(mixed $value): ?string
    {
        return $this->asDate($value)?->format('j M Y');
    }

    /**
     * The `date` casts hand back Carbon at runtime; this stays tolerant of a raw string so a date is
     * either rendered from the REAL column or not rendered at all — never defaulted to "today".
     */
    private function asDate(mixed $value): ?Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RateCalendar;
use App\Models\RateGuardrail;
use App\Models\RateProposal;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

/**
 * RATE-PROPOSAL state machine (Wave C · GATE 1 · ⚠️ REAL MONEY). The ONE path for an operator→owner rate proposal
 * and its decisions:
 *
 *   • {@see propose()}  — OPERATOR side. Only on a room type the target owner owns EXCLUSIVELY (no foreign unit —
 *     the same boundary {@see OwnerRateService} enforces). Snapshots the CURRENT rate/weekend/min-stay so the
 *     owner sees a truthful before/after → `pending`.
 *   • {@see approve()} — OWNER side. Applies the proposed change through the CERTIFIED {@see OwnerRateService}
 *     (per-room-type, exclusive-owned, integer money #17) — the rate GENUINELY changes → `approved`.
 *   • {@see decline()} — OWNER side. Nothing changes → `declined`.
 *   • {@see withdraw()} — OPERATOR side. A pending proposal is retracted → `withdrawn`.
 *
 * Every transition guards `pending` (a decided proposal can't be re-decided — bounded like #29) and is recorded
 * (audit + domain event). The caller is the controller, which supplies the capability gate + owner scope.
 */
class RateProposalService
{
    public function __construct(
        private OwnerRateService $rates,
        private AuditEventRecorder $audit,
        private DomainEventRecorder $domainEvents,
    ) {}

    /**
     * OPERATOR proposes a rate change to an owner's EXCLUSIVELY-owned room type. Throws if the room type is not
     * exclusively that owner's (editing it would move another party's price).
     */
    public function propose(User $operator, string $ownerId, string $roomTypeId, int $proposedRateMinor, ?float $weekendPct, ?int $minStay, string $from, string $to, ?string $note): RateProposal
    {
        if ($this->hasForeignUnit($roomTypeId, $ownerId)) {
            throw new InvalidArgumentException('That room type is not exclusively owned by this owner.');
        }

        $roomType = RoomType::query()->findOrFail($roomTypeId);
        [$currentRate, $currency] = $this->currentRate($roomType);

        // ⚠️ GUARDRAILS (#81) — when the owner has SAVED limits, they GENUINELY constrain the proposal. Absent
        // (no row) → today's behaviour (always pending), so existing proposals are undisturbed. A rate below the
        // floor / above the ceiling is REJECTED here — the manager cannot price outside the owner's band.
        $guardrail = RateGuardrail::query()->where('owner_id', $ownerId)->first();
        if ($guardrail !== null) {
            if ($guardrail->floor_minor !== null && $proposedRateMinor < $guardrail->floor_minor) {
                throw new InvalidArgumentException('That rate is below the owner’s floor.');
            }
            if ($guardrail->ceiling_minor !== null && $proposedRateMinor > $guardrail->ceiling_minor) {
                throw new InvalidArgumentException('That rate is above the owner’s ceiling.');
            }
        }

        $proposal = RateProposal::create([
            'owner_id' => $ownerId,
            'room_type_id' => $roomTypeId,
            'proposed_by' => (string) $operator->getKey(),
            'currency' => $currency,
            'current_rate_minor' => $currentRate,
            'proposed_rate_minor' => $proposedRateMinor,
            'current_weekend_pct' => $this->currentWeekendPct($roomType),
            'proposed_weekend_pct' => $weekendPct,
            'current_min_stay' => $this->currentMinStay($roomTypeId),
            'proposed_min_stay' => $minStay,
            'from_date' => $from,
            'to_date' => $to,
            'note' => $note,
            'state' => RateProposal::STATE_PENDING,
        ]);

        $this->audit->record('rate_proposal.created', $proposal, null, ['proposed_rate_minor' => $proposedRateMinor, 'currency' => $currency]);
        $this->domainEvents->record('rate_proposal.created', $proposal, ['owner_id' => $ownerId, 'room_type_id' => $roomTypeId]);

        // AUTO-APPROVE within the owner's tolerance (dynamic mode) — a small change goes live immediately, no
        // owner sign-off. Requires a known current rate to measure the delta against; a fixed-mode or
        // out-of-tolerance change stays pending (the owner decides). The rate REALLY changes (certified path).
        if ($guardrail !== null && $this->autoApproves($guardrail, $currentRate, $proposedRateMinor)) {
            $this->applyRate($proposal, $proposedRateMinor);
            $proposal->forceFill([
                'state' => RateProposal::STATE_APPROVED,
                'decided_at' => Carbon::now(),
                'decided_by' => (string) $operator->getKey(),
            ])->save();
            $this->audit->record('rate_proposal.auto_approved', $proposal, null, ['within_pct' => $guardrail->auto_approve_pct]);
            $this->domainEvents->record('rate_proposal.approved', $proposal, ['room_type_id' => $roomTypeId, 'auto' => true]);
        }

        return $proposal;
    }

    /** True when a dynamic guardrail's tolerance covers this change (|Δ| ≤ auto_approve_pct of the current rate). */
    private function autoApproves(RateGuardrail $guardrail, ?int $currentRate, int $proposedRate): bool
    {
        if ($guardrail->pricing_mode !== RateGuardrail::MODE_DYNAMIC || $guardrail->auto_approve_pct <= 0) {
            return false;
        }
        if ($currentRate === null || $currentRate <= 0) {
            return false;   // no baseline to measure against → the owner decides
        }

        $deltaPct = abs($proposedRate - $currentRate) / $currentRate * 100;

        return $deltaPct <= $guardrail->auto_approve_pct;
    }

    /**
     * OWNER approves → the proposed change is APPLIED via the certified rate path (the rate really changes). The
     * apply re-verifies exclusive ownership under the owner scope, so approving a room type the owner no longer
     * owns exclusively throws there (nothing applied). Idempotency: only a pending proposal is applied.
     */
    public function approve(RateProposal $proposal, User $decider): void
    {
        $this->assertPending($proposal);

        $this->applyRate($proposal, (int) $proposal->proposed_rate_minor);

        $proposal->forceFill([
            'state' => RateProposal::STATE_APPROVED,
            'decided_at' => Carbon::now(),
            'decided_by' => (string) $decider->getKey(),
        ])->save();

        $this->audit->record('rate_proposal.approved', $proposal, null, ['applied_rate_minor' => (int) $proposal->proposed_rate_minor]);
        $this->domainEvents->record('rate_proposal.approved', $proposal, ['room_type_id' => (string) $proposal->room_type_id]);
    }

    /**
     * OWNER COUNTERS a pending proposal with their OWN nightly rate (#81) → `countered`. Nothing is applied yet —
     * the ball is back with the operator, who accepts (applies the owner's rate) or withdraws. The owner's
     * counter keeps the proposal's window/weekend/min-stay; only the nightly rate is the owner's figure.
     */
    public function counter(RateProposal $proposal, User $owner, int $counterRateMinor, ?string $note): void
    {
        $this->assertPending($proposal);

        $proposal->forceFill([
            'state' => RateProposal::STATE_COUNTERED,
            'countered_rate_minor' => $counterRateMinor,
            'countered_note' => $note,
            'countered_at' => Carbon::now(),
            'countered_by' => (string) $owner->getKey(),
        ])->save();

        $this->audit->record('rate_proposal.countered', $proposal, null, ['countered_rate_minor' => $counterRateMinor]);
        $this->domainEvents->record('rate_proposal.countered', $proposal, ['room_type_id' => (string) $proposal->room_type_id]);
    }

    /**
     * OPERATOR ACCEPTS the owner's counter (#81) → the OWNER'S countered rate is applied via the certified path
     * (the rate really changes to the owner's figure) → `approved`. Only a `countered` proposal can be accepted.
     */
    public function acceptCounter(RateProposal $proposal, User $operator): void
    {
        if (! $proposal->isCountered()) {
            throw new RuntimeException('This proposal has no counter to accept.');
        }

        $this->applyRate($proposal, (int) $proposal->countered_rate_minor);

        $proposal->forceFill([
            'state' => RateProposal::STATE_APPROVED,
            'decided_at' => Carbon::now(),
            'decided_by' => (string) $operator->getKey(),
        ])->save();

        $this->audit->record('rate_proposal.counter_accepted', $proposal, null, ['applied_rate_minor' => (int) $proposal->countered_rate_minor]);
        $this->domainEvents->record('rate_proposal.approved', $proposal, ['room_type_id' => (string) $proposal->room_type_id, 'from_counter' => true]);
    }

    /**
     * Apply a nightly rate for the proposal's window through the CERTIFIED {@see OwnerRateService} — the ONE
     * writer (nightly + weekend-uplift + min-stay, per-room-type, exclusive-owned only). Shared by approve,
     * accept-counter and the guardrail auto-approve so the rate is written identically however it was decided.
     */
    private function applyRate(RateProposal $proposal, int $rateMinor): void
    {
        $toExclusive = Carbon::parse($proposal->to_date)->addDay()->toDateString();
        // Exclusive-owner apply — verifies the room type is entirely this owner's WITHOUT the owner scope, so it
        // works owner-side (approve) AND operator-side (auto-approve / accept-counter). Same certified writer.
        $this->rates->applyForExclusiveOwner(
            (string) $proposal->room_type_id,
            (string) $proposal->owner_id,
            Carbon::parse($proposal->from_date)->toDateString(),
            $toExclusive,
            $rateMinor,
            $proposal->proposed_min_stay !== null ? (int) $proposal->proposed_min_stay : null,
            null,
            $proposal->proposed_weekend_pct !== null ? (float) $proposal->proposed_weekend_pct : null,
        );
    }

    /** OWNER declines → nothing is applied; the proposal is closed. */
    public function decline(RateProposal $proposal, User $decider): void
    {
        $this->assertPending($proposal);

        $proposal->forceFill([
            'state' => RateProposal::STATE_DECLINED,
            'decided_at' => Carbon::now(),
            'decided_by' => (string) $decider->getKey(),
        ])->save();

        $this->audit->record('rate_proposal.declined', $proposal, null, []);
        $this->domainEvents->record('rate_proposal.declined', $proposal, []);
    }

    /** OPERATOR withdraws a pending OR countered proposal → closed, nothing applied (also rejects a counter). */
    public function withdraw(RateProposal $proposal, User $operator): void
    {
        if (! $proposal->isPending() && ! $proposal->isCountered()) {
            throw new RuntimeException('This proposal has already been decided.');
        }

        $proposal->forceFill([
            'state' => RateProposal::STATE_WITHDRAWN,
            'decided_at' => Carbon::now(),
            'decided_by' => (string) $operator->getKey(),
        ])->save();

        $this->audit->record('rate_proposal.withdrawn', $proposal, null, []);
    }

    private function assertPending(RateProposal $proposal): void
    {
        if (! $proposal->isPending()) {
            throw new RuntimeException('This proposal has already been decided.');
        }
    }

    /**
     * True if the room type contains any unit NOT owned by this owner — the SAME boundary OwnerRateService uses,
     * read without the owner scope so every unit is visible. Editing a shared room type would move another price.
     */
    private function hasForeignUnit(string $roomTypeId, string $ownerId): bool
    {
        return Unit::withoutOwnerScope()
            ->where('room_type_id', $roomTypeId)
            ->where(fn ($q) => $q->whereNull('owner_id')->orWhere('owner_id', '!=', $ownerId))
            ->exists();
    }

    /**
     * The room type's current nightly rate (minor units) on today's date + its currency — the truthful "current".
     *
     * @return array{0: ?int, 1: string}
     */
    private function currentRate(RoomType $roomType): array
    {
        $plan = $roomType->primaryRatePlan();
        if ($plan === null) {
            return [null, 'EUR'];
        }

        $row = RateCalendar::query()
            ->where('rate_plan_id', $plan->getKey())
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->first();

        return [$row === null ? null : (int) $row->price_minor, (string) $plan->currency];
    }

    private function currentWeekendPct(RoomType $roomType): ?float
    {
        $plan = $roomType->primaryRatePlan();

        return $plan?->weekend_uplift_pct !== null ? (float) $plan->weekend_uplift_pct : null;
    }

    private function currentMinStay(string $roomTypeId): ?int
    {
        $value = Restriction::query()
            ->where('room_type_id', $roomTypeId)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->whereNotNull('min_stay')
            ->orderBy('date')
            ->value('min_stay');

        return $value !== null ? (int) $value : null;
    }
}

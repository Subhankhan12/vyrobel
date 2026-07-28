<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RateCalendar;
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

        return $proposal;
    }

    /**
     * OWNER approves → the proposed change is APPLIED via the certified rate path (the rate really changes). The
     * apply re-verifies exclusive ownership under the owner scope, so approving a room type the owner no longer
     * owns exclusively throws there (nothing applied). Idempotency: only a pending proposal is applied.
     */
    public function approve(RateProposal $proposal, User $decider): void
    {
        $this->assertPending($proposal);

        // The certified owner rate path: nightly + weekend-uplift + min-stay, per-room-type, exclusive-owned only.
        $toExclusive = Carbon::parse($proposal->to_date)->addDay()->toDateString();
        $this->rates->apply(
            (string) $proposal->room_type_id,
            Carbon::parse($proposal->from_date)->toDateString(),
            $toExclusive,
            (int) $proposal->proposed_rate_minor,
            $proposal->proposed_min_stay !== null ? (int) $proposal->proposed_min_stay : null,
            null,
            $proposal->proposed_weekend_pct !== null ? (float) $proposal->proposed_weekend_pct : null,
        );

        $proposal->forceFill([
            'state' => RateProposal::STATE_APPROVED,
            'decided_at' => Carbon::now(),
            'decided_by' => (string) $decider->getKey(),
        ])->save();

        $this->audit->record('rate_proposal.approved', $proposal, null, ['applied_rate_minor' => (int) $proposal->proposed_rate_minor]);
        $this->domainEvents->record('rate_proposal.approved', $proposal, ['room_type_id' => (string) $proposal->room_type_id]);
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

    /** OPERATOR withdraws a pending proposal → closed, nothing applied. */
    public function withdraw(RateProposal $proposal, User $operator): void
    {
        $this->assertPending($proposal);

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

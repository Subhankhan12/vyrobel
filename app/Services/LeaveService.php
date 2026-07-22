<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Schedule\ScheduleException;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Support\Collection;

/**
 * THE single write path for staff time-off / leave (Phase 7d · SG2). Enforces the request → approval
 * SEPARATION and the self-approval guard in ONE place, so no controller re-implements it.
 *
 *   request()  — a staff member requests THEIR OWN time off → `pending`.
 *   grant()    — a manager grants approved leave directly to ANOTHER staff member (never themselves).
 *   approve()  — a manager approves a pending request → approved leave. 🔒 NOT the person it is for.
 *   deny()     — a manager declines a pending request. 🔒 NOT the person it is for.
 *
 * 🔒 SELF-APPROVAL is impossible: {@see approve()}/{@see deny()} throw if the actor is the staff member the
 * leave is FOR, and {@see grant()} throws if a manager targets themselves — so a person can never turn their
 * own time-off into approved leave. This mirrors the established request/approval pattern (guest change
 * requests · owner add-unit) and the role-assignment self guard.
 */
class LeaveService
{
    public function __construct(private readonly AuditEventRecorder $audit) {}

    /** A staff member requests their OWN time off (pending). `user_id` is the requester — never someone else's. */
    public function request(User $staff, string $startDate, string $endDate, ?string $reason = null): LeaveRequest
    {
        [$startDate, $endDate] = $this->orderedDates($startDate, $endDate);

        $leave = LeaveRequest::create([
            'user_id' => $staff->getKey(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => 'pending',
            'requested_by' => $staff->getKey(),
        ]);

        $this->audit->record('schedule.leave.requested', $leave, null, ['status' => 'pending']);

        return $leave;
    }

    /**
     * A manager grants approved leave to a staff member directly (skipping the request). 🔒 A manager may NOT
     * grant their OWN leave this way — that would be self-approval; they must request it and another manager
     * approves.
     */
    public function grant(User $staff, User $manager, string $startDate, string $endDate, ?string $reason = null): LeaveRequest
    {
        if ($manager->is($staff)) {
            throw new ScheduleException('You can’t grant your own leave — submit a request for a manager to approve.');
        }

        [$startDate, $endDate] = $this->orderedDates($startDate, $endDate);

        $leave = LeaveRequest::create([
            'user_id' => $staff->getKey(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => 'approved',
            'requested_by' => $manager->getKey(),
            'reviewed_by' => $manager->getKey(),
            'reviewed_at' => now(),
        ]);

        $this->audit->record('schedule.leave.granted', $leave, null, ['status' => 'approved']);

        return $leave;
    }

    /** A manager approves a pending request → approved leave. 🔒 The approver can't be the person it is FOR. */
    public function approve(LeaveRequest $leave, User $approver): void
    {
        $this->assertNotSelf($leave, $approver);

        $before = ['status' => $leave->status];
        $leave->update(['status' => 'approved', 'reviewed_by' => $approver->getKey(), 'reviewed_at' => now()]);
        $this->audit->record('schedule.leave.approved', $leave, $before, ['status' => 'approved']);
    }

    /** A manager declines a pending request. 🔒 The approver can't be the person it is FOR. */
    public function deny(LeaveRequest $leave, User $approver): void
    {
        $this->assertNotSelf($leave, $approver);

        $before = ['status' => $leave->status];
        $leave->update(['status' => 'denied', 'reviewed_by' => $approver->getKey(), 'reviewed_at' => now()]);
        $this->audit->record('schedule.leave.denied', $leave, $before, ['status' => 'denied']);
    }

    /** Whether a staff member is on APPROVED leave on a given date (the real conflict signal). */
    public function isOnApprovedLeave(string $userId, string $date): bool
    {
        return LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Approved leave overlapping a week, per staff member — the grid/PDF read it to mark leave days.
     * Returns a set of "user_id|date" keys the staff is on leave.
     *
     * @param  list<string>  $userIds
     * @return array<string, true>
     */
    public function approvedLeaveDays(array $userIds, string $from, string $to): array
    {
        $leaves = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereIn('user_id', $userIds)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get();

        $days = [];
        foreach ($leaves as $leave) {
            $cursor = $leave->start_date->copy();
            while ($cursor->lte($leave->end_date)) {
                $date = $cursor->toDateString();
                if ($date >= $from && $date <= $to) {
                    $days[$leave->user_id.'|'.$date] = true;
                }
                $cursor = $cursor->addDay();
            }
        }

        return $days;
    }

    /** The org's PENDING time-off requests (the manager's approval queue), newest first. */
    public function pendingRequests(): Collection
    {
        return LeaveRequest::query()
            ->where('status', 'pending')
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->get();
    }

    private function assertNotSelf(LeaveRequest $leave, User $approver): void
    {
        if ((string) $leave->user_id === (string) $approver->getKey()) {
            throw new ScheduleException('You can’t approve your own time-off — another manager must.');
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function orderedDates(string $start, string $end): array
    {
        return $start <= $end ? [$start, $end] : [$end, $start];
    }
}

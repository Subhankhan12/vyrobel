<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\LeaveService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A staff TIME-OFF / LEAVE record (Phase 7d · SG2). Starts `pending` (a request the staff member made for
 * themselves); a manager APPROVES it (never the requester — the self-approval guard is in
 * {@see LeaveService}) → approved leave that shows on the roster and conflicts with any shift on
 * those days. Org-scoped, about the PERSON + a date range (no property).
 *
 * @property string $user_id
 * @property string $status
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon|null $reviewed_at
 */
class LeaveRequest extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /** Whether this (approved) leave covers a given date (inclusive range). */
    public function coversDate(string $date): bool
    {
        return $this->isApproved()
            && $date >= $this->start_date->toDateString()
            && $date <= $this->end_date->toDateString();
    }

    /** A human day-range label for the grid / PDF, e.g. "Thu–Fri 11–12 Sep". */
    public function rangeLabel(): string
    {
        $start = $this->start_date;
        $end = $this->end_date;

        if ($start->isSameDay($end)) {
            return $start->format('D j M');
        }

        return $start->format('D j').'–'.$end->format('D j M');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

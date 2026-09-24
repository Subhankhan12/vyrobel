<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ADMIN 01 · PHASE 4 — one privacy request Vyrobel received (see the migration). ⚠️ A PLATFORM record: no tenant
 * scope, no free text, no guest identity. Changed only through the console's audited actions.
 *
 * @property string $kind
 * @property string $subject_kind
 * @property string|null $subject_user_id
 * @property string|null $related_organization_id
 * @property string $channel
 * @property string $status
 * @property Carbon $received_at
 * @property Carbon $due_at
 * @property Carbon|null $closed_at
 */
class PrivacyRequest extends Model
{
    use HasUlids;

    public const KINDS = ['export' => 'Export my data', 'delete' => 'Delete my data'];

    public const SUBJECTS = ['account_holder' => 'A Vyrobel account holder', 'relayed_guest' => 'A hotel’s guest (relayed — the hotel is the controller)'];

    public const CHANNELS = ['email' => 'Email', 'letter' => 'Letter', 'phone' => 'Phone', 'hotel' => 'Relayed by the hotel'];

    public const STATUSES = ['received' => 'Received', 'in_progress' => 'In progress', 'completed' => 'Completed', 'refused' => 'Refused'];

    /** GDPR Art. 12(3): answer "without undue delay and in any event within one month". */
    public const DUE_MONTHS = 1;

    protected $fillable = ['kind', 'subject_kind', 'subject_user_id', 'related_organization_id', 'channel', 'status', 'received_at', 'due_at', 'closed_at', 'recorded_by'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'due_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['received', 'in_progress'], true);
    }

    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'related_organization_id');
    }
}

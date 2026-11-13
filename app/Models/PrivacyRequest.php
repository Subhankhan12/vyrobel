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

    public const CHANNELS = ['email' => 'Email', 'letter' => 'Letter', 'phone' => 'Phone', 'hotel' => 'Relayed by the hotel', 'in_app' => 'In the app'];

    public const STATUSES = ['received' => 'Received', 'in_progress' => 'In progress', 'completed' => 'Completed', 'refused' => 'Refused'];

    /** A request kind CODE ({@see KINDS}' key) → its label in the reader's language, translated at call time (I18N-01). */
    public static function kindLabel(string $kind): string
    {
        return match ($kind) {
            'export' => (string) __('php/models/privacy_request.kind_export'),
            'delete' => (string) __('php/models/privacy_request.kind_delete'),
            default => $kind,
        };
    }

    /** Whom a request is about: a {@see SUBJECTS} CODE → its label in the reader's language. */
    public static function subjectLabel(string $subject): string
    {
        return match ($subject) {
            'account_holder' => (string) __('php/models/privacy_request.subject_account_holder'),
            'relayed_guest' => (string) __('php/models/privacy_request.subject_relayed_guest'),
            default => $subject,
        };
    }

    /** How a request arrived: a {@see CHANNELS} CODE → its label in the reader's language. */
    public static function channelLabel(string $channel): string
    {
        return match ($channel) {
            'email' => (string) __('php/models/privacy_request.channel_email'),
            'letter' => (string) __('php/models/privacy_request.channel_letter'),
            'phone' => (string) __('php/models/privacy_request.channel_phone'),
            'hotel' => (string) __('php/models/privacy_request.channel_hotel'),
            'in_app' => (string) __('php/models/privacy_request.channel_in_app'),
            default => $channel,
        };
    }

    /** A request status CODE ({@see STATUSES}' key) → its label in the reader's language. */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'received' => (string) __('php/models/privacy_request.status_received'),
            'in_progress' => (string) __('php/models/privacy_request.status_in_progress'),
            'completed' => (string) __('php/models/privacy_request.status_completed'),
            'refused' => (string) __('php/models/privacy_request.status_refused'),
            default => $status,
        };
    }

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

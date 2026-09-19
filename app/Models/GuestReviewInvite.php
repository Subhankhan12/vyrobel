<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * R3 — the durable record of whether a stay's guest was invited to review it.
 *
 * ⚠️⚠️ THE ABSENCE OF A ROW IS MEANINGFUL AND IS NOT "NO". Read it through {@see self::stateFor()}, never by
 * testing `sent_at` alone: a stay that checked out before this record existed has NO row, and reporting that
 * as "no request was sent" is the lie R3 was ruled to prevent.
 *
 * ⚠️ NO GUEST PII EVER — reservation, time and outcome only (#28).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $reservation_id
 * @property string $outcome
 * @property Carbon|null $sent_at
 * @property string $channel
 */
class GuestReviewInvite extends Model
{
    use BelongsToOrganization, HasUlids;

    /** A request genuinely left the building. */
    public const SENT = 'sent';

    /** The operator's org-level `review-request` template is switched off. */
    public const SUPPRESSED_BY_OPERATOR = 'suppressed_by_operator';

    /** The guest's own `reviews` email preference says no. */
    public const SUPPRESSED_BY_GUEST = 'suppressed_by_guest';

    /** No contact address to send to, or the send threw. */
    public const NO_CONTACT = 'no_contact';

    /** ⚠️ THE THIRD STATE. Not a stored value — the state of a stay with NO ROW AT ALL. */
    public const UNKNOWN_PREDATES_RECORD = 'unknown_predates_record';

    protected $fillable = ['organization_id', 'reservation_id', 'outcome', 'sent_at', 'channel'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * ⚠️⚠️ THE ONE READER OF THE THREE STATES — every surface asks THIS, so none of them can invent a fourth
     * answer or collapse the last two.
     *
     * @return array{state: string, sent: bool, known: bool, sent_at: ?Carbon}
     */
    public static function stateFor(string $reservationId): array
    {
        $row = static::query()->where('reservation_id', $reservationId)->first();

        if (! $row instanceof self) {
            // ⚠️ NO ROW — the stay checked out before this record existed. This is NOT "no email was sent".
            return ['state' => self::UNKNOWN_PREDATES_RECORD, 'sent' => false, 'known' => false, 'sent_at' => null];
        }

        return [
            'state' => (string) $row->outcome,
            'sent' => $row->sent_at !== null,
            'known' => true,
            'sent_at' => $row->sent_at,
        ];
    }
}

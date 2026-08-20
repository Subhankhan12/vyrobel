<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Email\GuestEmailPreferences;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A GUEST's opt-OUT from one optional transactional email category. Sparse: the row's EXISTENCE is the
 * opt-out; no row means the email sends. See the migration for why this is not `notification_preferences`.
 *
 * ⚠️ Deliberately NOT `BelongsToOrganization`-scoped: a guest's email choice follows the GUEST, and the
 * gate reads it through the guest's own relation ({@see GuestEmailPreferences}), never by
 * a tenant query. Scoping it by org would make the read depend on an org context the scheduled command and
 * the event listener resolve differently.
 */
class GuestEmailPreference extends Model
{
    use HasUlids;

    protected $fillable = ['guest_id', 'category'];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}

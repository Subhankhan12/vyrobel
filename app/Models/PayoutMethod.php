<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use App\Services\OwnerPayoutService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An owner's bank account for payouts (Phase 2 · Sub-gate 5). Owner-scoped via {@see BelongsToOwner} — a
 * portal read/resolve only ever sees the authenticated owner's own methods, and a cross-owner id fails to
 * resolve (404), exactly like a unit. The full `iban` is ENCRYPTED at rest; `iban_last4` + `iban_country`
 * are the only cleartext bits, kept solely so the masked display never has to decrypt the whole number.
 *
 * DATA CAPTURE ONLY — this model stores where an owner WANTS payouts to go. The actual disbursement still runs
 * through the certified {@see OwnerPayoutService} + its rail (manual today; Stripe Connect
 * deferred). Nothing here moves money.
 */
class PayoutMethod extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'label',
        'account_holder',
        'iban',
        'iban_last4',
        'iban_country',
        'bic',
        'currency',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'iban' => 'encrypted',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}

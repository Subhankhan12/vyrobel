<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A redeemable promo code (Phase 4c · Sub-gate 2) — the minimal real redemption mechanism: a valid code redeems to
 * a wallet CREDIT entry. `max_redemptions` bounds total redemptions (default 1 = single-use); redemption is also
 * once-per-guest. Tenant-scoped ({@see BelongsToOrganization}).
 *
 * @property int $amount_minor
 * @property int $max_redemptions
 * @property bool $is_active
 * @property Carbon|null $expires_at
 */
class PromoCode extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'code',
        'amount_minor',
        'currency',
        'description',
        'max_redemptions',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'max_redemptions' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}

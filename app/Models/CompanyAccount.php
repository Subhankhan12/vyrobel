<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company that settles after departure. ⚠️ It owes nothing of its own: what it owes is the sum of the
 * folio balances billed to it, which is why there is no separate receivables ledger to drift from the folios.
 */
class CompanyAccount extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    protected $fillable = ['organization_id', 'name', 'reference', 'is_active'];

    /**
     * ⚠️ The DB default never reaches the in-memory instance, so a freshly created account read as CLOSED
     * until it was refreshed — and the billing guard would have refused it. Defaulted here so the object
     * and the row agree from the moment it exists.
     */
    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<Folio, $this> */
    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'billed_to_account_id');
    }
}

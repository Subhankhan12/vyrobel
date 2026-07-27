<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use App\Models\Scopes\OwnerScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The owner's OWN monthly NET-revenue goal (GATE 1, Owner Bookings) — the real backing behind the wireframe's
 * "Monthly goal · 123% of €6k" ring. There is NO fabricated goal: the target is a value the owner sets, and the
 * ring divides the REAL current-month net by it (#39). One row per (owner, currency); the write is an idempotent
 * upsert. Owner-scoped like {@see OwnerLedgerEntry} — tenant scope + {@see OwnerScope} (reads
 * fail closed for a non-linked user). `target_minor` is integer minor units (#17), always positive.
 *
 * @property string $currency
 * @property int $target_minor
 */
class OwnerMonthlyTarget extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'currency',
        'target_minor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_minor' => 'integer',
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

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An OWNER-TEAM membership (Phase 7c) — links a delegate {@see User} to an {@see Owner} with a fixed
 * owner-team role ({@see OwnerTeamRole}). This is the nested boundary: a sub-user ⊆ their
 * owner ⊆ their org. TENANT-scoped (BelongsToOrganization) so it only ever resolves within the active org.
 *
 * @property string $role
 * @property string $status
 * @property ?string $user_id
 * @property ?string $owner_id
 */
class OwnerUser extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'user_id',
        'role',
        'status',
        'invited_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

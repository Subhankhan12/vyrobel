<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * `organization_user` — a user's membership in an org (base role rides on spatie).
 *
 * Deliberately does NOT use BelongsToOrganization: this pivot is part of how tenancy itself
 * is established, and a user must be able to read their memberships across every org they
 * belong to. It is queried explicitly by user_id / organization_id.
 */
class Membership extends Pivot
{
    // Intentionally does NOT use BelongsToOrganization: this is a tenancy-linking table and a
    // user must read their memberships across ALL orgs. Do not add the tenant global scope.
    use HasUlids;

    protected $table = 'organization_user';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'organization_id',
        'user_id',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * `property_user` — scopes a staff member to specific properties (property-level roles).
 *
 * Like {@see Membership}, this is a tenancy-linking table and is queried explicitly, so it
 * does not use the tenant global scope. The `property()` relation lights up in Step 2 when
 * the `properties` table/model exists (the column + index already exist as a forward hook).
 */
class PropertyAssignment extends Model
{
    // Intentionally does NOT use BelongsToOrganization: this is a tenancy-linking table and a
    // user must read their property assignments across ALL orgs. Do not add the tenant scope.
    use HasUlids;

    protected $table = 'property_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'property_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

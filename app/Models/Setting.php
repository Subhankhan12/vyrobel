<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Typed config with a polymorphic owner (organization or property). A null `organization_id`
 * denotes a platform-global setting, so the tenant global scope is not applied here; callers
 * scope by owner (and org where relevant) explicitly.
 */
class Setting extends Model
{
    use HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_type',
        'owner_id',
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}

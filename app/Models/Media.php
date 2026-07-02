<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Polymorphic file/image, reused across every module via the HasMedia concern.
 */
class Media extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'organization_id',
        'mediable_type',
        'mediable_id',
        'disk',
        'path',
        'mime',
        'size',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'size' => 'integer',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}

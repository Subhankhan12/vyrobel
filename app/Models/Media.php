<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\Media\OperationsPhotos;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Polymorphic file/image, reused across every module via the HasMedia concern. Photos are just media in
 * the `photos` collection, with ordering (`sort_order`), a `is_cover` flag, and `alt` text.
 *
 * @property string $mediable_type
 * @property string $mediable_id
 * @property string $disk
 * @property string $path
 * @property string $collection
 * @property bool $is_cover
 * @property array<string, mixed>|null $meta
 * @property-read string $url
 */
class Media extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'organization_id',
        'mediable_type',
        'mediable_id',
        'collection',
        'disk',
        'path',
        'mime',
        'size',
        'meta',
        'sort_order',
        'is_cover',
        'alt',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'size' => 'integer',
            'sort_order' => 'integer',
            'is_cover' => 'boolean',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The file's address. A housekeeping or maintenance photo has ONE — the signed-in route ({@see OperationsPhotos}, VY-34), whichever
     * disk it is on; every other file keeps its disk's own URL (the public disk → /media/…).
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => OperationsPhotos::isOperations($this)
            ? OperationsPhotos::url($this)
            : Storage::disk($this->disk)->url($this->path));
    }
}

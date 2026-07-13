<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Photos = media in the `photos` collection, ordered by `sort_order`. Reuses the shared polymorphic
 * `media` table (convention #7 — never a per-module photo table). Any sellable entity (RoomType, Unit)
 * `use`-es this to expose an ordered gallery + a cover.
 */
trait HasPhotos
{
    public const PHOTO_COLLECTION = 'photos';

    /**
     * @return MorphMany<Media, $this>
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
            ->where('collection', self::PHOTO_COLLECTION)
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    /**
     * The cover photo: the one flagged `is_cover`, else the first by order, else null. Uses the
     * eager-loaded `photos` relation when present (so card lists can avoid N+1).
     */
    public function coverPhoto(): ?Media
    {
        $photos = $this->relationLoaded('photos') ? $this->photos : $this->photos()->get();

        return $photos->firstWhere('is_cover', true) ?? $photos->first();
    }
}

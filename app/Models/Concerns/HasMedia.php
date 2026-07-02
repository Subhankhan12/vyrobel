<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Reusable polymorphic media relation. Any module attaches files/images by `use`-ing this
 * trait — never a per-module photo table (convention #6).
 */
trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}

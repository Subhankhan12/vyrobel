<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Reusable polymorphic amenities relation via the shared `amenitiables` pivot (convention #6).
 * Attachable to property / room_type / unit.
 */
trait HasAmenities
{
    public function amenities(): MorphToMany
    {
        return $this->morphToMany(Amenity::class, 'amenitiable');
    }
}

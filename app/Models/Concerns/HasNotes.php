<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Note;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Reusable polymorphic notes relation (convention #6).
 */
trait HasNotes
{
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }
}

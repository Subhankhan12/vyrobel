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
    /**
     * The relation genuinely yields Notes, so it says so: without the generic, static analysis infers a
     * bare `Model` and every caller that reads `$model->notes` or creates through it loses its type.
     *
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }
}

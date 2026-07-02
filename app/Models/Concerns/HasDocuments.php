<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Reusable polymorphic documents relation (convention #6).
 */
trait HasDocuments
{
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}

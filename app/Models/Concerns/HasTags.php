<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Reusable polymorphic tagging relation via the shared `taggables` pivot (convention #6).
 */
trait HasTags
{
    /**
     * The relation genuinely yields Tags, so it says so: without the generic, static analysis infers a
     * `Collection<int, Model>` and every caller that reads `$model->tags` gets an untyped Model back.
     *
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}

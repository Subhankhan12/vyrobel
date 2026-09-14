<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A link between a Vyrobel {@see User} and one external sign-in identity.
 *
 * ⚠️ THE MATCH IS ALWAYS `(provider, provider_user_id)`. See the migration for why `provider_email` is a
 *    display value and must never be used to find an identity.
 *
 * @property string $id
 * @property string $user_id
 * @property string $provider
 * @property string $provider_user_id
 * @property string|null $provider_email
 * @property Carbon|null $last_used_at
 */
final class UserIdentity extends Model
{
    use HasUlids;

    protected $fillable = ['user_id', 'provider', 'provider_user_id', 'provider_email', 'last_used_at'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }
}

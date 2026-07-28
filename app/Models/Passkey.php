<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A registered PASSKEY (Wave B · GATE 4) — a WebAuthn credential bound to a user. `public_key` holds the serialized
 * {@see Webauthn\PublicKeyCredentialSource} (public key + advancing signature counter). No secret is stored — the
 * private key lives only on the user's device. Self-only, resolved by user_id.
 *
 * @property ?Carbon $last_used_at
 */
class Passkey extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'credential_id',
        'public_key',
        'label',
        'last_used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * SUPER ADMIN · BUILD 2 · GATE 2 — a platform-level channel integration (the founder's master credentials for a
 * booking channel). `credentials` is ENCRYPTED at rest (`encrypted:array`, like the owner IBAN, #28) — the
 * secret is never exposed after saving; the UI is write-only/masked. See the migration for the account-gated
 * posture.
 *
 * @property array<string, string>|null $credentials
 */
class PlatformIntegration extends Model
{
    use HasUlids;

    /** The supported channel catalog (display name per key). The real sync is account-gated per channel. */
    public const CHANNELS = [
        'channex' => 'Channex',
        'booking_com' => 'Booking.com',
        'airbnb' => 'Airbnb',
        'expedia' => 'Expedia',
    ];

    protected $fillable = [
        'channel',
        'credentials',
        'is_enabled',
        'status',
        'last_tested_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',   // 🔒 ciphertext at rest — never rendered back plaintext
            'is_enabled' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    /** Whether a master credential has been stored (never exposes the value — just its presence). */
    public function isConfigured(): bool
    {
        return $this->credentials !== null && $this->credentials !== [];
    }

    /** The display name for this channel. */
    public function label(): string
    {
        return self::CHANNELS[$this->channel] ?? ucfirst((string) $this->channel);
    }
}

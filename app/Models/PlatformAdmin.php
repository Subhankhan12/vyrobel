<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Middleware\EnsurePlatformAdminTwoFactor;
use Database\Factories\PlatformAdminFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * SUPER ADMIN · GATE 0 — the platform-admin identity (the founder's console operator).
 *
 * A DELIBERATELY separate `Authenticatable` from {@see User}, resolved by the `admin` guard ONLY (see
 * config/auth.php). This is the whole isolation story:
 *   - it is NOT a tenant `User`, so the `web`/`owner`/`guest` guards can never resolve it and it can never
 *     resolve a tenant user — realm separation by construction (the `guest` realm's discipline);
 *   - it holds NO spatie roles and has NO `organization_id`, so it sits ABOVE the tenancy and carries no
 *     org scope of its own. It is NOT the (unassignable) global `Super Admin` role — the deferred Phase-7a
 *     escalation surface does not apply.
 *
 * 2FA is MANDATORY (enforced by {@see EnsurePlatformAdminTwoFactor}); the columns +
 * casts mirror the proven {@see User} 2FA discipline (secret + confirmation + encrypted recovery codes).
 *
 * @property Carbon|null $last_login_at
 * @property list<array{code: string, used_at: string|null}>|null $two_factor_recovery_codes
 */
#[Fillable(['name', 'email', 'password', 'status', 'two_factor_enabled', 'last_login_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class PlatformAdmin extends Authenticatable
{
    /** @use HasFactory<PlatformAdminFactory> */
    use HasFactory, HasUlids;

    protected $table = 'platform_admins';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * 2FA is ACTIVE iff a secret is present AND enrollment was CONFIRMED — the single source of truth, mirroring
     * {@see User::hasTwoFactorEnabled()}. A generated-but-unconfirmed secret is NOT active (anti-lockout), so the
     * mandatory-2FA gate keeps forcing enrollment until a valid code confirms it.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * THE single hash+rotate path for a platform admin's password (mirrors {@see User::changePasswordTo()}).
     * The `password` 'hashed' cast hashes exactly once; rotating the remember token ends other remembered
     * sessions.
     */
    public function changePasswordTo(string $plainPassword): void
    {
        $this->forceFill([
            'password' => $plainPassword,
            'remember_token' => Str::random(60),
        ])->save();
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlatformAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * @extends Factory<PlatformAdmin>
 */
class PlatformAdminFactory extends Factory
{
    protected $model = PlatformAdmin::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * A fully-enrolled admin: a confirmed TOTP secret so the mandatory-2FA gate lets them through. The secret is
     * returned via `->twoFactorSecret` on the state closure's model for tests that need to generate a live code.
     */
    public function withTwoFactor(): static
    {
        $secret = (new Google2FA)->generateSecretKey();

        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [['code' => 'AAAAA-BBBBB', 'used_at' => null]],
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'disabled']);
    }
}

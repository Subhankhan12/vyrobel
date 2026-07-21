<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Mail\PasswordResetMail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * Global (non-tenant) account. ULID PK so every user-referencing FK across the schema is
 * a uniform char(26). Roles/permissions are org-scoped via spatie teams (team = organization).
 *
 * The `encrypted:array` cast decrypts + JSON-decodes the recovery codes on read, so the runtime type is an
 * array of `{code, used_at}` entries (Larastan can only see the encrypted-string column, hence this hint).
 *
 * @property list<array{code: string, used_at: string|null}>|null $two_factor_recovery_codes
 * @property Carbon|null $last_login_at the `datetime` cast makes this a Carbon at runtime
 */
#[Fillable(['name', 'email', 'password', 'locale', 'status', 'two_factor_enabled', 'last_login_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUlids, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            // Secret + recovery codes are ENCRYPTED at rest (convention #5). The secret existed as PLAINTEXT
            // text before 2FA was wired; the cast fixes that latent exposure. `encrypted:array` stores the
            // recovery codes as encrypted JSON ([{code, used_at}]).
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Deliver the password-reset link through our own brand-consistent, email-safe Mailable (lands in
     * Mailpit in dev — prod needs a real provider), not the framework's default notification. Same
     * visual language as the invite email; the broker still owns the tokened, single-use, expiring token.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new PasswordResetMail((string) $token, (string) $this->email, (string) $this->name));
    }

    /**
     * Set a new password and rotate the remember token — THE single hash+rotate path, shared by the
     * password-RESET flow ({@see PasswordResetController::reset()}) and the in-app
     * CHANGE-password flow ({@see ChangePasswordController}), so the two can never
     * drift. The `password` 'hashed' cast hashes the plaintext exactly once; rotating `remember_token`
     * invalidates any "remember me" cookie held by OTHER sessions (a password change must end other remembered
     * sessions), while the CURRENT request's session stays valid — the caller keeps the user signed in here.
     */
    public function changePasswordTo(string $plainPassword): void
    {
        $this->forceFill([
            'password' => $plainPassword,
            'remember_token' => Str::random(60),
        ])->save();
    }

    /**
     * Whether 2FA is ACTIVE — the SINGLE source of truth (no drift, the #27 lesson): a secret is present AND
     * the user CONFIRMED enrollment with a valid code (`two_factor_confirmed_at` set). A generated-but-
     * unconfirmed secret is NOT active, so it never gates login (the anti-lockout confirm step). The
     * `two_factor_enabled` column is mirrored for display only and is NEVER read for this decision.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->using(Membership::class)
            ->withPivot(['id', 'status', 'invited_at'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function propertyAssignments(): HasMany
    {
        return $this->hasMany(PropertyAssignment::class);
    }

    /**
     * Properties this user is scoped to (property-level roles), via property_user.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_user')
            ->withPivot(['organization_id'])
            ->withTimestamps();
    }

    /**
     * The properties this user may work in, name-ordered (Phase 6a · multi-property access model).
     *
     * The `property_user` pivot is an OPTIONAL allow-list: if the user has any pivot rows they are
     * restricted to those properties; if they have NONE they get every property in the org. Empty
     * pivot ⇒ full org keeps single-property behaviour unregressed and locks no existing user out
     * (the pivot was a dormant forward-hook before Phase 6a). Both queries run under the active
     * tenant scope, so this only ever returns current-org property ids.
     *
     * @return list<string>
     */
    public function accessiblePropertyIds(): array
    {
        $pivotIds = $this->properties()->orderBy('properties.name')->pluck('properties.id');

        if ($pivotIds->isNotEmpty()) {
            return $pivotIds->map(fn ($id): string => (string) $id)->values()->all();
        }

        return Property::query()->orderBy('name')->pluck('id')
            ->map(fn ($id): string => (string) $id)->values()->all();
    }
}

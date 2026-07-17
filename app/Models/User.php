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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * Global (non-tenant) account. ULID PK so every user-referencing FK across the schema is
 * a uniform char(26). Roles/permissions are org-scoped via spatie teams (team = organization).
 */
#[Fillable(['name', 'email', 'password', 'locale', 'status', 'two_factor_enabled', 'last_login_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
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
}

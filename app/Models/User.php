<?php

namespace App\Models;

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Mail\PasswordResetMail;
use App\Services\OwnerInviteService;
use App\Services\OwnerTeamInviteService;
use App\Support\Auth\SignInCredentials;
use App\Support\Events\AuditEventRecorder;
use App\Support\Security\ActiveSessions;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
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
 * @property string|null $avatar_path relative path on the `public` disk of the user's own avatar
 * @property string|null $pending_email a login-email change parked here until the signed verify link is opened
 * @property Carbon|null $security_alerts_seen_at when "This was me" last cleared the Security page's sign-in warning
 * @property Carbon|null $password_changed_at when the password last changed (null: before this was recorded) — set by booted()
 * @property int|null $session_timeout_minutes the person's own inactivity sign-out (15/30/60), null = off
 */
#[Fillable(['name', 'preferred_name', 'email', 'phone', 'password', 'locale', 'timezone', 'avatar_path', 'status', 'two_factor_enabled', 'require_login_approval', 'quiet_hours_start', 'quiet_hours_end', 'last_login_at', 'compact_density', 'onboarding_tips', 'reduce_motion', 'date_format'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
/**
 * ⚠️⚠️ `MustVerifyEmail` — ENABLED 2026-09-23 (GATE 01), BY FOUNDER RULING: **verify before `/app`.**
 *
 * ⚠️ It was deliberately OFF, and the reason was recorded honestly in {@see OnboardingController}:
 * *"NO email verification GATE — `User` is not `MustVerifyEmail`, so the founder matches every existing
 * operator (they are logged in immediately, like accept-invite)"*, with the accept-and-verify variant
 * **deferred because it needs production email**. ⚠️ That deferral has now been answered: the Postmark
 * transport is installed and the founder has ruled the other way.
 *
 * ⚠️⚠️ WHY THIS DOES NOT LOCK OUT INVITED STAFF — MEASURED, NOT ASSUMED. 🔬 Both invite services
 * ({@see OwnerInviteService::accept()} and {@see OwnerTeamInviteService})
 * already set `email_verified_at = now()` on acceptance, because **clicking a mailed invite link IS proof of
 * email ownership**. So an invited user passes the gate the moment they set their password. ⚠️ Only the
 * SELF-SIGNUP path lands unverified — which is exactly the door this ruling closes.
 *
 * ⚠️ `Guest` has implemented this contract all along; this is the existing pattern, not a new mechanism.
 */
class User extends Authenticatable implements MustVerifyEmail, OAuthenticatable
{
    /**
     * ⚠️ THE ONE DEFINITION OF "THIS ACCOUNT CAN SIGN IN".
     *
     * `LoginRequest::authenticate()` folds this exact value into the credential query, so an account in any
     * other state cannot authenticate. Anything that reasons about who can still ACT — notably the
     * last-role-manager floor — must ask the same question, or it will count holders who are locked out.
     */
    public const SIGN_IN_STATUS = 'active';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUlids, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    /**
     * ⚠️ 2026-10-24 — WHEN THE PASSWORD LAST CHANGED, recorded in ONE place: the model, on every save that changes it. The password
     * is written by seven paths (change, reset, set-password, owner and staff invites, team invites, onboarding); a stamp in each
     * would miss the next one added. Null stays null for older accounts — the Security page says "Not recorded yet".
     */
    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty('password') && $user->password !== null) {
                $user->password_changed_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'require_login_approval' => 'boolean',
            'compact_density' => 'boolean',
            'onboarding_tips' => 'boolean',
            'reduce_motion' => 'boolean',
            // Secret + recovery codes are ENCRYPTED at rest (convention #5). The secret existed as PLAINTEXT
            // text before 2FA was wired; the cast fixes that latent exposure. `encrypted:array` stores the
            // recovery codes as encrypted JSON ([{code, used_at}]).
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'security_alerts_seen_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'session_timeout_minutes' => 'integer',
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
    /**
     * Does this account have a password at all?
     *
     * ⚠️⚠️ THE ONE DEFINITION (§N.8f), and it mirrors `Guest::isClaimed()`'s shape deliberately — the guest
     * table has been nullable-password since the claim flow was built, and a second way of asking the same
     * question is how two implementations of one concept drift apart (`Plan::isPerUnit()` and
     * `PlanTerms::isPerUnit()` were once found byte-identical).
     *
     * ⚠️ IT READS A COLUMN AND NOTHING ELSE. No role, no permission, no team — so it CANNOT be silently
     * false the way `can()` is without a team context set. That shape has bitten this project three times,
     * and here it would have sent every user, password or not, to set a password forever.
     */
    /**
     * External sign-in identities linked to this account (Google, and any later provider).
     *
     * ⚠️ A LINK IS A CREDENTIAL. {@see SignInCredentials} is the one place that counts
     *    them, so the unlink floor and any screen that describes it cannot disagree.
     *
     * @return HasMany<UserIdentity, $this>
     */
    public function identities(): HasMany
    {
        return $this->hasMany(UserIdentity::class);
    }

    public function hasPassword(): bool
    {
        return $this->password !== null && $this->password !== '';
    }

    /**
     * @param  ?string  $keepSessionId  the session to SPARE (the browser doing the change); null kills them all
     */
    public function changePasswordTo(string $plainPassword, ?string $keepSessionId = null): void
    {
        // ⚠️ F-9 — read BEFORE the write, or the distinction is destroyed by the line below.
        $wasFirstSet = ! $this->hasPassword();

        $this->forceFill([
            'password' => $plainPassword,
            'remember_token' => Str::random(60),
        ])->save();

        // ⚠️⚠️ F-9 IS A SECURITY GAP, NOT BOOKKEEPING. An account could be compromised and its password
        // changed with nothing, anywhere, recording it — while `platform.account.password_changed` already
        // existed for the platform admin, and this realm's NEIGHBOURS (owner.email_changed,
        // owner.other_sessions_signed_out, owner.trusted_device_revoked) all record. The password change was
        // the one security action in the `web` guard that recorded nowhere.
        //
        // ⚠️ IT LIVES HERE, NOT IN A CONTROLLER, because this is the ONE shared hash+rotate path — so the
        // self-service change AND the token reset are both covered, and neither can drift from the other.
        // A recorder on one controller would be the same defect in a new coat.
        //
        // ⚠️⚠️ NEVER THE SECRET. No password, no hash, nothing derived from either — `before` is deliberately
        // null because the only "previous value" here is a hash, and storing it would make the audit trail
        // the attack surface it exists to protect. What IS recorded: that it changed, to whom it belongs, and
        // (free, from the recorder) the timestamp, IP and user-agent. These rows are OPERATOR-READABLE.
        //
        // ⚠️ THE ACTOR: on a self-service change the authenticated user resolves normally. On a RESET nobody
        // is authenticated, and a null actor would close nothing — so the account holder is named, with
        // `via` saying how the change was authorised rather than implying a session that did not exist.
        $authId = auth()->id();

        app(AuditEventRecorder::class)->record(
            'user.password_changed',
            $this,
            null,
            [
                'first_set' => $wasFirstSet,
                'via' => $authId !== null ? 'authenticated_session' : 'reset_token',
                'other_sessions_terminated' => true,
            ],
            actorType: 'user',
            actorId: (string) ($authId ?? $this->getKey()),
        );

        // ⚠️ TERMINATE THE OTHER SESSIONS (auth pass, item 3). Rotating the remember-token kills "remember me"
        // COOKIES, but an attacker holding a live server-side session was completely unaffected — so a
        // password reset did not actually lock them out, which is the entire reason someone resets a
        // compromised password. This lives in the SHARED path (both PasswordResetController and
        // ChangePasswordController call it), so neither flow can forget it or drift from the other.
        ActiveSessions::terminateFor((string) $this->getKey(), $keepSessionId);
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

    /** Login-approval opt-in (Wave B · GATE 4) — an untrusted new device must be approved by email first. */
    public function requiresLoginApproval(): bool
    {
        return (bool) $this->require_login_approval;
    }

    /**
     * Public URL of the user's OWN uploaded avatar, or null when none is set (the UI falls back to the
     * initials chip). The bytes live on the `public` disk; only the relative path is stored on the row.
     */
    public function avatarUrl(): ?string
    {
        $path = $this->avatar_path;

        return ($path === null || $path === '') ? null : Storage::disk('public')->url($path);
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

<?php

use App\Models\Guest;
use App\Models\PlatformAdmin;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Phase 4a — the GUEST realm. A SEPARATE session guard, distinct from `web` (operator/owner). A guest is
        // authenticated here and ONLY here; `web` never authenticates a Guest and `guest` never authenticates a
        // User. Its provider looks a guest up by the blind-index email hash (the email is encrypted). Do NOT point
        // any `/app` or `/owner` route at this guard — the realm separation is by construction.
        'guest' => [
            'driver' => 'session',
            'provider' => 'guests',
        ],

        // Super Admin · Gate 0 — the PLATFORM-ADMIN realm. A SEPARATE session guard over the `platform_admins`
        // table (NOT `users`), so a tenant user can never authenticate here and a platform admin is never a tenant
        // user — the same by-construction realm separation the `guest` guard uses. It sits ABOVE the tenancy: a
        // platform admin has NO organization_id and NO spatie role, so `/app`/`/owner`/`/guest` never resolve it.
        // Do NOT point any tenant route at this guard.
        'admin' => [
            'driver' => 'session',
            'provider' => 'platform_admins',
        ],

        // OAuth2 (Wave D · Passport) — a TOKEN guard for the scoped third-party API (/api/*). Additive: it is
        // only ever invoked explicitly (`auth:api`), never a default, so the three session realms above are
        // untouched. It resolves a Passport access token → the `users` provider (a token is bound to a User,
        // whose owner+tenant context the API middleware re-establishes). NEVER point a web/owner route here.
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // Phase 4a — the GUEST provider. A CUSTOM driver (registered in AppServiceProvider) because a guest is
        // retrieved by the blind-index `email_hash`, not the encrypted `email`, and the lookup runs OUTSIDE any
        // tenant context (login is pre-tenant) so it queries without the organization scope. Only CLAIMED accounts
        // (password set) are retrievable for login.
        'guests' => [
            'driver' => 'guest-eloquent',
            'model' => Guest::class,
        ],

        // Super Admin · Gate 0 — the PLATFORM-ADMIN provider. A STANDARD eloquent provider (email is plaintext,
        // unlike the guest blind-index), over the separate PlatformAdmin model. No custom driver needed.
        'platform_admins' => [
            'driver' => 'eloquent',
            'model' => PlatformAdmin::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        // Phase 4a — the GUEST realm's reset broker, on the guests provider + its own token table. The token
        // table keys on the guest's blind-index hash (not the plaintext email); see the migration + Guest.
        'guests' => [
            'provider' => 'guests',
            'table' => 'guest_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];

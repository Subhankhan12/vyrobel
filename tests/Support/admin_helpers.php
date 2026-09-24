<?php

use App\Models\PlatformAdmin;
use Illuminate\Support\ViewErrorBag;

// Shared SUPER ADMIN test helpers (Build 1). In a Support file (required from Pest.php, guarded with
// function_exists) so every admin feature test reuses one definition without cross-file load-order coupling —
// the same discipline as auth_helpers.php. Gate 0's own test keeps its local `enrolledAdmin`; this is the
// forward-shared helper for the feature builds.

if (! function_exists('platformAdmin')) {
    /**
     * A fully-enrolled (2FA-confirmed) platform admin — ready to act on the `admin` guard past the mandatory-2FA
     * gate. Use `$this->actingAs(platformAdmin(), 'admin')` to drive any authenticated /admin route in a test.
     *
     * @param  array<string, mixed>  $attributes
     */
    function platformAdmin(array $attributes = []): PlatformAdmin
    {
        return PlatformAdmin::factory()->withTwoFactor()->create($attributes);
    }
}

if (! function_exists('sessionErrorKeys')) {
    /**
     * The validation-error KEYS in the session, whatever shape they are stored in. ⚠️ This app's session serialises the
     * error bag to a plain array ({"default": {"messages": {...}}}), which is also why Laravel's own failure message
     * crashes with "Call to a member function all() on array" when an error assertion fails.
     *
     * @return list<string>
     */
    function sessionErrorKeys(): array
    {
        $errors = session('errors');

        if ($errors instanceof ViewErrorBag) {
            return $errors->getBag('default')->keys();
        }

        return is_array($errors) ? array_keys((array) ($errors['default']['messages'] ?? [])) : [];
    }
}

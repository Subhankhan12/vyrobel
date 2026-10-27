<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsByRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\PasskeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PASSKEY SIGN-IN (Wave B · GATE 4) — passwordless login with a registered passkey. `options` mints + stashes the
 * assertion challenge; `login` verifies the authenticator's assertion with the real WebAuthn validator and, ONLY
 * on a genuine cryptographic success, logs the matched user in. A forged/replayed/cross-origin assertion fails the
 * validator → no login. Session regenerated on success (fixation protection). Returns JSON (the browser drives the
 * ceremony) with the role-appropriate redirect target.
 */
class PasskeyLoginController extends Controller
{
    use RedirectsByRole;

    public function __construct(private readonly PasskeyService $passkeys) {}

    /** Step 1 — the assertion options (JSON), stashed in session for the verify step. */
    public function options(Request $request): JsonResponse
    {
        $options = $this->passkeys->requestOptions($request);
        $json = $this->passkeys->toJson($options);
        $request->session()->put('passkey.request', $json);

        return response()->json(json_decode($json, true));
    }

    /** Step 2 — verify the assertion + log the matched user in (only on genuine success). */
    public function login(Request $request): JsonResponse
    {
        $stashed = $request->session()->pull('passkey.request');
        if (! is_string($stashed)) {
            return response()->json(['message' => __('php/http/controllers/auth/passkey_login_controller.start_again')], 422);
        }

        $options = $this->passkeys->requestOptionsFromJson($stashed);
        $passkey = $this->passkeys->verifyAssertion((string) $request->getContent(), $options, $request);

        $user = $passkey?->user;
        if (! $user instanceof User || $user->status !== 'active') {
            return response()->json(['message' => __('php/http/controllers/auth/passkey_login_controller.no_active_account')], 422);
        }

        $home = $this->homeFor($user);
        if ($home === null) {
            return response()->json(['message' => __('php/http/controllers/auth/passkey_login_controller.no_workspace')], 422);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return response()->json(['ok' => true, 'redirect' => $home]);
    }
}

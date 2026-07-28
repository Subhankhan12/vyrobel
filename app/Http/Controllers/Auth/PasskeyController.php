<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Passkey;
use App\Support\Auth\PasskeyService;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PASSKEY registration + management (Wave B · GATE 4) — for the AUTHENTICATED user (self-only via $request->user()).
 * The register ceremony is two steps: `options` mints + stashes the creation options (the challenge must survive to
 * the verify step), `store` verifies the authenticator response with the real WebAuthn validator and persists the
 * credential. `destroy` removes one (re-auth). Nothing is faked — a bad response fails the library validator.
 */
class PasskeyController extends Controller
{
    public function __construct(private readonly PasskeyService $passkeys) {}

    /** Step 1 — the registration options (JSON), stashed in session for the verify step. */
    public function options(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $options = $this->passkeys->creationOptions($user, $request);
        $json = $this->passkeys->toJson($options);
        $request->session()->put('passkey.creation', $json);

        return response()->json(json_decode($json, true));
    }

    /** Step 2 — verify the authenticator's registration response + persist the passkey. */
    public function store(Request $request, AuditEventRecorder $audit): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $stashed = $request->session()->pull('passkey.creation');
        abort_unless(is_string($stashed), 422, 'Start the passkey registration again.');

        $options = $this->passkeys->creationOptionsFromJson($stashed);

        try {
            $passkey = $this->passkeys->verifyRegistration(
                $user,
                (string) $request->getContent(),
                $options,
                $request,
                is_string($request->header('X-Passkey-Label')) ? $request->header('X-Passkey-Label') : null,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'That passkey could not be verified. Please try again.'], 422);
        }

        $audit->record('owner.passkey_registered', $user, null, ['passkey_id' => (string) $passkey->id]);

        return response()->json(['ok' => true, 'label' => $passkey->label]);
    }

    /** Remove one passkey (self-only, re-auth with the password). */
    public function destroy(Request $request, string $passkey, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $request->validate(['current_password' => ['required', 'current_password']]);

        // The where(user_id) makes a cross-user id a no-op (self-only).
        $deleted = Passkey::query()->where('user_id', $user->getKey())->whereKey($passkey)->delete();

        if ($deleted > 0) {
            $audit->record('owner.passkey_removed', $user, null, ['passkey_id' => $passkey]);
        }

        return back()->with('owner_notice', $deleted > 0 ? 'Passkey removed.' : 'That passkey was not found.');
    }
}

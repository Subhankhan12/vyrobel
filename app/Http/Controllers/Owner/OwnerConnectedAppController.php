<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OAuthConnectionService;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner SECURITY · Connected apps — REVOKE a third-party OAuth app (Wave D · OAuth · Phase 3). Follows the
 * hardened Security pattern: SELF-ONLY (the service scopes to `$request->user()`'s own tokens — a client id
 * from another user is a no-op), RE-AUTH with the current password, AUDITED (#20, user-as-subject), and a
 * controller POST (never a Livewire action, so the write runs through the web middleware). Revoke is IMMEDIATE
 * — the app's tokens are rejected on the very next API request.
 */
class OwnerConnectedAppController extends Controller
{
    public function revoke(Request $request, OAuthConnectionService $connections, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $data = $request->validate([
            'client_id' => ['required', 'string'],
            'current_password' => ['required', 'current_password'],
        ]);

        $revoked = $connections->revoke($user, (string) $data['client_id']);

        $audit->record('owner.connected_app_revoked', $user, null, [
            'client_id' => (string) $data['client_id'],
            'tokens_revoked' => $revoked,
        ]);

        return back()->with('owner_notice', $revoked > 0
            ? __('php/http/controllers/owner/owner_connected_app_controller.disconnected')
            : __('php/http/controllers/owner/owner_connected_app_controller.already_disconnected'));
    }
}

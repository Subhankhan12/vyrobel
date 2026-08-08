<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetTenantForWebRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The WORKSPACE (organization) switcher write — auth/isolation pass, item 2.
 *
 * ⚠️ `active_organization_id` was READ and NEVER WRITTEN. {@see SetTenantForWebRequest::resolveActiveOrganizationId()}
 * reads the session value and validates it against the user's active memberships, then falls back to
 * `$memberOrgIds->first()`. Nothing anywhere in the repo ever set it — so a user belonging to two
 * organizations was silently pinned to whichever membership came back first, permanently, with no way to
 * reach the other. The READ side was correct; the write path simply did not exist.
 *
 * This mirrors {@see PropertySwitchController} exactly — the same POST → validate-against-what-you-may-access
 * → session → PRG shape — rather than inventing a second mechanism.
 *
 * 🔒 ISOLATION (#38): the target org is validated against the user's OWN active memberships here, so a
 * crafted organization id in the request is rejected (422) and never stored. The middleware ALSO re-validates
 * on the next request, so even a smuggled session value cannot take effect — defence in depth, exactly as the
 * property switcher does it. Switching is a working-context change; it grants nothing the membership doesn't.
 */
class WorkspaceSwitchController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $memberOrgIds = $user instanceof User
            ? $user->memberships()->where('status', 'active')->pluck('organization_id')->map(fn ($id): string => (string) $id)->all()
            : [];

        $data = $request->validate([
            'organization_id' => ['required', 'string', 'in:'.implode(',', $memberOrgIds)],
        ]);

        session(['active_organization_id' => $data['organization_id']]);

        // The property context belongs to the OLD workspace — clear it so the new org resolves its own
        // default rather than carrying a foreign property id across the switch.
        session()->forget('current_property_id');

        return redirect()->route('app.dashboard');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InviteNoLongerRedeemable;
use App\Services\OwnerTeamInviteService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Accept an OWNER-TEAM invite / set-password activation (Phase 7c · SG3) — the owner-realm parallel of
 * {@see AcceptInviteController}. PUBLIC (the invitee has no usable password yet). GET validates the tokened
 * link and shows the set-password screen; POST sets the password, ACTIVATES the login + joins the sub-user
 * under the invite's owner at its role, spends the single-use token, and logs them into /owner.
 *
 * SECURITY: any invalid / expired / already-spent token yields the SAME generic response (no enumeration).
 * The token is matched by its sha256 hash. Rate-limited (see routes). Session regenerated on activation.
 */
class AcceptOwnerTeamInviteController extends Controller
{
    public function __construct(private readonly OwnerTeamInviteService $invites) {}

    /** Show the set-password form for a valid token, or a generic "invalid link" page. */
    public function show(Request $request, string $token): View
    {
        $invite = $this->invites->findRedeemable($token);
        if ($invite === null) {
            return view('auth.invite-invalid');
        }

        // Greet by the invitee's own name (their own link — not a leak). Resolved in the invite's org.
        $name = app(TenantContext::class)->forOrganization(
            (string) $invite->organization_id,
            fn (): string => (string) (User::query()->where('email', $invite->email)->value('name') ?? 'there'),
        );

        return view('auth.set-password', ['token' => $token, 'name' => $name, 'action' => url('/team-invite/'.$token)]);
    }

    /** Set the password → activate → join → auto-login → /owner. Generic error for any bad/spent token. */
    public function store(Request $request, string $token): View|RedirectResponse
    {
        $invite = $this->invites->findRedeemable($token);
        if ($invite === null) {
            return view('auth.invite-invalid');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user = $this->invites->accept($invite, (string) $request->string('password'));
        } catch (InviteNoLongerRedeemable) {
            return view('auth.invite-invalid');   // DEC-o — the owner's portal was switched off meanwhile: nothing was written
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();   // fixation protection on activation

        return redirect()->to(route('owner.dashboard'));
    }
}

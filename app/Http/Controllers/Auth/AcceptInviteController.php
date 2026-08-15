<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Accept-invite / set-password activation (Auth Phase B · Step 2). PUBLIC (the invitee has no usable
 * password yet). GET validates the tokened link and shows the set-password screen; POST sets the
 * password, ACTIVATES the login + owner, spends the single-use token, and logs the owner in → /owner.
 *
 * SECURITY: any invalid / expired / already-spent token yields the SAME generic response (no
 * enumeration of which invites exist). The token is matched by its sha256 hash. The route is
 * rate-limited (see routes). Session is regenerated on activation.
 */
class AcceptInviteController extends Controller
{
    public function __construct(private readonly OwnerInviteService $invites) {}

    /** Show the set-password form for a valid token, or a generic "invalid link" page. */
    public function show(Request $request, string $token): View
    {
        $invite = $this->invites->findRedeemable($token);
        if ($invite === null) {
            return view('auth.invite-invalid');
        }

        // Greet by the invitee's own name (loaded in the invite's org). Not a leak — it's their own link.
        // Owner invites carry an owner→name; staff invites (no owner) greet by the invited login's name.
        $name = app(TenantContext::class)->forOrganization(
            (string) $invite->organization_id,
            fn (): string => $invite->owner_id !== null
                ? (string) (Owner::query()->findOrFail($invite->owner_id)->name ?? 'there')
                : (string) (User::query()->where('email', $invite->email)->value('name') ?? 'there'),
        );

        return view('auth.set-password', ['token' => $token, 'name' => $name]);
    }

    /** Set the password → activate → auto-login → /owner. Generic error for any bad/spent token. */
    public function store(Request $request, string $token): View|RedirectResponse
    {
        $invite = $this->invites->findRedeemable($token);
        if ($invite === null) {
            return view('auth.invite-invalid');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Capture the role home BEFORE accept() (the invite is spent in the transaction): an owner invite
        // → the owner portal; a staff invite (no owner) → the operator app.
        $home = $invite->owner_id !== null ? route('owner.dashboard') : route('app.dashboard');

        $user = $this->invites->accept($invite, (string) $request->string('password'));

        Auth::guard('web')->login($user);
        $request->session()->regenerate();   // fixation protection on activation

        return redirect()->to($home);
    }
}

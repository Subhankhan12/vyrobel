<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\OwnerInvite;
use App\Services\InviteNoLongerRedeemable;
use App\Services\OwnerInviteService;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Owner\OwnerListParams;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Resend and cancel a waiting invite from the Owners page (2026-10-01). ⚠️ The "Invite sent" screen had always said "You can
 * resend it any time from the owner's row" — and there was no resend anywhere. Both go through {@see OwnerInviteService}, the
 * ONE invite writer; both are `users.manage` (the route) and tenant-scoped (another organisation's invite is a 404).
 *
 * ⚠️ RESEND ASKS FOR YOUR PASSWORD, as sending did: a fresh link is a way into the organisation, and resending is sending.
 * Cancelling takes access away, so it does not. A founder's set-password link (ADMIN 04) is not this page's to touch.
 */
class OwnerInviteActionController extends Controller
{
    public function resend(Request $request, OwnerInviteService $invites, string $invite): RedirectResponse
    {
        $model = $this->find($invite);
        if ($refused = $this->paused($request)) {
            return $refused;
        }

        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [
                'current_password.required' => 'Enter your password to send the invite again.',
                'current_password.current_password' => 'That password isn’t right — enter the one you sign in with.',
            ],
        );

        try {
            $result = $invites->resend($model);
        } catch (InviteNoLongerRedeemable $e) {
            return $this->back($request)->with('owners_error', $e->getMessage());   // used or cancelled — its own sentence
        } catch (RuntimeException) {
            return $this->back($request)->with('owners_error', 'That invite can’t be sent again — the email may already have a Vyrobel account.');
        }

        return $this->back($request)->with('owners_notice', 'Invite sent again to '.$model->email.' — a fresh link, valid until '
            .($result['invite']->expires_at?->format('j M Y') ?? 'in 7 days').'.');
    }

    public function cancel(Request $request, OwnerInviteService $invites, string $invite): RedirectResponse
    {
        $model = $this->find($invite);
        if ($refused = $this->paused($request)) {
            return $refused;
        }

        if (! $invites->cancel($model)) {
            return $this->back($request)->with('owners_error', 'That invite has already been used or cancelled.');
        }

        return $this->back($request)->with('owners_notice', 'Invite to '.$model->email.' cancelled — the link no longer works.');
    }

    /** This organisation's invite (the tenant scope makes another's a 404) — never a founder's link. */
    private function find(string $invite): OwnerInvite
    {
        return OwnerInvite::query()->where('role', '!=', OwnerInviteService::FOUNDER_ROLE)->findOrFail($invite);
    }

    private function paused(Request $request): ?RedirectResponse
    {
        return app(OperatorWorkspace::class)->isReadOnly()
            ? $this->back($request)->with('owners_error', 'Workspace paused — invites can’t be sent or cancelled for now.')
            : null;
    }

    private function back(Request $request): RedirectResponse
    {
        return OwnerListParams::redirect($request) ?? redirect()->route('app.owners');
    }
}

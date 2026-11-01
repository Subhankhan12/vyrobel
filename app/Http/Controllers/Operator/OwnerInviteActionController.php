<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Services\InviteNoLongerRedeemable;
use App\Services\OwnerInviteService;
use App\Support\I18n\LocalDate;
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
        if ($refused = $this->paused($request, $model)) {
            return $refused;
        }

        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [
                'current_password.required' => __('php/http/controllers/operator/owner_invite_action_controller.password_required'),
                'current_password.current_password' => __('php/http/controllers/operator/owner_invite_action_controller.password_wrong'),
            ],
        );

        try {
            $result = $invites->resend($model);
        } catch (InviteNoLongerRedeemable $e) {
            return $this->back($request, $model)->with('owners_error', $e->getMessage());   // used or cancelled — its own sentence
        } catch (RuntimeException) {
            return $this->back($request, $model)->with('owners_error', __('php/http/controllers/operator/owner_invite_action_controller.cannot_resend'));
        }

        $expiresAt = $result['invite']->expires_at;

        return $this->back($request, $model)->with('owners_notice', $expiresAt !== null
            ? __('php/http/controllers/operator/owner_invite_action_controller.resent_valid_until', ['email' => $model->email, 'date' => $expiresAt->translatedFormat(LocalDate::pattern('j M Y'))])
            : __('php/http/controllers/operator/owner_invite_action_controller.resent_valid_7_days', ['email' => $model->email]));
    }

    public function cancel(Request $request, OwnerInviteService $invites, string $invite): RedirectResponse
    {
        $model = $this->find($invite);
        if ($refused = $this->paused($request, $model)) {
            return $refused;
        }

        if (! $invites->cancel($model)) {
            return $this->back($request, $model)->with('owners_error', __('php/http/controllers/operator/owner_invite_action_controller.already_used_or_cancelled'));
        }

        return $this->back($request, $model)->with('owners_notice', __('php/http/controllers/operator/owner_invite_action_controller.invite_cancelled', ['email' => $model->email]));
    }

    /** This organisation's invite (the tenant scope makes another's a 404) — never a founder's link. */
    private function find(string $invite): OwnerInvite
    {
        return OwnerInvite::query()->where('role', '!=', OwnerInviteService::FOUNDER_ROLE)->findOrFail($invite);
    }

    private function paused(Request $request, OwnerInvite $invite): ?RedirectResponse
    {
        return app(OperatorWorkspace::class)->isReadOnly()
            ? $this->back($request, $invite)->with('owners_error', __('php/http/controllers/operator/owner_invite_action_controller.workspace_paused'))
            : null;
    }

    /**
     * Back where the form was: the Owners list as the operator had it, or (2026-10-03) the page of the invite's own owner —
     * the owner is read off the INVITE, never the request, and a cancel that removed a never-activated owner goes to the list.
     */
    private function back(Request $request, OwnerInvite $invite): RedirectResponse
    {
        if ($request->input('return') === 'owner' && $invite->owner_id !== null && Owner::query()->whereKey($invite->owner_id)->exists()) {
            return redirect()->route('app.owners.show', (string) $invite->owner_id);
        }

        return OwnerListParams::redirect($request) ?? redirect()->route('app.owners');
    }
}

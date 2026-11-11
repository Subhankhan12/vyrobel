<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Unit;
use App\Services\InviteNoLongerRedeemable;
use App\Services\OwnerInviteService;
use App\Support\I18n\ListJoin;
use App\Support\I18n\LocalDate;
use App\Support\I18n\LocalNumber;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Owner\OwnerListParams;
use App\Support\Property\PropertyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Operator "invite owner" write (Auth Phase B · Step 4). The proven operator-mutation pattern:
 * POST → controller → OwnerInviteService → PRG. The POST re-runs the web middleware, so the tenant
 * context is set — the invite (and the units it may assign) are created org-scoped. Rate-limited +
 * CSRF-protected via the route/web group.
 *
 * Three roles are accepted — `owner` (an external Owner party → the portal) and the two STAFF roles
 * `manager`/`accountant` (a Membership + real spatie role → the operator app). The service maps the
 * staff slugs to the exact catalog roles (Hotel Manager / Accountant) and validates they exist. The
 * server never trusts the client: an unknown role is rejected by validation, owner-only fields are
 * dropped for staff, and an email that already belongs to an active user surfaces as a clean flash
 * error (not a 500).
 *
 * ⚠️⚠️ 2026-10-01 — A UNIT THAT HAS AN OWNER IS NEVER GIVEN THROUGH AN INVITE. A unit's owner decides who is paid for it;
 * the dialog offered owned units and acceptance wrote the new owner over the old one. Such a unit is now refused here with
 * a sentence (and acceptance re-checks, {@see OwnerInviteService::accept()}). The units are also kept inside the person's
 * properties — the `readIds()` rule every unit and rate write follows since DEC-k and DEC-l.
 */
class InviteOwnerController extends Controller
{
    public function store(Request $request, OwnerInviteService $invites): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($request)->withInput($request->except('current_password'))
                ->with('owners_error', __('php/http/controllers/operator/invite_owner_controller.workspace_paused'));
        }

        // ⚠️ RE-AUTH (2026-08-30): this mints a NEW principal — `manager`/`accountant` map to real back-office
        // roles (Hotel Manager / Accountant), so it changes who can reach the whole organisation.
        // Bare `current_password`: /app runs on the default `web` guard (verified).
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [
                'current_password.required' => __('php/http/controllers/operator/invite_owner_controller.password_required'),
                'current_password.current_password' => __('php/http/controllers/operator/invite_owner_controller.password_wrong'),
            ],
        );

        LocalNumber::mergeInto($request, ['commission_pct']);   // D-MONEY-COMMA: "12,5" read in the page's language before `numeric`
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::in(['owner', 'manager', 'accountant'])],
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit_ids' => ['sometimes', 'array'],
            'unit_ids.*' => ['string'],
        ], [
            'name.required' => __('php/http/controllers/operator/invite_owner_controller.name_required'),
            'email.required' => __('php/http/controllers/operator/invite_owner_controller.email_required'),
            'email.email' => __('php/http/controllers/operator/invite_owner_controller.email_invalid'),
            'commission_pct.numeric' => __('php/http/controllers/operator/invite_owner_controller.commission_range'),
            'commission_pct.min' => __('php/http/controllers/operator/invite_owner_controller.commission_range'),
            'commission_pct.max' => __('php/http/controllers/operator/invite_owner_controller.commission_range'),
        ]);

        $isOwner = $validated['role'] === 'owner';

        // Owner-only fields (assigned units + commission) apply ONLY to owner invites; a staff invite never carries them.
        // The units are this organisation's (the tenant scope drops a foreign id) and inside the person's properties.
        $unitIds = [];
        if ($isOwner && ($validated['unit_ids'] ?? []) !== []) {
            $scope = app(PropertyContext::class)->readIds();
            $units = Unit::query()->whereIn('id', $validated['unit_ids'])
                ->when($scope !== [], fn ($q) => $q->whereIn('property_id', $scope))
                ->get(['id', 'code', 'name', 'owner_id']);

            $owned = $units->filter(fn (Unit $u): bool => $u->owner_id !== null);
            if ($owned->isNotEmpty()) {
                $names = Owner::query()->whereIn('id', $owned->pluck('owner_id'))->pluck('name', 'id');
                $which = $owned->map(fn (Unit $u): string => __('php/http/controllers/operator/invite_owner_controller.unit_owned_by', [
                    'unit' => $u->name ?: $u->code,
                    'owner' => $names[$u->owner_id] ?? __('php/http/controllers/operator/invite_owner_controller.another_owner'),
                ]))->pipe(fn ($which) => ListJoin::join($which));

                return $this->back($request)->withInput($request->except('current_password'))->withErrors(['unit_ids' => trans_choice(
                    'php/http/controllers/operator/invite_owner_controller.units_already_owned', $owned->count(), ['units' => $which])]);
            }
            $unitIds = $units->pluck('id')->map(fn ($id): string => (string) $id)->all();
        }
        $commissionPct = $isOwner && isset($validated['commission_pct']) ? (float) $validated['commission_pct'] : null;

        try {
            $result = $invites->invite($validated['name'], $validated['email'], $unitIds, $commissionPct, $validated['role']);
        } catch (InviteNoLongerRedeemable $e) {
            // DEC-o — the address belongs to an owner whose portal access is switched off: its own sentence, and nothing was written
            return $this->back($request)->withInput($request->except('current_password'))->with('owners_error', $e->getMessage());
        } catch (RuntimeException) {
            // e.g. the email already belongs to an active account, or an unmappable role — graceful, not a crash.
            return $this->back($request)->withInput($request->except('current_password'))
                ->with('owners_error', __('php/http/controllers/operator/invite_owner_controller.email_cannot_be_invited'));
        }

        // ⚠️ D-MAIL-CLAIMS (2026-11-11, his "do all"): the invite is created either way, but "Invite sent to <address>" is said only while email IS sent from
        //    Vyrobel; otherwise it says it was saved and that nobody has been told (production's mailer is `log` until the founder turns mail on).
        $mailSends = MailSafetyGuard::sendingNow();

        // Feed the "Invite sent" confirmation view (rendered on this PRG): the real invited email + the real 7-day expiry.
        return (OwnerListParams::redirect($request) ?? redirect()->route('app.owners'))
            ->with('owners_notice', __($mailSends ? 'php/http/controllers/operator/invite_owner_controller.invite_sent' : 'php/http/controllers/operator/invite_owner_controller.invite_saved_no_mail', ['email' => $validated['email']]))
            ->with('owner_invite_sent', [
                'mail_sends' => $mailSends,
                'email' => $validated['email'],
                'expires' => $result['invite']->expires_at?->translatedFormat(LocalDate::pattern('j M Y')),
            ]);
    }

    /** Back to the list as the operator had it when the dialog was opened there; otherwise back. */
    private function back(Request $request): RedirectResponse
    {
        return OwnerListParams::redirect($request) ?? back();
    }
}

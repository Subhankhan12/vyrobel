<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PayoutMethod;
use App\Services\OwnerPayoutMethodService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Payouts\Iban;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner Payout-Method writes (Phase 2 · Sub-gate 5) — POST/DELETE → {@see OwnerPayoutMethodService} → PRG. The
 * owner adds / sets-default / removes their OWN bank accounts. SELF-ONLY: new methods attach to the owner
 * resolved by user_id (never a request id), and set-default / remove resolve the method through the owner scope
 * (a cross-owner id 404s). The IBAN is validated by its real ISO mod-97 checksum before anything is stored.
 */
class OwnerPayoutMethodController extends Controller
{
    /**
     * ⚠️⚠️ RE-AUTHENTICATE BEFORE MOVING WHERE THE MONEY GOES (audit follow-up, 2026-08-30).
     *
     * All three actions below changed the payout destination with NO password — capability-gated only. A
     * hijacked owner session could redirect the destination IMMEDIATELY, without the slower email takeover
     * closed by H-3. REPRODUCED: added an attacker account and set it default, no password, both 302.
     *
     * ⚠️ ALL THREE, NOT JUST THE ADD. Adding an account achieves nothing on its own; SET-DEFAULT is the
     * actual redirect, and REMOVE deletes the real account so whatever remains becomes the destination.
     *
     * ⚠️ THE BARE `current_password`, as {@see OwnerAccountController} uses.
     * config/auth.php defines web · guest · admin and NO `owner` guard; /owner runs on plain `auth`, so a
     * guard-qualified rule would silently match nothing and validate nothing.
     *
     * ⚠️ THE DATED CLASS, TWICE CONFIRMED: this controller is 2026-07-20 (b95228f) and the owner email change
     * was 2026-07-25 — both BEFORE the `current_password` requirement arrived on 2026-07-27 (065b554) and was
     * never back-applied. Owner-realm code predating that date is the bounded suspect set.
     */
    private function reauthenticate(Request $request): void
    {
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [],
            ['current_password' => __('php/http/controllers/owner/owner_payout_method_controller.attr_current_password')],
        );
    }

    public function store(Request $request, OwnerPayoutMethodService $methods): RedirectResponse
    {
        $this->reauthenticate($request);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'account_holder' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:42', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Iban::isValid((string) $value)) {
                    $fail(__('php/http/controllers/owner/owner_payout_method_controller.invalid_iban'));
                }
            }],
            'bic' => ['nullable', 'string', 'max:20'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
        ]);

        try {
            $methods->add($request->user(), $data);
        } catch (ModelNotFoundException) {
            abort(404);   // non-linked user
        }

        return redirect()->to(route('owner.payout-method'))->with('payout_method_notice', __('php/http/controllers/owner/owner_payout_method_controller.account_saved'));
    }

    public function setDefault(Request $request, OwnerPayoutMethodService $methods, string $method): RedirectResponse
    {
        // ⚠️ ORDER IS LOAD-BEARING: capability (route middleware) → OWNERSHIP → identity.
        // assertCanManagePayout() resolves the method under the owner scope, so a cross-owner id 404s
        // BEFORE the password is considered. Inverted, a "wrong password" on someone else's account would
        // confirm it EXISTS AND BELONGS TO SOMEONE — an existence oracle the attacker did not have.
        $this->assertCanManagePayout($method);
        $this->reauthenticate($request);   // ⚠️ THE actual redirect — an attacker who can only ADD achieves nothing
        try {
            $methods->setDefault($method);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->to(route('owner.payout-method'))->with('payout_method_notice', __('php/http/controllers/owner/owner_payout_method_controller.default_updated'));
    }

    public function destroy(Request $request, OwnerPayoutMethodService $methods, string $method): RedirectResponse
    {
        // ⚠️ Ownership first, identity second — same reason as setDefault(): no existence oracle.
        $this->assertCanManagePayout($method);
        $this->reauthenticate($request);   // ⚠️ removing the real account leaves whatever remains as the destination
        try {
            $methods->remove($method);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->to(route('owner.payout-method'))->with('payout_method_notice', __('php/http/controllers/owner/owner_payout_method_controller.account_removed'));
    }

    /**
     * 🔒 P1-1: mutating a stored bank account is CAP_PAYOUT ON THAT method's owner — resolve the method under the
     * owner scope (cross-owner 404) then check the acting user's PER-OWNER payout role (never the union role, so a
     * co-owner of A + view-only of B can never set-default/remove B's account).
     */
    private function assertCanManagePayout(string $method): void
    {
        $model = PayoutMethod::query()->findOrFail($method);   // owner-scoped
        abort_unless(app(OwnerContext::class)->canOn((string) $model->owner_id, OwnerTeamRole::CAP_PAYOUT), 403);
    }
}

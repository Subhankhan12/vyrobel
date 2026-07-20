<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerPayoutMethodService;
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
    public function store(Request $request, OwnerPayoutMethodService $methods): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'account_holder' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:42', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Iban::isValid((string) $value)) {
                    $fail('Enter a valid IBAN.');
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

        return redirect()->to(route('owner.payout-method'))->with('payout_method_notice', 'Bank account saved.');
    }

    public function setDefault(Request $request, OwnerPayoutMethodService $methods, string $method): RedirectResponse
    {
        try {
            $methods->setDefault($method);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->to(route('owner.payout-method'))->with('payout_method_notice', 'Default account updated.');
    }

    public function destroy(Request $request, OwnerPayoutMethodService $methods, string $method): RedirectResponse
    {
        try {
            $methods->remove($method);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->to(route('owner.payout-method'))->with('payout_method_notice', 'Bank account removed.');
    }
}

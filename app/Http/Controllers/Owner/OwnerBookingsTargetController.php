<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Models\OwnerMonthlyTarget;
use App\Support\Money\MoneyInput;
use App\Support\Owner\OwnerBookingsReader;
use App\Support\Owner\OwnerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner Bookings — set the MONTHLY NET-REVENUE GOAL (GATE 1). The real backing behind the "Monthly goal" ring:
 * the owner types a target, we store it, and the ring divides the REAL current-month net by it (#39 — no
 * fabricated goal). A WRITE on a controller ROUTE (invariant #20/#30): sensitive owner writes never ride a
 * Livewire action (that would bypass {@see SetOwnerScopeForPortal} + the capability gate).
 *
 * GATED: `CAP_ACTIONS` (co-owner only) — setting a business target is an approval-class action, not a view or a
 * download; a view-only sub-user can SEE the ring but not change the shared target, an accountant can't reach the
 * page at all. OWNER-SCOPED: the row is written under the resolved {@see OwnerContext} owner id (never a request
 * id); a non-linked user has no owner id → nothing is written. Idempotent upsert on (owner_id, currency).
 *
 * CURRENCY is DERIVED server-side from the owner's own ledger ({@see OwnerBookingsReader::primaryCurrency()} — the
 * SAME rule the ring displays in), never trusted from the request: the target's currency always matches the one
 * the ring reads, and a tampered POST can't create a junk `(owner_id, 'ZZZ')` row.
 */
class OwnerBookingsTargetController extends Controller
{
    public function store(Request $request, OwnerContext $ownerContext, OwnerBookingsReader $reader): RedirectResponse
    {
        $ownerIds = $ownerContext->ownerIds();
        // Fail closed: no resolved owner → no target write (never a request-supplied owner id).
        if ($ownerIds === []) {
            abort(404);
        }

        $validated = $request->validate([
            'target' => ['required', 'string', 'max:20'],
        ]);

        // Same string-arithmetic money parse every typed amount uses (#17) — cap at €10,000,000/mo.
        $minor = MoneyInput::toMinor($validated['target'], 1_000_000_000);

        if ($minor === null) {
            return back()->withErrors(['target' => 'Enter a monthly target above zero, e.g. 6000.']);
        }

        // The currency the ring speaks in — the owner's real portfolio currency, not a request field.
        $currency = OwnerBookingsReader::primaryCurrency($reader->ledgerRows());

        // The first resolved owner is the target holder (a portal user is normally linked to one owner; the
        // Bookings ring is a single per-currency goal). organization_id is auto-filled by BelongsToOrganization.
        OwnerMonthlyTarget::query()->updateOrCreate(
            ['owner_id' => $ownerIds[0], 'currency' => $currency],
            ['target_minor' => $minor],
        );

        return back()->with('status', 'Monthly target updated.');
    }
}

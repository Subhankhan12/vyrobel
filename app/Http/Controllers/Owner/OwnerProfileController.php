<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner SELF-PROFILE write (Wave 4 · Sub-gate 3) — an owner edits their OWN contact details, via the
 * operator-mutation pattern (POST → controller → PRG).
 *
 * ⚠️ THE GUARD IS EXPLICIT AND LOAD-BEARING. {@see Owner} is `BelongsToOrganization`, NOT `BelongsToOwner`
 * (docs/WAVE4-INVENTORY.md — `BelongsToOwner` covers units/statements/payouts/ledger/expenses, NOT the owner
 * row itself). So `OwnerScope` does NOT scope this write: the owner is resolved by the EXPLICIT
 * `where('user_id', auth id)` filter, and there is no owner id anywhere in the request — an owner can only
 * ever write the single row that belongs to their own login. Tenant isolation still composes underneath
 * (`Owner` is org-scoped, and `/owner` runs inside the resolved tenant).
 *
 * `email`/`phone` are encrypted casts, so the values are safe at rest; the audit event records only WHICH
 * fields changed, never the values (the same PII discipline as the guest writes).
 */
class OwnerProfileController extends Controller
{
    public function update(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        // The ONLY owner this user may edit — resolved from their login, never a request-supplied id.
        $owner = Owner::query()->where('user_id', $request->user()?->getKey())->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $owner->update($data);

        $audit->record('owner.profile_updated', $owner, null, ['fields' => array_keys($data)]);

        return redirect()->to(route('owner.settings'))->with('owner_notice', 'Your details are saved.');
    }
}

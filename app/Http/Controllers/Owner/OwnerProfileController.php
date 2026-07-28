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
            // The owner's registered ADDRESS (GATE 3) — the recipient block on their statement letterhead.
            'address_street' => ['nullable', 'string', 'max:200'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_postcode' => ['nullable', 'string', 'max:40'],
            'address_country' => ['nullable', 'string', 'max:60'],
        ]);

        // Compose the four address parts into the existing `address` JSON column (array cast). An all-blank
        // submission clears it back to null so the letterhead shows no empty recipient block.
        $address = array_filter([
            'street' => trim((string) ($data['address_street'] ?? '')),
            'city' => trim((string) ($data['address_city'] ?? '')),
            'postcode' => trim((string) ($data['address_postcode'] ?? '')),
            'country' => trim((string) ($data['address_country'] ?? '')),
        ], fn (string $v): bool => $v !== '');

        $update = [
            'name' => $data['name'],
            'address' => $address === [] ? null : $address,
        ];

        // Only touch email/phone when the form actually submitted them — an address-only save must not
        // overwrite the existing contact (owners.email is NOT NULL, so a stray null would be rejected).
        if ($request->has('email')) {
            $update['email'] = $data['email'] ?? null;
        }
        if ($request->has('phone')) {
            $update['phone'] = $data['phone'] ?? null;
        }

        $owner->update($update);

        $audit->record('owner.profile_updated', $owner, null, ['fields' => array_keys($update)]);

        return redirect()->to(route('owner.settings'))->with('owner_notice', 'Your details are saved.');
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guest;

/**
 * The SINGLE guest-identity matcher — reused by OTA booking import ({@see BookingImportService}) and the
 * unified inbox ({@see InboxService}), so there is never a parallel matcher. Matches an existing guest by
 * `external_refs->{refKey} == refValue`, else creates one with the given profile + that ref.
 *
 * `refValue` MUST be a NON-PII token — an opaque OTA guest id, or a HASH of a contact address. Never a
 * raw email/phone: `external_refs` is plaintext JSON, and the guest's `email`/`phone` are encrypted at
 * rest precisely so contact PII is never queryable in the clear.
 */
class GuestMatcher
{
    /**
     * @param  array<string, mixed>  $profile  first_name/last_name/email/phone/… for a newly created guest
     */
    public function matchOrCreate(string $refKey, ?string $refValue, array $profile): Guest
    {
        $hasRef = $refValue !== null && $refValue !== '';

        if ($hasRef) {
            $existing = Guest::query()->where('external_refs->'.$refKey, $refValue)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return Guest::create(array_merge($profile, [
            'external_refs' => $hasRef ? [$refKey => $refValue] : [],
        ]));
    }
}

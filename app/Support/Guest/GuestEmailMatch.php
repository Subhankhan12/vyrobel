<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Guest;

/**
 * THE EXISTING GUEST FOR A TYPED EMAIL (DEC-r, 2026-11-11, his "I like your suggestions") — the New reservation wizard used to create a NEW guest even when
 * the email already belonged to one (a walk made four "Lena Walk"s). The wizard now OFFERS the existing guest — "Use this guest / Create a new one".
 *
 * ⚠️ NAME ONLY (#28). A match is found by the email's BLIND INDEX (`guests.email_hash`, an HMAC of the lower-cased, trimmed email — the one the Guests
 * search and the portal claim already use; no second index) and what comes back is the guest's id, NAME and the same "N stays · guest since YYYY" line the
 * returning-guest picker shows — never the email, never the phone, never money. Tenant-scoped through the model (another organisation's guest can never
 * match); an ERASED guest (`anonymized_at`) or one whose erasure is scheduled is never offered — booking a stay on them would be cancelled by the erase.
 * Phone has no index, so it is never matched. Several matches are possible (the duplicates that already exist): at most {@see LIMIT}, each with its history.
 */
final class GuestEmailMatch
{
    public const LIMIT = 3;

    /** @return list<array{id: string, name: string, history: string}> */
    public static function for(string $email): array
    {
        $email = trim($email);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return [];
        }

        return Guest::query()
            ->whereNull('anonymized_at')
            ->whereNull('scheduled_deletion_at')
            ->where('email_hash', Guest::emailHash($email))
            ->with(['reservations' => fn ($q) => $q->select(['id', 'lead_guest_id', 'status', 'check_in', 'check_out'])])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::LIMIT)
            ->get()
            ->map(function (Guest $guest): array {
                $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));

                return [
                    'id' => (string) $guest->getKey(),
                    'name' => $name !== '' ? $name : (string) __('php/livewire/operator/new_reservation.guest_no_name'),
                    'history' => GuestHistory::for($guest->reservations)['stat'] ?? (string) __('php/livewire/operator/new_reservation.no_stays_yet'),
                ];
            })
            ->all();
    }
}

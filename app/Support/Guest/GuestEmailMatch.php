<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Builder;

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

    /**
     * VY-17 (audit of 2026-11-24) — THE ONE existing guest a PUBLIC booking joins for this email, or NULL (then a new guest is made).
     * Every storefront booking used to create a new guest record, so a guest who booked twice had two records and their portal
     * account — one of them — showed only its own booking.
     *
     * The same blind index and the same exclusions as {@see self::for()}: tenant-scoped through the model, never an erased guest or
     * one whose erasure is scheduled. Of several records (the duplicates that already exist) the one with a PORTAL ACCOUNT comes
     * first — that is the record the guest signs in as — then the oldest.
     *
     * ⚠️ The caller must not write the public form's name or language onto the record it gets back: the form is anonymous, and a
     * visitor who types someone's email must not be able to rename them.
     */
    public static function bookingGuest(?string $email): ?Guest
    {
        $email = trim((string) $email);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return self::holding($email)
            ->orderByRaw('CASE WHEN password IS NOT NULL AND email_verified_at IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('id')   // a ULID: the oldest record
            ->first();
    }

    /**
     * The guests who hold this email in THIS organisation — the ONE base both reads above and below share, so the wizard's offer and
     * the storefront's match can never disagree about who counts: found by the blind index, tenant-scoped through the model, never
     * an erased guest and never one whose erasure is scheduled.
     *
     * @return Builder<Guest>
     */
    private static function holding(string $email): Builder
    {
        return Guest::query()
            ->whereNull('anonymized_at')
            ->whereNull('scheduled_deletion_at')
            ->where('email_hash', Guest::emailHash($email));
    }

    /** @return list<array{id: string, name: string, history: string}> */
    public static function for(string $email): array
    {
        $email = trim($email);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return [];
        }

        return self::holding($email)
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

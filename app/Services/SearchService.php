<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guest;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

/**
 * GLOBAL cross-entity operator search — reservations (by reference OR lead-guest name), guests (by NAME), units
 * (by code/name), owners (by name). ⚠️ NOT {@see BookingEngineService::search()}, which is a property-scoped
 * AVAILABILITY search (a different question); the audit flagged the risk of conflating the two.
 *
 * ⚠️ ENCRYPTED CONTACT (the honesty guard). `guests.email`/`phone` are `encrypted` casts — the ciphertext is not
 * indexed by value, so a `LIKE` over it matches NOTHING. Guests are therefore searched by NAME ONLY (the two
 * plaintext columns, via `CONCAT_WS` so "Ada", "Lovelace" and "Ada Lovelace" all match), exactly as the Guests
 * CRM + New Reservation already do. Searching by email/phone needs a blind index (deferred); the CALLER surfaces
 * that limitation ({@see looksLikeContact()}) rather than silently returning nothing for a valid email. An
 * anonymized guest (names nulled) never matches — `CONCAT_WS` skips NULLs, so it doesn't error either.
 *
 * TENANT-scoped BY CONSTRUCTION: every model here is BelongsToOrganization, so its global scope restricts every
 * query to the acting org — another org's rows can never appear. Per-entity RESULTS are gated by the caller's
 * abilities (guests → `guests.view`, units → `units.view`); reservations gate the page (`reservations.view`) and
 * owners mirror the #26-only operator owners list. No result is fabricated — every row is a real record.
 */
class SearchService
{
    private const LIMIT = 8;

    /**
     * @param  array{units: bool, guests: bool}  $can  which optional entity groups the viewer may see
     * @return array{reservations: list<array{id: string, reference: string, guest: string, status: string, source: string, url: string}>, guests: list<array{id: string, name: string, url: string}>, units: list<array{id: string, code: string, name: string, url: string}>, owners: list<array{id: string, name: string, url: string}>}
     */
    public function search(string $query, array $can): array
    {
        $term = '%'.addcslashes($query, '%_\\').'%';

        return [
            'reservations' => $this->reservations($term),
            'guests' => $can['guests'] ? $this->guests($term) : [],
            'units' => $can['units'] ? $this->units($term) : [],
            'owners' => $this->owners($term),
        ];
    }

    /** Does the query look like an email/phone the operator might expect us to match — but can't (encrypted)? */
    public function looksLikeContact(string $query): bool
    {
        $query = trim($query);

        return str_contains($query, '@') || preg_match('/^\+?[\d\s()\-]{6,}$/', $query) === 1;
    }

    /** @return list<array{id: string, reference: string, guest: string, status: string, source: string, url: string}> */
    private function reservations(string $term): array
    {
        return Reservation::query()
            ->with('leadGuest')
            ->where(function (Builder $q) use ($term): void {
                $q->where('reference', 'like', $term)
                    ->orWhereHas('leadGuest', fn (Builder $g) => $g->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term]));
            })
            ->latest('created_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Reservation $r): array => [
                'id' => (string) $r->getKey(),
                'reference' => (string) $r->reference,
                'guest' => $this->guestName($r->leadGuest),
                'status' => (string) $r->status,
                'source' => (string) $r->source,
                'url' => route('app.reservations.show', $r->getKey()),
            ])
            ->all();
    }

    /** @return list<array{id: string, name: string, url: string}> */
    private function guests(string $term): array
    {
        return Guest::query()
            ->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term])   // NAME ONLY — email/phone are encrypted
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Guest $g): array => [
                'id' => (string) $g->getKey(),
                'name' => $this->guestName($g),
                'url' => route('app.guests.show', $g->getKey()),
            ])
            ->all();
    }

    /** @return list<array{id: string, code: string, name: string, url: string}> */
    private function units(string $term): array
    {
        return Unit::query()
            ->where(fn (Builder $q) => $q->where('code', 'like', $term)->orWhere('name', 'like', $term))
            ->orderBy('code')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Unit $u): array => [
                'id' => (string) $u->getKey(),
                'code' => (string) $u->code,
                'name' => (string) ($u->name ?? $u->code),
                'url' => route('app.units'),   // the operator has no per-unit detail screen; land on the units board
            ])
            ->all();
    }

    /** @return list<array{id: string, name: string, url: string}> */
    private function owners(string $term): array
    {
        return Owner::query()
            ->where('name', 'like', $term)
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Owner $o): array => [
                'id' => (string) $o->getKey(),
                'name' => (string) $o->name,
                'url' => route('app.owners.show', $o->getKey()),
            ])
            ->all();
    }

    private function guestName(?Guest $guest): string
    {
        if ($guest === null) {
            return 'Guest';
        }

        $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));

        return $name !== '' ? $name : 'Guest';
    }
}

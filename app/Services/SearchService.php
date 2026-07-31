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
     * @param  array<string, mixed>  $filters  source / status / from / to / occupancy / sort (task #102)
     * @return array{reservations: list<array{id: string, reference: string, guest: string, status: string, source: string, url: string}>, guests: list<array{id: string, name: string, url: string}>, units: list<array{id: string, code: string, name: string, url: string}>, owners: list<array{id: string, name: string, url: string}>, counts: array{reservations: int, guests: int, units: int, owners: int, all: int}}
     */
    public function search(string $query, array $can, array $filters = []): array
    {
        $term = '%'.addcslashes($query, '%_\\').'%';

        // The reservation query carries the filters; the others take the term only (they have no dates/status).
        $resQuery = fn (): Builder => $this->applyFilters($this->reservationMatch($term, $query), $filters);
        $guestQuery = fn (): Builder => $this->guestMatch($term, $query);
        $unitQuery = fn (): Builder => Unit::query()->where(fn (Builder $q) => $q->where('code', 'like', $term)->orWhere('name', 'like', $term));
        $ownerQuery = fn (): Builder => Owner::query()->where('name', 'like', $term);

        $counts = [
            'reservations' => (clone $resQuery())->count(),
            'guests' => $can['guests'] ? (clone $guestQuery())->count() : 0,
            'units' => $can['units'] ? (clone $unitQuery())->count() : 0,
            'owners' => (clone $ownerQuery())->count(),
        ];

        return [
            'reservations' => $this->reservations($resQuery(), (string) ($filters['sort'] ?? 'recent')),
            'guests' => $can['guests'] ? $this->guests($guestQuery()) : [],
            'units' => $can['units'] ? $this->units($unitQuery()) : [],
            'owners' => $this->owners($ownerQuery()),
            'counts' => $counts + ['all' => array_sum($counts)],
        ];
    }

    /** The canonical filter option sets — reused by the blade's filter chips (anti-drift with the query). */
    public const SOURCES = ['direct', 'ota', 'walk_in', 'corporate', 'group', 'agent'];

    public const STATUSES = ['confirmed', 'checked_in', 'checked_out', 'tentative', 'enquiry', 'no_show', 'cancelled'];

    /**
     * Apply the real reservation filters (task #102) — source / status (canonical sets), a check-in date range,
     * and an occupancy band (adults+children). Unknown values are ignored, never fabricated.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        $source = (string) ($filters['source'] ?? '');
        if (in_array($source, self::SOURCES, true)) {
            $query->where('source', $source);
        }

        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        foreach (['from' => '>=', 'to' => '<='] as $key => $op) {
            $date = (string) ($filters[$key] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
                $query->whereDate('check_in', $op, $date);
            }
        }

        $occupancy = (string) ($filters['occupancy'] ?? '');
        if ($occupancy === '1' || $occupancy === '2') {
            $query->whereRaw('(COALESCE(adults,0) + COALESCE(children,0)) = ?', [(int) $occupancy]);
        } elseif ($occupancy === '3plus') {
            $query->whereRaw('(COALESCE(adults,0) + COALESCE(children,0)) >= 3');
        }

        return $query;
    }

    private function reservationMatch(string $term, string $query): Builder
    {
        return Reservation::query()
            ->with('leadGuest')
            ->where(function (Builder $q) use ($term): void {
                $q->where('reference', 'like', $term)
                    ->orWhereHas('leadGuest', fn (Builder $g) => $g->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term]));
            });
    }

    /**
     * Guest match — NAME (plaintext) OR, when the query is an email, the deterministic BLIND INDEX `email_hash`
     * ({@see Guest::emailHash()}) EXACT-match (the ciphertext isn't `LIKE`-searchable). Phone has no index.
     */
    private function guestMatch(string $term, string $query): Builder
    {
        $query = trim($query);

        return Guest::query()->where(function (Builder $q) use ($term, $query): void {
            $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term]);
            if (str_contains($query, '@')) {
                $q->orWhere('email_hash', Guest::emailHash($query));   // exact-match blind index (#28)
            }
        });
    }

    /** Does the query look like an email/phone the operator might expect us to match — but can't (encrypted)? */
    public function looksLikeContact(string $query): bool
    {
        $query = trim($query);

        return str_contains($query, '@') || preg_match('/^\+?[\d\s()\-]{6,}$/', $query) === 1;
    }

    /**
     * @param  Builder<Reservation>  $query
     * @return list<array{id: string, reference: string, guest: string, status: string, source: string, url: string}>
     */
    private function reservations(Builder $query, string $sort): array
    {
        $query = match ($sort) {
            'checkin' => $query->orderBy('check_in'),
            'reference' => $query->orderBy('reference'),
            default => $query->latest('created_at'),
        };

        return $query
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

    /**
     * @param  Builder<Guest>  $query
     * @return list<array{id: string, name: string, url: string}>
     */
    private function guests(Builder $query): array
    {
        return $query
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

    /**
     * @param  Builder<Unit>  $query
     * @return list<array{id: string, code: string, name: string, url: string}>
     */
    private function units(Builder $query): array
    {
        return $query
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

    /**
     * @param  Builder<Owner>  $query
     * @return list<array{id: string, name: string, url: string}>
     */
    private function owners(Builder $query): array
    {
        return $query
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

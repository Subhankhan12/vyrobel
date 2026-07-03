<?php

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\FolioService;
use App\Support\Money\Money;

// Shared owner-accounting test helpers (Phase 3 · Module 1). In a Support file (required from
// Pest.php) so any owner test can reuse them without depending on cross-file function-load order —
// same discipline as channel_helpers.php.

if (! function_exists('ownUnit')) {
    /** Assign a unit to a fresh owner and give that owner a governing agreement covering the stay. */
    function ownUnit(Unit $unit, array $agreement = []): Owner
    {
        $owner = Owner::factory()->create(['payout_currency' => 'EUR']);
        $unit->update(['owner_id' => $owner->id]);

        ManagementAgreement::factory()->create(array_merge([
            'owner_id' => $owner->id,
            'unit_id' => null,
            'commission_pct' => 15.00,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ], $agreement));

        return $owner;
    }
}

if (! function_exists('postRoom')) {
    /** Open a folio and post the lump room charge (how BookingEngineService posts room revenue). */
    function postRoom(Reservation $reservation, int $minor): void
    {
        $folio = app(FolioService::class)->openFolio($reservation);
        app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of($minor, 'EUR'));
    }
}

if (! function_exists('ownerNet')) {
    /** Sum signed minor across every ledger line for an owner (credits +, debits −, reversals included). */
    function ownerNet(string $ownerId): int
    {
        return OwnerLedgerEntry::where('owner_id', $ownerId)->get()
            ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);
    }
}

if (! function_exists('lineFor')) {
    /** The single live (non-reversed) ledger line of a given entry_type for an owner. */
    function lineFor(string $ownerId, string $entryType): ?OwnerLedgerEntry
    {
        return OwnerLedgerEntry::where('owner_id', $ownerId)
            ->where('entry_type', $entryType)->whereNull('reverses_id')->first();
    }
}

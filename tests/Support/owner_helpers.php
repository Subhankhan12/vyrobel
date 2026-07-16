<?php

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Testing\TestResponse;

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

if (! function_exists('assertNoRawBlade')) {
    /**
     * Assert no unrendered Blade directive leaked into the response body.
     *
     * A directive whose `@` follows a word character is NOT compiled (Blade guards with `\B`, so an
     * email address renders literally) — e.g. `expenses@if ($x) …@endif` ships the raw source to the
     * user. That renders as ordinary text, so an assertSee/assertDontSee suite passes straight over
     * it; only scanning the body catches it. Found in the browser on the owner Expenses screen
     * (Wave 1 · Sub-gate 1) — this locks the whole class of bug for every owner screen.
     */
    function assertNoRawBlade(TestResponse $response): void
    {
        $body = $response->getContent();

        foreach (['@if', '@endif', '@else', '@elseif', '@foreach', '@endforeach', '@forelse', '@empty', '@unless', '@isset', '@php', '@endphp'] as $directive) {
            expect($body)->not->toContain($directive, "Unrendered Blade directive {$directive} leaked into the rendered page");
        }
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

<?php

use App\Models\Guest;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use App\Support\Pdf\OwnerBookingSummaryDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 2 — branded owner BOOKING SUMMARY PDF: the real bookings behind ONE finalized
// statement's gross. Real owner_ledger figures; reconciles to statement.gross_minor to the cent; NO
// occupancy, NO guest names, NO OTA brands; OWNER-scoped download (cross-owner 404).

/**
 * A linked owner with one booking per owned unit, all folded into one finalized statement.
 * Mirrors the `payoutOwner` fixture idiom (seed → own → book → attribute → finalize).
 *
 * @param  list<int>  $unitIndexes  which of the scenario's units this owner owns
 * @param  list<int>  $roomMinors  the room charge posted for each of those units' bookings
 */
function bookingSummaryOwner(array $s, array $unitIndexes, array $roomMinors, string $first = 'G', string $last = 'One'): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    // A distinctively-named lead guest — so the PII assertion below cannot pass by accident.
    $s['guest'] = Guest::create(['first_name' => $first, 'last_name' => $last]);

    $units = [];
    $reservations = [];

    foreach ($unitIndexes as $i => $unitIndex) {
        $unit = $s['units'][$unitIndex];
        $unit->update(['owner_id' => $owner->id]);
        $units[] = $unit;

        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, $roomMinors[$i]);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        $reservations[] = $reservation;
    }

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return compact('user', 'owner', 'units', 'reservations', 'statement');
}

// ── The owner downloads their booking summary (application/pdf, filename, real %PDF bytes) ───────
it('streams a real PDF to the owner', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000]);

    $response = $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}/bookings");

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($response->headers->get('content-disposition'))->toContain('vyrobel-bookings-')
        ->and(str_starts_with((string) $response->getContent(), '%PDF'))->toBeTrue();
});

// ── THE HEADLINE: the rendered bookings reconcile to statement.gross_minor, to the cent ──────────
it('reconciles to the cent — the rendered bookings sum to the statement gross', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0, 1], [20000, 30000]);   // gross €500.00
    $statement = $a['statement'];

    // The engine's own gross: Σ signed room_revenue claimed by this statement (integer minor, from the DB).
    $grossFromLedger = OwnerLedgerEntry::query()
        ->where('statement_id', $statement->id)
        ->where('entry_type', 'room_revenue')
        ->get()
        ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);

    expect($grossFromLedger)->toBe(50000)
        ->and((int) $statement->gross_minor)->toBe(50000);

    $data = OwnerBookingSummaryDocument::data($statement);

    // Σ (the booking rows the document renders) === statement.gross_minor
    expect(array_sum(array_column($data['bookings'], 'net_minor')))->toBe((int) $statement->gross_minor)
        // Σ per-unit revenue === the same total
        ->and(array_sum(array_column($data['by_unit'], 'revenue_minor')))->toBe((int) $statement->gross_minor)
        // Σ per-source revenue === the same total
        ->and(array_sum(array_column($data['by_source'], 'revenue_minor')))->toBe((int) $statement->gross_minor)
        // ...and the formatted total is byte-identical to the shared formatter (so it matches the screen)
        ->and($data['total_display'])->toBe(MoneyFormatter::display((int) $statement->gross_minor, (string) $statement->source_currency))
        ->and($data['gross_display'])->toBe('€500.00');

    // The KPIs are real folds, not estimates: 2 bookings × 2 real nights each.
    expect($data['bookings_count'])->toBe(2)
        ->and($data['nights_total'])->toBe(4);

    // The formatted total actually appears in the rendered document.
    expect(view('pdfs.owner-booking-summary', $data)->render())->toContain('€500.00');
});

// ── A two-unit owner: both units render, and their revenues sum to the gross ─────────────────────
it('breaks a two-unit owner down per unit, reconciling to the gross', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0, 1], [20000, 30000]);

    $data = OwnerBookingSummaryDocument::data($a['statement']);
    $html = view('pdfs.owner-booking-summary', $data)->render();

    // Exact per-unit rows (seedScenario codes its units U1, U2) — the real attributed revenue each.
    expect(array_column($data['by_unit'], 'unit_code'))->toBe(['U1', 'U2'])
        ->and(array_column($data['by_unit'], 'revenue_minor'))->toBe([20000, 30000])
        ->and(array_sum(array_column($data['by_unit'], 'revenue_minor')))->toBe((int) $a['statement']->gross_minor)
        ->and(array_column($data['by_unit'], 'nights'))->toBe([2, 2]);

    // Both units' rows are rendered, with their real per-unit figures.
    expect($html)->toContain('€200.00')->toContain('€300.00');
});

// ── HONESTY: real reference + source; NO guest PII, NO occupancy, NO OTA brand, no truncation ────
it('renders the real reference and source, and never a guest name, occupancy or an OTA brand', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000], first: 'Zephyrina', last: 'Quillfeather');

    $data = OwnerBookingSummaryDocument::data($a['statement']);
    $html = view('pdfs.owner-booking-summary', $data)->render();

    // NOTE: asserted on the rendered HTML rather than the compressed PDF byte stream — dompdf deflates
    // its text objects, so a `not->toContain` over raw bytes would pass vacuously. This is the same
    // approach OwnerStatementPdfTest uses, and is the stronger assertion.
    expect($html)
        // REAL — the human booking code the engine generated, and the real `source` enum's label
        ->toContain((string) $a['reservations'][0]->reference)
        ->and($html)->toContain('Direct')
        ->and($html)->toContain('€200.00')
        // PII — owner surfaces show NO guest names (locked precedent)
        ->and($html)->not->toContain('Zephyrina')
        ->and($html)->not->toContain('Quillfeather')
        // OMITTED — occupancy is not an attributed value; the only real read is room-type-wide and
        // would leak non-owner units' numbers, so it appears nowhere.
        ->and(strtolower($html))->not->toContain('occupancy')
        // OMITTED — the OTA BRAND IS NOT MODELLED (source is a 7-value enum; provider only has 'channex')
        ->and($html)->not->toContain('Booking.com')
        ->and($html)->not->toContain('Airbnb')
        // OMITTED — the design's fabricated reference formats and its truncation fiction
        ->and($html)->not->toContain('BS-2025-09')
        ->and($html)->not->toContain('OS-2025-09')
        ->and(strtolower($html))->not->toContain('showing 8 of 18')
        // the document lists EVERY row, so it never claims to be showing a subset
        ->and(strtolower($html))->not->toContain('showing');

    // The document loads no guest field at all — the data array carries no guest anywhere.
    $json = (string) json_encode($data);
    expect($json)->not->toContain('Zephyrina')
        ->and($json)->not->toContain('Quillfeather');
});

// ── PDF-SAFE: dompdf renders HTML4/CSS2.1 — bundled Unicode font, no frosted glass ───────────────
it('renders a PDF-safe document', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000]);

    $html = view('pdfs.owner-booking-summary', OwnerBookingSummaryDocument::data($a['statement']))->render();

    expect($html)->toContain('DejaVu Sans')          // dompdf's bundled Unicode font — renders € and U+2212
        ->and($html)->not->toContain('backdrop-filter')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex');
});

// ── OWNER ISOLATION (the headline): owner B's statement id → 404 for owner A ─────────────────────
it('404s a cross-owner booking summary', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000]);
    $b = bookingSummaryOwner($s, [1], [30000]);

    // Owner B asking for owner A's booking summary → 404 (owner-scoped, no leak).
    $this->actingAs($b['user'])->get("/owner/statements/{$a['statement']->id}/bookings")->assertNotFound();
    // ...and the reverse.
    $this->actingAs($a['user'])->get("/owner/statements/{$b['statement']->id}/bookings")->assertNotFound();
    // Owner A can download their own.
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}/bookings")->assertOk();
});

// ── FAIL-CLOSED: an authenticated member with no linked Owner can download nothing ───────────────
it('fails closed for a non-linked member', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000]);

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get("/owner/statements/{$a['statement']->id}/bookings")->assertNotFound();
});

<?php

use App\Models\Guest;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Money\MoneyFormatter;
use App\Support\Pdf\OwnerBookingSummaryDocument;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 2 → GATE 2 — branded owner BOOKING SUMMARY PDF: the real bookings behind ONE finalized
// statement's gross. Real owner_ledger figures; reconciles to statement.gross_minor to the cent. GATE 2 added
// REAL occupancy (owner-unit-scoped), the guest NAME (name-only, #28 — never contact), and the real per-channel
// commission (ota_cost); the OTA BRAND stays refused (not modelled). OWNER-scoped download (cross-owner 404).

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

    // A distinctively-named lead guest WITH contact — so the name-only assertion (name shown, contact never)
    // cannot pass by accident.
    $s['guest'] = Guest::create([
        'first_name' => $first, 'last_name' => $last,
        'email' => 'pdf.guest@icloud.test', 'phone' => '+351911223344',
    ]);

    $units = [];
    $reservations = [];

    foreach ($unitIndexes as $i => $unitIndex) {
        $unit = $s['units'][$unitIndex];
        $unit->update(['owner_id' => $owner->id]);
        $units[] = $unit;

        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, $roomMinors[$i]);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
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

// ── HONESTY (GATE 2): real ref + source; guest NAME-only (never contact); OTA brand still refused ─
it('renders the guest name-only and never contact, and never an OTA brand or a truncation fiction', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000], first: 'Zephyrina', last: 'Quillfeather');

    $data = OwnerBookingSummaryDocument::data($a['statement']);
    $html = view('pdfs.owner-booking-summary', $data)->render();

    // NOTE: asserted on the rendered HTML rather than the compressed PDF byte stream — dompdf deflates its text
    // objects, so a `not->toContain` over raw bytes would pass vacuously. Same approach as OwnerStatementPdfTest.
    expect($html)
        // REAL — the human booking code the engine generated, the real `source` enum's label, the real revenue
        ->toContain((string) $a['reservations'][0]->reference)
        ->and($html)->toContain('Direct')
        ->and($html)->toContain('€200.00')
        // NEW REAL — the guest NAME (name-only, #28), the same name the owner Bookings page shows
        ->and($html)->toContain('Zephyrina Quillfeather')
        // CONTACT — NEVER, on any owner surface (#28)
        ->and($html)->not->toContain('pdf.guest@icloud.test')
        ->and($html)->not->toContain('+351911223344')
        // STILL REFUSED — the OTA BRAND IS NOT MODELLED (source is a 7-value enum; provider only has 'channex')
        ->and($html)->not->toContain('Booking.com')
        ->and($html)->not->toContain('Airbnb')
        // STILL REFUSED — the design's fabricated reference formats and its truncation fiction
        ->and($html)->not->toContain('BS-2025-09')
        ->and($html)->not->toContain('OS-2025-09')
        ->and(strtolower($html))->not->toContain('showing 8 of 18')
        // the document lists EVERY row, so it never claims to be showing a subset
        ->and(strtolower($html))->not->toContain('showing');

    // The data array carries the guest NAME (name-only) but loads NO contact field anywhere.
    $json = (string) json_encode($data);
    expect($json)->toContain('Zephyrina Quillfeather')
        ->and($json)->not->toContain('pdf.guest@icloud.test')
        ->and($json)->not->toContain('+351911223344');
});

// ── GATE 2: occupancy is a REAL owner-unit-scoped read; the channel commission is the real ota_cost ─
it('shows a real owner-scoped occupancy and a real channel commission, never a fabricated figure', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000]);   // 1 owned unit, 1 booking

    $data = OwnerBookingSummaryDocument::data($a['statement']);

    // Occupancy is the SAME real PerformanceMetrics read the rest of the app uses, owner-UNIT-scoped (keyed by
    // physical unit id, never room-type-wide, never a fabricated 78%). Headline == the single unit's value.
    $expected = PerformanceMetrics::forOwnerUnits(
        [(string) $a['units'][0]->id],
        CarbonImmutable::parse('2026-09-01'),
        CarbonImmutable::parse('2026-10-01'),
        'EUR',
    )->occupancyPct();

    expect($data['occupancy_pct'])->toBe($expected)
        ->and($data['by_unit'][0]['occupancy_pct'])->toBe($expected)
        ->and($data['occupancy_pct'])->toBeGreaterThanOrEqual(0)
        ->and($data['occupancy_pct'])->toBeLessThanOrEqual(100);

    // The channel commission is REAL — a direct stay bears no channel cost → honest "no commission", not €0-faked.
    expect($data['by_source'][0]['commission_minor'])->toBe(0)
        ->and($data['by_source'][0]['commission_note'])->toBe('no commission');

    // Both render.
    $html = view('pdfs.owner-booking-summary', $data)->render();
    expect(strtolower($html))->toContain('occupancy')
        ->and($html)->toContain('no commission');
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

// ── The document's ink stays post-AA — a booking summary is read on WHITE PAPER ───────────────────
// Owner-20 measured this document's OWN content stream and found the same pre-AA pair the statement
// carried: #8A8079 (3.86 on white) across 25 text runs — every section heading, table header, sublabel
// and the letterhead — and #A69A92 (2.74) the footer. Retargeted to the values the statement document
// already uses, so the two owner PDFs read alike: #5F5853 (6.98) and #6C645F (5.79), measured on the
// regenerated PDF. Nothing guarded the statement's identical fix for a full pass, which is why this
// exists. Blade comments are stripped at compile time but CSS comments are NOT, so asserting the
// RENDERED document is what catches a literal smuggled back in inside a comment.
it('keeps the booking summary document free of the pre-AA greys', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000]);

    $html = view('pdfs.owner-booking-summary', OwnerBookingSummaryDocument::data($a['statement']))->render();

    expect($html)->not->toContain('#8A8079')
        ->and($html)->not->toContain('#A69A92');
});

// ── The footer must not spill onto a blank second sheet ──────────────────────────────────────────
// Owner-20 counted the GENERATED pdf's pages and found the real demo summary printing on TWO sheets,
// the second carrying nothing but the footer row: dompdf applies its own default page margin ON TOP
// of .wrap's padding-bottom, so the footer overflowed the content box by a few points. Invisible to
// any html-level check, because the html is perfectly valid.
//
// ⚠️ This asserts the RULE, not the page count. A page-count assertion was written first and REJECTED:
// it passed with the bug reintroduced, because this file's fixture caps at two bookings (a third
// collides on availability) and two rows fit either way. A guard whose fixture cannot reach the
// failure is not a guard — the same trap as the #28 DontSee guard repaired in Owner-16.
it('keeps the booking summary footer inside the printable page', function () {
    $s = seedScenario(2);
    $a = bookingSummaryOwner($s, [0], [20000]);

    $html = view('pdfs.owner-booking-summary', OwnerBookingSummaryDocument::data($a['statement']))->render();

    expect($html)->toContain('.wrap { padding: 36px 40px 0; }');
});

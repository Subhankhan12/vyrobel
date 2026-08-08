<?php

use App\Models\Organization;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Pdf\GuestInvoiceDocument;
use App\Support\Pdf\OperatorLetterhead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * A TAX DOCUMENT MUST CARRY ITS SUPPLIER'S STATUTORY IDENTIFIERS (audit 2026-08-07 · §5 parity).
 *
 * The guest VAT invoice printed only the organization's TRADING name. It carried no registered legal
 * name and no supplier VAT registration number — although both are real, stored, operator-editable
 * values (`organizations.billing_company` / `billing_vat_id`, edited on /app/settings) that the FIVE
 * owner PDFs had been printing all along, and which the demo org has had seeded the whole time. The
 * data was there; the invoice just never rendered it.
 *
 * ⚠️ AND THE FIX MUST NOT INVENT (#39). The far worse failure mode is a document that prints a
 * placeholder or inferred VAT number: a fabricated tax identifier is materially more dangerous than an
 * absent one. So the unset case is pinned just as hard as the set case.
 *
 * ⚠️ ONE RULE, SIX DOCUMENTS. The rule now lives in OperatorLetterhead because five hand-rolled copies
 * had ALREADY drifted (the booking-summary resolved the ISO country to a full name; the others printed
 * the code). A guest's invoice and an owner's remittance naming different suppliers is exactly the
 * drift this codebase forbids, so the shared identity is asserted across surfaces below.
 */

/** A real booking with a real folio, through the certified services — never a hand-built row. */
function invoiceScenario(): array
{
    $s = seedScenario(1);
    $org = $s['org'];

    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05',
            'check_out' => '2026-09-08', 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => 30000, 'currency' => 'EUR']],
    );

    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));

    return [$org, $reservation];
}

it('prints the registered legal name and supplier VAT number when the operator has set them', function () {
    [$org, $reservation] = invoiceScenario();

    $org->forceFill([
        'billing_company' => 'Seaside Hospitality Unipessoal, Lda',
        'billing_vat_id' => 'PT 512 034 776',
        'billing_city' => 'Lisboa',
    ])->save();

    $data = GuestInvoiceDocument::data($reservation->fresh());

    expect($data['seller'])->toBe('Seaside Hospitality Unipessoal, Lda')
        ->and($data['seller_is_registered'])->toBeTrue()
        ->and($data['seller_vat_id'])->toBe('PT 512 034 776')
        ->and($data['seller_city'])->toBe('Lisboa')
        ->and($data['doc_title'])->toBe('VAT invoice');

    // …and they reach the rendered document, not merely the view data.
    $html = view('pdfs.guest-vat-invoice', $data)->render();
    expect($html)
        ->toContain('Seaside Hospitality Unipessoal, Lda')
        ->toContain('VAT reg. no.')
        ->toContain('PT 512 034 776');
});

it('OMITS what is unset rather than inventing it — no placeholder VAT number', function () {
    [$org, $reservation] = invoiceScenario();

    $org->forceFill(['billing_company' => null, 'billing_vat_id' => null, 'billing_city' => null])->save();

    $data = GuestInvoiceDocument::data($reservation->fresh());
    $html = view('pdfs.guest-vat-invoice', $data)->render();

    // Falls back to the trading name — but never CLAIMS it is the registered one.
    expect($data['seller_vat_id'])->toBeNull()
        ->and($data['seller_is_registered'])->toBeFalse()
        ->and($data['seller'])->not->toBe('');

    expect($html)
        ->not->toContain('VAT reg. no.')
        ->not->toContain('Registered legal name');

    // ⚠️ The document stops calling itself a VAT invoice: with no registration AND no tax line, that
    // claim is false. The title follows the data — never the other way round.
    expect($data['doc_title'])->toBe('Invoice')
        ->and($data['is_vat_document'])->toBeFalse();
});

it('names the SAME supplier on the guest invoice as on the owner documents', function () {
    [$org, $reservation] = invoiceScenario();

    $org->forceFill(['billing_company' => 'Seaside Hospitality Unipessoal, Lda', 'billing_vat_id' => 'PT999'])->save();
    $org->refresh();

    // The one shared definition — what every document reads.
    expect(GuestInvoiceDocument::data($reservation->fresh())['seller'])
        ->toBe(OperatorLetterhead::registeredName($org));

    // The owner-facing one-liner carries the same VAT number, so the two surfaces cannot disagree.
    expect(OperatorLetterhead::line($org))->toContain('VAT PT999');
});

it('treats a blank string as unset, not as a value worth printing', function () {
    [$org, $reservation] = invoiceScenario();

    // A cleared form field arrives as '' — which must behave exactly like NULL, or the invoice prints
    // an empty "VAT reg. no." row.
    $org->forceFill(['billing_company' => '   ', 'billing_vat_id' => ''])->save();

    $data = GuestInvoiceDocument::data($reservation->fresh());

    expect($data['seller_vat_id'])->toBeNull()
        ->and($data['seller_is_registered'])->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Models\FolioItem;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// D-I18N-STORED-RECORDS · the folio lines (2026-11-11, his go). A folio line the SYSTEM wrote — "Room · Double Room", "Room (R-26-0042)", "Group
// discount (10%)", "Wallet credit applied" — stayed English in every language. The row is a record and is never rewritten: it is SAID in the reader's
// language on the screens (the operator's folio, the guest's reservation) and the operator's folio PDF, which mirror each other. A line a person
// named (an add-on, a tax, a fee, a charge) matches none of the sentences and shows as typed. The guest's tax invoice is a legal printout and prints
// what was recorded.

function fls_item(string $description): FolioItem
{
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);

    return $folios->postCharge($folio, 'room', $description, new Money(10000, 'EUR'));
}

it('⚠️ says a line the system wrote in the reader\'s language, with what was filled in carried over — and an English reader sees exactly what is stored', function () {
    $room = fls_item('Room · Double Room');
    $ref = fls_item('Room (R-26-0042)');
    $group = fls_item('Group discount (10%)');
    $master = fls_item('Room · Double Room · R-26-0042');
    $wallet = fls_item('Wallet credit applied');

    app()->setLocale('en');
    expect($room->label())->toBe('Room · Double Room')->and($group->label())->toBe('Group discount (10%)')->and($wallet->label())->toBe('Wallet credit applied');

    app()->setLocale('de');
    try {
        expect($room->label())->toBe('Zimmer · Double Room')
            ->and($ref->label())->toBe('Zimmer (R-26-0042)')
            ->and($master->label())->toBe('Zimmer · Double Room · R-26-0042')
            ->and($group->label())->toBe("Gruppenrabatt (10\u{00A0}%)")
            ->and($wallet->label())->toBe('Guthaben angerechnet');
    } finally {
        app()->setLocale('en');
    }
});

it('⚠️ a line a PERSON named matches none of the sentences and shows exactly as typed, in every language', function () {
    foreach (['Late checkout fee', 'Minibar — 2 water', 'Room service', 'Room', 'Room ·', 'Groupe discount (10%)', 'Wallet credit'] as $typed) {
        $item = fls_item($typed);
        foreach (['en', 'de', 'ar'] as $locale) {
            app()->setLocale($locale);
            expect($item->label())->toBe($typed, "{$typed} in {$locale}");
        }
    }
    app()->setLocale('en');
});

it('the operator\'s folio screen says them in the signed-in person\'s language', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room · Double Room', new Money(10000, 'EUR'));
    $folios->postCharge($folio, 'fee', 'Late checkout fee', new Money(2500, 'EUR'));
    $user = operatorUser($s, ['locale' => 'de'], 'Group Admin');

    $html = test()->actingAs($user)->get(route('app.reservations.show', $reservation->id))->assertOk()->getContent();

    expect($html)->toContain('Zimmer · Double Room')->not->toContain('Room · Double Room')
        ->and($html)->toContain('Late checkout fee');   // typed by a person: shown as typed
});

it('⚠️ the guest\'s TAX INVOICE is a legal printout: it never goes through the said-in-your-language label', function () {
    // pinned in the source, not by a render: the invoice builder must keep reading the stored description
    $invoice = (string) file_get_contents(app_path('Support/Pdf/GuestInvoiceDocument.php'));
    expect($invoice)->toContain("'desc' => (string) \$item->description,")->not->toContain('->label()');

    // the operator's folio PDF mirrors the screen, so it says them like the screen does
    $folioPdf = (string) file_get_contents(app_path('Support/Pdf/FolioDocument.php'));
    expect($folioPdf)->toContain("'desc' => \$item->label(),");
});

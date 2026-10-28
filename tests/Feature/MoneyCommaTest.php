<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Services\FolioService;
use App\Support\I18n\LocalNumber;
use App\Support\Money\Money;
use App\Support\Money\MoneyInput;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/** A back-office user who may record and refund payments (Group Admin holds payments.record + payments.refund). */
function mcMember(array $s): User
{
    $user = User::factory()->create(['name' => 'Comma Tester']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $s['org'], 'Group Admin');
}

/** A booking with a folio: room €300 + city tax €30, nothing paid; optionally a paid €300 to refund from. */
function mcFolio(array $s, int $paidMinor = 0): array
{
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));
    $folios->postCharge($folio, 'tax', 'City tax', Money::of(3000, 'EUR'));
    $payment = $paidMinor > 0 ? $folios->recordPayment($folio, Money::of($paidMinor, 'EUR')) : null;

    return ['res' => $res, 'folio' => $folio->refresh(), 'payment' => $payment];
}

// ⚠️⚠️ D-MONEY-COMMA (ruled 2026-10-28: "Go ahead and make sure it real"). 19 of the 27 languages write a decimal comma. The old
// reader dropped every comma as thousands — "150,50" was €15,050.00 on a rate or a payment — and the folio's float cast read
// "150,50" as €150.00 (the cents silently gone). One reader now, LocalNumber, refusing what cannot be told apart.

it('⚠️⚠️ reads what the person meant — on an English page and on a German page', function () {
    $both = [
        '150.50' => '150.50', '150,50' => '150.50', '150' => '150', '1,234.50' => '1234.50', '1.234,50' => '1234.50',
        '1 234,50' => '1234.50', '€150,50' => '150.50', '1.234.567,89' => '1234567.89', '۱۵۰٫۵۰' => '150.50',
        '150.' => null, '1.2345' => null, '1,2,3' => null, 'abc' => null, '' => null, '-5' => null,
    ];
    foreach ($both as $typed => $read) {
        expect(LocalNumber::normalize((string) $typed, 2, 'en'))->toBe($read, "en: {$typed}")
            ->and(LocalNumber::normalize((string) $typed, 2, 'de'))->toBe($read, "de: {$typed}");
    }
    // the ONLY language-dependent reading: one mark followed by exactly three digits
    expect(LocalNumber::normalize('1,234', 2, 'en'))->toBe('1234')    // English thousands
        ->and(LocalNumber::normalize('1.234', 2, 'de'))->toBe('1234')  // German thousands
        ->and(LocalNumber::normalize('1.234', 2, 'en'))->toBeNull()    // an English decimal with 3 places — refused, never guessed
        ->and(LocalNumber::normalize('1,234', 2, 'de'))->toBeNull();   // a German decimal with 3 places — refused
});

it('⚠️⚠️ MoneyInput gives the right minor units — never the old 100× amount', function () {
    app()->setLocale('en');
    expect(MoneyInput::toMinor('150,50'))->toBe(15050)        // was 1505000
        ->and(MoneyInput::toMinor('150.50'))->toBe(15050)
        ->and(MoneyInput::toMinor('1,234.50'))->toBe(123450)
        ->and(MoneyInput::toMinor('1.234,50'))->toBe(123450)
        ->and(MoneyInput::toMinor('0'))->toBeNull()
        ->and(MoneyInput::toMinor('99', 5000))->toBeNull();      // the caller's bound still refuses
});

it('⚠️⚠️ records a payment typed with a decimal comma as exactly that — the folio path that dropped the cents', function () {
    $s = seedScenario(1);
    $f = mcFolio($s);   // charges €330, nothing paid

    $this->actingAs(mcMember($s))
        ->post(route('app.reservations.payment', $f['res']->id), ['amount' => '150,50', 'method' => 'cash', 'idempotency_key' => 'comma-pay'])
        ->assertRedirect();

    // 🔬 before: the float cast read "150,50" as 150 → 15000 recorded, the 50 cents gone without a word
    expect((int) Payment::query()->where('idempotency_key', 'comma-pay')->sole()->amount_minor)->toBe(15050);
    $this->assertDatabaseHas('folios', ['id' => $f['folio']->id, 'paid_minor' => 15050, 'balance_minor' => 17950]);
});

it('⚠️⚠️ reads a German-written amount on a German page, and refuses an amount it cannot tell apart — nothing recorded', function () {
    config(['app.available_locales' => ['en', 'de']]);
    $s = seedScenario(1);
    $f = mcFolio($s);
    $member = mcMember($s);
    $member->forceFill(['locale' => 'de'])->save();

    $this->actingAs($member)
        ->post(route('app.reservations.payment', $f['res']->id), ['amount' => '1,25', 'method' => 'cash', 'idempotency_key' => 'de-pay'])
        ->assertRedirect();
    expect((int) Payment::query()->where('idempotency_key', 'de-pay')->sole()->amount_minor)->toBe(125);

    // "1,234" on a German page: 1.234 (three decimals) or 1234? — refused, and no payment row is written
    $this->actingAs($member)
        ->post(route('app.reservations.payment', $f['res']->id), ['amount' => '1,234', 'method' => 'cash', 'idempotency_key' => 'de-ambiguous'])
        ->assertRedirect();
    expect(Payment::query()->where('idempotency_key', 'de-ambiguous')->exists())->toBeFalse();
});

it('⚠️⚠️ refunds the amount typed with a comma — not €150 for "150,50", and the #27 bound still holds', function () {
    $s = seedScenario(1);
    $f = mcFolio($s, 30000);
    $staff = mcMember($s);
    $post = "/app/reservations/{$f['res']->id}/refunds";
    $refunded = fn (): int => abs((int) Payment::query()->where('type', 'refund')->where('reservation_id', $f['res']->id)->sum('amount_minor'));

    $this->actingAs($staff)->post($post, ['payment' => $f['payment']->id, 'amount' => '100,50'])->assertRedirect();
    expect($refunded())->toBe(10050);   // 🔬 before: (float) "100,50" = 100 → 10000 refunded

    // more than what is left (€199.50) — still refused, whatever the format
    $this->actingAs($staff)->post($post, ['payment' => $f['payment']->id, 'amount' => '250,00'])->assertRedirect();
    expect($refunded())->toBe(10050);
});

it('shows what will be recorded under the payment amount, by the server\'s own rule', function () {
    $s = seedScenario(1);
    $f = mcFolio($s);

    $html = $this->actingAs(mcMember($s))->get(route('app.reservations.show', $f['res']->id))->assertOk()->getContent();

    expect($html)->toContain('window.vyLocalNumber')                   // the one script copy of the reader
        ->toContain('id="vy-mro-amount"')                             // the readout under the payment field
        ->toMatch('~<input class="vy-input" type="text" inputmode="decimal"[^>]*name="amount" id="amount"~');   // text: a comma can be typed
});

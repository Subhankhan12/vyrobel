<?php

declare(strict_types=1);

use App\Models\Payment;
use App\Models\PaymentAuthorization;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\FolioService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

// ⚠️ D-HOLD-STUCK — the BUTTON (found 2026-10-27, narrowed 2026-11-11, built 2026-11-11, his go). A card hold that is still `authorized` at the
// processor had no way out: the delete refused for ever and no screen could release it ("Vyrobel has no button to release one yet"). The
// reservation's payments now carry "Release hold" for an open hold: the hold is cancelled at the processor (the guest's card is freed, NOTHING is
// charged) and the authorization advances `authorized → released`. A hold was never counted as paid, so no total moves. Test-mode only — the fake
// Stripe client in CI; no live key is touched, and no live hold exists for a session to release.

/** a reservation with a room charge and an OPEN hold on its folio */
function chr_world(string $gateway = 'manual'): array
{
    config(['services.payments.gateway' => $gateway]);
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));
    $authorization = $folios->authorizeDeposit($folio, moneyEur(10000), 'pm_card_visa');
    $manager = operatorUser($s, ['name' => 'Gina Admin'], 'Group Admin');   // holds payments.refund (a manager and Front Desk do not)

    return compact('s', 'reservation', 'folio', 'folios', 'authorization', 'manager');
}

function chr_release_url(array $w, ?string $reservationId = null, ?string $authorizationId = null): string
{
    return route('app.reservations.hold-release', [$reservationId ?? $w['reservation']->id, $authorizationId ?? $w['authorization']->id]);
}

it('⚠️⚠️ releases an open hold: the authorization becomes `released`, nothing is charged, and no total moves', function () {
    $w = chr_world();
    $paidBefore = (int) $w['folio']->refresh()->paid_minor;
    $totalBefore = (int) $w['folio']->total_minor;

    $this->actingAs($w['manager'])->post(chr_release_url($w))
        ->assertRedirect()->assertSessionHas('folio_notice');

    expect($w['authorization']->refresh()->status)->toBe('released')
        ->and((int) $w['folio']->refresh()->paid_minor)->toBe($paidBefore)        // a hold was never paid — nothing moved
        ->and((int) $w['folio']->total_minor)->toBe($totalBefore)
        ->and(Payment::query()->where('folio_id', $w['folio']->id)->where('type', 'capture')->count())->toBe(0)   // and nothing was captured
        ->and(DB::table('audit_events')->where('action', 'deposit.released')->count())->toBe(1)
        ->and(DB::table('domain_events')->where('event_type', 'deposit.released')->count())->toBe(1);
});

it('⚠️ a second press records nothing more — a double click or a raced request releases it ONCE', function () {
    $w = chr_world();

    $this->actingAs($w['manager'])->post(chr_release_url($w))->assertRedirect();
    $second = $this->actingAs($w['manager'])->post(chr_release_url($w));
    $second->assertRedirect();

    expect(session('folio_refused'))->toBeTrue()   // the second press is refused in words, not swallowed
        ->and(DB::table('audit_events')->where('action', 'deposit.released')->count())->toBe(1)
        ->and(DB::table('domain_events')->where('event_type', 'deposit.released')->count())->toBe(1)
        ->and($w['authorization']->refresh()->status)->toBe('released');

    // and the service itself refuses a hold that is no longer open — with its own sentence, never a silent no-op
    expect(fn () => $w['folios']->releaseDeposit($w['authorization']->refresh()))->toThrow(RuntimeException::class, 'not open any more');
});

it('⚠️ a captured hold is not releasable (a capture already took the money)', function () {
    $w = chr_world();
    $w['folios']->captureDeposit($w['authorization'], moneyEur(10000));

    $this->actingAs($w['manager'])->post(chr_release_url($w))->assertRedirect();

    expect($w['authorization']->refresh()->status)->toBe('captured')
        ->and(DB::table('audit_events')->where('action', 'deposit.released')->count())->toBe(0);
});

it('⚠️⚠️ the processor is asked to release it — and if it refuses, nothing is written and the hold stays open', function () {
    $w = chr_world('stripe');
    /** @var FakeStripeClient $fake */
    $fake = app(StripeClientInterface::class);
    expect($fake->voids)->toBe([]);

    // a processor that does NOT cancel: the hold must stay `authorized`, and the refusal is said
    $intent = (string) $w['authorization']->payment->gateway_ref;
    $fake->refuseCancel = true;
    $this->actingAs($w['manager'])->post(chr_release_url($w))->assertRedirect();

    expect($w['authorization']->refresh()->status)->toBe('authorized')
        ->and(DB::table('audit_events')->where('action', 'deposit.released')->count())->toBe(0)
        ->and(session('folio_notice'))->toContain('did not release')->and(session('folio_refused'))->toBeTrue();

    // and when it does, the very intent that was held is the one cancelled
    $fake->refuseCancel = false;
    $this->actingAs($w['manager'])->post(chr_release_url($w))->assertRedirect()->assertSessionHas('folio_notice');
    expect($fake->voids)->toContain($intent)->and($w['authorization']->refresh()->status)->toBe('released');
});

it('⚠️ gated like a refund: Front Desk and a visitor are refused, another reservation\'s hold is a 404, and the button follows the permission', function () {
    $w = chr_world();
    $frontDesk = operatorUser($w['s'], ['name' => 'Fred Desk'], 'Front Desk');   // deliberately does NOT hold payments.refund

    $this->actingAs($frontDesk)->post(chr_release_url($w))->assertForbidden();
    expect($w['authorization']->refresh()->status)->toBe('authorized');

    auth()->logout();
    $this->post(chr_release_url($w))->assertRedirect();   // a visitor is sent to sign in
    expect($w['authorization']->refresh()->status)->toBe('authorized');

    // a PERMITTED actor on a different reservation's id cannot release this hold (the 404 must not be a masked 403)
    $other = book($w['s'], ['unit_id' => $w['s']['units'][0]->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-07']);
    $this->actingAs($w['manager'])->post(chr_release_url($w, (string) $other->id))->assertNotFound();
    expect($w['authorization']->refresh()->status)->toBe('authorized');

    // the page offers the button to a permitted role, and to nobody else
    $this->actingAs($w['manager'])->get(route('app.reservations.show', $w['reservation']->id))->assertOk()
        ->assertSee('Release hold')->assertSee(chr_release_url($w), false);
    $this->actingAs($frontDesk)->get(route('app.reservations.show', $w['reservation']->id))->assertOk()->assertDontSee('Release hold');
});

it('after a release the folio says so — a released hold is not offered again and is not shown as an open hold', function () {
    $w = chr_world();
    $this->actingAs($w['manager'])->post(chr_release_url($w))->assertRedirect();

    $html = $this->actingAs($w['manager'])->get(route('app.reservations.show', $w['reservation']->id))->assertOk()->getContent();

    expect($html)->toContain('Released — the card was freed, nothing was charged')
        ->and($html)->not->toContain('Release hold')
        ->and($html)->not->toContain('Pre-authorized');
});

it('cross-tenant: another organisation\'s hold cannot be released through this reservation (tenant scope)', function () {
    $w = chr_world();
    $b = chr_world();   // a second organisation with its own hold
    $this->actingAs($w['manager'])->post(chr_release_url($w, (string) $w['reservation']->id, (string) $b['authorization']->id))->assertNotFound();

    expect(PaymentAuthorization::withoutGlobalScopes()->find($b['authorization']->id)->status)->toBe('authorized');
});

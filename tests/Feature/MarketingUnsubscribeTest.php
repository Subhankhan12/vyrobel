<?php

declare(strict_types=1);

use App\Mail\OperatorGuestMessageMail;
use App\Mail\WinbackMail;
use App\Models\Guest;
use App\Support\Guest\GuestMarketing;
use App\Support\Guest\MarketingUnsubscribe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the wireframe-parity sweep's HIGH-5.
 *
 * Marketing email could be sent with NO way to opt out: no unsubscribe route existed, no template carried a link,
 * and no send set a `List-Unsubscribe` header. The ONLY opt-out was a toggle inside a CLAIMED guest portal
 * account — so a guest who never claimed one (the common case: a guest who booked through an OTA and got a
 * marketing blast) had no route out at all. That is a CAN-SPAM / GDPR / PECR exposure, not a cosmetic gap.
 *
 * The blind spot: {@see GuestMarketing::canReceive()} was already correct and TESTED — the send really did honour
 * `marketing_consent`. Nothing asserted that a guest could ever CHANGE it. A gate is only as good as the guest's
 * ability to reach it.
 *
 * ⚠️ Transactional mail is deliberately outside this: a guest cannot opt out of their own booking confirmation.
 * The last test pins that boundary so a future "add unsubscribe everywhere" change cannot quietly cross it.
 */
function unsubGuest(array $attributes = []): Guest
{
    return Guest::factory()->create(array_merge([
        'marketing_consent' => true,
        'email' => 'ada@example.test',
    ], $attributes));
}

it('lets a guest unsubscribe from a signed link with NO login', function () {
    $guest = unsubGuest();

    // The link as it appears in an email — no session, no guest account, no claim.
    $this->get(MarketingUnsubscribe::url($guest))
        ->assertOk()
        ->assertSee('Unsubscribe from marketing emails');

    $this->post(MarketingUnsubscribe::oneClickUrl($guest))->assertOk();

    expect((bool) $guest->fresh()->marketing_consent)->toBeFalse()
        ->and(GuestMarketing::canReceive($guest->fresh()))->toBeFalse();
});

it('refuses a marketing send to a guest who unsubscribed', function () {
    $guest = unsubGuest();

    expect(GuestMarketing::canReceive($guest))->toBeTrue();   // reachable before

    $this->post(MarketingUnsubscribe::oneClickUrl($guest))->assertOk();

    // The SAME gate the bulk-message send gates on now refuses them.
    expect(GuestMarketing::canReceive($guest->fresh()))->toBeFalse();
});

it('does NOT unsubscribe on a GET — a prefetching scanner must not opt a guest out', function () {
    $guest = unsubGuest();

    $this->get(MarketingUnsubscribe::url($guest))->assertOk();

    expect((bool) $guest->fresh()->marketing_consent)->toBeTrue();
});

it('rejects an unsigned or tampered link', function () {
    $guest = unsubGuest();
    $other = unsubGuest(['email' => 'grace@example.test']);

    $this->get('/unsubscribe/'.$guest->getKey())->assertForbidden();          // no signature at all
    $this->post('/unsubscribe/'.$guest->getKey())->assertForbidden();

    // A signature minted for one guest cannot be pointed at another.
    $signed = MarketingUnsubscribe::oneClickUrl($guest);
    $swapped = str_replace((string) $guest->getKey(), (string) $other->getKey(), $signed);
    $this->post($swapped)->assertForbidden();

    expect((bool) $guest->fresh()->marketing_consent)->toBeTrue()
        ->and((bool) $other->fresh()->marketing_consent)->toBeTrue();
});

it('puts a working unsubscribe link AND List-Unsubscribe headers on a marketing send', function () {
    $guest = unsubGuest();

    $mail = new WinbackMail('Ada', 'Seaside', MarketingUnsubscribe::url($guest), MarketingUnsubscribe::oneClickUrl($guest));

    $html = $mail->render();
    $headers = $mail->headers()->text;

    expect($html)->toContain('Unsubscribe')
        ->and($html)->toContain(URL::signedRoute('marketing.unsubscribe', ['guest' => $guest->getKey()]))
        ->and($headers)->toHaveKey('List-Unsubscribe')
        ->and($headers['List-Unsubscribe-Post'])->toBe('List-Unsubscribe=One-Click');
});

it('keeps transactional mail free of an unsubscribe it cannot honour', function () {
    // The same Mailable used for an operator SERVICE message — sent with no unsubscribe URLs.
    $mail = new OperatorGuestMessageMail('Ada', 'Your key code is ready.', 'Seaside');

    expect($mail->render())->not->toContain('Unsubscribe')
        ->and($mail->headers()->text)->toBe([])
        ->and($mail->render())->toContain('relates to your booking');   // the service-message footer, unchanged
});

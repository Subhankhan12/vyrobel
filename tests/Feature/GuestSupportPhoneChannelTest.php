<?php

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * DO-SUP-1 — THE SUPPORT NUMBER IS A PHONE NUMBER, AND NOTHING CLAIMS OTHERWISE (2026-08-13).
 *
 * ⚠️ THE HONESTY QUESTION. One number is stored. Rendering it as `tel:` is honest — every phone takes a call.
 * Rendering it as `wa.me/…`, or beside a WhatsApp mark, would ASSERT that the number answers on WhatsApp, and
 * nothing in this product verifies that: the operator never said so, and there is no field in which they could.
 * A channel nobody confirmed is a fabricated one (#39, §N.8d).
 *
 * ⚠️ THIS FILE PINS A VERIFIED-FINE STATE — it fixes nothing (§N.16.7). Measured as the guest with the number
 * populated: exactly one link, `tel:+…`, named "Call …", and no `wa.me`/`whatsapp` anywhere in the DOM. What
 * makes it honest is STRUCTURAL rather than careful: `SettingsService::SUPPORT_FIELDS` has no WhatsApp field,
 * so there is nothing to render a verified claim FROM. The guard exists so that a future change cannot
 * manufacture one out of the bare phone number.
 *
 * ⚠️ RENDERED AS THE GUEST. `/guest/help` is a guest-realm surface; a test acting as anyone else gets a
 * redirect, and every "no WhatsApp" assertion below would pass on an empty page (§N.14.1).
 */

/** The guest Help page, with the property's support phone POPULATED so the block actually renders. */
function supportHelpHtml(object $test, array $support = []): string
{
    // A guest with a real stay — the same fixture the sibling support test uses, so this file measures the
    // surface a guest actually reaches rather than a hand-built page.
    $scenario = seedScenario(1);
    book($scenario);

    $guest = $scenario['guest'];
    $guest->update(['email' => 'wa@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    app(SettingsService::class)->updateSupport($scenario['property'], array_merge([
        'support_phone' => '+351 289 000 111',
        'support_hours' => 'Mon–Fri, 9am–6pm',
        'support_reply_norm' => 'same_day',
    ], $support));

    return (string) $test->actingAs($guest->fresh(), 'guest')->get(route('guest.help'))->assertOk()->getContent();
}

it('⚠️ the stored number renders as a TELEPHONE — no WhatsApp channel is claimed', function () {
    $html = supportHelpHtml($this);

    // ⚠️ NON-VACUITY: the number is really on the page, so the absence assertions below are about a rendered
    // support block rather than about a page that never showed one.
    expect($html)->toContain('tel:+351289000111')
        ->and($html)->toContain('+351 289 000 111');

    // The claim that must never appear: this number answers on WhatsApp.
    expect($html)->not->toContain('wa.me')
        ->and($html)->not->toContain('api.whatsapp')
        ->and(stripos($html, 'whatsapp'))->toBeFalse();
});

it('⚠️ there is no WhatsApp FIELD to claim from — the assertion is structurally unavailable', function () {
    // The load-bearing fact. A `wa.me` link would have to be derived from the bare phone, because the operator
    // has nowhere to state that the number is on WhatsApp. Keyed-not-typed in its strongest form: the claim is
    // unrepresentable rather than merely discouraged.
    expect(SettingsService::SUPPORT_FIELDS)->toBe(['support_phone', 'support_reply_norm', 'support_hours'])
        ->and(SettingsService::SUPPORT_FIELDS)->not->toContain('support_whatsapp');
});

it('the phone link says CALL — the action matches the channel', function () {
    $html = supportHelpHtml($this);

    // Measured in the browser as "Call Demo Seaside Property on +351 289 000 111". A name like "Message us on
    // +351…" would assert a messaging channel the `tel:` href cannot deliver.
    expect($html)->toMatch('/aria-label="Call [^"]*\+351 289 000 111"/');
});

it('⚠️ the NEIGHBOURING claims are hedged too — the reply norm is a guide, not a guarantee (N.13.1)', function () {
    $html = supportHelpHtml($this);

    // The risk sits beside the flagged thing. The reply time is keyed to a vetted phrase and hedged with
    // "usually"; a bare "We reply the same day" would be a promise the product cannot keep.
    expect($html)->toContain('usually reply the same day')
        ->and($html)->not->toContain('We reply within')
        ->and($html)->not->toContain('guaranteed');
});

it('with NO phone stored the affordance is absent entirely — not an empty or dead link', function () {
    $html = supportHelpHtml($this, ['support_phone' => null]);

    // ⚠️ Scoped to the ELEMENT, not the class name: `.vy-sup-phone { … }` is a CSS rule in the shell's <style>
    // block and ships on every render regardless of data. Asserting the bare string would have failed against a
    // stylesheet rather than against a rendered link — the shared-layout vacuity trap in its inverted form.
    expect($html)->not->toContain('tel:')
        ->and($html)->not->toContain('<a class="vy-sup-phone"');
});

<?php

use App\Mail\AdminEmailChangeMail;

/*
 * DO-EMAIL-3 (2026-08-12) — the platform-admin email-change mail, brought on-brand.
 *
 * It was the one email outside the warm brand system: a standalone dark slate/indigo document
 * (#0C1322 / #111B2E / #6366F1 / #94A3B8), no brand mark, no footer. It now extends the shared layout like its
 * sibling `owner-email-change`, which does the identical job in the owner realm and was already on-brand.
 *
 * ⚠️ THE POINT OF THIS GUARD IS THE CONTENT, NOT THE BRANDING. An email-address change is the classic
 * account-takeover move, so the sentences the recipient acts on are asserted verbatim. A future restyle may
 * move them; it may not reword, drop or demote them.
 *
 * ⚠️ It also replaces a now-VACUOUS assertion (the O32 lesson). DO-EMAIL-1 fixed this email's CTA by darkening
 * an indigo gradient; DO-EMAIL-3 removed the gradient entirely in favour of the layout's bulletproof solid
 * #C2410C button (white on it = 5.18 — the ratified invite-email decision). The sweep test still forbids the
 * pre-AA gradient string returning, but that assertion now guards an absence that is structural. The real
 * invariant — that this template extends the shared layout — is asserted here instead.
 */

function adminEmailChangeHtml(): string
{
    return (new AdminEmailChangeMail(
        'Alex Admin',
        'new-admin@vyrobel.test',
        'https://vyrobel.test/admin/account/email/verify/signed-token',
    ))->render();
}

it('renders the platform-admin email-change mail inside the SHARED brand layout', function () {
    $source = (string) file_get_contents(resource_path('views/emails/admin-email-change.blade.php'));

    expect($source)->toContain("@extends('emails.layout')");

    $html = adminEmailChangeHtml();

    // The shared layout's own furniture: the warm wash, the 480px card, the brand lockup.
    expect($html)->toContain('#F6EBE2')       // body / footer wash
        ->and($html)->toContain('#FFFDFB')    // the card every @section('content') renders on
        ->and($html)->toContain('Vyrobel')    // the brand lockup the standalone version never had
        // …and none of the dark slate/indigo palette that made it off-brand.
        ->and($html)->not->toContain('#0C1322')
        ->and($html)->not->toContain('#111B2E')
        ->and($html)->not->toContain('#6366F1')
        ->and($html)->not->toContain('#94A3B8')
        ->and($html)->not->toContain('linear-gradient');
});

it('preserves every security sentence verbatim — the wrapper may change, the message may not', function () {
    $html = adminEmailChangeHtml();
    $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))) ?? '');

    expect($text)->toContain('Vyrobel · Platform admin')
        ->and($text)->toContain('Confirm your new email')
        ->and($text)->toContain('Hi Alex Admin,')
        // the address being switched TO — the fact the recipient checks first
        ->and($text)->toContain('new-admin@vyrobel.test')
        // the three load-bearing security claims, each verified against AccountController
        ->and($text)->toContain('your email stays the same until you do')
        ->and($text)->toContain('The link expires in one hour.')
        ->and($text)->toContain("If you didn't request this, you can ignore this email — nothing changes without opening the link.");

    // The CTA points at the verify URL, and it is the ONLY destination in the mail.
    preg_match_all('/href="([^"]+)"/', $html, $m);
    expect(array_values(array_unique($m[1])))->toBe(['https://vyrobel.test/admin/account/email/verify/signed-token']);
});

it('keeps the warning under the CTA, never demoted into the footer', function () {
    $html = adminEmailChangeHtml();

    $cta = strpos($html, 'Confirm new email</a>');
    $warning = strpos($html, e("If you didn't request this"));   // I18N-01: printed through {{ }} (&#039;)
    $footer = strpos($html, 'Powered by Vyrobel');

    expect($cta)->not->toBeFalse()
        ->and($warning)->not->toBeFalse()
        ->and($footer)->not->toBeFalse()
        ->and($warning)->toBeGreaterThan($cta)      // it follows the action it warns about
        ->and($warning)->toBeLessThan($footer);     // and it is in the CARD, not the footer
});

it('does not inherit the layout footer line that would be false for a platform admin', function () {
    $html = adminEmailChangeHtml();

    // The claim this test defends is unchanged: a platform admin has no booking, so that line must not appear.
    expect($html)->not->toContain('it relates to your booking with us');

    // ⚠️ HOW it is avoided changed at DO-EMAIL-9 (2026-08-12). This template used to carry its own hand-written
    // footer sentence via a one-off override slot; the layout now derives the line from a declared category, so
    // the sentence lives in the shared catalogue and this template declares `email_change`.
    //
    // ⚠️ AND THE SPECIFICITY WAS DELIBERATELY KEPT, NOT FLATTENED. The obvious move was to declare `security`
    // and accept the generic "a security change on your account" — but this mail is sent to the address being
    // ADDED, whose reader may not hold an account at all yet, so that line is not safely true of them. The
    // `email_change` category says only what is certain: someone gave us this address. That is the same fact the
    // one-off sentence carried, which is why this assertion still has something specific to check.
    expect($html)->toContain('this address was given as the login email for a Vyrobel account');
});

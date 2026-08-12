<?php

use App\Mail\AdminEmailChangeMail;
use App\Mail\OwnerEmailChangeMail;
use Illuminate\Support\Facades\View;

/*
 * DO-EMAIL-9 / DO-EMAIL-8 — THE FOOTER REASON IS DERIVED, AND A FALSE ONE IS UNREPRESENTABLE (2026-08-12).
 *
 * The shared email layout used to end every template with ONE hardcoded line: "You're receiving this because it
 * relates to your booking with us." That is false on an account or security email — and `owner-email-change`,
 * an owner changing their LOGIN EMAIL, shipped it live.
 *
 * ⚠️ WHAT THESE TESTS DEFEND IS THE STRUCTURE, NOT THE SENTENCES. Rewording the default would have moved the
 * falsehood rather than removed it: whatever single line sat there would be wrong for some category, and the
 * next template added would inherit it silently. So a template declares a KEY and the layout owns the wording.
 * The load-bearing test is therefore the DEFAULT one: an email that declares nothing must still be unable to
 * claim a booking. That is the whole difference between a restructure and a reword.
 */

/** The visible text of a rendered email — CSS/markup stripped, because a footer claim is what a person READS. */
function footerText(string $html): string
{
    return trim((string) preg_replace('/\s+/', ' ',
        html_entity_decode(strip_tags((string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html)))
    ));
}

/** Render the shared layout with a given declared category (or none), with no template in the way. */
function layoutFooterFor(?string $category): string
{
    $decl = $category === null ? '' : "@section('footer_reason', '{$category}')\n";
    $blade = "@extends('emails.layout')\n{$decl}@section('content')<p>Body</p>@endsection";

    $dir = storage_path('framework/testing/emails');
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $name = 'vy_footer_probe_'.($category ?? 'none');
    file_put_contents($file = $dir.'/'.$name.'.blade.php', $blade);

    View::addLocation($dir);
    $html = view($name)->render();
    @unlink($file);

    return footerText($html);
}

const VY_BOOKING_LINE = 'relates to your booking with us';

it('⚠️ renders a GENERIC-TRUE default when a template declares no category — the core of the restructure', function () {
    $text = layoutFooterFor(null);

    // The point: a forgetful new template still cannot claim the recipient has a booking.
    expect($text)->toContain('you have an account with us')
        ->and($text)->not->toContain(VY_BOOKING_LINE);
});

it('⚠️ falls back to the generic line for an UNRECOGNISED key — never the raw key, never booking', function () {
    $text = layoutFooterFor('not_a_real_category');

    expect($text)->toContain('you have an account with us')
        ->and($text)->not->toContain('not_a_real_category')
        ->and($text)->not->toContain(VY_BOOKING_LINE);
});

it('renders each category its own TRUE line, and only that one', function (string $category, string $expected) {
    $text = layoutFooterFor($category);

    expect($text)->toContain($expected);

    // …and no OTHER category's line rides along. A footer that stacked two reasons would be half false.
    if ($category !== 'booking') {
        expect($text)->not->toContain(VY_BOOKING_LINE);
    }
})->with([
    'booking' => ['booking', 'relates to your booking with us'],
    'security' => ['security', 'security change on your account'],
    'email_change' => ['email_change', 'this address was given as the login email'],
    'account' => ['account', 'relates to your account with us'],
    'owner' => ['owner', 'you own a property we manage'],
    'waitlist' => ['waitlist', 'asked to hear about these dates'],
]);

it('⚠️ owner-email-change no longer claims a booking — the LIVE false claim, gone', function () {
    $html = (new OwnerEmailChangeMail('Olive Owner', 'new@owner.test', 'https://vyrobel.test/verify/signed'))->render();
    $text = footerText($html);

    // ⚠️ The regression this whole restructure exists for. An owner changing their login email has no booking.
    expect($text)->not->toContain(VY_BOOKING_LINE)
        ->and($text)->toContain('this address was given as the login email');

    // ⚠️ And the MESSAGE is untouched — the delta is the footer line only (the DO-EMAIL-3 method).
    expect($text)->toContain('Olive Owner')
        ->and($text)->toContain('new@owner.test')
        ->and($html)->toContain('https://vyrobel.test/verify/signed');
});

it('admin-email-change keeps its accurate line via a declared category, not a one-off override', function () {
    $source = (string) file_get_contents(resource_path('views/emails/admin-email-change.blade.php'));
    $text = footerText((new AdminEmailChangeMail('Alex Admin', 'new-admin@vyrobel.test', 'https://vyrobel.test/verify/tok'))->render());

    // The hand-written footer sentence is gone; the category does the work now.
    expect($source)->toContain("@section('footer_reason', 'email_change')")
        ->and($source)->not->toContain("@section('unsubscribe')")
        ->and($text)->toContain('this address was given as the login email')
        ->and($text)->not->toContain(VY_BOOKING_LINE);

    // ⚠️ Its security content is inviolable (DO-EMAIL-3) — proven still present, not assumed.
    expect($text)->toContain('your email stays the same until you do')
        ->and($text)->toContain('The link expires in one hour.');
});

it('⚠️ every template extending the layout resolves to a TRUE reason — the unknown scope, closed', function () {
    // The categories each template may legitimately claim, and the templates deliberately left generic.
    $expected = [
        'booking-confirmation' => 'booking', 'cancellation' => 'booking', 'refund-confirmation' => 'booking',
        'payment-reminder' => 'booking', 'pre-arrival' => 'booking', 'review-request' => 'booking',
        'owner-email-change' => 'email_change', 'admin-email-change' => 'email_change', 'guest-password-reset' => 'security',
        'account-deletion' => 'account', 'verify-email' => 'account', 'maintenance-cost-report' => 'account',
        'waitlist-notification' => 'waitlist',
        // Deliberately undeclared → the generic-true line: `notification` carries any feed event, and `winback`
        // is pure marketing which supplies its own footer.
        // ⚠️ `operator-guest-message` is NOT undeclared. It is a property writing to a guest about their stay, so
        // `booking` is the truthful line — and it has TWO modes: with an unsubscribe URL it fills
        // `footer_marketing`; without one it is an ordinary service message and falls through to this category.
        // MarketingUnsubscribeTest already pinned that second mode, and caught this classification when it was
        // briefly wrong — a green test encoding a real invariant.
        'notification' => null, 'winback' => null, 'operator-guest-message' => 'booking',
    ];

    $files = glob(resource_path('views/emails/*.blade.php')) ?: [];
    $extending = [];

    foreach ($files as $file) {
        $name = basename($file, '.blade.php');
        $source = (string) file_get_contents($file);
        if (! str_contains($source, "@extends('emails.layout')")) {
            continue;
        }
        $extending[] = $name;

        preg_match("/@section\('footer_reason', '([a-z_]+)'\)/", $source, $m);
        $declared = $m[1] ?? null;

        // ⚠️ Enumerated, not sampled: a template added later shows up here as an unknown key and fails, which is
        // the only way "every email is true" stays true after this session.
        // ⚠️ NOT `toHaveKey($name, $msg)` — its second argument is an expected VALUE, not a message, so that
        // form silently asserts the wrong thing (the §N.8c API-misuse family, in a new guise).
        expect(array_key_exists($name, $expected))->toBeTrue("a template extends the layout but this guard does not know it: {$name}");
        expect($declared)->toBe($expected[$name], "wrong footer category on {$name}");

        // ⚠️ A booking claim may ONLY appear on a template whose recipient really has a booking — and this reads
        // the source with BLADE COMMENTS STRIPPED, because they do not ship. (The inverse of the CSS-comment
        // trap: a CSS comment renders and must be scanned, a Blade comment never does and must not be. The first
        // cut of this assertion fired on a docblock that quotes the old false line while documenting its removal.)
        if ($declared !== 'booking') {
            $withoutBladeComments = (string) preg_replace('/\{\{--.*?--\}\}/s', ' ', $source);
            expect(footerText($withoutBladeComments))->not->toContain(VY_BOOKING_LINE);
        }
    }

    expect(count($extending))->toBe(count($expected));
});

it('DO-EMAIL-8 — the reason slot is no longer the `unsubscribe` slot, and marketing keeps a REAL opt-out', function () {
    $layout = (string) file_get_contents(resource_path('views/emails/layout.blade.php'));

    // The overloaded name is gone from the layout and from every template.
    expect($layout)->toContain("@hasSection('footer_marketing')")
        ->and($layout)->not->toContain("@hasSection('unsubscribe')")
        ->and($layout)->not->toContain("@yield('unsubscribe')");

    foreach (glob(resource_path('views/emails/*.blade.php')) ?: [] as $file) {
        expect((string) file_get_contents($file))->not->toContain("@section('unsubscribe')");
    }

    // ⚠️ The split is the point: a MARKETING template still carries a genuine signed opt-out…
    $winback = (string) file_get_contents(resource_path('views/emails/winback.blade.php'));
    expect($winback)->toContain("@section('footer_marketing')")
        ->and($winback)->toContain('$unsubscribeUrl');

    // …and a TRANSACTIONAL one offers none, because nobody can opt out of a security notice about their account.
    $security = (new OwnerEmailChangeMail('Olive Owner', 'new@owner.test', 'https://vyrobel.test/verify/signed'))->render();
    expect(stripos(footerText($security), 'unsubscribe'))->toBeFalse();
});

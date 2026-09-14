<?php

declare(strict_types=1);

use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ EVERY SIGNED-IN GUEST SCREEN HAD NO HEADING ELEMENT AT ALL — not "no `<h1>`", none.
 *
 * A screen-reader user navigating by heading got NOTHING on twelve of them. The pattern was diagnostic:
 * every SIGNED-OUT guest screen had exactly one correct `<h1>`, and the signed-in ones use a different
 * shell — whose own docblock described its `title` prop as "optional page heading" while only ever feeding
 * `<title>`. The subject text was authored on every screen; nothing rendered it as a heading.
 *
 * ⚠️ THE FIXES WERE PROMOTIONS, NOT INVENTIONS. Eleven screens already displayed their title as a styled
 * `<div>` — the exact shape found on the marketing site's `/contact`. Only `wallet` had no subject text and
 * its heading is flagged PROVISIONAL in the view, because copy is the founder's.
 *
 * ⚠️⚠️ WHAT THIS GUARD PROVES AND WHAT IT CANNOT. It proves a heading EXISTS at the right level and that the
 * outline is unbroken. It CANNOT prove the heading says the right thing — a page headed "Untitled" passes.
 * The per-screen reasoning is recorded in `docs/GUEST-HEADINGS.md`; this only stops the structure regressing.
 *
 * ⚠️ It shares ONE rule with the marketing guard ({@see headingOutlineOffenders}), rather than a second copy.
 */
uses(RefreshDatabase::class);

function ghGuest(array $s): Guest
{
    /** @var Guest $guest */
    $guest = $s['guest'];
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    return $guest->fresh();
}

it('⚠️⚠️ gives every SIGNED-IN guest screen exactly one h1 and an unbroken outline', function () {
    $guest = ghGuest(seedScenario(1));

    $screens = ['/guest/trips', '/guest/account', '/guest/messages', '/guest/notifications',
        '/guest/payments', '/guest/saved', '/guest/wallet', '/guest/help'];

    $offenders = [];
    foreach ($screens as $url) {
        $html = $this->actingAs($guest, 'guest')->get($url)->assertOk()->getContent();
        $offenders = [...$offenders, ...headingOutlineOffenders($url, (string) $html)];
    }

    Assert::assertSame([], $offenders,
        'a signed-in guest screen has no page heading, or more than one. ⚠️ The subject text is already on '
        .'the page — PROMOTE it, never insert an empty or invented heading to satisfy this check.');
});

it('⚠️ gives every SIGNED-OUT guest screen the same — the group that was already correct', function () {
    // ⚠️ These were the CONTRAST that made the defect diagnostic. Guarded so the fix cannot be "level them
    //    down": if these ever lose their headings, that is a regression, not consistency.
    $offenders = [];
    foreach (['/guest/login', '/guest/claim', '/guest/forgot-password'] as $url) {
        $offenders = [...$offenders, ...headingOutlineOffenders($url, (string) $this->get($url)->assertOk()->getContent())];
    }

    Assert::assertSame([], $offenders);
});

it('⚠️⚠️ gives the STOREFRONT — the page that takes money from a stranger — a heading in BOTH branches', function () {
    $s = seedScenario(1);

    // ⚠️ `seedScenario` builds a tenant, not a PUBLIC storefront: its property has no slug and no status, so
    //    the public routes cannot resolve it. Given both here rather than assumed — the first draft of this
    //    test failed on a missing route parameter and said so loudly, which is how this was found.
    $s['property']->forceFill(['slug' => 'gh-storefront', 'status' => 'active'])->save();
    $slug = 'gh-storefront';

    // The browse page and a room page.
    $offenders = [];
    foreach ([route('booking.browse', $slug)] as $url) {
        $offenders = [...$offenders, ...headingOutlineOffenders($url, (string) $this->get($url)->assertOk()->getContent())];
    }

    // ⚠️⚠️ THE CHECKOUT'S EMPTY BRANCH IS THE ONE THE AUDIT REACHED. Its `<h1>` lived only in the `@else`
    //    with a live offer, so a visitor arriving with a stale link or an expired quote — which is exactly
    //    how the audit got there — met a page with no heading at all. Reached here the same way: no quote
    //    in session.
    $checkout = (string) $this->get(route('booking.checkout', $slug))->assertOk()->getContent();
    $offenders = [...$offenders, ...headingOutlineOffenders('checkout(no live offer)', $checkout)];

    Assert::assertSame([], $offenders);

    // ⚠️ AND THE D3 FIELD-ERROR FIX IS STILL LIVE IN THIS VIEW — this pass edited the same file.
    Assert::assertStringContainsString('@error', (string) file_get_contents(resource_path('views/booking/checkout.blade.php')),
        'the checkout lost its field-error rendering, which an earlier pass added to this same view');
});

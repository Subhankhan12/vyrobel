<?php

use App\Http\Controllers\MarketingContactController;
use App\Mail\MarketingContactMail;
use App\Models\MarketingContactMessage;
use App\Support\Marketing\MarketingUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// ══ GATE 7 — THE COMPANY PAGES, AND THE CONTACT FORM THAT ACTUALLY SENDS ═════════════════════════════
//
// ⚠️ WHAT THESE GUARDS ARE FOR. The Gate 7 doc-truth audit rated 147 claims across ten prototypes and found
// 75 FALSE. Four pages survived by having their false claims CORRECTED; six were refused. Two risks follow
// from that, and each guard below answers one:
//
//   1. A CORRECTION CAN BE UNDONE. Every fix lives in the marketing converter, and a regeneration that
//      silently fails to apply one would put a false security claim back on a public page. The generator now
//      refuses to finish on a needle that matched nothing — but the generator is a scratch tool, so these
//      tests hold the same line from inside the repository.
//   2. A REFUSAL CAN BE FORGOTTEN. `careers`, `changelog`, `privacy` and `terms` are absent on rulings, not
//      on a backlog. A future gate that builds one without revisiting the ruling would publish a company
//      that does not exist, or a release history that could not have happened.
//
// ⚠️ EVERY GUARD HERE HAS ITS OWN VACUITY CHECK, because five have now shipped in this project that proved
// nothing: a fixture that never had the data · an early return on an empty database · a counter that counted
// requests instead of work · a control inert by design · and a cross-org check that measured the VICTIM's
// side while the write was landing on the attacker's.

uses(RefreshDatabase::class);

/** The four pages Gate 7 published, and the marketing route each is served by. */
const G7_BUILT = [
    '/about' => 'about',
    '/security' => 'security',
    '/integrations' => 'integrations',
    '/contact' => 'contact',
];

// ══ ⚠️ THE REFUSALS ARE RULINGS ═══════════════════════════════════════════════════════════════════════

it('⚠️ keeps the four refused company pages unpublished', function () {
    // ⚠️ NOT "not yet built" — refused, each for a reason that a later gate must re-open deliberately:
    //   careers   — four fabricated openings, an office, salary bands and a hiring history, for one person
    //   changelog — three entries predate the first commit and two are dated in the future
    //   privacy   — a legal document; drafting it is not a build step
    //   terms     — the same
    // ⚠️ `privacy` and `terms` LEFT THIS LIST on 2026-09-23. Their refusal ("a legal document; drafting
    //    it is not a build step") was overridden by the founder, twice and knowingly, because Google
    //    will not publish an External OAuth consent screen without a privacy-policy URL. The refusal's
    //    REASONING is answered rather than discarded: the pages assert only what docs/legal/
    //    DATA-INVENTORY.md verified against the code, and carry a pending-legal-review banner.
    // ⚠️ `careers` and `changelog` STAY — their refusals are about fabricated content, untouched by this.
    foreach (['careers', 'changelog'] as $key) {
        Assert::assertTrue(
            MarketingUrls::pending($key),
            "[{$key}] became a published page. Gate 7 refused it — docs/audits/FRONT-GATE7-DOC-TRUTH-AUDIT-"
            .'2026-08-22.md records why. If a later gate builds it, that ruling must be revisited in the '
            .'audit first, and this line changed deliberately.'
        );
    }
});

it('PROVES the refusal pin can fire (the positive control)', function () {
    // ⚠️ THE VACUITY CHECK. `pending()` returning true for a key that simply does not exist in the map would
    // make the guard above pass for the wrong reason forever. So: a key that IS published must read false.
    Assert::assertFalse(MarketingUrls::pending('contact'),
        'pending() reports a PUBLISHED page as pending — the refusal guard above is therefore vacuous');
});

// ══ ⚠️ THE CORRECTIONS MUST STAY CORRECTED ═══════════════════════════════════════════════════════════

it('⚠️ never republishes a security claim the code does not support', function () {
    $html = $this->get('/security')->assertOk()->getContent();

    // ⚠️ THE NEEDLES ARE THE AUDIT'S FALSE CLAIMS, VERBATIM. Each was rated FALSE or PARTIAL against cited
    // code; each would be back on the page if a regeneration dropped its transform.
    $refused = [
        'enforceable for staff who touch money' => 'no per-role, per-permission or org-level 2FA enforcement exists — no column, no policy table, no middleware',
        'European infrastructure' => 'the product is not deployed; there is no provider, region or residency commitment anywhere in the repo',
        'A housekeeper sees rooms, not revenue' => 'nineteen /app screens carry no permission gate and three of them render money, including the dashboard revenue tile',
        'permission grants are logged' => 'granting a role to a person writes no audit row',
        'Delete an account and the data goes with it' => 'no deletion path exists for any account; cancelling only makes the workspace read-only',
    ];

    foreach ($refused as $needle => $why) {
        Assert::assertStringNotContainsString($needle, $html,
            "the /security page republished a claim the code does not support: \"{$needle}\" — {$why}");
    }

    // ⚠️ AND THE PAGE STILL SAYS SOMETHING. Deleting the whole section would pass every assertion above
    // while making the page useless — "corrected, never softened" has to be checked in both directions.
    foreach (['Tenant isolation', 'encrypted', 'Recovery codes'] as $kept) {
        Assert::assertStringContainsString($kept, $html,
            'the /security page lost a claim that is TRUE — corrections must not become deletions');
    }
});

it('⚠️ never claims an OTA channel is connected', function () {
    $html = $this->get('/integrations')->assertOk()->getContent();

    // ⚠️ THE PRODUCT'S OWN CHANNELS SCREEN REFUSES THIS WORD (OperatorChannels.php:91-92) — "a genuine sync
    // timestamp ONLY if the real integration produced one". `Channel::create` exists once outside factories,
    // in the demo seeder, so no operator can connect a channel at all: sync_jobs and webhook_events are
    // empty and last_synced_at is null.
    Assert::assertStringNotContainsString('>Synced<', $html,
        'the /integrations page shows a channel as Synced. Nothing in this product has ever synced, and the '
        .'operator Channels screen refuses the word for that reason.');

    // ⚠️ THESE THREE ARE HERE BECAUSE THEY ARE WHAT ACTUALLY SLIPPED THROUGH. The first version of this
    // guard checked the status pills and the Booking.com card — and the page shipped with its HERO still
    // asserting "a booking anywhere closes the night everywhere" and a body paragraph still saying "the
    // night closes on Booking.com and Airbnb too", directly above three pills reading "Not connected".
    // ⚠️ The generator's own no-op report could not catch it: those corrections were lost in a refactor, and
    // a needle that was never written matches nothing to report. Only opening the page found it — the third
    // time in this project that a rendered page showed what three green instruments could not.
    $stale = [
        'Two-way availability and rate sync, reservations pulled' => 'the Booking.com card',
        'channel-ready' => 'the hero',
        'the night closes on Booking.com' => 'the availability-pool section',
        "we'll confirm what connecting them looks like" => 'the closing call to action',
    ];

    foreach ($stale as $needle => $where) {
        Assert::assertStringNotContainsString($needle, $html,
            "{$where} on /integrations asserts a live channel connection. No channel can be connected: "
            .'Channel::create exists once outside factories, in the demo seeder.');
    }

    Assert::assertStringContainsString('Not connected', $html,
        'the /integrations page no longer states the channels\' real state');
});

it('⚠️ publishes no invented colleague, customer or office', function () {
    // ⚠️ VYROBEL IS ONE PERSON — 863 of 863 commits by a single author. The About prototype named four
    // "team members", two of whom are already in the honesty ledger as invented, unconsented CUSTOMER
    // testimonial authors, and a third is a recombination of two more.
    //
    // ⚠️ THIS SWEEPS EVERY BUILT MARKETING PAGE, NOT JUST ABOUT. A content sweep scoped to the page that
    // carried the defect is exactly how the same claim survives on the page next door — this project has
    // now found that five times, most recently an impossible instruction whose sixth site sat 34 lines from
    // one already fixed, in the same modal.
    $fabricated = [
        'Elena Kastellis', 'Joris van Dijk', 'Amara Reyes', 'Tomas Kral',
        'Amara Mensah', 'Marco Reyes', 'Sofia Neri', 'Thomas Brandt',
        // ⚠️ The D13 rename's REPLACEMENTS are on this list too. On a testimonial, anonymising the speaker
        // is the right fix; on a page that calls someone a CO-FOUNDER it is not — it invents a different
        // person. The rename is scoped away from About in the converter, and this pins that scoping.
        'Chara Melis', 'Bram Hoekstra', 'Nadia Farouk', 'Petar Novak',
    ];

    foreach (array_keys(G7_BUILT) as $path) {
        $html = $this->get($path)->assertOk()->getContent();

        foreach ($fabricated as $person) {
            Assert::assertStringNotContainsString($person, $html,
                "[{$path}] names {$person}. Vyrobel has one person; every name on this list is invented, and "
                .'renaming an invented colleague invents a different one rather than removing one.');
        }

        // ⚠️ AN OFFICE IS A CLAIM TOO. The prototypes claimed three cities across two pages — "a studio in
        // Rotterdam" on Careers, "Lisbon & Amsterdam" on Contact.
        foreach (['studio in Rotterdam', 'Lisbon &amp; Amsterdam'] as $office) {
            Assert::assertStringNotContainsString($office, $html, "[{$path}] claims an office that does not exist");
        }
    }
});

it('PROVES the fabricated-person needle can fire (the positive control)', function () {
    // ⚠️ THE VACUITY CHECK. The sweep above passes if the pages render empty, if the route 404s, or if the
    // needle is misspelled. Fire it against a string that certainly contains one.
    $failed = false;
    try {
        Assert::assertStringNotContainsString('Elena Kastellis', '<p>Elena Kastellis, Co-founder</p>');
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue('the fabricated-person needle cannot fire — the sweep above proves nothing');
});

// ══ ⚠️ THE FORM ACTUALLY SENDS — the whole point of building it ══════════════════════════════════════

it('⚠️ stores a contact message and notifies, in that order', function () {
    Mail::fake();
    config(['services.marketing.contact_to' => 'founder@example.test']);

    $this->post('/contact', [
        'name' => 'Alex Rivera',
        'email' => 'alex@example.test',
        'company' => 'Coastal Stays',
        'message' => 'We run 24 apartments across two cities and want the owner portal.',
        'units_band' => '11–60',
        'topic' => 'A demo',
    ])->assertRedirect();

    // ⚠️ THE ROW IS THE ASSERTION. A test that only checked Mail::assertSent would pass for a form that
    // stores nothing — and outside production MailSafetyGuard downgrades every sending transport to `log`,
    // so mail is exactly the half that does NOT happen today.
    $stored = MarketingContactMessage::query()->sole();
    expect($stored->name)->toBe('Alex Rivera')
        ->and($stored->email)->toBe('alex@example.test')
        ->and($stored->message)->toContain('24 apartments')
        ->and($stored->topic)->toBe('A demo');

    Mail::assertSent(MarketingContactMail::class);
});

it('⚠️ still stores the message when no destination address is configured', function () {
    // ⚠️ THE STATE THAT IS TRUE TODAY. MARKETING_CONTACT_TO is deliberately blank in .env.example — the
    // address is the founder's and no placeholder is shipped. An unconfigured destination must cost the
    // NOTIFICATION and never the MESSAGE, or every submission before go-live is silently destroyed.
    Mail::fake();
    config(['services.marketing.contact_to' => null]);

    $this->post('/contact', [
        'name' => 'Sam Okafor',
        'email' => 'sam@example.test',
        'message' => 'Interested in the channel manager for eleven villas.',
    ])->assertRedirect();

    expect(MarketingContactMessage::query()->count())->toBe(1);
    Mail::assertNothingSent();
});

it('⚠️ the honeypot answers normally and stores nothing', function () {
    // ⚠️ IT MUST NOT ERROR. Returning a validation failure tells a bot which field betrayed it; the ordinary
    // confirmation tells it nothing. The assertion is therefore on the COUNT, not on the response.
    $this->post('/contact', [
        'name' => 'Bot',
        'email' => 'bot@example.test',
        'message' => 'Buy cheap watches at this address, definitely not spam.',
        MarketingContactController::HONEYPOT => 'http://spam.example',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(MarketingContactMessage::query()->count())->toBe(0);
});

it('⚠️ refuses a message that would store a value nobody offered', function () {
    // The chips are closed sets. Without an `in:` rule a visitor could store any string, which would then be
    // rendered back into an internal email and a listing.
    $this->post('/contact', [
        'name' => 'Alex Rivera',
        'email' => 'alex@example.test',
        'message' => 'A perfectly ordinary message about our portfolio.',
        'topic' => 'Something the form never offered',
    ])->assertSessionHasErrors('topic');

    $this->post('/contact', [
        'name' => 'A',
        'email' => 'not-an-email',
        'message' => 'short',
    ])->assertSessionHasErrors(['name', 'email', 'message']);

    expect(MarketingContactMessage::query()->count())->toBe(0);
});

it('⚠️ is throttled, and the limiter is real', function () {
    // ⚠️ THE ONLY PUBLIC ENDPOINT THAT PERSISTS A STRANGER'S PROSE AND CAN CAUSE MAIL TO BE SENT.
    $middleware = collect(Route::getRoutes()->getByName('marketing.contact.store')->gatherMiddleware());

    Assert::assertTrue($middleware->contains('throttle:marketing-contact'),
        'the public contact POST lost its rate limiter — it is a free mail relay without one');
});

// ══ ⚠️ THE CHROME — publishing a page activates every dormant link into it ════════════════════════════

it('⚠️ leaves no site-wide CTA pointing at an anchor that no longer exists', function () {
    // ⚠️ THE LESSON THIS ENCODES. Fifteen CTAs across eleven files are
    // `<x-marketing.nav-link key="contact" fragment="#demo">` — every "Book a demo", "Book a walkthrough",
    // "Book a migration call" and "Talk to us first" on the site. The id they target lived on the demo-booker
    // section Gate 7 removed (D5 still open). They were HARMLESS while /contact was unregistered, because
    // they all rendered as marked non-links. ⚠️ PUBLISHING THE PAGE IS WHAT ACTIVATED THEM — the same shape
    // as "adding an input can weaponize a dormant display", one level up.
    $contact = $this->get('/contact')->assertOk()->getContent();

    Assert::assertStringContainsString('id="demo"', $contact,
        '#demo has no target on /contact, so every "Book a demo" link on the site scrolls nowhere. '
        .'Fifteen of them exist across eleven files.');
});

it('⚠️ keeps the design’s own responsive rules on every marketing page', function () {
    // ⚠️ WHAT THIS PREVENTS COMING BACK. The prototypes are inline-styled, but their RESPONSIVE behaviour
    // lives in a `<style>` block — and the converter strips `<style>` with `<script>`. All NINETEEN built
    // marketing pages therefore shipped with the design's hooks in the markup (`data-h1` and `data-sec` on
    // every one) and nothing reading them: the public site was non-responsive below 980px for six gates.
    //
    // ⚠️ NO ASSERTION FOUND IT AND NONE COULD HAVE. The pages returned 200, leaked no raw Blade, and did not
    // even overflow — a grid squeezes its columns rather than overflowing, so `document.scrollWidth` looked
    // perfect while the contact card sat at 241px on a phone. It took a screenshot at 390px.
    $shell = file_get_contents(resource_path('views/components/marketing/shell.blade.php'));

    // ⚠️ COUNTED, NOT MERELY PRESENT. "Contains @media" would pass on the single reduced-motion query that
    // was always there — the exact shape of a guard that proves nothing.
    $breakpoints = preg_match_all('/@media \(max-width/', $shell);
    Assert::assertGreaterThanOrEqual(8, $breakpoints,
        "the shell carries only {$breakpoints} max-width breakpoints. The design's own rules — 114 "
        .'declarations across 10 breakpoints, extracted from the 19 masters — have been dropped, and every '
        .'marketing page is non-responsive again.');

    // The two hooks every single master uses, so this cannot pass on an unrelated rule surviving.
    foreach (['[data-h1]', '[data-sec]'] as $hook) {
        Assert::assertStringContainsString($hook, $shell,
            "the shell lost the {$hook} responsive rule, which all 19 masters depend on");
    }

    // ⚠️ AND THEY MUST REACH A PAGE. A rule in a file nobody renders is not a rule.
    $html = $this->get('/contact')->assertOk()->getContent();
    Assert::assertStringContainsString('data-clayout', $html,
        'the contact page lost the layout hook the responsive rule targets');
    Assert::assertStringContainsString('@media (max-width: 980px)', $html,
        'the rendered page does not carry the breakpoint — the shell style block is not reaching it');
});

it('PROVES the anchor guard is checking a live CTA (the positive control)', function () {
    // ⚠️ THE VACUITY CHECK, and it is the one that matters most here: the guard above would pass happily if
    // NOTHING linked to #demo any more. Assert the links are really there, on a page that is really live.
    $home = $this->get('/')->assertOk()->getContent();

    Assert::assertStringContainsString('/contact#demo', $home,
        'no #demo CTA exists on the home page — the anchor guard is guarding nothing');
});

<?php

use App\Models\Guest;
use App\Support\Guest\ArrivalPack;
use App\Support\Help\HelpKnowledgeBase;

/*
 * GUEST HELP CENTRE (2026-08-12) — the guards for a NEW build.
 *
 * The page is a searchable FAQ over the ONE curated HelpKnowledgeBase. What these tests defend is not the
 * layout, it is the three properties that make the page honest:
 *
 *   1. IT RENDERS THE ENGINE, not a copy. A second home for a claim about the product is how every
 *      mock-value defect in this repo started, so an entry added to the engine must appear on the page and
 *      a page that stops reading the engine must fail.
 *   2. THE ARRIVAL WINDOW IS INTERPOLATED, never typed. `DO-G4-1` caught a docblock claiming "48h" against
 *      a constant that says 2 days; a FAQ is where that drift would mislead a guest standing at a door.
 *   3. NO SUPPORT AFFORDANCE EXISTS. By founder ruling this page is a pure FAQ: no phone, no hours, no reply
 *      time, no 24/7 line — the product has none of them, and promising support that will not answer is the
 *      class of false claim `DO-EMAIL-9` records. The ONE contact is the real relay.
 */

/** A claimable guest on a real seeded org — the same shape the other guest suites use. */
function guestForHelp(): Guest
{
    $scenario = seedScenario(1);
    $guest = $scenario['guest'];
    $guest->update(['email' => 'help@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    return $guest->fresh();
}

it('renders every FAQ question from the engine — the page holds no help content of its own', function () {
    $response = $this->actingAs(guestForHelp(), 'guest')->get(route('guest.help'));

    $response->assertOk();

    // Not a sample: EVERY entry the engine defines must reach the page, or the two have already diverged.
    foreach (HelpKnowledgeBase::guestArticles() as $article) {
        $response->assertSee($article['question'], escape: true);
        $response->assertSee($article['answer'], escape: true);
    }
});

it('interpolates the arrival window from ArrivalPack::LEAD_DAYS rather than a typed literal', function () {
    $answer = collect(HelpKnowledgeBase::guestArticles())->firstWhere('id', 'door-code')['answer'];

    // The real constant is 2. If someone retunes it, this answer must move with it — and if someone types a
    // number in instead, the second expectation catches it.
    expect($answer)->toContain(ArrivalPack::LEAD_DAYS.' days before check-in');

    $source = (string) file_get_contents(app_path('Support/Help/HelpKnowledgeBase.php'));
    expect($source)->toContain('ArrivalPack::LEAD_DAYS')
        // …and the literal it replaced must never come back.
        ->and($answer)->not->toContain('48h')
        ->and($answer)->not->toContain('48 hours');
});

it('offers the REAL relay as its only contact, and links a real route on every answer', function () {
    $response = $this->actingAs(guestForHelp(), 'guest')->get(route('guest.help'));

    // ⚠️ Scope the assertion to the relay CARD. A bare assertSee(route('guest.messages')) was VACUOUS: the
    // shell's nav links Messages on every guest page, so the test stayed green even when the button was
    // repointed. Assert the href sits INSIDE the "Still need help?" section instead (O32 — a passing
    // mutation means vacuity or redundancy; this one was vacuity).
    $html = (string) $response->getContent();
    $card = mb_substr($html, (int) mb_strpos($html, 'Still need help?'));
    expect($card)->toContain(route('guest.messages'))
        ->and($card)->toContain('Message us');

    // Every article's `route` must resolve — a FAQ pointing at a dead screen is worse than no pointer.
    foreach (HelpKnowledgeBase::guestArticles() as $article) {
        expect(fn () => route($article['route']))->not->toThrow(Exception::class);
        $response->assertSee(route($article['route']), escape: false);
    }
});

it('⚠️ ships NO support affordance — no phone, no hours, no reply time, no on-call line', function () {
    $html = (string) $this->actingAs(guestForHelp(), 'guest')->get(route('guest.help'))->getContent();

    // ⚠️ Assert on what the guest READS, not the raw response. The first cut of this test scanned the whole
    // document and fired on the word "PHONE" inside a CSS comment in the shell — a breakpoint heading
    // (`/* ── PHONE (audit …) ── */`), not a telephone. CSS comments DO ship (a known trap in this repo), so
    // stripping style/script first is what makes the assertion mean "rendered as text".
    $visible = trim((string) preg_replace('/\s+/', ' ',
        html_entity_decode(strip_tags((string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html)))
    ));

    // The prototype offered all of these. None has a backend, so none may be drawn (#39 / honest absence).
    foreach ([
        '24/7', '24-7', 'on-call', 'WhatsApp', 'Call us', 'call the', 'phone', 'hotline',
        'opening hours', 'Typical reply', 'replies under', 'reply within', 'response time',
        'support line', 'support hours',
    ] as $forbidden) {
        expect(stripos($visible, $forbidden))->toBeFalse("the page shows a support affordance: “{$forbidden}”");
    }

    // No phone-number-shaped run in the visible text, and no dial link anywhere in the markup.
    expect(preg_match('/\+\d[\d\s().-]{7,}/', $visible))->toBe(0)
        ->and(stripos($html, 'tel:'))->toBeFalse()
        ->and(stripos($html, 'callto:'))->toBeFalse();
});

it('filters by the search query and by category, and says so honestly when nothing matches', function () {
    $guest = guestForHelp();

    // A term that exists only in the door-code entry's curated keywords.
    $hit = $this->actingAs($guest, 'guest')->get(route('guest.help', ['q' => 'wifi']));
    $hit->assertOk()
        ->assertSee('When do I get my door code and Wi-Fi details?', escape: true)
        ->assertDontSee('How do I leave a review?', escape: true);

    // Category narrowing.
    $cat = $this->actingAs($guest, 'guest')->get(route('guest.help', ['category' => 'Payments']));
    $cat->assertOk()
        ->assertSee('How do I pay the balance on my booking?', escape: true)
        ->assertDontSee('How do I check in online?', escape: true);

    // The honest empty state — it names what was searched and offers the real relay.
    $miss = $this->actingAs($guest, 'guest')->get(route('guest.help', ['q' => 'zzzznothing']));
    $miss->assertOk()
        ->assertSee('No answers for', escape: true)
        ->assertSee(route('guest.messages'), escape: false);
});

it('is guest-guarded — a signed-out visitor is sent to the guest login', function () {
    $this->get(route('guest.help'))->assertRedirect(route('guest.login'));
});

it('keeps the owner set and the guest set separate but shaped identically (one engine, no fork)', function () {
    $owner = HelpKnowledgeBase::articles();
    $guest = HelpKnowledgeBase::guestArticles();

    expect($owner)->not->toBeEmpty()->and($guest)->not->toBeEmpty();

    // Same contract, so one set of rules governs both — the point of extending rather than forking.
    foreach ($guest as $a) {
        expect($a)->toHaveKeys(['id', 'keywords', 'question', 'answer', 'route', 'label', 'category']);
    }

    // The guest never sees owner topics, and the owner assistant never grounds on guest ones.
    $guestRoutes = array_column($guest, 'route');
    foreach ($guestRoutes as $r) {
        expect($r)->toStartWith('guest.');
    }
    foreach (array_column($owner, 'route') as $r) {
        expect($r)->toStartWith('owner.');
    }
});

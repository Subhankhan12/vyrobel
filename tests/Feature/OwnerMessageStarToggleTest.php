<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * DO-STAR-1 — THE MESSAGES STAR IS A BOOKMARK TOGGLE, NOT A RATING (2026-08-13).
 *
 * The register flagged this because DO10-4 had just fixed the RATING star, and a second star in the product is
 * exactly the kind of thing that gets conflated with it. It was not: the bookmark is its own inline SVG and
 * never touches `<x-star-rating>` (which renders a 0–5 COUNT). Its state is carried by the GLYPH — filled vs
 * outline — so it survives greyscale, which is the property DO10-4 had to add to ratings.
 *
 * ⚠️ WHAT WAS ACTUALLY WRONG WAS THE NAME, and it was wrong in an unusually instructive way. The markup already
 * computed the right words — `title` was "Unstar" when starred — and then a STATIC `aria-label="Star"` sat
 * beside it and overrode them. A screen-reader user heard "Star" on a button that would unstar. The information
 * existed and was discarded one attribute later.
 *
 * ⚠️ AND THE ROW ACTIONS WERE HOVER-ONLY (`opacity:0`, no `:focus-within`), so a keyboard user could TAB to a
 * real button and see neither it nor its focus ring — WCAG 2.4.7.
 *
 * ⚠️ RENDERED AS THE OWNER. Messages is an owner-portal surface; a test acting as anyone else gets a page with
 * no star on it, and every assertion below would pass for the wrong reason (§N.14.1).
 */

/**
 * The view SOURCE with Blade comments stripped.
 *
 * ⚠️ Stripped because this file's own documentation names `<x-star-rating>` in order to say it is NOT used —
 * and a substring guard that reads the comments would fail on its own explanation. (The same trap
 * `OperatorOwnerGreyDriftTest` records.)
 */
function messagesSource(): string
{
    $source = (string) file_get_contents(resource_path('views/livewire/owner/messages.blade.php'));

    return (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);
}

/** The owner Messages page as the owner sees it. */
function ownerMessagesHtml(object $test): string
{
    $s = seedScenario(1);
    $owner = ownerRoleUser($s);

    return (string) $test->actingAs($owner)->get('/owner/messages')->assertOk()->getContent();
}

it('⚠️ NOT CONFLATED — the bookmark never uses the RATING component', function () {
    $source = messagesSource();

    // ⚠️ NON-VACUITY: the file really does contain a star affordance, so "no rating component" is a statement
    // about a star that exists rather than about an empty page.
    expect($source)->toContain('owner.messages.star')
        ->and($source)->toContain('aria-pressed');

    // A bookmark is a BINARY toggle; `<x-star-rating>` renders a 0–5 count. Borrowing it here would make a
    // two-state flag render as a five-star scale.
    expect($source)->not->toContain('x-star-rating')
        ->and($source)->not->toContain('star-rating');
});

it('⚠️ THE TOGGLE ANNOUNCES ITS STATE — aria-pressed, not a static label that contradicts the action', function () {
    // ⚠️ ASSERTED ON THE MARKUP, NOT THE RENDER, and deliberately: the star only appears once a thread has
    // messages, which this suite has no fixture for. The RENDERED proof was taken in a real browser as the
    // demo owner, with both states forced — starred announced `aria-pressed="true"` with the name
    // "Star message", unstarred `"false"` — and is recorded in DECISIONS. This pins the shape so the
    // regression cannot return unnoticed.
    $source = messagesSource();

    // ⚠️ NON-VACUITY: the star button really is in this file.
    expect($source)->toContain('owner.messages.star');

    // The state is announced. Before the fix there was NO aria-pressed at all and the name was the bare,
    // state-blind string "Star" — identical whether the message was starred or not.
    expect($source)->toContain('aria-label="Star message"')
        ->and($source)->toContain("aria-pressed=\"{{ \$m['starred'] ? 'true' : 'false' }}\"");

    // ⚠️ The exact regression that was here: a static name saying "Star" on a button which unstars.
    expect($source)->not->toContain('aria-label="Star"');
});

it('⚠️ STATE SURVIVES GREYSCALE — the glyph differs, not just the colour (the DO10-4 1.4.1 rule)', function () {
    $source = messagesSource();

    // Starred is a FILLED star, unstarred is an OUTLINE — a shape difference, so a colourblind or greyscale
    // user can still tell them apart. A gold-vs-grey same-glyph star would be the colour-only failure DO10-4
    // fixed for ratings.
    expect($source)->toMatch("/fill=\"\{\{ \\\$m\['starred'\] \? '#E8A63A' : 'none' \}\}\"/");
});

it('⚠️ KEYBOARD FOCUS REVEALS THE ROW ACTIONS — hover-only would hide a focused button', function () {
    $html = ownerMessagesHtml($this);

    // The actions are opacity:0 until hover. They are real <button>s in the tab order, so without
    // `:focus-within` a keyboard user focuses something invisible (WCAG 2.4.7).
    expect($html)->toContain('.vy-msg-item:focus-within .vy-msg-acts');

    // ⚠️ NON-VACUITY: the hidden-by-default rule this compensates for is still there — if it were removed the
    // focus rule would be pointless, and this assertion documents that they belong together.
    expect($html)->toContain('.vy-msg-acts { display:flex; align-items:center; gap:2px; opacity:0');
});

it('the PIN toggle — the star’s twin — got the same treatment, not an arbitrary half-fix', function () {
    $source = messagesSource();

    expect($source)->toContain('aria-label="Pin message"')
        ->and($source)->toContain("aria-pressed=\"{{ \$m['pinned'] ? 'true' : 'false' }}\"")
        ->and($source)->not->toContain('aria-label="Pin"');

    // Reply and React are NOT toggles — they act once and carry no state — so their static labels are correct
    // and were deliberately left alone.
    expect($source)->toContain('aria-label="Reply"');
});

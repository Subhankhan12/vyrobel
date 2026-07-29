<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Owner HELP & SUPPORT (exact-match #66; "Ask Vyrobel" made REAL in Wave D) — HONEST throughout: the REAL
// grounded assistant + the real message relay + real FAQ (linking real screens). The wireframe's support-ticket
// queue, live system-status board and invented owner-desk phone/email stay REFUSED (#39). Nothing is faked.

it('renders the honest help page — real Ask Vyrobel + relay + FAQ, and NO fabricated tickets/status/numbers', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $res = $this->actingAs($user)->get('/owner/help')->assertOk()
        // REAL: the grounded assistant (posts to the real ask route) + the honest "verify"/account-help note
        ->assertSee('Ask Vyrobel')
        ->assertSee(route('owner.help.ask'))
        ->assertSee('message your team for anything account-specific')
        // REAL: the message relay + the real FAQ (linking real owner screens)
        ->assertSee('Message your manager')
        ->assertSee(route('owner.messages'))
        ->assertSee('Owner FAQs')
        ->assertSee('When and how do I get paid?')
        ->assertSee(route('owner.payout-method'))
        // HONEST: a static status line + messaging as the tracked channel (no ticket queue)
        ->assertSee('All systems operational')
        ->assertSee('no separate ticket queue')
        // ⚠️ NEVER the wireframe's fabrications (#39)
        ->assertDontSee('instant answer')       // the assistant is grounded, not a magic instant oracle
        ->assertDontSee('574 0192')             // no invented owner-desk phone number
        ->assertDontSee('tracked · routed automatically');   // no fake ticket queue count

    assertNoRawBlade($res);
});

// Wave D · Ask Vyrobel · Phase 3 — the surface end-to-end, through the real controller route + service (fake LLM).

it('answers an in-scope question with a grounded reply, sources, and the escalate relay', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $res = $this->actingAs($user)
        ->post('/owner/help/ask', ['question' => 'When will I get paid?'])
        ->assertRedirect(route('owner.help'));

    $res = $this->actingAs($user)->get('/owner/help')->assertOk()
        ->assertSee('You asked:')
        ->assertSee('When will I get paid?')
        ->assertSee('Sources')
        ->assertSee(route('owner.payout-method'))            // the real source link
        ->assertSee('Message your team about this')          // the always-present escalate
        ->assertSee(route('owner.messages.start'));

    assertNoRawBlade($res);
});

it('escalates honestly for an out-of-scope question — no fabricated answer, straight to the relay', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $this->actingAs($user)
        ->post('/owner/help/ask', ['question' => 'What is the capital of France?'])
        ->assertRedirect(route('owner.help'));

    $res = $this->actingAs($user)->get('/owner/help')->assertOk()
        ->assertSee('I don’t have that in Vyrobel’s help material', false)
        ->assertSee('Message your team about this')
        ->assertDontSee('Sources');   // nothing retrieved → no invented citations

    assertNoRawBlade($res);
});

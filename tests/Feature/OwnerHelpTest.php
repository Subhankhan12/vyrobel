<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Owner HELP & SUPPORT (exact-match #66) — HONEST throughout: the real message relay + real FAQ (linking real
// screens); the wireframe's AI Q&A, support-ticket queue, live system-status board and invented owner-desk
// phone/email are REFUSED (#39). Nothing is faked.

it('renders the honest help page — real relay + FAQ, and NO fabricated AI/tickets/status/numbers', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $res = $this->actingAs($user)->get('/owner/help')->assertOk()
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
        ->assertDontSee('instant answer')       // no fake AI Q&A
        ->assertDontSee('Ask Vyrobel')
        ->assertDontSee('574 0192')             // no invented owner-desk phone number
        ->assertDontSee('tracked · routed automatically');   // no fake ticket queue count

    assertNoRawBlade($res);
});

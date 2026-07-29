<?php

use App\Support\Help\HelpKnowledgeBase;
use App\Support\Help\HelpRetriever;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// Boot the app so the KB test can assert each article links a REAL registered route (Route facade). The retrieval
// tests are pure, but sharing one TestCase keeps the file cohesive and stays cheap (no DB refresh).
uses(TestCase::class);

// Wave D · Ask Vyrobel · Phase 1 — the grounding gate. These prove the ARCHITECTURAL guarantee that makes the
// assistant honest: an in-scope question retrieves the real help article; an out-of-scope OR account-specific
// question retrieves NOTHING (→ the caller escalates, never fabricates). Pure, deterministic — no DB, no network.

it('grounds on real curated help content, not invented policy', function () {
    $articles = HelpKnowledgeBase::articles();

    expect($articles)->toHaveCount(7);

    foreach ($articles as $a) {
        expect($a)->toHaveKeys(['id', 'keywords', 'question', 'answer', 'route', 'label'])
            ->and($a['question'])->not->toBe('')
            ->and($a['answer'])->not->toBe('')
            // Every article links a REAL registered owner screen.
            ->and(Route::has($a['route']))->toBeTrue();
    }
});

it('retrieves the relevant real article for an in-scope question', function (string $question, string $expectedId) {
    $hits = (new HelpRetriever)->retrieve($question);

    expect($hits)->not->toBeEmpty()
        ->and($hits[0]['id'])->toBe($expectedId)
        // What comes back is verbatim KB content — never anything synthesised.
        ->and($hits[0]['answer'])->toBe(
            collect(HelpKnowledgeBase::articles())->firstWhere('id', $expectedId)['answer']
        );
})->with([
    'payout phrasing' => ['When will I receive my payout?', 'pay'],
    'get paid' => ['how do I get paid', 'pay'],
    'statement' => ['help me read my statement', 'statement'],
    'commission' => ['what commission does the management team charge', 'fees'],
    'tax/VAT' => ['what are my VAT obligations', 'tax'],
    'blocking dates' => ['can I block dates for my own stay', 'block'],
    'refund' => ['a guest was refunded, how does it affect me', 'refund'],
]);

it('returns NOTHING for out-of-scope or account-specific questions (→ escalate, never fabricate)', function (string $question) {
    expect((new HelpRetriever)->retrieve($question))->toBeEmpty();
})->with([
    'off-topic' => ['what is the capital of France'],
    'gibberish' => ['asdfghjkl qwerty zxcvbn'],
    'weather' => ['is it going to rain tomorrow'],
    'empty' => [''],
    'stopwords only' => ['how do I'],
]);

it('orders results by relevance, most relevant first, and caps at k', function () {
    $hits = (new HelpRetriever)->retrieve('payout statement tax fees block refund unit', k: 3);

    expect($hits)->toHaveCount(3);

    // Scores are non-increasing (deterministic ranking).
    for ($i = 1; $i < count($hits); $i++) {
        expect($hits[$i]['score'])->toBeLessThanOrEqual($hits[$i - 1]['score']);
    }
});

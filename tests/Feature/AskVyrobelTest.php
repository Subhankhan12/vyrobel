<?php

use App\Models\LlmUsage;
use App\Services\AskVyrobelService;
use App\Support\Help\AskResult;
use App\Support\Llm\FakeLlmClient;
use App\Support\Llm\LlmClientInterface;
use App\Support\Llm\LlmSafetyGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

// Wave D · Ask Vyrobel · Phase 2 — the guardrails behaviour-proven. The honesty guarantee is architectural:
// an out-of-scope/account question NEVER reaches the LLM; failures degrade to the real help + relay; the ledger
// caps spend. All against the deterministic FakeLlmClient — no network, no cost.

beforeEach(function () {
    Cache::flush(); // reset the circuit breaker between tests
    $this->fake = new FakeLlmClient;
    $this->app->instance(LlmClientInterface::class, $this->fake);
});

function ask(string $question, ?string $ownerId = 'owner-1'): AskResult
{
    return app(AskVyrobelService::class)->ask($ownerId, $question);
}

it('answers an in-scope question with a grounded reply + citations, calling the LLM once', function () {
    $this->fake->willReturn('Payouts go to your default bank account when your monthly statement is finalized.');

    $result = ask('When will I get paid?');

    expect($result->isAnswered())->toBeTrue()
        ->and($result->text)->toContain('Payouts')
        ->and($result->citations)->not->toBeEmpty()
        ->and($result->citations[0]['id'])->toBe('pay')
        ->and($this->fake->calls)->toBe(1);
});

it('escalates an out-of-scope question with NO LLM call (grounding gate)', function () {
    $result = ask('What is the capital of France?');

    expect($result->isEscalate())->toBeTrue()
        ->and($result->reason)->toBe('no_match')
        ->and($result->citations)->toBeEmpty()
        ->and($this->fake->calls)->toBe(0); // the model was never handed the question
});

it('escalates an empty question with no LLM call', function () {
    $result = ask('   ');

    expect($result->isEscalate())->toBeTrue()
        ->and($this->fake->calls)->toBe(0);
});

it('records real token spend in the cost ledger after a successful answer', function () {
    $this->fake->willReturn('A grounded answer.');

    ask('How do I read my statement?', ownerId: 'owner-42');

    $row = LlmUsage::where('owner_id', 'owner-42')->whereDate('usage_date', today())->first();

    expect($row)->not->toBeNull()
        ->and($row->request_count)->toBe(1)
        ->and($row->token_count)->toBeGreaterThan(0);
});

it('falls back to the real help content (no LLM call) once the daily token cap is reached', function () {
    // Seed usage at/over the configured cap for this owner today.
    config(['services.llm.daily_token_cap' => 100]);
    LlmUsage::create([
        'owner_id' => 'owner-capped',
        'usage_date' => today(),
        'request_count' => 3,
        'token_count' => 100,
    ]);

    $result = ask('How do I read my statement?', ownerId: 'owner-capped');

    expect($result->outcome)->toBe(AskResult::GROUNDED_FALLBACK)
        ->and($result->reason)->toBe('cost_capped')
        ->and($result->hasCitations())->toBeTrue()   // the real help is still shown
        ->and($result->text)->toBeNull()             // but nothing synthesised
        ->and($this->fake->calls)->toBe(0);          // and the LLM was never called
});

it('trips the circuit breaker on a provider failure and falls back to real help', function () {
    $this->fake->willThrow();

    $first = ask('How do I read my statement?');

    expect($first->outcome)->toBe(AskResult::GROUNDED_FALLBACK)
        ->and($first->reason)->toBe('provider_down')
        ->and($first->hasCitations())->toBeTrue()
        ->and($this->fake->calls)->toBe(1);

    // Breaker now OPEN: a second in-scope ask must NOT call the provider again.
    $second = ask('When will I get paid?');

    expect($second->outcome)->toBe(AskResult::GROUNDED_FALLBACK)
        ->and($second->reason)->toBe('provider_down')
        ->and($this->fake->calls)->toBe(1); // still 1 — no second call
});

// The spend guard — a real key never fires outside production (mirrors StripeSafetyGuard).
it('forces the fake LLM client outside production even with a real key', function () {
    expect(LlmSafetyGuard::forceFakeOutsideProduction('local', false, 'sk-ant-real-key'))->toBeTrue()
        ->and(LlmSafetyGuard::forceFakeOutsideProduction('production', false, 'sk-ant-real-key'))->toBeFalse()
        ->and(LlmSafetyGuard::forceFakeOutsideProduction('production', true, 'sk-ant-real-key'))->toBeTrue()
        ->and(LlmSafetyGuard::forceFakeOutsideProduction('production', false, null))->toBeTrue()
        ->and(LlmSafetyGuard::forceFakeOutsideProduction('production', false, '  '))->toBeTrue();
});

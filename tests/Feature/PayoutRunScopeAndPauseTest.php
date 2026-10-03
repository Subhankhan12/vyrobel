<?php

declare(strict_types=1);

use App\Exceptions\Owner\PayoutsPausedException;
use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\PayoutRun;
use App\Services\OwnerPayoutService;
use App\Services\PayoutRunService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE PAYOUT RUN — ONE ORGANISATION, AND A PAUSE THAT HOLDS (2026-10-02). His go on A11 ("Run payouts now" reached
// every organisation's owners) and his ruling on DEC-n: "a pause stops every payout" — the automatic run, Run payouts now,
// Create payouts and Pay. Each test below failed first against the code as it stood.
//
// ⚠️ Every organisation's rows are made BEFORE the next scenario is seeded: seeding switches the tenant context, and a
// row made after it would quietly belong to the other organisation.

beforeEach(fn () => config(['services.payouts.engine_enabled' => true]));

function prsPause(Owner $owner): void
{
    OwnerPayoutPreference::query()->updateOrCreate(['owner_id' => $owner->id], ['paused' => true]);
}

// ── A11 — Run payouts now stays inside its own organisation ──────────────────────────────────────

it('⚠️⚠️ runs payouts only for the organisation that pressed Run payouts now (A11)', function () {
    $a = seedScenario(1);
    $adminA = owpAdmin($a);
    $mine = owpOwner($a, 'Owner In A');
    owpStatement($mine, '2026-08', 10000);

    $b = seedScenario(1);
    $theirs = owpOwner($b, 'Owner In B');
    owpStatement($theirs, '2026-08', 20000);

    // 🔬 before: "2 created · 2 paid" — organisation A's click created AND paid organisation B's owner
    $this->actingAs($adminA)->post(route('app.payouts.run-now'))->assertSessionHas('payout_notice');

    expect(Payout::withoutGlobalScopes()->where('owner_id', $theirs->id)->count())->toBe(0)
        ->and(Payout::withoutGlobalScopes()->where('owner_id', $mine->id)->value('status'))->toBe('paid');
})->group('security', 'money');

it('⚠️ starts no run when nothing is due here — and says so', function () {
    $a = seedScenario(1);
    $adminA = owpAdmin($a);

    $b = seedScenario(1);
    $theirs = owpOwner($b, 'Owner In B');
    owpStatement($theirs, '2026-08', 20000);
    $runs = PayoutRun::query()->count();

    $this->actingAs($adminA)->post(route('app.payouts.run-now'))
        ->assertSessionHas('payout_notice', fn (string $m): bool => str_contains($m, 'Nothing to run'));

    expect(PayoutRun::query()->count())->toBe($runs)
        ->and(Payout::withoutGlobalScopes()->where('owner_id', $theirs->id)->count())->toBe(0);
})->group('security');

it('⚠️⚠️ shows under Last run only what this organisation’s own payouts say — never another organisation’s run', function () {
    $a = seedScenario(1);
    $adminA = owpAdmin($a);
    $mine = owpOwner($a, 'Owner In A');
    owpStatement($mine, '2026-08', 10000);

    $b = seedScenario(1);
    $adminB = owpAdmin($b);
    $theirs = owpOwner($b, 'Owner In B');
    owpStatement($theirs, '2026-08', 20000);
    $otherOwner = owpOwner($b, 'Second In B');
    owpStatement($otherOwner, '2026-08', 30000);

    // organisation B runs: two payouts created and paid THERE
    $this->actingAs($adminB)->post(route('app.payouts.run-now'));
    // ⚠️ one test session serves both users — B's own "2 created" flash would otherwise show on A's next page
    $this->flushSession();

    // 🖥 before: organisation A's page read "Last run · Completed · 2 created · 2 paid · 0 held" — B's run
    $html = (string) $this->actingAs($adminA)->get(route('app.payouts.run'))->assertOk()->getContent();
    expect($html)->not->toContain('2 created')
        ->and($html)->toContain('No payout run yet');

    // and once A runs, A sees its own run — one payout, here
    $this->actingAs($adminA)->post(route('app.payouts.run-now'));
    $this->flushSession();
    $html = (string) $this->actingAs($adminA)->get(route('app.payouts.run'))->assertOk()->getContent();
    expect($html)->toContain('1 created · 1 paid · 0 held')->not->toContain('2 created');
})->group('security');

it('⚠️ counts only this organisation’s payouts under Last run — even for the run that covers every organisation', function () {
    $a = seedScenario(1);
    $adminA = owpAdmin($a);
    $mine = owpOwner($a, 'Owner In A');
    owpStatement($mine, '2026-08', 10000);

    $b = seedScenario(1);
    $theirs = owpOwner($b, 'Owner In B');
    owpStatement($theirs, '2026-08', 20000);

    // the platform's own run — every organisation, as the daily automatic run does
    app(PayoutRunService::class)->run(PayoutRun::TRIGGER_SCHEDULED, null, 'scheduled:test-every-organisation');

    $html = (string) $this->actingAs($adminA)->get(route('app.payouts.run'))->assertOk()->getContent();
    expect($html)->toContain('1 created · 1 paid · 0 held')->not->toContain('2 created');
})->group('security');

it('never ends the payout run’s read-only refusal with "until ." — a cancelled workspace has no resume date', function () {
    $a = seedScenario(1);
    $adminA = owpAdmin($a);
    $mine = owpOwner($a, 'Owner In A');
    owpStatement($mine, '2026-08', 10000);
    $a['org']->forceFill(['readonly_from' => now()->subDay()])->save();

    $this->actingAs($adminA)->post(route('app.payouts.run-now'));

    expect((string) session('payout_error'))->toBe('Workspace paused — payouts are read-only for now.');
});

// ── DEC-n — a pause stops every payout ───────────────────────────────────────────────────────────

it('⚠️⚠️ refuses to create a payout by hand for a paused owner — and says why', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = owpOwner($s, 'Paused Owner');
    $statement = owpStatement($owner, '2026-08', 10000);
    prsPause($owner);

    $this->actingAs($admin)->post(route('app.payouts.create'), ['statement_id' => $statement->id])
        ->assertSessionHas('payout_error', fn (string $m): bool => str_contains($m, 'paused'));

    expect(Payout::query()->where('owner_id', $owner->id)->count())->toBe(0);
})->group('money');

it('⚠️⚠️ refuses to pay a waiting payout by hand once its owner is paused — the payout stays waiting', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = owpOwner($s, 'Paused Owner');
    $payout = app(OwnerPayoutService::class)->createFromStatement(owpStatement($owner, '2026-08', 10000));
    prsPause($owner);

    $this->actingAs($admin)->post(route('app.payouts.pay'), ['payout_id' => $payout->id])
        ->assertSessionHas('payout_error', fn (string $m): bool => str_contains($m, 'paused'));

    expect($payout->fresh()->status)->toBe('pending');
})->group('money');

it('⚠️⚠️ skips a paused owner in Run payouts now, Create payouts and Pay — and says how many', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $paid = owpOwner($s, 'Active Owner');
    owpStatement($paid, '2026-08', 10000);
    $paused = owpOwner($s, 'Paused Owner');
    owpStatement($paused, '2026-08', 20000);
    owpStatement($paused, '2026-07', 5000, ['sequence_no' => 2]);   // two statements, ONE paused owner — counted as one
    prsPause($paused);

    $this->actingAs($admin)->post(route('app.payouts.create'))
        ->assertSessionHas('payout_notice', fn (string $m): bool => str_contains($m, '1 payout created') && str_contains($m, '1 paused owner skipped'));
    $this->actingAs($admin)->post(route('app.payouts.pay'))
        ->assertSessionHas('payout_notice', fn (string $m): bool => str_contains($m, '1 payout marked paid'));
    expect(Payout::query()->where('owner_id', $paused->id)->count())->toBe(0);

    // a waiting payout made before the pause is left waiting by Pay all and by Run payouts now
    OwnerPayoutPreference::query()->where('owner_id', $paused->id)->update(['paused' => false]);
    $waiting = app(OwnerPayoutService::class)->createFromStatement(OwnerStatement::query()->where('owner_id', $paused->id)->where('sequence_no', 1)->firstOrFail());
    prsPause($paused);

    $this->actingAs($admin)->post(route('app.payouts.pay'))
        ->assertSessionHas('payout_notice', fn (string $m): bool => str_contains($m, '1 paused'));
    $this->actingAs($admin)->post(route('app.payouts.run-now'))
        ->assertSessionHas('payout_notice', fn (string $m): bool => str_contains($m, '1 paused'));
    expect($waiting->fresh()->status)->toBe('pending');
})->group('money');

it('⚠️ lists a paused owner under Payouts paused on the payout run — never under Ready to pay', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $ready = owpOwner($s, 'Ready Owner');
    owpStatement($ready, '2026-08', 10000);
    $paused = owpOwner($s, 'Paused Owner');
    owpStatement($paused, '2026-08', 20000);
    // a payout made for them before the pause — it waits, shown but never offered
    app(OwnerPayoutService::class)->createFromStatement(owpStatement($paused, '2026-07', 5000, ['sequence_no' => 2]));
    prsPause($paused);

    $html = (string) $this->actingAs($admin)->get(route('app.payouts.run'))->assertOk()->getContent();
    preg_match('/<h2 class="vy-card-title">Pending transfers<\/h2>(.*?)<\/table>/s', $html, $pendingCard);
    expect($pendingCard[1] ?? '')->toContain('Paused Owner')->toContain('>Paused</span>')
        ->not->toContain('name="payout_id"')
        ->not->toContain('Mark 1 paid');
    preg_match('/<h2 class="vy-card-title">Ready to pay<\/h2>(.*?)<h2 class="vy-card-title">/s', $html, $readyCard);
    preg_match('/<h2 class="vy-card-title">Payouts paused<\/h2>(.*?)<\/table>/s', $html, $pausedCard);

    expect($readyCard[1] ?? '')->toContain('Ready Owner')->not->toContain('Paused Owner')
        ->and($readyCard[1] ?? '')->toContain('Create 1 payout')
        ->and($pausedCard[1] ?? '')->toContain('Paused Owner')
        ->and($pausedCard[1] ?? '')->toContain(route('app.owners.show', $paused->id));
});

it('⚠️⚠️ refuses a paused owner in the payout service itself — the one check behind every path', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s, 'Paused Owner');
    $statement = owpStatement($owner, '2026-08', 10000);
    $payouts = app(OwnerPayoutService::class);
    $waiting = $payouts->createFromStatement(owpStatement($owner, '2026-07', 5000, ['sequence_no' => 2]));
    prsPause($owner);

    expect(fn () => $payouts->createFromStatement($statement))->toThrow(PayoutsPausedException::class)
        ->and(fn () => $payouts->pay($waiting))->toThrow(PayoutsPausedException::class)
        ->and($waiting->fresh()->status)->toBe('pending')
        ->and(Payout::query()->where('owner_statement_id', $statement->id)->count())->toBe(0);

    // the engine's own selection leaves a paused owner out — named or not (were it selected, the service's refusal
    // would count as a failed payout, which is how these two assertions tell the layers apart)
    $run = app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL, [(string) $owner->id], 'test:paused-named');
    expect((int) $run->payouts_created)->toBe(0)->and((int) $run->payouts_failed)->toBe(0);
    $every = app(PayoutRunService::class)->run(PayoutRun::TRIGGER_SCHEDULED, null, 'test:paused-every-organisation');
    expect((int) $every->payouts_failed)->toBe(0)
        ->and(Payout::query()->where('owner_statement_id', $statement->id)->count())->toBe(0);
})->group('money');

// ── The words that describe a pause ──────────────────────────────────────────────────────────────

it('says a pause stops every payout — on the Owners page and on the owner’s own page', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = owpOwner($s, 'Paused Owner');
    owpStatement($owner, '2026-08', 10000);
    prsPause($owner);

    $list = (string) $this->actingAs($admin)->get(route('app.owners'))->assertOk()->getContent();
    expect($list)->toContain('No payout goes out to them until you resume')
        ->not->toContain('is not stopped by a pause')
        ->not->toContain('the automatic payout run skips them');

    $detail = (string) $this->actingAs($admin)->get(route('app.owners.show', $owner->id))->assertOk()->getContent();
    expect($detail)->toContain('No payout is created or sent until resumed')
        ->not->toContain('Amounts accumulate and carry forward until resumed.');
});

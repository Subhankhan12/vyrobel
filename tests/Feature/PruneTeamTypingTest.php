<?php

declare(strict_types=1);

use App\Console\Commands\PruneTeamTyping;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// D-TC-TYPING-SWEEP (2026-11-11, his go): `team_typing` holds one row per person per conversation, updated in place — never a pile, but a
// row left by someone who stopped typing stayed for ever. An hourly sweep deletes what no screen could still show (the page reads a row
// for six seconds), in the scheduler's own shape: no tenant context, every organisation.

/** a typing row for a person in a conversation, last typed $minutesAgo minutes ago */
function ptt_row(array $s, string $conversationId, string $userId, int $minutesAgo): void
{
    DB::table('team_typing')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'conversation_id' => $conversationId,
        'user_id' => $userId, 'typed_at' => now()->subMinutes($minutesAgo), 'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('⚠️ deletes the rows nobody could still see, keeps the live ones, and runs with no tenant (the scheduler\'s shape)', function () {
    $s = seedScenario(1);
    $anna = operatorUser($s, ['name' => 'Anna Lee'], 'Housekeeping');
    $ben = operatorUser($s, ['name' => 'Ben Ortiz'], 'Front Desk');
    $conversation = test()->actingAs($anna)->postJson(route('app.inbox.team.start'), ['members' => [(string) $ben->id]])->assertSuccessful()->json('conversation.id');

    ptt_row($s, $conversation, (string) $anna->id, 3 * 60);                          // three hours ago — dead
    ptt_row($s, $conversation, (string) $ben->id, PruneTeamTyping::KEEP_MINUTES - 1); // inside the window — kept (and ignored by the page anyway)

    expect(DB::table('team_typing')->where('conversation_id', $conversation)->count())->toBe(2);

    app(TenantContext::class)->forget();   // the scheduler has NO tenant — clear whatever the test set
    expect(app(TenantContext::class)->hasContext())->toBeFalse();
    expect(Artisan::call('team-chat:prune-typing'))->toBe(0);
    expect(Artisan::output())->toContain('Deleted 1 stale typing row(s).');

    $left = DB::table('team_typing')->where('conversation_id', $conversation)->pluck('user_id')->all();
    expect($left)->toBe([(string) $ben->id]);

    // idempotent: a second run has nothing to do
    Artisan::call('team-chat:prune-typing');
    expect(Artisan::output())->toContain('Deleted 0 stale typing row(s).');
});

it('is on the schedule, hourly', function () {
    $events = collect(app(Schedule::class)->events())->filter(fn ($e): bool => str_contains((string) $e->command, 'team-chat:prune-typing'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 * * * *');
});

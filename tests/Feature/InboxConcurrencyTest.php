<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Organization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

// Unified Inbox · Step 1 — INBOUND idempotency under TRUE OS-level concurrency (mirrors the Channex
// webhook createOrFirst race + the no-double-booking harness). Two separate processes post the SAME
// inbound external_message_id at a shared wall-clock barrier; the unique index + createOrFirst must
// collapse them to EXACTLY ONE message (and one thread). Does NOT use RefreshDatabase — the workers
// need committed data across their own connections.

/**
 * Launch two race workers that post the same inbound message id simultaneously; return decoded results.
 *
 * @return list<array<string, mixed>>
 */
function launchInboundRace(Organization $org, string $threadRef, string $msgId): array
{
    $startAt = microtime(true) + 1.5;
    $baseEnv = array_merge(getenv(), [
        'RACE_ORG_ID' => $org->id,
        'RACE_CHANNEL' => 'manual',
        'RACE_THREAD_REF' => $threadRef,
        'RACE_MSG_ID' => $msgId,
        'RACE_BODY' => 'Concurrent inbound',
        'RACE_START_AT' => (string) $startAt,
        'QUEUE_CONNECTION' => 'sync',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'vyrobel_test',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
    ]);

    $worker = base_path('tests/Support/inbound_message_race_worker.php');
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

    $procs = [];
    $pipes = [];
    foreach ([0, 1] as $i) {
        $procs[$i] = proc_open([PHP_BINARY, $worker], $descriptors, $pipes[$i], base_path(), $baseEnv);
    }

    $results = [];
    foreach ($procs as $i => $proc) {
        $out = stream_get_contents($pipes[$i][1]);
        $err = stream_get_contents($pipes[$i][2]);
        fclose($pipes[$i][1]);
        fclose($pipes[$i][2]);
        proc_close($proc);
        $decoded = json_decode(trim((string) $out), true);
        $results[$i] = is_array($decoded) ? $decoded : ['status' => 'parse_error', 'raw' => $out, 'err' => $err];
    }

    return $results;
}

it('collapses two concurrent inbound deliveries of the same external id to exactly one message (5×)', function () {
    Artisan::call('migrate', ['--force' => true]);

    foreach (range(1, 5) as $round) {
        $tenant = app(TenantContext::class);
        $org = $tenant->runAsSuperAdmin(fn () => Organization::create([
            'name' => 'Inbox Race Org', 'slug' => 'inbox-race-'.Str::lower(Str::random(10)), 'default_currency' => 'EUR',
        ]));
        $tenant->set($org->id);

        $threadRef = 'race-thread-'.Str::random(8);
        $msgId = 'race-msg-'.Str::random(8);

        $results = launchInboundRace($org, $threadRef, $msgId);
        $context = "round {$round}: ".json_encode($results);

        // Both processes succeeded (neither 500'd on the unique-violation race).
        expect($results[0]['status'])->toBe('ok', $context)
            ->and($results[1]['status'])->toBe('ok', $context);

        // EXACTLY ONE message with this external id, and both workers resolved to the SAME row.
        $messages = Message::query()->where('organization_id', $org->id)->where('external_message_id', $msgId)->get();
        expect($messages)->toHaveCount(1, $context)
            ->and($results[0]['message_id'])->toBe($results[1]['message_id'], $context);

        // And exactly one thread for the shared anchor (thread createOrFirst is race-safe too).
        $threads = MessageThread::query()->where('organization_id', $org->id)->where('external_thread_ref', $threadRef)->get();
        expect($threads)->toHaveCount(1, $context)
            ->and((int) $threads->first()->unread_count)->toBe(1, $context);   // counted once, not twice
    }
})->group('concurrency');

<?php

/**
 * Inbound-message concurrency worker (Unified Inbox · Step 1). A standalone process (its own MySQL
 * connection) that posts ONE inbound message through InboxService at a shared future timestamp, so two
 * of these race for real over committed data — the only way to exercise the createOrFirst / unique-index
 * catch-the-race on `messages(organization_id, channel, external_message_id)` under true OS parallelism.
 * Both workers use the SAME external_message_id (and thread ref): exactly one row must result. Prints
 * one JSON line.
 */

use App\Services\InboxService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

app(TenantContext::class)->set((string) getenv('RACE_ORG_ID'));

$startAt = (float) getenv('RACE_START_AT');
$now = microtime(true);
if ($startAt > $now) {
    usleep((int) (($startAt - $now) * 1_000_000));
}

try {
    $message = app(InboxService::class)->postInbound([
        'channel' => (string) getenv('RACE_CHANNEL'),
        'body' => (string) getenv('RACE_BODY'),
        'external_thread_ref' => (string) getenv('RACE_THREAD_REF'),
        'external_message_id' => (string) getenv('RACE_MSG_ID'),
        'participant_identity' => 'race@guest.test',
        'sender_label' => 'Race Guest',
    ]);

    echo json_encode(['status' => 'ok', 'message_id' => $message->id, 'thread_id' => $message->thread_id, 'pid' => getmypid()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'pid' => getmypid()]);
}

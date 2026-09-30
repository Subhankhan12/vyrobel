<?php

declare(strict_types=1);

// Mutation set message-attachments — his ruling on DEC-m (2026-09-30): "let only people in that conversation open them, after
// signing in". Owner message attachments moved to the private disk and open only through two signed-in routes; the operator
// inbox shows them. Each mutation removes one part of that, and a named test must go red.
// Run with `php tests/Mutations/run.php --set=message-attachments`.
//
// ⚠️ Not in this set, on purpose: the `collection` and `mediable_type` filters in MessageAttachments::find(). With them gone,
// a non-attachment file's id still fails the Message lookup behind them (a 404), so no behaviour changes — an equivalent
// mutant, which would read as NOT CAUGHT while proving nothing. They are a second layer; the Message lookup is the first.

$t = 'tests/Feature/MessageAttachmentAccessTest.php';
$support = 'app/Support/Inbox/MessageAttachments.php';
$inbox = 'app/Livewire/Operator/OperatorInbox.php';
$routes = 'routes/web.php';

return [
    [
        'id' => 'MA-M1',
        'name' => 'attachments go back to the public disk',
        'file' => $support,
        'old' => "    public const DISK = 'local';",
        'new' => "    public const DISK = 'public';",
        'tests' => [['file' => $t, 'filter' => 'keeps an owner’s attachments on the private disk']],
    ],
    [
        'id' => 'MA-M2',
        'name' => 'the conversation no longer decides — any owner of the organisation opens another owner’s file',
        'file' => $support,
        'old' => "        MessageThread::query()->findOrFail((string) \$message->thread_id);\n",
        'new' => '',
        'tests' => [['file' => $t, 'filter' => 'refuses everyone outside that conversation on the owner side']],
    ],
    [
        'id' => 'MA-M3',
        'name' => 'the back office’s door loses the inbox permission — any staff role opens an owner’s file',
        'file' => $routes,
        'old' => "    Route::get('/inbox/attachments/{media}', MessageAttachmentController::class)->middleware('can:guests.view')->name('inbox.attachment');",
        'new' => "    Route::get('/inbox/attachments/{media}', MessageAttachmentController::class)->name('inbox.attachment');",
        'tests' => [['file' => $t, 'filter' => 'shows the attachments in the operator inbox']],
    ],
    [
        'id' => 'MA-M4',
        'name' => 'every file opens in place — a document is rendered in the page’s origin instead of downloaded',
        'file' => $support,
        'old' => '        $inline = in_array($type, self::INLINE, true);',
        'new' => '        $inline = true;',
        'tests' => [['file' => $t, 'filter' => 'keeps an owner’s attachments on the private disk']],
    ],
    [
        'id' => 'MA-M5',
        'name' => 'the conversations link to the file’s disk address again instead of the signed-in route',
        'file' => $support,
        'old' => "                'url' => route(\$routeName, (string) \$m->getKey()),",
        'new' => "                'url' => (string) \$m->url,",
        'tests' => [['file' => $t, 'filter' => 'keeps an owner’s attachments on the private disk'], ['file' => $t, 'filter' => 'shows the attachments in the operator inbox']],
    ],
    [
        'id' => 'MA-M6',
        'name' => 'a private document may be kept by the browser',
        'file' => $support,
        'old' => "            'Cache-Control' => 'private, no-store',",
        'new' => "            'Cache-Control' => 'private, max-age=86400',",
        'tests' => [['file' => $t, 'filter' => 'keeps an owner’s attachments on the private disk']],
    ],
    [
        'id' => 'MA-M7',
        'name' => 'the operator inbox stops showing what the owner attached',
        'file' => $inbox,
        'old' => "            'attachments' => app(MessageAttachments::class)->rows(\$m, 'app.inbox.attachment'),",
        'new' => "            'attachments' => [],",
        'tests' => [['file' => $t, 'filter' => 'shows the attachments in the operator inbox']],
    ],
];

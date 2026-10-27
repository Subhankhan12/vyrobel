<?php

declare(strict_types=1);

// Mutation set inbox-live — the Guests side of Messages, live, and files to guests and owners (2026-10-06, the founder's team
// chat request: "notification icon … notification sound … Ajax based runtime … attachment … picture … voice"; `docs/TEAM-CHAT-
// DESIGN.md` §6). Each mutation brings back one way it would be wrong — nobody told when a guest writes, a housekeeper told,
// the bell stacking, the open Inbox never refreshing, a file sent where the other side cannot open it, a guest opening
// another's file, a guest's files outliving their erasure — and its test must go red.
// Run with `php tests/Mutations/run.php --set=inbox-live`.

$live = 'tests/Feature/InboxLiveTest.php';
$files = 'tests/Feature/InboxAttachmentsTest.php';
$listener = 'app/Listeners/DispatchNotificationsForDomainEvent.php';
$reply = 'app/Http/Controllers/Operator/InboxActionController.php';
$attachments = 'app/Support/Inbox/MessageAttachments.php';

return [
    // ── a guest or an owner writes: the bell ──────────────────────────────────────────────────────────────────────────
    [
        'id' => 'IL-M1',
        'name' => 'nobody on the staff is told when a guest writes (the event has no handler again)',
        'file' => $listener,
        'old' => "        'inbox.message_received',\n",
        'new' => '',
        'tests' => [['file' => $live, 'filter' => 'rings the bell for the staff who may read the conversation']],
    ],
    [
        'id' => 'IL-M2',
        'name' => 'an assigned conversation still rings everyone',
        'file' => $listener,
        'old' => '        $recipients = $assignee instanceof User ? collect([$assignee]) : $readers;',
        'new' => '        $recipients = $readers;',
        'tests' => [['file' => $live, 'filter' => 'rings only the assigned person']],
    ],
    [
        'id' => 'IL-M3',
        'name' => 'a housekeeper is told about guest conversations they may not read',
        'file' => $listener,
        'old' => "        \$readers = \$this->recipients->operatorsForOrgWithPermission(\$orgId, 'guests.view');",
        'new' => '        $readers = $this->recipients->operatorsForOrg($orgId);',
        'tests' => [['file' => $live, 'filter' => 'rings the bell for the staff who may read the conversation']],
    ],
    [
        'id' => 'IL-M4',
        'name' => 'the bell stacks one entry per guest message',
        'file' => $listener,
        'old' => "            \$recipient->notifications()   // one entry per conversation — never a pile of read ones beneath it\n                ->where('type', InboxMessageNotification::class)\n                ->where('data->thread_id', (string) \$thread->getKey())\n                ->delete();\n",
        'new' => '',
        'tests' => [['file' => $live, 'filter' => 'keeps one entry per conversation while it is unread']],
    ],
    [
        'id' => 'IL-M5',
        'name' => 'opening the conversation leaves everyone\'s bell entry unread',
        'file' => 'app/Services/InboxService.php',
        'old' => "            ->where('data->thread_id', (string) \$thread->getKey())\n            ->update(['read_at' => now()]);",
        'new' => "            ->where('data->thread_id', (string) \$thread->getKey())\n            ->count();",
        'tests' => [['file' => $live, 'filter' => 'keeps one entry per conversation while it is unread']],
    ],
    [
        'id' => 'IL-M13',
        'name' => 'every guest message is also emailed to the staff',
        'file' => 'app/Notifications/Operator/InboxMessageNotification.php',
        'old' => "        return ['database'];",
        'new' => "        return ['database', 'mail'];",
        'tests' => [['file' => $live, 'filter' => 'rings the bell for the staff who may read the conversation']],
    ],
    // ── the open Inbox, live ──────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'IL-M6',
        'name' => 'the stamp never moves — the open Inbox never refreshes',
        'file' => 'app/Http/Controllers/Operator/InboxLiveController.php',
        'old' => "            'stamp' => substr(sha1((string) json_encode([\$all?->getAttributes(), \$open])), 0, 16),",
        'new' => "            'stamp' => 'still',",
        'tests' => [['file' => $live, 'filter' => 'tells the open Inbox when something changed']],
    ],
    [
        'id' => 'IL-M7',
        'name' => 'the stamp loses the Inbox\'s own permission',
        'file' => 'routes/web.php',
        'old' => "    Route::get('/inbox/live', InboxLiveController::class)->middleware(['can:guests.view', 'throttle:inbox-live'])->name('inbox.live');",
        'new' => "    Route::get('/inbox/live', InboxLiveController::class)->middleware(['throttle:inbox-live'])->name('inbox.live');",
        'tests' => [['file' => $live, 'filter' => 'tells the open Inbox when something changed']],
    ],
    [
        'id' => 'IL-M8',
        'name' => 'the conversation\'s messages are no longer a part the page refreshes',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<div class="vy-thread-scroll" data-ibx-live="thread" data-thread-id="{{ $active[\'id\'] }}" data-unread="{{ $active[\'unread\'] }}">',
        'new' => '<div class="vy-thread-scroll" data-thread-id="{{ $active[\'id\'] }}" data-unread="{{ $active[\'unread\'] }}">',
        'tests' => [['file' => $live, 'filter' => 'marks the parts of the Inbox the page refreshes in place']],
    ],
    // ── files, pictures and voice notes to guests and owners ─────────────────────────────────────────────────────────
    [
        'id' => 'IA-M1',
        'name' => 'a file is sent on a channel that cannot carry it (the guest would never see it)',
        'file' => $reply,
        'old' => "        if ((\$files !== [] || \$voice !== null) && \$model->owner_id === null && \$model->channel !== 'manual') {",
        'new' => '        if (false) {',
        'tests' => [['file' => $files, 'filter' => 'refuses files on a channel that cannot carry them']],
    ],
    [
        'id' => 'IA-M2',
        'name' => 'an empty reply goes out',
        'file' => $reply,
        'old' => "        if (\$body === '' && \$files === [] && \$voice === null) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $files, 'filter' => 'refuses files on a channel that cannot carry them']],
    ],
    [
        'id' => 'IA-M3',
        'name' => 'the reply no longer answers the live page in JSON',
        'file' => $reply,
        'old' => '        if ($request->expectsJson()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $files, 'filter' => 'still works as a plain form without JavaScript']],
    ],
    [
        'id' => 'IA-M4',
        'name' => 'a guest opens another guest\'s file',
        'file' => $attachments,
        'old' => "            ->where('guest_id', \$guest->getKey())\n",
        'new' => '',
        'tests' => [['file' => $files, 'filter' => 'opens a guest\'s file for that guest only']],
    ],
    [
        'id' => 'IA-M5',
        'name' => 'a guest opens an owner\'s file through the guest door',
        'file' => $attachments,
        'old' => "            ->whereNull('owner_id')\n            ->firstOrFail();",
        'new' => '            ->firstOrFail();',
        'tests' => [['file' => $files, 'filter' => 'opens a guest\'s file for that guest only']],
    ],
    [
        'id' => 'IA-M6',
        'name' => 'a voice note sent to an owner downloads instead of playing',
        'file' => $attachments,
        'old' => '        if ($media->collection === self::VOICE && $voice !== null) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $files, 'filter' => 'plays an operator\'s voice note in the owner\'s portal']],
    ],
    [
        'id' => 'IA-M7',
        'name' => 'the owner portal shows a voice note as a file link',
        'file' => 'resources/views/livewire/owner/messages.blade.php',
        'old' => "@elseif ((\$att['kind'] ?? '') === 'voice')",
        'new' => '@elseif (false)',
        'tests' => [['file' => $files, 'filter' => 'plays an operator\'s voice note in the owner\'s portal']],
    ],
    [
        'id' => 'IA-M8',
        'name' => 'the guest\'s account shows no voice note',
        'file' => 'resources/views/guest/message-thread.blade.php',
        'old' => "                        @elseif (\$file['kind'] === 'voice')",
        'new' => '                        @elseif (false)',
        'tests' => [['file' => $files, 'filter' => 'sends pictures, a document and a voice note to a guest']],
    ],
    [
        'id' => 'IA-M9',
        'name' => 'a guest\'s files outlive their erasure (GDPR)',
        'file' => 'app/Models/Guest.php',
        'old' => "                app(MessageAttachments::class)->eraseInThreads(\$threadIds->all());\n",
        'new' => '',
        'tests' => [['file' => $files, 'filter' => 'erases the files in a guest\'s conversations with the guest']],
    ],

    // ── the independent review (2026-10-06): each fix brought back, each test red ─────────────────────────────────────
    [
        'id' => 'IL-M9',
        'name' => 'the page\'s own refresh reads the open conversation — messages nobody looked at, and a refresh loop',
        'file' => 'app/Livewire/Operator/OperatorInbox.php',
        'old' => "        if (\$explicitThread !== null && ! request()->boolean('live')) {",
        'new' => '        if ($explicitThread !== null) {',
        'tests' => [['file' => $live, 'filter' => 'never marks a conversation read by refreshing it']],
    ],
    [
        'id' => 'IL-M10',
        'name' => 'anyone in the back office can mark a guest conversation read — the Inbox\'s permission lost',
        'file' => 'routes/web.php',
        'old' => "    Route::post('/inbox/{thread}/read', [InboxActionController::class, 'read'])->middleware(['can:guests.view', 'throttle:inbox-live'])->name('inbox.read');",
        'new' => "    Route::post('/inbox/{thread}/read', [InboxActionController::class, 'read'])->middleware(['throttle:inbox-live'])->name('inbox.read');",
        'tests' => [['file' => $live, 'filter' => 'never marks a conversation read by refreshing it']],
    ],
    [
        'id' => 'IL-M11',
        'name' => 'a preview counts as opened — the page reads it for the person',
        'file' => 'app/Livewire/Operator/OperatorInbox.php',
        'old' => "'opened' => \$explicitThread !== null,",
        'new' => "'opened' => true,",
        'tests' => [['file' => $live, 'filter' => 'refreshes the conversation the page shows']],
    ],
    [
        'id' => 'IL-M12',
        'name' => 'the refresh no longer checks which conversation came back — it swaps in whichever became newest',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => "                    if (key === 'thread' && there.dataset.threadId !== cfg.c) return;\n",
        'new' => '',
        'tests' => [['file' => $live, 'filter' => 'refreshes the conversation the page shows']],
    ],
    [
        'id' => 'IA-M10',
        'name' => 'a guest\'s erasure leaves the staff\'s bell entries naming them (GDPR)',
        'file' => 'app/Models/Guest.php',
        'old' => "                })->delete();\n",
        'new' => "                })->count();\n",
        'tests' => [['file' => $live, 'filter' => 'erases the staff\'s bell entries that name an erased guest']],
    ],
    [
        'id' => 'IA-M11',
        'name' => 'a reply is sent although a file could not be kept — a reply without its files, or an empty one',
        'file' => $reply,
        'old' => "            return \$this->answer(\$request, \$model, __('php/http/controllers/operator/inbox_action_controller.file_not_saved'), false);\n",
        'new' => '',
        'tests' => [['file' => $files, 'filter' => 'keeps a reply\'s files first']],
    ],
    [
        'id' => 'IA-M12',
        'name' => 'a file whose record failed is left on the disk',
        'file' => $reply,
        'old' => "            Media::query()->whereKey(array_map(fn (Media \$m): string => (string) \$m->getKey(), \$recorded))->forceDelete();\n            \$attachments->discard(array_column(\$kept, 'file'));\n",
        'new' => "            Media::query()->whereKey(array_map(fn (Media \$m): string => (string) \$m->getKey(), \$recorded))->forceDelete();\n",
        'tests' => [['file' => $files, 'filter' => 'says so when the words went but the files could not be recorded']],
    ],
    [
        'id' => 'IA-M13',
        'name' => 'the files that failed are reported as sent — the page clears them',
        'file' => $reply,
        'old' => "            return \$this->answer(\$request, \$model, __('php/http/controllers/operator/inbox_action_controller.files_not_saved_after_send'), true, filesKept: true);",
        'new' => "            return \$this->answer(\$request, \$model, __('php/http/controllers/operator/inbox_action_controller.reply_sent'), true);",
        'tests' => [['file' => $files, 'filter' => 'says so when the words went but the files could not be recorded']],
    ],
    [
        'id' => 'IA-M14',
        'name' => 'a guest\'s file downloads under the extension its name claims (".bat")',
        'file' => $attachments,
        'old' => "        return ChatFiles::displayName((string) \$media->alt, (string) \$media->path, 'attachment');",
        'new' => '        return (string) $media->alt;',
        'tests' => [['file' => $files, 'filter' => 'downloads a file under its own words']],
    ],
    [
        'id' => 'IA-M15',
        'name' => 'the refusal says 10 MB where this server takes less',
        'file' => $reply,
        'old' => "            'attachments.*.max' => __('php/http/controllers/operator/inbox_action_controller.attachment_too_big', ['mb' => \$mb]),",
        'new' => "            'attachments.*.max' => __('php/http/controllers/operator/inbox_action_controller.attachment_too_big', ['mb' => 10]),",
        'tests' => [['file' => $files, 'filter' => 'tells the size this server takes']],
    ],
];

<?php

declare(strict_types=1);

// Mutation set team-chat — team chat (2026-10-06, the founder: "messages must be owner and the employees as well … toggle for
// client and employees … attachment … picture … voice … notification icon … notification sound … Ajax based runtime";
// `docs/TEAM-CHAT-DESIGN.md`). Each mutation brings back one way the feature would be wrong — a conversation or a file seen
// by someone not in it, a support session reading staff messages, a paused workspace still sending, the microphone on every
// page, a bell that emails or stacks up — and its test must go red. Run with `php tests/Mutations/run.php --set=team-chat`.
// ⚠️ How it looks and plays is what a browser does: these prove the rules; the 🖥 walk (two people, live) proved the rest.
// ⚠️ Not provable here, and said so: the read position's ONE-statement update (two reads at once cannot move it back —
// a race a feature test cannot stage) and the page's own cursor, merge and recorder (JavaScript — the 🖥 walk).

$test = 'tests/Feature/TeamChatTest.php';
$service = 'app/Services/TeamChatService.php';
$controller = 'app/Http/Controllers/Operator/TeamChatController.php';
$files = 'app/Support/TeamChat/TeamChatFiles.php';
$reader = 'app/Support/TeamChat/TeamChatReader.php';
$chatFiles = 'app/Support/Messaging/ChatFiles.php';

return [
    [
        'id' => 'TC-M1',
        'name' => 'a conversation can be started with someone who does not work here (another workspace, a unit owner)',
        'file' => $service,
        'old' => '        if (! in_array((string) $by->getKey(), $staff, true) || $others === [] || array_diff($others, $staff) !== []) {',
        'new' => '        if (! in_array((string) $by->getKey(), $staff, true) || $others === []) {',
        'tests' => [['file' => $test, 'filter' => 'refuses a teammate who is not on this workspace']],
    ],
    [
        'id' => 'TC-M2',
        'name' => 'the live routes stop asking whether the person is in the conversation',
        'file' => $controller,
        'old' => "        \$isMember = TeamConversationMember::query()->where('conversation_id', \$id)->where('user_id', \$user->getKey())->exists();",
        'new' => '        $isMember = true;',
        'tests' => [['file' => $test, 'filter' => 'hides a conversation from everyone who is not in it']],
    ],
    [
        'id' => 'TC-M3',
        'name' => 'the person\'s list starts from every conversation of the workspace, not their own',
        'file' => $reader,
        'old' => "        \$memberships = TeamConversationMember::query()->where('user_id', \$viewer->getKey())->get()->keyBy('conversation_id');\n        if (\$memberships->isEmpty()) {\n            return [];",
        'new' => "        \$memberships = TeamConversationMember::query()->get()->keyBy('conversation_id');\n        if (\$memberships->isEmpty()) {\n            return [];",
        'tests' => [['file' => $test, 'filter' => 'hides a conversation from everyone who is not in it']],
    ],
    [
        'id' => 'TC-M4',
        'name' => 'a file opens for anyone in the workspace, not only the conversation\'s members',
        'file' => $files,
        'old' => "        abort_unless(TeamConversationMember::query()\n            ->where('conversation_id', \$message->conversation_id)\n            ->where('user_id', \$viewer->getKey())\n            ->exists(), 404);",
        'new' => '        abort_unless(true, 404);',
        'tests' => [['file' => $test, 'filter' => 'keeps pictures and files private']],
    ],
    [
        'id' => 'TC-M5',
        'name' => 'team chat files go to the public disk',
        'file' => $files,
        'old' => "    public const DISK = 'local';",
        'new' => "    public const DISK = 'public';",
        'tests' => [['file' => $test, 'filter' => 'keeps pictures and files private']],
    ],
    [
        'id' => 'TC-M6',
        'name' => 'a voice note downloads as a file instead of playing (no audio type, no byte ranges)',
        'file' => $files,
        'old' => '        if ($media->collection === self::VOICE && $voice !== null) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'keeps a voice note with its length and plays it as audio']],
    ],
    [
        'id' => 'TC-M7',
        'name' => 'the bell stacks one entry per message instead of one per conversation',
        'file' => 'app/Listeners/DispatchNotificationsForDomainEvent.php',
        'old' => "            \$recipient->notifications()   // one entry per conversation — never a pile of read ones beneath it\n                ->where('type', TeamMessageNotification::class)\n                ->where('data->conversation_id', (string) \$conversation->getKey())\n                ->delete();\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'rings the bell once per conversation while unread']],
    ],
    [
        'id' => 'TC-M8',
        'name' => 'every chat message is also emailed',
        'file' => 'app/Notifications/Operator/TeamMessageNotification.php',
        'old' => "        return ['database'];",
        'new' => "        return ['database', 'mail'];",
        'tests' => [['file' => $test, 'filter' => 'rings the bell once per conversation while unread']],
    ],
    [
        'id' => 'TC-M9',
        'name' => 'reading the conversation leaves its bell entry unread',
        'file' => $service,
        'old' => "            ->where('data->conversation_id', (string) \$conversation->getKey())\n            ->update(['read_at' => now()]);",
        'new' => "            ->where('data->conversation_id', (string) \$conversation->getKey())\n            ->count();",
        'tests' => [['file' => $test, 'filter' => 'rings the bell once per conversation while unread']],
    ],
    [
        'id' => 'TC-M10',
        'name' => 'a Vyrobel support session reads staff conversations through the poll',
        'file' => $controller,
        'old' => "        if (BreakGlass::hasStash(\$request)) {\n            return \$this->notFound();",
        'new' => "        if (false) {\n            return \$this->notFound();",
        'tests' => [['file' => $test, 'filter' => 'keeps team chat closed to a Vyrobel support session']],
    ],
    [
        'id' => 'TC-M11',
        'name' => 'a Vyrobel support session sees staff conversations on the page',
        'file' => 'app/Livewire/Operator/OperatorTeamChat.php',
        'old' => '        if (BreakGlass::hasStash($request)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'keeps team chat closed to a Vyrobel support session']],
    ],
    [
        'id' => 'TC-M12',
        'name' => 'a paused workspace still sends and starts conversations',
        'file' => $service,
        'old' => '        if ($this->workspace->isReadOnly()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'keeps a paused workspace']],
    ],
    [
        'id' => 'TC-M13',
        'name' => 'the microphone is allowed on every page',
        'file' => 'app/Http/Middleware/SecurityHeaders.php',
        'old' => "        \$microphone = \$request->routeIs('app.inbox', 'app.inbox.team') ? 'microphone=(self)' : 'microphone=()';",
        'new' => "        \$microphone = 'microphone=(self)';",
        'tests' => [['file' => $test, 'filter' => 'allows the microphone on the two Messages pages only']],
    ],
    [
        'id' => 'TC-M14',
        'name' => 'the microphone stays off everywhere — no voice note can be recorded',
        'file' => 'app/Http/Middleware/SecurityHeaders.php',
        'old' => "        \$microphone = \$request->routeIs('app.inbox', 'app.inbox.team') ? 'microphone=(self)' : 'microphone=()';",
        'new' => "        \$microphone = 'microphone=()';",
        'tests' => [['file' => $test, 'filter' => 'allows the microphone on the two Messages pages only']],
    ],
    [
        'id' => 'TC-M15',
        'name' => 'a recording cannot be heard before it is sent (no media-src)',
        'file' => 'app/Http/Middleware/SecurityHeaders.php',
        'old' => "            'media-src' => \"'self' blob:\",\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'allows the microphone on the two Messages pages only']],
    ],
    [
        'id' => 'TC-M16',
        'name' => 'the pulse tells a housekeeper how many guest conversations are waiting',
        'file' => 'app/Http/Controllers/Operator/PulseController.php',
        'old' => "            'guests' => \$user->can('guests.view')",
        'new' => "            'guests' => true",
        'tests' => [['file' => $test, 'filter' => 'gives every back-office page its live counts']],
    ],
    [
        'id' => 'TC-M17',
        'name' => 'your own messages count as unread to you',
        'file' => $reader,
        'old' => "            ->where(fn (\$q) => \$q->whereNull('team_messages.user_id')->orWhere('team_messages.user_id', '!=', \$viewer->getKey()))\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'clears your unread when you read']],
    ],
    [
        'id' => 'TC-M18',
        'name' => 'the live poll stops looking back — a message saved a moment late, behind the cursor, is never shown',
        'file' => $reader,
        'old' => "            \$rows = \$query->where('id', '>', self::lookBackFrom(\$after))",
        'new' => "            \$rows = \$query->where('id', '>', \$after)",
        'tests' => [['file' => $test, 'filter' => 'looks 30 seconds back'], ['file' => $test, 'filter' => 'never skips a colleague']],
    ],
    [
        'id' => 'TC-M19',
        'name' => 'a housekeeper\'s Messages disappears from the sidebar again',
        'file' => 'resources/views/components/operator/shell.blade.php',
        'old' => "auth()->user()?->can('guests.view') ? route('app.inbox') : route('app.inbox.team')],",
        'new' => "auth()->user()?->can('guests.view') ? route('app.inbox') : null],",
        'tests' => [['file' => $test, 'filter' => 'leads a housekeeper']],
    ],
    [
        'id' => 'TC-M20',
        'name' => 'a message over 5,000 characters is kept',
        'file' => $service,
        'old' => '        if (mb_strlen($body) > self::MAX_BODY) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'refuses an empty message and one over 5,000 characters']],
    ],
    [
        'id' => 'TC-M21',
        'name' => 'a web page can be sent as a file',
        'file' => $chatFiles,
        'old' => "    public const FILE_EXTENSIONS = 'jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt';",
        'new' => "    public const FILE_EXTENSIONS = 'jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,html';",
        'tests' => [['file' => $test, 'filter' => 'refuses a file type that is not a picture or a document']],
    ],
    [
        'id' => 'TC-M22',
        'name' => 'a picture can be posted as a voice note',
        'file' => $chatFiles,
        'old' => "    public const VOICE_EXTENSIONS = 'webm,weba,ogg,oga,opus,m4a,mp4,mp3,mpga,wav';",
        'new' => "    public const VOICE_EXTENSIONS = 'webm,weba,ogg,oga,opus,m4a,mp4,mp3,mpga,wav,png';",
        'tests' => [['file' => $test, 'filter' => 'keeps a voice note with its length and plays it as audio']],
    ],
    [
        'id' => 'TC-M23',
        'name' => 'a voice note\'s length is taken as the page said it, past the 3-minute cap',
        'file' => $chatFiles,
        'old' => '        return is_numeric($value) ? max(1, min(self::MAX_VOICE_SECONDS, (int) $value)) : null;',
        'new' => '        return is_numeric($value) ? max(1, (int) $value) : null;',
        'tests' => [['file' => $test, 'filter' => 'keeps a voice note with its length and plays it as audio']],
    ],

    // ── the independent review (2026-10-06): each fix brought back, each test red ─────────────────────────────────────
    [
        'id' => 'TC-M24',
        'name' => 'sending moves the sender\'s read position to their own message — a colleague\'s earlier one is read for them',
        'file' => $service,
        'old' => "                // ⚠️ Sending reads nothing (the independent review, 2026-10-06): moving the sender's read position to their own\n",
        'new' => "                TeamConversationMember::query()->where('conversation_id', \$conversation->getKey())->where('user_id', \$from->getKey())\n                    ->update(['last_read_message_id' => \$message->getKey(), 'last_read_at' => \$message->created_at]);\n                // ⚠️ Sending reads nothing (the independent review, 2026-10-06): moving the sender's read position to their own\n",
        'tests' => [['file' => $test, 'filter' => 'never skips a colleague']],
    ],
    [
        'id' => 'TC-M25',
        'name' => 'a Vyrobel support session sees the staff member\'s team chat entries in the bell and the feed',
        'file' => 'app/Support/Notifications/NotificationFeed.php',
        'old' => "        return \$query->when(BreakGlass::hasStash(request()), fn (Builder \$q) => \$q->where('type', '!=', TeamMessageNotification::class));",
        'new' => '        return $query;',
        'tests' => [['file' => $test, 'filter' => 'keeps a Vyrobel support session out of team chat']],
    ],
    [
        'id' => 'TC-M26',
        'name' => 'a person whose membership ended is still rung',
        'file' => 'app/Listeners/DispatchNotificationsForDomainEvent.php',
        'old' => "        \$recipients = User::query()->whereIn('id', \$staff)->whereIn('id', TeamConversationMember::query()",
        'new' => "        \$recipients = User::query()->whereIn('id', TeamConversationMember::query()",
        'tests' => [['file' => $test, 'filter' => 'stops ringing a person who no longer works here']],
    ],
    [
        'id' => 'TC-M27',
        'name' => 'read bell entries pile up — one more per message once the last was read',
        'file' => 'app/Listeners/DispatchNotificationsForDomainEvent.php',
        'old' => "            \$recipient->notifications()   // one entry per conversation — never a pile of read ones beneath it\n                ->where('type', TeamMessageNotification::class)",
        'new' => "            \$recipient->unreadNotifications()\n                ->where('type', TeamMessageNotification::class)",
        'tests' => [['file' => $test, 'filter' => 'keeps ONE bell entry per conversation']],
    ],
    [
        'id' => 'TC-M28',
        'name' => 'a file downloads under the extension its name claims, not the one its bytes earned (a script\'s)',
        'file' => $chatFiles,
        'old' => '        if ($claimed === $earned || self::sameKind($claimed, $earned)) {',
        'new' => '        if (true) {',
        'tests' => [['file' => $test, 'filter' => 'downloads a file under its own name']],
    ],
    [
        'id' => 'TC-M29',
        'name' => 'a right-to-left override survives in a file\'s name',
        'file' => $chatFiles,
        'old' => "        \$name = (string) preg_replace('/[\\x00-\\x1F\\x7F\\p{Cf}]/u', '', \$name);",
        'new' => "        \$name = (string) preg_replace('/[\\x00-\\x1F\\x7F]/u', '', \$name);",
        'tests' => [['file' => $test, 'filter' => 'downloads a file under its own name']],
    ],
];

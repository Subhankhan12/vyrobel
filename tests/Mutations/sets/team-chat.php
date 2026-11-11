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
// TC-M30…M59 (2026-11-11, D-TC-LATER + D-TC-PII): edit and delete (author only, the 15-minute window, a tombstone that keeps no words or
// files and counts as no unread), the poll's "what changed since", reactions (the allow-list; the touch that delivers them), search
// (members only, literal % and _), typing, "online now", and a guest's erasure reaching team chat (the full name, the email, the
// phone, never a lone name, never another workspace). ⚠️ NOT PROVABLE HERE, and said so: desktop notifications (the browser's
// Notification API, fired from the pulse), the page's merge-by-upsert, hover/long-press tools and the jump-to-message loop —
// JavaScript and a browser; the page's INPUTS and WORDS are pinned in TeamChatTest ("gives the page its new inputs").
// ⚠️ TC-M54 removes BOTH the explicit organisation clause and the global scope: either alone is defence in depth, so removing one
// would survive by design.
// ⚠️ TC-M38/M55/M56/M57 are the SERVICE's own membership check, which the controller's `messageFor()` check sits in front of: the controller
// layer alone is an equivalent mutant while the service stands (a non-member is still refused there), so each layer has its own test —
// the service's is called directly, with the controller out of the way.

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
        'old' => "    public function poll(Request \$request): JsonResponse\n    {\n        if (BreakGlass::hasStash(\$request)) {",
        'new' => "    public function poll(Request \$request): JsonResponse\n    {\n        if (false) {",
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
        'old' => "                \$q->where('id', '>', self::lookBackFrom(\$after));",
        'new' => "                \$q->where('id', '>', \$after);",
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
        'old' => "        if (mb_strlen(\$body) > self::MAX_BODY) {\n            throw new TeamChatRefused(__('php/services/team_chat_service.message_too_long'));\n        }\n\n        \$written = [];",
        'new' => "        if (false) {\n            throw new TeamChatRefused(__('php/services/team_chat_service.message_too_long'));\n        }\n\n        \$written = [];",
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
    [
        'id' => 'TC-M30',
        'name' => 'a teammate in the conversation can edit or delete a message they did not write',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
        if ((string) $message->user_id !== (string) $by->getKey()) {
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        if (false) {
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'lets only the author edit or delete']],
    ],
    [
        'id' => 'TC-M31',
        'name' => 'the 15-minute edit window never closes',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
        if ($message->created_at->copy()->addMinutes(self::EDIT_WINDOW_MINUTES)->isPast()) {
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        if (false) {
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'closes editing 15 minutes after sending']],
    ],
    [
        'id' => 'TC-M32',
        'name' => 'an edit can leave a message with nothing in it',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
        if ($body === '' && $files === 0) {
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        if (false) {
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'closes editing 15 minutes after sending']],
    ],
    [
        'id' => 'TC-M33',
        'name' => 'the edit event carries the new words (the audit trail becomes a second copy of the conversation)',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
            $facts = ['conversation_id' => (string) $conversation->getKey(), 'characters' => mb_strlen($body), 'files' => $files];
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
            $facts = ['conversation_id' => (string) $conversation->getKey(), 'characters' => mb_strlen($body), 'files' => $files, 'body' => $body];
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'lets the author change the words']],
    ],
    [
        'id' => 'TC-M34',
        'name' => 'a deleted message keeps its words',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
            $message->forceFill(['body' => null, 'edited_at' => null, 'redacted_at' => null, 'deleted_at' => now()])->save();
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
            $message->forceFill(['deleted_at' => now()])->save();
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'deletes as a tombstone']],
    ],
    [
        'id' => 'TC-M35',
        'name' => 'a deleted message keeps its files — the rows and the bytes',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
            $files = $this->files->erase($message);
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
            $files = 0;
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'deletes as a tombstone']],
    ],
    [
        'id' => 'TC-M36',
        'name' => 'a deleted message still counts as unread',
        'file' => 'app/Support/TeamChat/TeamChatReader.php',
        'old' => <<<'NOWDOC_END'
            ->whereNull('team_messages.deleted_at')   // a deleted message is nothing to read

NOWDOC_END,
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'deletes as a tombstone']],
    ],
    [
        'id' => 'TC-M37',
        'name' => 'the poll never asks what changed since — an edit, a delete, a reaction or a removed name never reaches an open screen',
        'file' => 'app/Support/TeamChat/TeamChatReader.php',
        'old' => <<<'NOWDOC_END'
                    $q->orWhere('updated_at', '>=', $changedFrom);
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
                    $q->orWhere('id', '<', '');
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'brings a change to a screen that already holds the message']],
    ],
    [
        'id' => 'TC-M38',
        'name' => 'a teammate outside the conversation can edit a message (the service takes the person on trust)',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
$this->member($conversation, $by);
        $this->refuseWhilePaused();
        $this->refuseUnlessAuthor($message, $by);
        if ($message->deleted_at !== null) {
            throw
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
$this->refuseWhilePaused();
        $this->refuseUnlessAuthor($message, $by);
        if ($message->deleted_at !== null) {
            throw
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'refuses a person outside the conversation at the SERVICE']],
    ],
    [
        'id' => 'TC-M39',
        'name' => 'any text is accepted as a reaction',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
        if (! in_array($emoji, InboxService::REACTIONS, true)) {
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        if (false) {
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'toggles a reaction']],
    ],
    [
        'id' => 'TC-M40',
        'name' => 'a reaction does not touch its message — a screen holding it never hears',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
            $message->touch();
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
            $message->getKey();
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'toggles a reaction']],
    ],
    [
        'id' => 'TC-M41',
        'name' => 'a % or _ typed in the search is a pattern, not a character',
        'file' => 'app/Support/TeamChat/TeamChatReader.php',
        'old' => <<<'NOWDOC_END'
->where('body', 'like', '%'.addcslashes($term, '%_\\').'%');
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
->where('body', 'like', '%'.$term.'%');
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'searches one conversation']],
    ],
    [
        'id' => 'TC-M42',
        'name' => 'the search opens a conversation you are not in',
        'file' => 'app/Http/Controllers/Operator/TeamChatController.php',
        'old' => <<<'NOWDOC_END'
        $isMember = TeamConversationMember::query()->where('conversation_id', $id)->where('user_id', $user->getKey())->exists();
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        $isMember = true;
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'searches one conversation']],
    ],
    [
        'id' => 'TC-M43',
        'name' => '"typing" lasts ten minutes, not six seconds',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
    public const TYPING_SECONDS = 6;
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
    public const TYPING_SECONDS = 600;
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'shows who is typing for six seconds']],
    ],
    [
        'id' => 'TC-M44',
        'name' => 'sending leaves "is typing…" on',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
                TeamTyping::query()->where('conversation_id', $conversation->getKey())->where('user_id', $from->getKey())->delete();

NOWDOC_END,
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'shows who is typing for six seconds']],
    ],
    [
        'id' => 'TC-M45',
        'name' => 'you are told you are typing yourself',
        'file' => 'app/Support/TeamChat/TeamChatReader.php',
        'old' => <<<'NOWDOC_END'
            ->where('user_id', '!=', $viewer->getKey())
            ->where('typed_at', '>=', now()->subSeconds(TeamChatService::TYPING_SECONDS))
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
            ->where('typed_at', '>=', now()->subSeconds(TeamChatService::TYPING_SECONDS))
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'shows who is typing for six seconds']],
    ],
    [
        'id' => 'TC-M46',
        'name' => '"online" lasts fifteen minutes, not ninety seconds',
        'file' => 'app/Support/TeamChat/TeamChatReader.php',
        'old' => <<<'NOWDOC_END'
    public const ONLINE_SECONDS = 90;
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
    public const ONLINE_SECONDS = 900;
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'shows who is online from the sessions table']],
    ],
    [
        'id' => 'TC-M47',
        'name' => 'presence names people you share no conversation with',
        'file' => 'app/Support/TeamChat/TeamChatReader.php',
        'old' => <<<'NOWDOC_END'
        $others = TeamConversationMember::query()->whereIn('conversation_id', $mine)->where('user_id', '!=', $viewer->getKey())->pluck('user_id')->unique();
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        $others = User::query()->where('id', '!=', $viewer->getKey())->pluck('id');
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'shows who is online from the sessions table']],
    ],
    [
        'id' => 'TC-M48',
        'name' => 'presence is guessed on a driver that cannot tell',
        'file' => 'app/Support/TeamChat/TeamChatReader.php',
        'old' => <<<'NOWDOC_END'
        if (config('session.driver') !== 'database') {
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        if (false) {
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'shows who is online from the sessions table']],
    ],
    [
        'id' => 'TC-M49',
        'name' => 'the poll trusts a `since` from the future (it can hide a change)',
        'file' => 'app/Http/Controllers/Operator/TeamChatController.php',
        'old' => <<<'NOWDOC_END'
        return min($at, $now);
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        return $at;
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'brings a change to a screen that already holds the message']],
    ],
    [
        'id' => 'TC-M50',
        'name' => 'erasure never looks in team chat',
        'file' => 'app/Models/Guest.php',
        'old' => <<<'NOWDOC_END'
            $teamMessagesScrubbed = app(TeamChatErasure::class)->scrub($this);
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
            $teamMessagesScrubbed = 0;
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'scrubs a guest from team chat when they are erased']],
    ],
    [
        'id' => 'TC-M51',
        'name' => 'erasure takes a first name or a surname alone out of every message',
        'file' => 'app/Support/TeamChat/TeamChatErasure.php',
        'old' => <<<'NOWDOC_END'
        return '/(?<![\p{L}\p{N}\p{Mn}])(?:'.$a.'\s+'.$b.'|'.$b.'\s*,?\s+'.$a.')(?![\p{L}\p{N}\p{Mn}])/iu';
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        return '/(?<![\p{L}\p{N}\p{Mn}])(?:'.$a.'|'.$b.')(?![\p{L}\p{N}\p{Mn}])/iu';
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'scrubs a guest from team chat when they are erased']],
    ],
    [
        'id' => 'TC-M52',
        'name' => 'erasure leaves the guest\'s email in team chat',
        'file' => 'app/Support/TeamChat/TeamChatErasure.php',
        'old' => <<<'NOWDOC_END'
            $email !== '' ? $this->emailPattern($email) : null,
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
            null,
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'scrubs a guest from team chat when they are erased']],
    ],
    [
        'id' => 'TC-M53',
        'name' => 'erasure misses a phone number written with a trunk 0 and no country code',
        'file' => 'app/Support/TeamChat/TeamChatErasure.php',
        'old' => <<<'NOWDOC_END'
        $tail = strlen($digits) > self::PHONE_TAIL_DIGITS ? '|0?'.$join(substr($digits, -self::PHONE_TAIL_DIGITS)) : '';
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        $tail = '';
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'scrubs a guest from team chat when they are erased']],
    ],
    [
        'id' => 'TC-M54',
        'name' => 'erasure reads every workspace\'s team chat, not the guest\'s own',
        'file' => 'app/Support/TeamChat/TeamChatErasure.php',
        'old' => <<<'NOWDOC_END'
        return TeamMessage::query()->where('organization_id', $guest->organization_id)
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
        return TeamMessage::query()->withoutGlobalScopes()
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'leaves another workspace']],
    ],
    [
        'id' => 'TC-M55',
        'name' => 'a teammate outside the conversation can delete a message (the service takes the person on trust)',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
$this->member($conversation, $by);
        $this->refuseWhilePaused();
        $this->refuseUnlessAuthor($message, $by);
        if ($message->deleted_at !== null) {
            return $message;
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
$this->refuseWhilePaused();
        $this->refuseUnlessAuthor($message, $by);
        if ($message->deleted_at !== null) {
            return $message;
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'refuses a person outside the conversation at the SERVICE']],
    ],
    [
        'id' => 'TC-M56',
        'name' => 'a person outside the conversation can react to its messages (the service takes the person on trust)',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
$this->member($conversation, $by);
        $this->refuseWhilePaused();
        if (! in_array($emoji
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
$this->refuseWhilePaused();
        if (! in_array($emoji
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'refuses a person outside the conversation at the SERVICE']],
    ],
    [
        'id' => 'TC-M57',
        'name' => 'a person outside the conversation can say they are typing in it (the service takes the person on trust)',
        'file' => 'app/Services/TeamChatService.php',
        'old' => <<<'NOWDOC_END'
$this->member($conversation, $by);
        $this->refuseWhilePaused();

        $where
NOWDOC_END,
        'new' => <<<'NOWDOC_END'
$this->refuseWhilePaused();

        $where
NOWDOC_END,
        'tests' => [['file' => $test, 'filter' => 'refuses a person outside the conversation at the SERVICE']],
    ],
    [
        'id' => 'TC-M58',
        'name' => 'an edit can make a message over 5,000 characters',
        'file' => $service,
        'old' => "        if (mb_strlen(\$body) > self::MAX_BODY) {\n            throw new TeamChatRefused(__('php/services/team_chat_service.message_too_long'));\n        }\n        if (\$body === (string) \$message->body) {",
        'new' => "        if (false) {\n            throw new TeamChatRefused(__('php/services/team_chat_service.message_too_long'));\n        }\n        if (\$body === (string) \$message->body) {",
        'tests' => [['file' => $test, 'filter' => 'closes editing 15 minutes after sending']],
    ],
    [
        'id' => 'TC-M59',
        'name' => 'a Vyrobel support session searches staff conversations',
        'file' => $controller,
        'old' => "    public function search(Request \$request, string \$conversation): JsonResponse\n    {\n        if (BreakGlass::hasStash(\$request)) {",
        'new' => "    public function search(Request \$request, string \$conversation): JsonResponse\n    {\n        if (false) {",
        'tests' => [['file' => $test, 'filter' => 'keeps team chat closed to a Vyrobel support session']],
    ],
];

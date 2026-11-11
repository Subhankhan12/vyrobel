<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\TeamChatRefused;
use App\Http\Controllers\Controller;
use App\Models\TeamConversation;
use App\Models\TeamConversationMember;
use App\Models\TeamMessage;
use App\Models\User;
use App\Services\TeamChatService;
use App\Support\Messaging\ChatFiles;
use App\Support\Notifications\NotificationFeed;
use App\Support\Platform\BreakGlass;
use App\Support\TeamChat\TeamChatFiles;
use App\Support\TeamChat\TeamChatReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * TEAM CHAT — the page's live half (2026-10-06, `docs/TEAM-CHAT-DESIGN.md`): the poll, start, send and mark-read, all JSON.
 *
 * ⚠️ JSON ERRORS ARE WRITTEN HERE: the app renders JSON errors only under `api/*`, so a refusal is answered in words with
 * its status (422), and a conversation the person is not in is a JSON 404 — exactly as if it did not exist.
 * ⚠️ A VYROBEL SUPPORT (break-glass) SESSION borrows a staff member's view, not their conversations: every read here is a
 * 404 for it (its writes are already refused by RefuseWritesDuringBreakGlass).
 */
final class TeamChatController extends Controller
{
    public function __construct(private TeamChatService $chat, private TeamChatReader $reader) {}

    /** What changed: the person's conversations, and — for the open one — new messages and who has read how far. */
    public function poll(Request $request): JsonResponse
    {
        if (BreakGlass::hasStash($request)) {
            return $this->notFound();
        }
        $user = $this->user($request);
        $after = $this->ulidOrNull($request->query('after'));
        $before = $this->ulidOrNull($request->query('before'));
        // the SERVER's clock, taken before anything is read: the page hands it back as `since`, so what changes while this
        // answer is being built is found by the next poll (and the 30 seconds of look-back cover a write not yet visible)
        $serverTime = now()->getTimestamp();
        $since = $this->epochOrNull($request->query('since'), $serverTime);

        $open = $this->conversationFor($user, $request->query('c'));

        return response()->json([
            'conversations' => $this->reader->conversations($user),
            'unread_total' => $this->reader->unreadTotal($user),
            'notifications' => $this->unreadNotifications($user),   // the bell's own count, so it moves with the chat
            'messages' => $open !== null ? $this->reader->messages($open, $user, $after, $before, $since) : [],
            'reads' => $open !== null ? $this->reader->reads($open, $user) : [],
            // who is typing in the open conversation (a few seconds old at most) and who of the people you talk to is online
            // now — null when sessions are not kept in the database (the page then shows no presence at all)
            'typing' => $open !== null ? $this->reader->typing($open, $user) : [],
            'online' => $this->reader->online($user),
            'server_time' => $serverTime,
        ]);
    }

    /** Open a conversation: one teammate → your direct conversation with them; two or more → a new group. */
    public function start(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $data = Validator::make($request->all(), [
            'members' => ['required', 'array', 'min:1', 'max:'.TeamChatService::MAX_MEMBERS],
            'members.*' => ['string', 'max:40'],
            'title' => ['nullable', 'string', 'max:80'],
        ], [
            'members.required' => __('php/http/controllers/operator/team_chat_controller.choose_members'),
            'members.min' => __('php/http/controllers/operator/team_chat_controller.choose_members'),
            'members.max' => __('php/http/controllers/operator/team_chat_controller.group_max_people', ['limit' => TeamChatService::MAX_MEMBERS]),
            'title.max' => __('php/http/controllers/operator/team_chat_controller.group_name_max'),
        ]);
        if ($data->fails()) {
            return $this->refused((string) $data->errors()->first());
        }

        try {
            $members = array_values(array_map('strval', (array) $request->input('members')));
            $conversation = $this->chat->start($user, $members, $request->string('title')->toString() ?: null);
        } catch (TeamChatRefused $e) {
            return $this->refused($e->getMessage());
        }

        $row = collect($this->reader->conversations($user))->firstWhere('id', (string) $conversation->getKey());

        return response()->json(['conversation' => $row], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    /** Send a message — text, up to five files, a voice note — and get it back as the page shows it. */
    public function send(Request $request, string $conversation): JsonResponse
    {
        $user = $this->user($request);
        $open = $this->conversationFor($user, $conversation);
        if ($open === null) {
            return $this->notFound();
        }

        // the size this server takes (ours, or PHP's own ceiling when lower) — and said in those words
        $mb = ChatFiles::limits()['mb'];
        $data = Validator::make($request->all(), [
            'body' => ['nullable', 'string'],
            'files' => ['nullable', 'array', 'max:'.TeamChatFiles::MAX_FILES],
            'files.*' => ['file', 'max:'.($mb * 1024), 'mimes:'.TeamChatFiles::FILE_EXTENSIONS],
            'voice' => ['nullable', 'file', 'max:'.($mb * 1024), 'mimes:'.TeamChatFiles::VOICE_EXTENSIONS],
            'voice_seconds' => ['nullable', 'integer'],
        ], [
            'files.max' => __('php/http/controllers/operator/team_chat_controller.files_max', ['limit' => TeamChatFiles::MAX_FILES]),
            'files.*.max' => __('php/http/controllers/operator/team_chat_controller.file_too_big', ['size' => $mb]),
            'files.*.mimes' => __('php/http/controllers/operator/team_chat_controller.file_types'),
            'files.*.file' => __('php/http/controllers/operator/team_chat_controller.file_unreadable', ['size' => $mb]),
            'voice.max' => __('php/http/controllers/operator/team_chat_controller.voice_too_big', ['size' => $mb]),
            'voice.mimes' => __('php/http/controllers/operator/team_chat_controller.voice_unreadable'),
            'voice.file' => __('php/http/controllers/operator/team_chat_controller.voice_unreadable'),
        ]);
        if ($data->fails()) {
            return $this->refused((string) $data->errors()->first());
        }

        /** @var list<UploadedFile> $files */
        $files = array_values(array_filter((array) $request->file('files', []), fn ($f): bool => $f instanceof UploadedFile));
        $voice = $request->file('voice');
        $seconds = $request->filled('voice_seconds') ? (int) $request->input('voice_seconds') : null;

        try {
            $message = $this->chat->send($open, $user, $request->input('body'), $files, $voice instanceof UploadedFile ? $voice : null, $seconds);
        } catch (TeamChatRefused $e) {
            return $this->refused($e->getMessage());
        }

        return response()->json(['message' => $this->reader->messageRow($message, $user)], 201);
    }

    /** Change the words of your own message (within the window): the message as the page shows it, or the reason in words. */
    public function edit(Request $request, string $message): JsonResponse
    {
        $user = $this->user($request);
        $found = $this->messageFor($user, $message);
        if ($found === null) {
            return $this->notFound(__('php/http/controllers/operator/team_chat_controller.message_not_found'));
        }
        $data = Validator::make($request->all(), ['body' => ['nullable', 'string']]);
        if ($data->fails()) {
            return $this->refused((string) $data->errors()->first());
        }

        try {
            $edited = $this->chat->edit($found, $user, $request->input('body'));
        } catch (TeamChatRefused $e) {
            return $this->refused($e->getMessage());
        }

        return response()->json(['message' => $this->reader->messageRow($edited, $user)]);
    }

    /** Delete your own message: it stays as a tombstone ("This message was deleted"). */
    public function destroy(Request $request, string $message): JsonResponse
    {
        $user = $this->user($request);
        $found = $this->messageFor($user, $message);
        if ($found === null) {
            return $this->notFound(__('php/http/controllers/operator/team_chat_controller.message_not_found'));
        }

        try {
            $deleted = $this->chat->delete($found, $user);
        } catch (TeamChatRefused $e) {
            return $this->refused($e->getMessage());
        }

        return response()->json(['message' => $this->reader->messageRow($deleted, $user)]);
    }

    /** Toggle your emoji reaction on a message (the same tap again removes it): the message as the page shows it. */
    public function react(Request $request, string $message): JsonResponse
    {
        $user = $this->user($request);
        $found = $this->messageFor($user, $message);
        if ($found === null) {
            return $this->notFound(__('php/http/controllers/operator/team_chat_controller.message_not_found'));
        }
        $data = Validator::make($request->all(), ['emoji' => ['required', 'string', 'max:16']]);
        if ($data->fails()) {
            return $this->refused(__('php/http/controllers/operator/team_chat_controller.choose_reaction'));
        }

        try {
            $this->chat->react($found, $user, (string) $request->input('emoji'));
        } catch (TeamChatRefused $e) {
            return $this->refused($e->getMessage());
        }

        return response()->json(['message' => $this->reader->messageRow($found->load('media', 'sender', 'reactions.user:id,name'), $user)]);
    }

    /** "I am typing" — recorded for a few seconds; no body, nothing is stored but the moment. */
    public function typing(Request $request, string $conversation): JsonResponse
    {
        $user = $this->user($request);
        $open = $this->conversationFor($user, $conversation);
        if ($open === null) {
            return $this->notFound();
        }

        try {
            $this->chat->typing($open, $user);
        } catch (TeamChatRefused $e) {
            return $this->refused($e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Search one conversation (members only; at least two characters; the newest 30 and how many matched in all).
     * ⚠️ A GET that writes nothing. A Vyrobel support session reads no team chat, so it is a 404 here too.
     */
    public function search(Request $request, string $conversation): JsonResponse
    {
        if (BreakGlass::hasStash($request)) {
            return $this->notFound();
        }
        $user = $this->user($request);
        $open = $this->conversationFor($user, $conversation);
        if ($open === null) {
            return $this->notFound();
        }
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < TeamChatReader::SEARCH_MIN) {
            return $this->refused(__('php/http/controllers/operator/team_chat_controller.search_too_short', ['min' => TeamChatReader::SEARCH_MIN]));
        }
        if (mb_strlen($term) > 100) {
            return $this->refused(__('php/http/controllers/operator/team_chat_controller.search_too_long'));
        }

        $found = $this->reader->search($open, $user, $term);
        $shown = count($found['results']);
        // the count in words, in the reader's language (a script cannot pluralise for every language)
        $summary = $found['total'] > $shown
            ? trans_choice('php/http/controllers/operator/team_chat_controller.search_summary_capped', $found['total'], ['shown' => $shown, 'count' => $found['total']])
            : trans_choice('php/http/controllers/operator/team_chat_controller.search_summary', $found['total'], ['count' => $found['total']]);

        return response()->json($found + ['q' => $term, 'summary' => $summary]);
    }

    /** You have read the conversation (up to the message the page last showed you, when it says). */
    public function read(Request $request, string $conversation): JsonResponse
    {
        $user = $this->user($request);
        $open = $this->conversationFor($user, $conversation);
        if ($open === null) {
            return $this->notFound();
        }
        $this->chat->markRead($open, $user, $this->ulidOrNull($request->input('upto')));

        return response()->json(['unread_total' => $this->reader->unreadTotal($user), 'notifications' => $this->unreadNotifications($user)]);
    }

    /** The bell's own count — the feed's one rule ({@see NotificationFeed::unreadCount()}). */
    private function unreadNotifications(User $user): int
    {
        return NotificationFeed::unreadCount($user);
    }

    /** The conversation, if the person is in it — otherwise null (the caller answers 404 or "nothing open"). */
    private function conversationFor(User $user, mixed $id): ?TeamConversation
    {
        $id = $this->ulidOrNull($id);
        if ($id === null) {
            return null;
        }
        $isMember = TeamConversationMember::query()->where('conversation_id', $id)->where('user_id', $user->getKey())->exists();

        return $isMember ? TeamConversation::query()->find($id) : null;
    }

    /**
     * The message, if it is in one of the person's own conversations — otherwise null (a 404, as if it did not exist).
     * ⚠️ A plain string id, never route-model binding: a scoped model must not bind before the tenant scope is set.
     */
    private function messageFor(User $user, mixed $id): ?TeamMessage
    {
        $id = $this->ulidOrNull($id);
        $message = $id !== null ? TeamMessage::query()->find($id) : null;
        if (! $message instanceof TeamMessage) {
            return null;
        }
        $isMember = TeamConversationMember::query()->where('conversation_id', $message->conversation_id)->where('user_id', $user->getKey())->exists();

        return $isMember ? $message : null;
    }

    /** A moment the page was given by this server (epoch seconds) and hands back — never in the future, never absurdly old. */
    private function epochOrNull(mixed $value, int $now): ?int
    {
        if (! is_string($value) || preg_match('/^\d{9,11}$/', $value) !== 1) {
            return null;
        }
        $at = (int) $value;

        return min($at, $now);
    }

    private function ulidOrNull(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/i', $value) === 1 ? strtolower($value) : null;
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function refused(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 422);
    }

    private function notFound(?string $message = null): JsonResponse
    {
        return response()->json(['message' => $message ?? __('php/http/controllers/operator/team_chat_controller.conversation_not_found')], 404);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\TeamChatRefused;
use App\Http\Controllers\Controller;
use App\Models\TeamConversation;
use App\Models\TeamConversationMember;
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

        $open = $this->conversationFor($user, $request->query('c'));

        return response()->json([
            'conversations' => $this->reader->conversations($user),
            'unread_total' => $this->reader->unreadTotal($user),
            'notifications' => $this->unreadNotifications($user),   // the bell's own count, so it moves with the chat
            'messages' => $open !== null ? $this->reader->messages($open, $user, $after, $before) : [],
            'reads' => $open !== null ? $this->reader->reads($open, $user) : [],
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
            'members.required' => 'Choose who to message.',
            'members.min' => 'Choose who to message.',
            'members.max' => 'A group can have up to '.TeamChatService::MAX_MEMBERS.' people.',
            'title.max' => 'A group name can be up to 80 characters.',
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
            'files.max' => 'Send up to '.TeamChatFiles::MAX_FILES.' files at a time.',
            'files.*.max' => 'Each file can be up to '.$mb.' MB.',
            'files.*.mimes' => 'Send pictures (JPG, PNG, WebP, GIF) or documents (PDF, Word, Excel, CSV, text).',
            'files.*.file' => 'That file could not be read — files can be up to '.$mb.' MB. Choose it again.',
            'voice.max' => 'A voice note can be up to '.$mb.' MB.',
            'voice.mimes' => 'That voice note could not be read — record it again.',
            'voice.file' => 'That voice note could not be read — record it again.',
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

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'That conversation was not found.'], 404);
    }
}

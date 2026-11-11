<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TeamChatRefused;
use App\Models\TeamConversation;
use App\Models\TeamConversationMember;
use App\Models\TeamMessage;
use App\Models\TeamMessageReaction;
use App\Models\TeamTyping;
use App\Models\User;
use App\Notifications\Operator\TeamMessageNotification;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Messaging\ChatFiles;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Schedule\StaffRoster;
use App\Support\TeamChat\TeamChatFiles;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * TEAM CHAT — the ONE writer (2026-10-06, `docs/TEAM-CHAT-DESIGN.md`). Conversations between the people who work in a
 * workspace: the account owner and the staff ({@see StaffRoster} — an active membership and a back-office role).
 *
 * ⚠️ MEMBERS ONLY, HERE AS WELL AS AT THE CONTROLLER: a message is written only by a member of its conversation, and a
 * conversation only ever holds back-office staff of this workspace — never a unit owner, a guest, or someone elsewhere.
 * ⚠️ The events say WHAT happened (ids and counts), never what was written: the text stays in `team_messages`.
 */
final class TeamChatService
{
    public const MAX_BODY = 5000;

    public const MAX_MEMBERS = 30;

    /** A person counts as "typing" for this long after their last keystroke was posted. */
    public const TYPING_SECONDS = 6;

    /** How long after sending an author may still change the words. After it, delete is the only change left. */
    public const EDIT_WINDOW_MINUTES = 15;

    public function __construct(
        private DomainEventRecorder $events,
        private AuditEventRecorder $audit,
        private StaffRoster $roster,
        private TeamChatFiles $files,
        private OperatorWorkspace $workspace,
    ) {}

    /**
     * Open a conversation with these teammates: one person → the direct conversation the two of you share (made once,
     * whoever starts it); two or more → a new group.
     *
     * @param  list<string>  $memberIds
     */
    public function start(User $by, array $memberIds, ?string $title = null): TeamConversation
    {
        $this->refuseWhilePaused();
        $staff = $this->roster->staffUserIds();
        $others = array_values(array_unique(array_diff(array_map('strval', $memberIds), [(string) $by->getKey()])));

        if (! in_array((string) $by->getKey(), $staff, true) || $others === [] || array_diff($others, $staff) !== []) {
            throw new TeamChatRefused(__('php/services/team_chat_service.choose_people'));
        }
        if (count($others) > self::MAX_MEMBERS) {
            throw new TeamChatRefused(__('php/services/team_chat_service.group_too_large', ['max' => self::MAX_MEMBERS]));
        }

        return count($others) === 1 ? $this->direct($by, $others[0]) : $this->group($by, $others, $title);
    }

    /**
     * Send a message: text, up to five files, and/or one voice note.
     *
     * @param  list<UploadedFile>  $files
     */
    public function send(TeamConversation $conversation, User $from, ?string $body, array $files = [], ?UploadedFile $voice = null, ?int $voiceSeconds = null): TeamMessage
    {
        $this->member($conversation, $from);
        $this->refuseWhilePaused();
        $body = $body !== null ? trim(str_replace("\r\n", "\n", $body)) : '';
        if ($body === '' && $files === [] && $voice === null) {
            throw new TeamChatRefused(__('php/services/team_chat_service.empty_message'));
        }
        if (mb_strlen($body) > self::MAX_BODY) {
            throw new TeamChatRefused(__('php/services/team_chat_service.message_too_long'));
        }

        $written = [];
        try {
            return DB::transaction(function () use ($conversation, $from, $body, $files, $voice, $voiceSeconds, &$written): TeamMessage {
                $message = TeamMessage::create([
                    'conversation_id' => $conversation->getKey(),
                    'user_id' => $from->getKey(),
                    'body' => $body !== '' ? $body : null,
                ]);

                foreach ($files as $i => $file) {
                    $written[] = $this->files->store($message, $file, TeamChatFiles::FILES, $i)->path;
                }
                if ($voice !== null) {
                    $written[] = $this->files->store($message, $voice, TeamChatFiles::VOICE, count($files), ChatFiles::seconds($voiceSeconds))->path;
                }

                $conversation->forceFill(['last_message_at' => $message->created_at])->save();
                // sending ends "is typing…" at once — the message itself is the answer
                TeamTyping::query()->where('conversation_id', $conversation->getKey())->where('user_id', $from->getKey())->delete();
                // ⚠️ Sending reads nothing (the independent review, 2026-10-06): moving the sender's read position to their own
                // message marked as read a colleague's message they had not yet been shown. Your own never count as unread.

                $facts = ['conversation_id' => (string) $conversation->getKey(), 'files' => count($files), 'voice' => $voice !== null];
                $this->audit->record('team_chat.message_sent', $message, null, $facts);
                $this->events->record('team_chat.message_sent', $message, $facts);

                return $message->load('media', 'sender', 'reactions.user:id,name');
            });
        } catch (Throwable $e) {
            foreach ($written as $path) {   // a refused message leaves no file behind
                Storage::disk(TeamChatFiles::DISK)->delete((string) $path);
            }
            throw $e;
        }
    }

    /**
     * Change the words of YOUR OWN message, for {@see EDIT_WINDOW_MINUTES} minutes after it was sent. The page then shows
     * "edited"; the old words are NOT kept (no history is stored). Files stay as they were. A change that would leave the
     * message with nothing in it is refused — deleting is the way to remove it.
     *
     * ⚠️ The events say THAT it was edited and how long it now is, never the words (the audit trail is not a second copy
     * of the conversation).
     */
    public function edit(TeamMessage $message, User $by, ?string $body): TeamMessage
    {
        $conversation = $this->conversationOf($message);
        $this->member($conversation, $by);
        $this->refuseWhilePaused();
        $this->refuseUnlessAuthor($message, $by);
        if ($message->deleted_at !== null) {
            throw new TeamChatRefused(__('php/services/team_chat_service.already_deleted'));
        }
        if ($message->created_at->copy()->addMinutes(self::EDIT_WINDOW_MINUTES)->isPast()) {
            throw new TeamChatRefused(__('php/services/team_chat_service.edit_window_over', ['minutes' => self::EDIT_WINDOW_MINUTES]));
        }

        $body = $body !== null ? trim(str_replace("\r\n", "\n", $body)) : '';
        $files = $message->media()->whereIn('collection', [TeamChatFiles::FILES, TeamChatFiles::VOICE])->count();
        if ($body === '' && $files === 0) {
            throw new TeamChatRefused(__('php/services/team_chat_service.edit_to_nothing'));
        }
        if (mb_strlen($body) > self::MAX_BODY) {
            throw new TeamChatRefused(__('php/services/team_chat_service.message_too_long'));
        }
        if ($body === (string) $message->body) {
            return $message->load('media', 'sender', 'reactions.user:id,name');   // nothing changed: no "edited" label, no event
        }

        return DB::transaction(function () use ($message, $conversation, $body, $files): TeamMessage {
            $message->forceFill(['body' => $body !== '' ? $body : null, 'edited_at' => now()])->save();

            $facts = ['conversation_id' => (string) $conversation->getKey(), 'characters' => mb_strlen($body), 'files' => $files];
            $this->audit->record('team_chat.message_edited', $message, null, $facts);
            $this->events->record('team_chat.message_edited', $message, $facts);

            return $message->load('media', 'sender', 'reactions.user:id,name');
        });
    }

    /**
     * Delete YOUR OWN message, at any time. It stays as a TOMBSTONE — the row, without its words, files or reactions — so the
     * order, the read positions and "Seen" keep pointing at real messages; every screen then shows "This message was
     * deleted". Deleting twice changes nothing.
     */
    public function delete(TeamMessage $message, User $by): TeamMessage
    {
        $conversation = $this->conversationOf($message);
        $this->member($conversation, $by);
        $this->refuseWhilePaused();
        $this->refuseUnlessAuthor($message, $by);
        if ($message->deleted_at !== null) {
            return $message;
        }

        return DB::transaction(function () use ($message, $conversation): TeamMessage {
            $files = $this->files->erase($message);
            TeamMessageReaction::query()->where('message_id', $message->getKey())->delete();
            $message->forceFill(['body' => null, 'edited_at' => null, 'redacted_at' => null, 'deleted_at' => now()])->save();

            $facts = ['conversation_id' => (string) $conversation->getKey(), 'files' => $files];
            $this->audit->record('team_chat.message_deleted', $message, null, $facts);
            $this->events->record('team_chat.message_deleted', $message, $facts);

            return $message->load('media', 'sender', 'reactions.user:id,name');
        });
    }

    /**
     * Toggle YOUR emoji reaction on a message — the same tap adds it, and again removes it. Only an emoji on the fixed
     * allow-list ({@see InboxService::REACTIONS}) is accepted, so no free text can reach a screen. Returns whether it is
     * now on. The message is touched, so a screen that already holds it receives the change on its next poll.
     *
     * ⚠️ A FLAGGED EXCEPTION to "an audit row AND a domain event": a reaction records an AUDIT row only, exactly as the
     * Inbox's own reactions do ({@see InboxService::toggleReaction()}) — an emoji tap is not a business fact worth an outbox
     * row, and one per tap would be noise. Nobody is notified of a reaction.
     */
    public function react(TeamMessage $message, User $by, string $emoji): bool
    {
        $conversation = $this->conversationOf($message);
        $this->member($conversation, $by);
        $this->refuseWhilePaused();
        if (! in_array($emoji, InboxService::REACTIONS, true)) {
            throw new TeamChatRefused(__('php/services/team_chat_service.reaction_not_allowed'));
        }
        if ($message->deleted_at !== null) {
            throw new TeamChatRefused(__('php/services/team_chat_service.already_deleted'));
        }

        return DB::transaction(function () use ($message, $conversation, $by, $emoji): bool {
            $existing = TeamMessageReaction::query()
                ->where('message_id', $message->getKey())->where('user_id', $by->getKey())->where('emoji', $emoji)->first();
            $facts = ['conversation_id' => (string) $conversation->getKey(), 'emoji' => $emoji];

            if ($existing instanceof TeamMessageReaction) {
                $existing->delete();
                $this->audit->record('team_chat.reaction_removed', $message, $facts, null);
                $on = false;
            } else {
                try {
                    TeamMessageReaction::create(['message_id' => $message->getKey(), 'user_id' => $by->getKey(), 'emoji' => $emoji]);
                    $this->audit->record('team_chat.reaction_added', $message, null, $facts);
                } catch (UniqueConstraintViolationException) {
                    // the same tap arrived twice at the same moment — the other one made it; it is on, once
                }
                $on = true;
            }
            $message->touch();

            return $on;
        });
    }

    /**
     * "I am typing" — recorded as the moment, once a few seconds at most (the page decides how often). The poll shows the
     * person for {@see TYPING_SECONDS} after it; sending clears it. There are no websockets here: this is the polling
     * design's honest version, and a stale row is simply ignored. Refused while the workspace is paused, like every write.
     */
    public function typing(TeamConversation $conversation, User $by): void
    {
        $this->member($conversation, $by);
        $this->refuseWhilePaused();

        $where = ['conversation_id' => $conversation->getKey(), 'user_id' => $by->getKey()];
        $updated = TeamTyping::query()->where($where)->update(['typed_at' => now(), 'updated_at' => now()]);
        if ($updated === 0) {
            try {
                TeamTyping::create($where + ['typed_at' => now()]);
            } catch (UniqueConstraintViolationException) {
                TeamTyping::query()->where($where)->update(['typed_at' => now(), 'updated_at' => now()]);   // both made it at once
            }
        }
    }

    /**
     * You have read the conversation — up to the message you last saw ($upTo, when the page says which), otherwise up to
     * its latest — and its bell entry is read too. A read position only ever moves forward.
     */
    public function markRead(TeamConversation $conversation, User $user, ?string $upTo = null): void
    {
        $member = $this->member($conversation, $user);
        $latest = TeamMessage::query()->where('conversation_id', $conversation->getKey())
            ->when($upTo !== null, fn ($q) => $q->where('id', '<=', $upTo))
            ->max('id');
        if (is_string($latest)) {
            // forward only, in ONE statement — two reads at once (the poll and the page) can never move it back
            TeamConversationMember::query()->whereKey($member->getKey())
                ->where(fn ($q) => $q->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $latest))
                ->update(['last_read_message_id' => $latest, 'last_read_at' => now()]);
        }

        $user->unreadNotifications()
            ->where('type', TeamMessageNotification::class)
            ->where('data->conversation_id', (string) $conversation->getKey())
            ->update(['read_at' => now()]);
    }

    /** The person's membership of this conversation — or a 404, as if it did not exist. */
    public function member(TeamConversation $conversation, User $user): TeamConversationMember
    {
        $member = TeamConversationMember::query()
            ->where('conversation_id', $conversation->getKey())->where('user_id', $user->getKey())->first();
        abort_unless($member instanceof TeamConversationMember, 404);

        return $member;
    }

    private function conversationOf(TeamMessage $message): TeamConversation
    {
        $conversation = $message->conversation;
        abort_unless($conversation instanceof TeamConversation, 404);

        return $conversation;
    }

    /** Only the person who wrote a message may change or delete it (a member who did not is told so, in words). */
    private function refuseUnlessAuthor(TeamMessage $message, User $by): void
    {
        if ((string) $message->user_id !== (string) $by->getKey()) {
            throw new TeamChatRefused(__('php/services/team_chat_service.only_author'));
        }
    }

    /** A paused workspace is read-only for every write, as the Inbox is (a read position is not a write). */
    private function refuseWhilePaused(): void
    {
        if ($this->workspace->isReadOnly()) {
            throw new TeamChatRefused(__('php/services/team_chat_service.workspace_paused'));
        }
    }

    private function direct(User $by, string $otherId): TeamConversation
    {
        $key = TeamConversation::directKey((string) $by->getKey(), $otherId);
        $existing = TeamConversation::query()->where('direct_key', $key)->first();
        if ($existing instanceof TeamConversation) {
            return $existing;
        }

        try {
            return $this->create($by, [$otherId], TeamConversation::DIRECT, null, $key);
        } catch (UniqueConstraintViolationException) {
            // both started it at the same moment — the other request made it; open that one
            return TeamConversation::query()->where('direct_key', $key)->firstOrFail();
        }
    }

    /** @param list<string> $others */
    private function group(User $by, array $others, ?string $title): TeamConversation
    {
        $title = $title !== null ? trim($title) : '';

        return $this->create($by, $others, TeamConversation::GROUP, $title !== '' ? mb_substr($title, 0, 80) : null, null);
    }

    /** @param list<string> $others */
    private function create(User $by, array $others, string $kind, ?string $title, ?string $directKey): TeamConversation
    {
        return DB::transaction(function () use ($by, $others, $kind, $title, $directKey): TeamConversation {
            $conversation = TeamConversation::create([
                'kind' => $kind, 'title' => $title, 'direct_key' => $directKey, 'created_by' => $by->getKey(),
            ]);
            foreach (array_merge([(string) $by->getKey()], $others) as $userId) {
                TeamConversationMember::create(['conversation_id' => $conversation->getKey(), 'user_id' => $userId]);
            }

            $facts = ['kind' => $kind, 'members' => count($others) + 1];
            $this->audit->record('team_chat.conversation_started', $conversation, null, $facts);
            $this->events->record('team_chat.conversation_started', $conversation, $facts);

            return $conversation;
        });
    }
}

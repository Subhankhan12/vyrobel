<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TeamChatRefused;
use App\Models\TeamConversation;
use App\Models\TeamConversationMember;
use App\Models\TeamMessage;
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
            throw new TeamChatRefused('Choose people who work in this workspace.');
        }
        if (count($others) > self::MAX_MEMBERS) {
            throw new TeamChatRefused('A group can have up to '.self::MAX_MEMBERS.' people.');
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
            throw new TeamChatRefused('Write a message, or add a file or a voice note.');
        }
        if (mb_strlen($body) > self::MAX_BODY) {
            throw new TeamChatRefused('A message can be up to 5,000 characters.');
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
                // ⚠️ Sending reads nothing (the independent review, 2026-10-06): moving the sender's read position to their own
                // message marked as read a colleague's message they had not yet been shown. Your own never count as unread.

                $facts = ['conversation_id' => (string) $conversation->getKey(), 'files' => count($files), 'voice' => $voice !== null];
                $this->audit->record('team_chat.message_sent', $message, null, $facts);
                $this->events->record('team_chat.message_sent', $message, $facts);

                return $message->load('media', 'sender');
            });
        } catch (Throwable $e) {
            foreach ($written as $path) {   // a refused message leaves no file behind
                Storage::disk(TeamChatFiles::DISK)->delete((string) $path);
            }
            throw $e;
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

    /** A paused workspace is read-only for every write, as the Inbox is (a read position is not a write). */
    private function refuseWhilePaused(): void
    {
        if ($this->workspace->isReadOnly()) {
            throw new TeamChatRefused('This workspace is paused — messages can be read, not sent.');
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

<?php

declare(strict_types=1);

namespace App\Support\TeamChat;

use App\Models\Media;
use App\Models\TeamConversationMember;
use App\Models\TeamMessage;
use App\Models\User;
use App\Services\TeamChatService;
use App\Support\Messaging\ChatFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * A TEAM CHAT FILE, AND WHO MAY OPEN IT — the one definition (team chat, 2026-10-06). Pictures and documents are the
 * `attachments` collection, a voice note the `voice` collection, both on the PRIVATE disk (the DEC-m rule for message
 * files: "let only people in that conversation open them, after signing in").
 *
 * ⚠️ WHO MAY OPEN ONE IS THE CONVERSATION'S MEMBERSHIP — not the organisation. A file outside the viewer's own
 * conversations is NOT FOUND (a 404, never a 403 that would confirm it exists), in this workspace or any other.
 *
 * ⚠️ THE FILE'S OWN BYTES DECIDE how it is served (never the name it was sent with): the four picture types are shown in
 * place, a voice note is played as audio — with byte ranges, which Safari asks for before it plays — and everything else
 * downloads under its own name. `private, no-store` and `nosniff` on all of them.
 */
final class TeamChatFiles
{
    public const FILES = 'attachments';

    public const VOICE = 'voice';

    public const DISK = 'local';

    // what a file and a voice note may be — the one definition, shared with the owner chat and the Inbox ({@see ChatFiles})
    public const FILE_EXTENSIONS = ChatFiles::FILE_EXTENSIONS;

    public const VOICE_EXTENSIONS = ChatFiles::VOICE_EXTENSIONS;

    public const MAX_FILES = ChatFiles::MAX_FILES;

    public const MAX_KB = ChatFiles::MAX_KB;

    public const MAX_VOICE_SECONDS = ChatFiles::MAX_VOICE_SECONDS;

    /** Keep one file on a message. */
    public function store(TeamMessage $message, UploadedFile $file, string $collection, int $order, ?int $seconds = null): Media
    {
        $name = Str::lower((string) Str::ulid()).'.'.($file->guessExtension() ?: 'bin');
        // a literal folder name, so PublicMediaTest's scan of every upload folder can see it (it is in NOT_SERVED)
        $path = $file->storeAs('team-chat/'.$message->conversation_id.'/'.$message->getKey(), $name, self::DISK);
        if ($path === false) {
            throw new RuntimeException('A file could not be saved.');
        }

        try {
            /** @var Media $media */
            $media = $message->media()->create([
                'organization_id' => $message->organization_id,
                'disk' => self::DISK,
                'path' => $path,
                'mime' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'collection' => $collection,
                'sort_order' => $order,
                'alt' => $collection === self::VOICE ? 'Voice message' : ChatFiles::sentName($file->getClientOriginalName(), 'Attachment'),
                'meta' => $seconds !== null ? ['seconds' => $seconds] : null,
            ]);
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($path);   // a file with no record is never left on the disk
            throw $e;
        }

        return $media;
    }

    /**
     * A deleted message's files go with it — the rows AND the bytes on the disk (D-TC-LATER, 2026-11-11). Returns how many
     * files there were. The bytes are removed once the delete is kept: a rolled-back delete must not leave rows pointing at
     * nothing. Called inside {@see TeamChatService::delete()}'s transaction.
     */
    public function erase(TeamMessage $message): int
    {
        $media = $message->media()->whereIn('collection', [self::FILES, self::VOICE])->get();
        if ($media->isEmpty()) {
            return 0;
        }
        Media::query()->whereKey($media->modelKeys())->forceDelete();
        $folder = 'team-chat/'.$message->conversation_id.'/'.$message->getKey();
        DB::afterCommit(function () use ($media, $folder): void {
            foreach ($media as $m) {
                Storage::disk((string) $m->disk)->delete((string) $m->path);
            }
            Storage::disk(self::DISK)->deleteDirectory($folder);
        });

        return $media->count();
    }

    /** The file behind this id, if it is in one of the viewer's own conversations — otherwise a 404. */
    public function find(string $mediaId, User $viewer): Media
    {
        $media = Media::query()
            ->whereIn('collection', [self::FILES, self::VOICE])
            ->where('mediable_type', (new TeamMessage)->getMorphClass())
            ->findOrFail($mediaId);                                                       // this organisation's
        $message = TeamMessage::query()->findOrFail((string) $media->mediable_id);

        abort_unless(TeamConversationMember::query()
            ->where('conversation_id', $message->conversation_id)
            ->where('user_id', $viewer->getKey())
            ->exists(), 404);

        return $media;
    }

    /** The file: a picture in place, a voice note as playable audio, anything else a download — never cached or sniffed. */
    public function response(Media $media): BinaryFileResponse
    {
        $disk = Storage::disk((string) $media->disk);
        $path = (string) $media->path;
        abort_unless($disk->fileExists($path), 404);

        $absolute = $disk->path($path);
        $type = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($absolute);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];

        $voice = ChatFiles::voiceType($type);
        if ($media->collection === self::VOICE && $voice !== null) {
            return response()->file($absolute, $headers + ['Content-Type' => $voice]);
        }
        if ($media->collection === self::FILES && in_array($type, ChatFiles::INLINE_PICTURES, true)) {
            return response()->file($absolute, $headers + ['Content-Type' => $type]);
        }

        return response()->download($absolute, $this->downloadName($media), $headers + ['Content-Type' => 'application/octet-stream']);
    }

    /**
     * A message's files as the page shows them.
     *
     * @return list<array{id: string, kind: string, name: string, size: string, url: string, seconds: int|null}>
     */
    public function rows(TeamMessage $message): array
    {
        $out = [];
        foreach ($message->media->sortBy('sort_order') as $m) {
            if (! in_array($m->collection, [self::FILES, self::VOICE], true)) {
                continue;
            }
            $kind = $m->collection === self::VOICE ? 'voice'
                : (in_array((string) $m->mime, ChatFiles::INLINE_PICTURES, true) ? 'image' : 'file');
            $seconds = is_array($m->meta) && isset($m->meta['seconds']) ? (int) $m->meta['seconds'] : null;
            $out[] = [
                'id' => (string) $m->getKey(),
                'kind' => $kind,
                'name' => $kind === 'voice' ? __('php/support/team_chat/team_chat_files.voice_message') : ChatFiles::displayName((string) $m->alt, (string) $m->path),
                'size' => ChatFiles::humanSize((int) $m->size),
                'url' => route('app.inbox.team.file', (string) $m->getKey()),
                'seconds' => $seconds,
            ];
        }

        return $out;
    }

    /** The name it downloads under — the shared rule ({@see ChatFiles::displayName()}: its own words, the extension its bytes earned). */
    private function downloadName(Media $media): string
    {
        return ChatFiles::displayName((string) $media->alt, (string) $media->path, 'attachment');
    }
}

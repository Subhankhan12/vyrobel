<?php

declare(strict_types=1);

namespace App\Support\Inbox;

use App\Http\Controllers\MessageAttachmentController;
use App\Models\Guest;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageThread;
use App\Support\Messaging\ChatFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * A MESSAGE ATTACHMENT, AND WHO MAY OPEN IT — the ONE definition, used by the owner portal's conversation, the operator
 * inbox and the signed-in route that serves the file ({@see MessageAttachmentController}).
 *
 * ⚠️⚠️ HIS RULING, DEC-m (2026-09-30): "let only people in that conversation open them, after signing in". The files are
 * documents in a private owner–operator conversation. Before, they sat on the PUBLIC disk and were linked by their public
 * address: 🔬 production refuses that address (`/storage`, KNOWN-LIMITS L12p), so they never opened — and had it worked,
 * anyone holding a link could have opened one. Now they are stored on the private disk and opened only here.
 *
 * ⚠️ WHO IS "IN THE CONVERSATION" IS DECIDED BY THE THREAD, THROUGH THE SCOPES THE CONVERSATION ITSELF IS READ WITH —
 * never re-derived here. In the owner portal the owner scope limits threads to the viewer's own owner (a teammate of that
 * owner included, as on the Messages page); in the back office every thread of the organisation is the inbox's, behind
 * the inbox's own permission (`guests.view`, on the route). A thread outside that is NOT FOUND — a 404, not a 403 that
 * would confirm the file exists.
 */
final class MessageAttachments
{
    public const COLLECTION = 'attachments';

    /** A voice note (2026-10-06) — played as audio, never downloaded as a file ({@see ChatFiles}). */
    public const VOICE = 'voice';

    /** The one place new attachments are written: the PRIVATE disk (DEC-m). Older ones keep the disk their row records. */
    public const DISK = 'local';

    /** Image types shown in place; every other file — or an image whose bytes say otherwise — is a download. */
    private const INLINE = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * The attachment behind this id, if the CURRENT viewer's conversation holds it — otherwise a 404.
     */
    public function find(string $mediaId): Media
    {
        $media = Media::query()
            ->whereIn('collection', [self::COLLECTION, self::VOICE])
            ->where('mediable_type', (new Message)->getMorphClass())
            ->findOrFail($mediaId);                                             // organisation-scoped
        $message = Message::query()->findOrFail((string) $media->mediable_id);   // organisation-scoped

        // ⚠️ THE CONVERSATION DECIDES — the owner scope in /owner, the organisation in /app (see the class docblock).
        MessageThread::query()->findOrFail((string) $message->thread_id);

        return $media;
    }

    /**
     * The attachment behind this id, if it is in THIS guest's own conversation (2026-10-06 — the guest portal's door) —
     * otherwise a 404. A guest's conversation is theirs by `guest_id`, and never an owner's (`owner_id` is NULL).
     * ⚠️ Plain `query()`: the guest group runs inside the guest's own organisation (`SetTenantForGuestRequest`), as
     * `GuestMessagesController::owned()` does — dropping the scopes would also bring back deleted files and messages.
     */
    public function findForGuest(string $mediaId, Guest $guest): Media
    {
        $media = Media::query()
            ->whereIn('collection', [self::COLLECTION, self::VOICE])
            ->where('mediable_type', (new Message)->getMorphClass())
            ->findOrFail($mediaId);
        $message = Message::query()->findOrFail((string) $media->mediable_id);
        MessageThread::query()
            ->whereKey((string) $message->thread_id)
            ->where('guest_id', $guest->getKey())
            ->whereNull('owner_id')
            ->firstOrFail();

        return $media;
    }

    /**
     * Keep a file on a message — the ONE writer of message attachments (the owner portal's and the Inbox's replies): the
     * PRIVATE disk, a fresh name, the name it was sent with kept for the download. A voice note keeps its length.
     */
    public function store(Message $message, UploadedFile $file, string $collection, int $order, ?int $seconds = null): Media
    {
        $kept = $this->keep($file, (string) $message->getKey(), $collection);
        try {
            return $this->record($message, $kept, $collection, $order, $seconds);
        } catch (Throwable $e) {
            $this->discard([$kept]);   // a file with no record is never left on the disk
            throw $e;
        }
    }

    /**
     * The first half of {@see store()}: the file onto the private disk, before its message exists — the Inbox's reply
     * keeps its files FIRST, so a disk that refuses one means nothing is sent (2026-10-06, the independent review).
     *
     * @return array{path: string, mime: string, size: int, alt: string}
     */
    public function keep(UploadedFile $file, string $folder, string $collection): array
    {
        $name = (string) Str::ulid().'.'.($file->extension() ?: 'bin');
        $path = $file->storeAs('message-attachments/'.$folder, $name, self::DISK);
        if ($path === false) {
            throw new RuntimeException('A file could not be saved.');
        }

        return [
            'path' => $path,
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'alt' => $collection === self::VOICE ? 'Voice message' : ChatFiles::sentName($file->getClientOriginalName(), 'Attachment'),
        ];
    }

    /**
     * The second half: a kept file recorded on its message.
     *
     * @param  array{path: string, mime: string, size: int, alt: string}  $kept
     */
    public function record(Message $message, array $kept, string $collection, int $order, ?int $seconds = null): Media
    {
        return Media::create([
            'organization_id' => $message->organization_id,
            'mediable_type' => $message->getMorphClass(),
            'mediable_id' => $message->getKey(),
            'collection' => $collection,
            'disk' => self::DISK,
            'path' => $kept['path'],
            'mime' => $kept['mime'],
            'size' => $kept['size'],
            'sort_order' => $order,
            'is_cover' => false,
            'alt' => $kept['alt'],
            'meta' => $seconds !== null ? ['seconds' => $seconds] : null,
        ]);
    }

    /**
     * Kept files that will not be sent, off the disk.
     *
     * @param  list<array{path: string, mime: string, size: int, alt: string}>  $kept
     */
    public function discard(array $kept): void
    {
        foreach ($kept as $file) {
            Storage::disk(self::DISK)->delete($file['path']);
        }
    }

    /** The file itself: a photo in place, anything else a download under its own name — never cached, never sniffed. */
    public function response(Media $media): Response
    {
        $disk = Storage::disk((string) $media->disk);
        $path = (string) $media->path;
        abort_unless($disk->fileExists($path), 404);

        // the file's OWN bytes decide whether it may be shown in place (the disk's mimeType() falls back to the name)
        $type = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($disk->path($path));
        // a voice note plays — with byte ranges, which Safari asks for before it plays (2026-10-06)
        $voice = ChatFiles::voiceType($type);
        if ($media->collection === self::VOICE && $voice !== null) {
            return response()->file($disk->path($path), ['Content-Type' => $voice, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        }
        $inline = in_array($type, self::INLINE, true);

        return $disk->response($path, $this->downloadName($media), [
            'Content-Type' => $inline ? $type : 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }

    /**
     * What a conversation shows for a message's attachments — the same rows in the owner portal and the operator inbox,
     * each linking to that realm's own signed-in route.
     *
     * @return list<array{url: string, name: string, is_image: bool, size: string, kind: string, seconds: int|null}>
     */
    public function rows(Message $message, string $routeName): array
    {
        $out = [];
        foreach ($message->media->sortBy('sort_order') as $m) {
            if (! in_array($m->collection, [self::COLLECTION, self::VOICE], true)) {
                continue;
            }
            $voice = $m->collection === self::VOICE;
            $out[] = [
                'url' => route($routeName, (string) $m->getKey()),
                'name' => $voice ? 'Voice message' : ChatFiles::displayName((string) $m->alt, (string) $m->path),
                'is_image' => ! $voice && str_starts_with((string) $m->mime, 'image/'),
                'size' => $this->humanSize((int) $m->size),
                'kind' => $voice ? 'voice' : (str_starts_with((string) $m->mime, 'image/') ? 'image' : 'file'),
                'seconds' => $voice && is_array($m->meta) && isset($m->meta['seconds']) ? (int) $m->meta['seconds'] : null,
            ];
        }

        return $out;
    }

    /**
     * GDPR (2026-10-06): the files in a guest's conversations go with the guest — the rows and the files on the disk.
     * Called by {@see Guest::anonymize()}, inside its transaction.
     *
     * @param  iterable<string>  $threadIds
     */
    public function eraseInThreads(iterable $threadIds): void
    {
        $messageIds = Message::query()->whereIn('thread_id', collect($threadIds)->all())->pluck('id');
        if ($messageIds->isEmpty()) {
            return;
        }
        $media = Media::query()->where('mediable_type', (new Message)->getMorphClass())->whereIn('mediable_id', $messageIds)->get();
        Media::query()->whereKey($media->modelKeys())->forceDelete();
        // the files go once the erase is kept — a rolled-back erase must not leave records pointing at nothing
        DB::afterCommit(function () use ($media): void {
            foreach ($media as $m) {
                Storage::disk((string) $m->disk)->delete((string) $m->path);
            }
        });
    }

    /** The name it downloads under — the shared rule ({@see ChatFiles::displayName()}: its own words, the extension its bytes earned). */
    private function downloadName(Media $media): string
    {
        return ChatFiles::displayName((string) $media->alt, (string) $media->path, 'attachment');
    }

    /** A byte count as a compact human size. */
    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Inbox;

use App\Http\Controllers\MessageAttachmentController;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

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
            ->where('collection', self::COLLECTION)
            ->where('mediable_type', (new Message)->getMorphClass())
            ->findOrFail($mediaId);                                             // organisation-scoped
        $message = Message::query()->findOrFail((string) $media->mediable_id);   // organisation-scoped

        // ⚠️ THE CONVERSATION DECIDES — the owner scope in /owner, the organisation in /app (see the class docblock).
        MessageThread::query()->findOrFail((string) $message->thread_id);

        return $media;
    }

    /** The file itself: a photo in place, anything else a download under its own name — never cached, never sniffed. */
    public function response(Media $media): Response
    {
        $disk = Storage::disk((string) $media->disk);
        $path = (string) $media->path;
        abort_unless($disk->fileExists($path), 404);

        // the file's OWN bytes decide whether it may be shown in place (the disk's mimeType() falls back to the name)
        $type = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($disk->path($path));
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
     * @return list<array{url: string, name: string, is_image: bool, size: string}>
     */
    public function rows(Message $message, string $routeName): array
    {
        $out = [];
        foreach ($message->media as $m) {
            if ($m->collection !== self::COLLECTION) {
                continue;
            }
            $out[] = [
                'url' => route($routeName, (string) $m->getKey()),
                'name' => (string) ($m->alt ?: 'Attachment'),
                'is_image' => str_starts_with((string) $m->mime, 'image/'),
                'size' => $this->humanSize((int) $m->size),
            ];
        }

        return $out;
    }

    /** The name the file was sent with, made safe for a download header (no path separators, no control characters). */
    private function downloadName(Media $media): string
    {
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $media->alt);
        $name = trim(str_replace(['/', '\\'], '-', $name));

        return $name !== '' ? mb_substr($name, 0, 150) : 'attachment';
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

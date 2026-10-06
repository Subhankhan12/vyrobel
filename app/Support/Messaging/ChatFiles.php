<?php

declare(strict_types=1);

namespace App\Support\Messaging;

/**
 * WHAT A FILE IN A CONVERSATION MAY BE — the one definition (2026-10-06), read by team chat, the owner chat and the
 * Inbox's replies to guests and owners, so a picture or a voice note that one of them accepts the others accept too.
 *
 * ⚠️ Types are judged by the file's OWN bytes: Laravel's `mimes` rule checks the extension the detected type implies,
 * never the name it was sent with. 🔬 An audio-only WebM (Chrome's voice notes) reads as `video/webm` and Safari's MP4 as
 * `video/mp4` (PHP's finfo), so a voice note accepts those — through the voice field only, never as an ordinary file.
 */
final class ChatFiles
{
    /** Pictures and documents (the owner chat's list since GATE 2 of the owner messages). */
    public const FILE_EXTENSIONS = 'jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt';

    public const VOICE_EXTENSIONS = 'webm,weba,ogg,oga,opus,m4a,mp4,mp3,mpga,wav';

    public const MAX_FILES = 5;

    public const MAX_KB = 10240;

    public const MAX_VOICE_SECONDS = 180;

    /** Pictures shown in place; anything else downloads. */
    public const INLINE_PICTURES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** The audio type a voice note is played as, by what its bytes read as. */
    private const VOICE_TYPES = [
        'video/webm' => 'audio/webm', 'audio/webm' => 'audio/webm',
        'video/mp4' => 'audio/mp4', 'audio/mp4' => 'audio/mp4', 'audio/x-m4a' => 'audio/mp4',
        'audio/ogg' => 'audio/ogg', 'application/ogg' => 'audio/ogg', 'audio/opus' => 'audio/ogg',
        'audio/mpeg' => 'audio/mpeg', 'audio/x-wav' => 'audio/wav', 'audio/wav' => 'audio/wav',
    ];

    /** The audio type to serve a voice note as, or null when its bytes are not a voice note we play. */
    public static function voiceType(string $detected): ?string
    {
        return self::VOICE_TYPES[$detected] ?? null;
    }

    /** A voice note's length as the page reported it, kept within the cap (null when it said nothing). */
    public static function seconds(mixed $value): ?int
    {
        return is_numeric($value) ? max(1, min(self::MAX_VOICE_SECONDS, (int) $value)) : null;
    }

    /**
     * What one message may carry on THIS server, read where the page is served (the web server's PHP can differ from the
     * command line's): each file at most `mb` (our 10 MB, or PHP's own upload ceiling when lower) and all of it together at
     * most `post_bytes` (PHP's request ceiling, less room for the words). The pages check a message against these BEFORE
     * sending — past PHP's ceiling the files would arrive as nothing and the refusal would name the wrong cause (the
     * independent review, 2026-10-06).
     *
     * @return array{mb: int, post_bytes: int}
     */
    public static function limits(): array
    {
        $upload = self::iniBytes((string) ini_get('upload_max_filesize'));
        $file = $upload > 0 ? min(self::MAX_KB * 1024, $upload) : self::MAX_KB * 1024;
        $post = self::iniBytes((string) ini_get('post_max_size'));

        return [
            'mb' => max(1, intdiv($file, 1048576)),
            'post_bytes' => $post > 0 ? max(0, $post - 65536) : PHP_INT_MAX,
        ];
    }

    /** "8M" → bytes ("0" or nothing: no limit → 0). */
    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        $n = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }

    /** A file's size, short. */
    public static function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }

    /**
     * The name a file was sent with, kept safe for a header and a page: no path separators, no control characters and no
     * invisible formatting characters — ⚠️ a right-to-left override makes "invoice‮fdp.vbs" read as "invoicesbv.pdf".
     */
    public static function sentName(string $name, string $fallback): string
    {
        $name = (string) preg_replace('/[\x00-\x1F\x7F\p{Cf}]/u', '', $name);
        $name = trim(str_replace(['/', '\\'], '-', $name));

        return $name !== '' ? mb_substr($name, 0, 150) : $fallback;
    }

    /**
     * The name a file is shown and downloaded under — its own name, with the extension its BYTES earned (the stored
     * file's, decided by the detected type). A name that claims another kind keeps its words and loses the claim:
     * "invoice.vbs" holding text downloads as "invoice.txt" (the independent review, 2026-10-06). Within one kind
     * (`jpg`/`jpeg`, `txt`/`csv`) the sender's own name stands — a CSV stays a CSV.
     */
    public static function displayName(string $sent, string $storedPath, string $fallback = 'Attachment'): string
    {
        $name = self::sentName($sent, $fallback);
        $earned = strtolower((string) pathinfo($storedPath, PATHINFO_EXTENSION));
        $earned = $earned !== '' ? $earned : 'bin';
        $claimed = preg_match('/\.([A-Za-z0-9]{1,8})$/', $name, $m) === 1 ? strtolower($m[1]) : '';

        if ($claimed === $earned || self::sameKind($claimed, $earned)) {
            return $name;
        }
        $base = $claimed !== '' ? mb_substr($name, 0, mb_strlen($name) - strlen($claimed) - 1) : $name;

        return (trim($base) !== '' ? trim($base) : $fallback).'.'.$earned;
    }

    private static function sameKind(string $a, string $b): bool
    {
        foreach ([['jpg', 'jpeg'], ['txt', 'csv']] as $kind) {
            if (in_array($a, $kind, true) && in_array($b, $kind, true)) {
                return true;
            }
        }

        return false;
    }
}

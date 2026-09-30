<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * THE PUBLIC DISK'S IMAGES, SERVED AT /media (2026-09-30 — the founder: "when I add photo, the preview is not available").
 *
 * ⚠️⚠️ WHY A ROUTE, AND WHY NOT /storage. On production the document root IS the application root (KNOWN-LIMITS L12p),
 * so `public_html/storage` is the REAL storage folder — logs, and the maintenance bypass secret — and `.htaccess` refuses
 * every `/storage…` path at the server (`RedirectMatch 404`), deliberately. The public disk's address was Laravel's
 * default `APP_URL/storage`, so 🔬 every uploaded photo answered 404 while its file sat on disk — room photos, logos,
 * avatars, housekeeping and maintenance photos alike. That server rule stays exactly as it is: the public disk now
 * answers at `/media` (config/filesystems.php), which no server rule touches, through this controller. It is registered
 * OUTSIDE the web middleware group (bootstrap/app.php), so an image is never a session write or a cookie.
 *
 * ⚠️ WHAT IT SERVES, AND ONLY THAT: an IMAGE — by its extension AND by the file's detected type — inside one of the
 * directories the app writes photos, logos and avatars into. Everything else is a 404: a document, a hidden file, a
 * folder (never a listing), a path that leaves the disk (the pattern admits no dot segment at all), and any directory
 * not on the list. ⚠️ Message attachments are deliberately NOT served: they are documents in a private owner–operator
 * conversation, and whether they may be reachable by address is the founder's decision (OPEN.md D-MEDIA-ATTACH).
 */
final class PublicMediaController
{
    /**
     * The directories whose images the app shows by address. ⚠️ A new upload directory must be named here or in
     * NOT_SERVED — PublicMediaTest reads the code and fails otherwise, because a directory on neither list would 404
     * silently, which is exactly how this was found.
     */
    public const SERVED_DIRECTORIES = ['room-photos', 'demo-photos', 'brand-logos', 'avatars', 'housekeeping', 'maintenance', 'unit-request-photos'];

    /** @var array<string, string> Upload directories deliberately NOT served here, each with its reason. */
    public const NOT_SERVED = [
        'message-attachments' => 'documents in a private owner–operator conversation — how they may be reached is the founder\'s call',
    ];

    /** The image types served, by extension — and the file's own detected type must agree. */
    private const TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];

    public function __invoke(string $path): Response
    {
        // a listed directory, then plain segments, then a plain file name with an extension — no dot segment, no hidden
        // file, no way out of the disk (the pattern never lets the disk be asked for one)
        if (preg_match('#^([a-z0-9-]+)/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.([A-Za-z0-9]{1,5})$#', $path, $m) !== 1
            || ! in_array($m[1], self::SERVED_DIRECTORIES, true)) {
            abort(404);
        }

        $type = self::TYPES[strtolower($m[2])] ?? null;
        $disk = Storage::disk('public');
        // ⚠️ The file's OWN bytes decide, through finfo directly — not the disk's mimeType(): Flysystem falls back to the
        //    EXTENSION when the content is inconclusive, so 🔬 a text file named .jpg came back as image/jpeg and was served.
        if ($type === null || ! $disk->fileExists($path)
            || (new \finfo(FILEINFO_MIME_TYPE))->file($disk->path($path)) !== $type) {
            abort(404);
        }

        return $disk->response($path, null, [
            'Content-Type' => $type,
            // ⚠️ A day in the viewer's BROWSER, never at the shared edge: `private`. Production sits behind a CDN
            //    (KNOWN-LIMITS L12m), and nothing PHP answers is publicly cacheable (HtmlIsNeverPubliclyCacheableTest) —
            //    letting images in would be a deployment decision, not this fix's. It also means a removed photo is never
            //    handed out by the edge after it is gone.
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

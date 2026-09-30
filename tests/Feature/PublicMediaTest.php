<?php

declare(strict_types=1);

use App\Http\Controllers\PublicMediaController;
use App\Models\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE PUBLIC DISK ANSWERS AT /media (2026-09-30 — the founder: "when I add photo, the preview is not available").
//
// 🔬 Measured on production: a room photo's file sat on disk, and its address — Laravel's default `APP_URL/storage/…` —
// answered 404, because the server refuses every `/storage…` path ON PURPOSE (KNOWN-LIMITS L12p: the document root IS the
// application root, so `/storage` there is the REAL storage folder — logs and the maintenance bypass secret). That rule
// stays exactly as it is. The public disk now answers at `/media`, which no server rule touches, through
// PublicMediaController — outside the session middleware, so an image is never a session write or a cookie.

/** A fake public disk that keeps the REAL address — Storage::fake() carries over only `throw`, and would say /storage. */
function fakePublicDisk(): void
{
    Storage::fake('public', ['url' => config('filesystems.disks.public.url')]);
}

it('⚠️⚠️ gives an uploaded room photo an address that serves the image — /media, never the blocked /storage', function () {
    fakePublicDisk();
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s))->post(route('app.room-types.photos.store', $s['roomType']->id), [
        'photo' => UploadedFile::fake()->image('garden.jpg', 64, 48),
    ])->assertRedirect();

    $photo = Media::query()->where('mediable_id', $s['roomType']->id)->where('collection', 'photos')->firstOrFail();
    $path = (string) parse_url((string) $photo->url, PHP_URL_PATH);
    expect($path)->toStartWith('/media/room-photos/')->not->toContain('/storage/');

    $response = $this->get($path);
    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->streamedContent())->toBe(Storage::disk('public')->get((string) $photo->path))
        // an image is not a page: no session was started for it, so no cookie comes back
        ->and($response->headers->getCookies())->toBe([]);

    // …and the Units page shows that very address as the thumbnail
    $html = (string) $this->actingAs(operatorUser($s))->get('/app/units')->assertOk()->getContent();
    expect($html)->toContain('src="'.e((string) $photo->url).'"');
})->group('security');

it('⚠️⚠️ serves only images, only from the listed directories — never a document, a hidden file, a folder or a path outside the disk', function () {
    fakePublicDisk();
    $disk = Storage::disk('public');
    $jpeg = UploadedFile::fake()->image('ok.jpg', 8, 8)->getContent();
    $disk->put('room-photos/RT/OK.jpg', $jpeg);
    $disk->put('demo-photos/APT-1.jpg', $jpeg);
    $disk->put('room-photos/RT/notes.txt', 'plain text');
    $disk->put('room-photos/RT/FAKE.jpg', 'not an image at all');
    $disk->put('message-attachments/M/PHOTO.jpg', $jpeg);   // a real image — in a private conversation
    $disk->put('ROOT.jpg', $jpeg);                           // a real image outside every listed directory
    $disk->put('.gitignore', '*');

    $this->get('/media/room-photos/RT/OK.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->get('/media/demo-photos/APT-1.jpg')->assertOk();

    foreach ([
        '/media/room-photos/RT/notes.txt',               // not an image
        '/media/room-photos/RT/FAKE.jpg',                // named .jpg, is not one
        '/media/message-attachments/M/PHOTO.jpg',        // not a served directory
        '/media/.gitignore',                             // hidden
        '/media/room-photos',                            // folders are never listed
        '/media/room-photos/RT',
        '/media/room-photos/RT/../../.gitignore',        // out of the disk
        '/media/room-photos/RT/%2e%2e/%2e%2e/.gitignore',
        '/media/room-photos/RT/../../ROOT.jpg',          // out of its listed directory, to a real image
        '/media/room-photos/RT/%2e%2e/%2e%2e/ROOT.jpg',
        '/media/room-photos/RT/MISSING.jpg',
    ] as $path) {
        $this->get($path)->assertNotFound();
    }
})->group('security');

it('knows every directory the app uploads into — served at /media, or deliberately not', function () {
    // A photo feature whose directory is on neither list would 404 SILENTLY — the very failure this file was written for.
    $found = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
            preg_match_all("/(?:storeAs|->store)\\(\\s*'([a-z0-9-]+)/", (string) file_get_contents($file->getPathname()), $m);
            array_push($found, ...$m[1]);
        }
    }
    $found = array_values(array_unique($found));
    $decided = [...PublicMediaController::SERVED_DIRECTORIES, ...array_keys(PublicMediaController::NOT_SERVED)];

    expect(array_values(array_diff($found, $decided)))->toBe([])
        // the scan reads the real code, not nothing
        ->and($found)->toContain('room-photos')->toContain('message-attachments');
});

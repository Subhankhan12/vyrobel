<?php

declare(strict_types=1);

// Mutation set public-media — the public disk served at /media (2026-09-30, the founder: "when I add photo, the preview is
// not available"). 🔬 On production every /storage… address is refused by the server by design (KNOWN-LIMITS L12p), so
// every uploaded photo answered 404. Each mutation below removes one part of the fix, and a named test must go red.
// Run with `php tests/Mutations/run.php --set=public-media`.

$t = 'tests/Feature/PublicMediaTest.php';
$ctlFile = 'app/Http/Controllers/PublicMediaController.php';
$diskConfig = 'config/filesystems.php';
$boot = 'bootstrap/app.php';

return [
    [
        'id' => 'PM-M1',
        'name' => 'the public disk goes back to the /storage address the server refuses',
        'file' => $diskConfig,
        'old' => "            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/media',",
        'new' => "            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',",
        'tests' => [['file' => $t, 'filter' => 'gives an uploaded room photo an address that serves the image']],
    ],
    [
        'id' => 'PM-M2',
        'name' => 'any directory of the public disk is served — message attachments included',
        'file' => $ctlFile,
        'old' => '            || ! in_array($m[1], self::SERVED_DIRECTORIES, true)) {',
        'new' => '            || false) {',
        'tests' => [['file' => $t, 'filter' => 'serves only images, only from the listed directories']],
    ],
    [
        'id' => 'PM-M3',
        'name' => 'the type check trusts the disk, which falls back to the file name — a text file named .jpg is served',
        'file' => $ctlFile,
        'old' => '            || (new \\finfo(FILEINFO_MIME_TYPE))->file($disk->path($path)) !== $type) {',
        'new' => '            || $disk->mimeType($path) !== $type) {',
        'tests' => [['file' => $t, 'filter' => 'serves only images, only from the listed directories']],
    ],
    [
        'id' => 'PM-M4',
        'name' => 'dot segments are let through — a path climbs out of its listed directory',
        'file' => $ctlFile,
        'old' => "        if (preg_match('#^([a-z0-9-]+)/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\\.([A-Za-z0-9]{1,5})$#', \$path, \$m) !== 1",
        'new' => "        if (preg_match('#^([a-z0-9-]+)/(?:[A-Za-z0-9_.-]+/)*[A-Za-z0-9_.-]+\\.([A-Za-z0-9]{1,5})$#', \$path, \$m) !== 1",
        'tests' => [['file' => $t, 'filter' => 'serves only images, only from the listed directories']],
    ],
    [
        'id' => 'PM-M5',
        'name' => 'the media route joins the web group — every image starts a session and sets a cookie',
        'file' => $boot,
        'old' => "            Route::get('/media/{path}', PublicMediaController::class)",
        'new' => "            Route::middleware('web')->get('/media/{path}', PublicMediaController::class)",
        'tests' => [['file' => $t, 'filter' => 'gives an uploaded room photo an address that serves the image']],
    ],
    [
        'id' => 'PM-M6',
        'name' => 'an upload directory on neither list goes unnoticed (it would 404 silently)',
        'file' => $ctlFile,
        'old' => "        'message-attachments' => 'documents in a private owner–operator conversation — how they may be reached is the founder\\'s call',\n",
        'new' => '',
        'tests' => [['file' => $t, 'filter' => 'knows every directory the app uploads into']],
    ],
];

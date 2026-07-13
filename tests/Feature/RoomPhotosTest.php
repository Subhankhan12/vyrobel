<?php

use App\Models\Media;
use App\Models\RoomType;
use App\Support\Media\PlaceholderImage;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Demo-readiness polish · Part A · Step A1 — photos reuse the shared polymorphic `media` table (collection
// 'photos') with ordering + a cover flag + alt text. Attached at the RoomType level (the sellable entity).

function attachPhoto(RoomType $rt, int $sort, bool $cover = false): Media
{
    return Media::create([
        'mediable_type' => $rt->getMorphClass(),
        'mediable_id' => $rt->getKey(),
        'collection' => 'photos',
        'disk' => 'public',
        'path' => 'photos/'.$rt->getKey().'-'.$sort.'.jpg',
        'mime' => 'image/jpeg',
        'size' => 1000,
        'sort_order' => $sort,
        'is_cover' => $cover,
        'alt' => 'alt '.$sort,
    ]);
}

it('exposes ordered photos and the cover on a room-type', function () {
    $rt = seedScenario(1)['roomType'];
    attachPhoto($rt, 2);
    $cover = attachPhoto($rt, 0, true);
    attachPhoto($rt, 1);

    expect($rt->photos()->count())->toBe(3)
        ->and($rt->photos->pluck('sort_order')->all())->toBe([0, 1, 2])   // ordered by sort_order
        ->and($rt->coverPhoto()->id)->toBe($cover->id);                   // the is_cover one
});

it('coverPhoto falls back to the first photo when none is flagged', function () {
    $rt = seedScenario(1)['roomType'];
    $first = attachPhoto($rt, 0);
    attachPhoto($rt, 1);

    expect($rt->coverPhoto()->id)->toBe($first->id);
});

it('returns null cover for a room-type with no photos', function () {
    $rt = seedScenario(1)['roomType'];

    expect($rt->coverPhoto())->toBeNull()->and($rt->photos()->count())->toBe(0);
});

it('scopes photos to the tenant', function () {
    $sA = seedScenario(1);
    attachPhoto($sA['roomType'], 0, true);

    seedScenario(1);   // switches context to a fresh org B
    expect(Media::query()->where('collection', 'photos')->count())->toBe(0);

    app(TenantContext::class)->set($sA['org']->id);
    expect(Media::query()->where('collection', 'photos')->count())->toBe(1);
});

it('stores the enforced morph alias, not an FQCN', function () {
    $photo = attachPhoto(seedScenario(1)['roomType'], 0, true);

    expect($photo->mediable_type)->toBe('room_type');
});

it('exposes a public URL accessor for the stored file', function () {
    $photo = attachPhoto(seedScenario(1)['roomType'], 0);

    expect($photo->url)->toContain('/storage/photos/');
});

it('generates a real, decodable JPEG for demo placeholders (not a hotlinked URL)', function () {
    $bytes = PlaceholderImage::jpeg('Double Room', 'Vyrobel Demo · photo 1');
    $info = getimagesizefromstring($bytes);

    expect($info)->not->toBeFalse()
        ->and($info['mime'])->toBe('image/jpeg')
        ->and($info[0])->toBeGreaterThan(0)   // width
        ->and($info[1])->toBeGreaterThan(0);  // height
});

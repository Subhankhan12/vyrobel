<?php

use App\Models\Media;
use App\Models\Membership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Demo-readiness polish · Part A · Step A2 — the operator photo UPLOAD (the app's first user file-upload
// path, treated as a security surface) + the public/operator/owner DISPLAY wiring. Reuses the media table.

/** An active org member holding a real back-office role — the operator. */
function photoOperator(array $s, string $email = 'photo-op@vyrobel.test'): User
{
    $u = User::factory()->create(['email' => $email, 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $u->id, 'status' => 'active']);

    // A Membership alone is NOT operator access: /app is an allow-list gate (EnsureBackOfficeAccess),
    // so the uploader must hold a real back-office role, as a live invited staff member does.
    return grantBackOfficeRole($u, $s['org']);
}

/** Count a room-type's photos under super-admin (cross-org assertions). */
function photoCount(string $roomTypeId): int
{
    return app(TenantContext::class)->runAsSuperAdmin(
        fn () => Media::query()->where('mediable_id', $roomTypeId)->where('collection', 'photos')->count(),
    );
}

// ── Upload: a valid image is stored + a media row created (first = cover) ─────────────────────────
it('uploads a valid image and stores it as a room-type photo (first is cover)', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $rt = $s['roomType'];
    $op = photoOperator($s);

    $this->actingAs($op)->post(route('app.room-types.photos.store', $rt->id), [
        'photo' => UploadedFile::fake()->image('room.jpg', 1000, 700),
    ])->assertRedirect()->assertSessionHas('units_notice');

    app(TenantContext::class)->set($s['org']->id);
    $photo = Media::query()->where('mediable_id', $rt->id)->where('collection', 'photos')->firstOrFail();
    expect($photo->is_cover)->toBeTrue()
        ->and($photo->mime)->toContain('image/')
        ->and($photo->path)->not->toContain('room.jpg');   // generated filename, not the user's
    Storage::disk('public')->assertExists($photo->path);
});

// ── Security: a non-image file is rejected, nothing written ──────────────────────────────────────
it('rejects a non-image file', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $rt = $s['roomType'];

    $this->actingAs(photoOperator($s))->post(route('app.room-types.photos.store', $rt->id), [
        'photo' => UploadedFile::fake()->create('notes.txt', 12, 'text/plain'),
    ])->assertSessionHasErrors('photo');

    expect(photoCount($rt->id))->toBe(0);
});

// ── Security: a DISGUISED file (image extension, non-image content/mime) is rejected ─────────────
it('rejects a disguised file (jpg name but non-image content)', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $rt = $s['roomType'];

    $this->actingAs(photoOperator($s))->post(route('app.room-types.photos.store', $rt->id), [
        'photo' => UploadedFile::fake()->create('evil.jpg', 12, 'text/x-php'),   // .jpg name, script mime
    ])->assertSessionHasErrors('photo');

    expect(photoCount($rt->id))->toBe(0);
});

// ── Security: an oversized image is rejected ─────────────────────────────────────────────────────
it('rejects an oversized image', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $rt = $s['roomType'];

    $this->actingAs(photoOperator($s))->post(route('app.room-types.photos.store', $rt->id), [
        'photo' => UploadedFile::fake()->image('huge.jpg')->size(6000),   // 6 MB > 5 MB cap
    ])->assertSessionHasErrors('photo');

    expect(photoCount($rt->id))->toBe(0);
});

// ── Tenant isolation: cannot attach to another org's room-type (404, nothing written) ────────────
it('cannot attach a photo to another org’s room-type', function () {
    Storage::fake('public');
    $other = seedScenario(1);          // org B
    $foreignRt = $other['roomType'];
    $s = seedScenario(1);              // org A (context switches here)
    $op = photoOperator($s);

    $this->actingAs($op)->post(route('app.room-types.photos.store', $foreignRt->id), [
        'photo' => UploadedFile::fake()->image('x.jpg'),
    ])->assertNotFound();

    expect(photoCount($foreignRt->id))->toBe(0);   // nothing written to org B
});

// ── Set cover: exactly one cover per room-type ───────────────────────────────────────────────────
it('sets the cover photo (exactly one cover)', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $rt = $s['roomType'];
    $op = photoOperator($s);

    $this->actingAs($op)->post(route('app.room-types.photos.store', $rt->id), ['photo' => UploadedFile::fake()->image('a.jpg')]);
    $this->actingAs($op)->post(route('app.room-types.photos.store', $rt->id), ['photo' => UploadedFile::fake()->image('b.jpg')]);

    app(TenantContext::class)->set($s['org']->id);
    $second = Media::query()->where('mediable_id', $rt->id)->orderByDesc('sort_order')->firstOrFail();

    $this->actingAs($op)->post(route('app.room-types.photos.cover', [$rt->id, $second->id]))->assertRedirect();

    expect(Media::query()->where('mediable_id', $rt->id)->where('is_cover', true)->count())->toBe(1)
        ->and(Media::query()->find($second->id)->is_cover)->toBeTrue();
});

// ── Reorder: move a photo swaps its sort_order with the neighbour ────────────────────────────────
it('reorders photos by swapping sort_order', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $rt = $s['roomType'];
    $op = photoOperator($s);

    $this->actingAs($op)->post(route('app.room-types.photos.store', $rt->id), ['photo' => UploadedFile::fake()->image('a.jpg')]);
    $this->actingAs($op)->post(route('app.room-types.photos.store', $rt->id), ['photo' => UploadedFile::fake()->image('b.jpg')]);

    app(TenantContext::class)->set($s['org']->id);
    $second = Media::query()->where('mediable_id', $rt->id)->where('sort_order', 1)->firstOrFail();

    $this->actingAs($op)->post(route('app.room-types.photos.move', [$rt->id, $second->id]), ['direction' => 'up'])->assertRedirect();

    expect(Media::query()->find($second->id)->sort_order)->toBe(0);   // moved to the front
});

// ── Delete: removes the file + soft-deletes the row + promotes a new cover ───────────────────────
it('removes a photo and promotes a new cover when the cover is deleted', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $rt = $s['roomType'];
    $op = photoOperator($s);

    $this->actingAs($op)->post(route('app.room-types.photos.store', $rt->id), ['photo' => UploadedFile::fake()->image('a.jpg')]);
    $this->actingAs($op)->post(route('app.room-types.photos.store', $rt->id), ['photo' => UploadedFile::fake()->image('b.jpg')]);

    app(TenantContext::class)->set($s['org']->id);
    $cover = Media::query()->where('mediable_id', $rt->id)->where('is_cover', true)->firstOrFail();

    $this->actingAs($op)->delete(route('app.room-types.photos.destroy', [$rt->id, $cover->id]))->assertRedirect();

    Storage::disk('public')->assertMissing($cover->path);
    expect(Media::query()->find($cover->id))->toBeNull()                              // soft-deleted
        ->and(Media::query()->where('mediable_id', $rt->id)->where('is_cover', true)->count())->toBe(1);   // promoted
});

// ── Public display: real photos render on the room detail; no "Photos soon" flag ─────────────────
it('shows real photos on the public room detail and drops the “Photos soon” flag', function () {
    $a = publicPropertyScenario(units: 1);
    $rt = $a['roomType'];
    Media::create([
        'organization_id' => $a['org']->id, 'mediable_type' => $rt->getMorphClass(), 'mediable_id' => $rt->id,
        'collection' => 'photos', 'disk' => 'public', 'path' => 'room-photos/'.$rt->id.'/cover.jpg',
        'mime' => 'image/jpeg', 'size' => 1000, 'sort_order' => 0, 'is_cover' => true, 'alt' => 'Std room',
    ]);

    test()->get("/book/{$a['property']->slug}/rooms/{$rt->id}?check_in=2026-09-01&check_out=2026-09-03")
        ->assertOk()
        ->assertSee('room-photos/'.$rt->id)   // the real photo URL renders
        ->assertDontSee('Photos soon');       // the stub flag is gone
});

// ── Public display: a room with NO photos still renders (gradient fallback, no flag) ─────────────
it('keeps the gradient fallback for a room with no photos (not broken, no flag)', function () {
    $a = publicPropertyScenario(units: 1);

    test()->get("/book/{$a['property']->slug}/rooms/{$a['roomType']->id}?check_in=2026-09-01&check_out=2026-09-03")
        ->assertOk()
        ->assertDontSee('Photos soon')        // no stub flag
        ->assertSee('vy-detail-banner');      // the decorative banner still renders
});

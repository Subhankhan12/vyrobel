<?php

declare(strict_types=1);

use App\Http\Controllers\PublicMediaController;
use App\Models\HousekeepingTask;
use App\Models\Media;
use App\Models\Property;
use App\Services\MaintenanceService;
use App\Support\Media\OperationsPhotos;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// ⚠️⚠️ VY-34 (audit of 2026-11-24, read in code) — HOUSEKEEPING AND MAINTENANCE PHOTOS OPENED WITHOUT SIGN-IN.
//
// They were stored on the PUBLIC disk and served by PublicMediaController at /media/housekeeping/… and /media/maintenance/… to anyone
// holding the address. They now live on the PRIVATE disk and open only through /app/photos/{media}: signed in, back office (#26), the
// tenant, and a task or work order on one of the person's properties. /media no longer serves those two directories at all, and photos
// uploaded before the fix are moved by `media:privatise-operations-photos` (verified copy first, public file removed last).

uses(DatabaseTransactions::class);

function vy34Task(array $s): HousekeepingTask
{
    return HousekeepingTask::create([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'unit_id' => $s['units'][0]->id,
        'date' => '2026-09-03', 'type' => 'departure', 'status' => 'pending', 'priority' => 'normal',
    ]);
}

/** Upload a real proof photo as a Hotel Manager; returns the Media row. */
function vy34Upload(array $s, HousekeepingTask $task): Media
{
    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->post(route('app.housekeeping.photos.store', $task->id), ['photo' => UploadedFile::fake()->image('clean.jpg', 40, 30)])
        ->assertRedirect();
    auth()->logout();

    return Media::query()->where('mediable_id', $task->id)->where('collection', 'hk_proof')->firstOrFail();
}

it('⚠️⚠️ VY-34 (as read): a new proof photo is stored on the PRIVATE disk, and its only address is the signed-in route', function () {
    Storage::fake('local');
    Storage::fake('public');
    $s = seedScenario(1);
    $media = vy34Upload($s, vy34Task($s));

    expect($media->disk)->toBe('local')
        ->and($media->url)->toBe(route('app.photos.show', $media->id));
    Storage::disk('local')->assertExists($media->path);
    Storage::disk('public')->assertMissing($media->path);
});

it('⚠️⚠️ VY-34: signed out, the photo is not served — the sign-in page instead; and /media serves neither directory any more', function () {
    Storage::fake('local');
    Storage::fake('public');
    $s = seedScenario(1);
    $media = vy34Upload($s, vy34Task($s));

    $this->get(route('app.photos.show', $media->id))->assertRedirect(route('login'));

    // even a file that IS on the public disk under those directories (one uploaded before the fix) is not served at /media
    Storage::disk('public')->put($media->path, (string) Storage::disk('local')->get($media->path));
    $this->get('/media/'.$media->path)->assertNotFound();
    $this->get('/media/maintenance/'.Str::ulid().'/x.jpg')->assertNotFound();
});

it('VY-34 ALLOW: a signed-in member who can see the task gets the image — private, never stored by the browser or the edge', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    $media = vy34Upload($s, vy34Task($s));

    $response = $this->actingAs(operatorUser($s, [], 'Housekeeping'))->get(route('app.photos.show', $media->id))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/jpeg')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Cache-Control'))->toContain('private');
});

it('⚠️ VY-34: another organisation\'s member, an owner, and staff limited to another property get no photo', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    $media = vy34Upload($s, vy34Task($s));

    $other = seedScenario(1);
    $this->actingAs(operatorUser($other, [], 'Hotel Manager'))->get(route('app.photos.show', $media->id))->assertNotFound();
    auth()->logout();

    $this->actingAs(ownerRoleUser($s))->get(route('app.photos.show', $media->id))->assertStatus(302);   // refused from /app (#26)
    auth()->logout();

    app(TenantContext::class)->set($s['org']->id);   // the second property is the SAME organisation's (seedScenario() moved the context)
    $second = Property::create(['name' => 'Far', 'slug' => 'far-'.Str::lower(Str::random(6)), 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC', 'status' => 'active']);
    $scoped = operatorUser($s, [], 'Housekeeping');
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $second->id, 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($scoped)->get(route('app.photos.show', $media->id))->assertNotFound();
});

it('VY-34: a maintenance photo is private the same way', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Crack']);

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->post(route('app.maintenance.photos.store', $ticket->id), ['photo' => UploadedFile::fake()->image('crack.png', 40, 30)])
        ->assertRedirect();
    $media = Media::query()->where('mediable_id', $ticket->id)->where('collection', 'mt_photo')->firstOrFail();
    auth()->logout();

    expect($media->disk)->toBe('local');
    $this->get(route('app.photos.show', $media->id))->assertRedirect(route('login'));
    $this->actingAs(operatorUser($s, [], 'Maintenance'))->get(route('app.photos.show', $media->id))->assertOk();
});

it('⚠️ VY-34: the move command takes a photo uploaded before the fix off the public disk — verified copy first, then the public file goes', function () {
    Storage::fake('local');
    Storage::fake('public');
    $s = seedScenario(1);
    $task = vy34Task($s);
    $path = 'housekeeping/'.$task->id.'/'.Str::ulid().'.jpg';
    $upload = UploadedFile::fake()->image('old.jpg', 20, 20);
    $bytes = (string) file_get_contents($upload->getRealPath());
    Storage::disk('public')->put($path, $bytes);
    $legacy = Media::create(['mediable_type' => $task->getMorphClass(), 'mediable_id' => $task->id, 'collection' => 'hk_proof',
        'disk' => 'public', 'path' => $path, 'mime' => 'image/jpeg', 'size' => strlen($bytes), 'alt' => 'old']);

    // before the move it already opens only signed in (the address is the route's), and the route serves it from where it is
    expect($legacy->url)->toBe(route('app.photos.show', $legacy->id));

    expect(Artisan::call('media:privatise-operations-photos', ['--dry-run' => true]))->toBe(0);
    Storage::disk('public')->assertExists($path);                       // a dry run changes nothing

    expect(Artisan::call('media:privatise-operations-photos'))->toBe(0);
    $legacy->refresh();
    expect($legacy->disk)->toBe(OperationsPhotos::DISK);
    Storage::disk('public')->assertMissing($path);
    expect(Storage::disk('local')->get($path))->toBe($bytes);

    expect(Artisan::call('media:privatise-operations-photos'))->toBe(0);   // idempotent
});

it('VY-34 CONTROL: a room photo for the storefront is still public at /media (only the two private directories moved)', function () {
    expect(PublicMediaController::SERVED_DIRECTORIES)->toContain('room-photos')
        ->not->toContain('housekeeping')
        ->not->toContain('maintenance');
    expect(PublicMediaController::NOT_SERVED)->toHaveKeys(['housekeeping', 'maintenance']);
});

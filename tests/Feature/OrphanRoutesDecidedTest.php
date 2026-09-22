<?php

declare(strict_types=1);

use App\Models\HousekeepingTask;
use App\Models\Media;
use App\Services\OwnerInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ APPLY 04 · ITEM 2 — THE THREE REMAINING UNREFERENCED ROUTES, EACH DECIDED AND EACH PINNED.
 *
 * 🔬 The census left three routes with no view, no component, no URI and no emitter:
 *
 *   `app.housekeeping.photos.destroy` → **A CONTROL.** ⚠️ Three photo families exist in this app
 *        (housekeeping · maintenance · room-types), each a `store` + `destroy` pair, and TWO of the three
 *        already offered a delete affordance on the thumbnail. Housekeeping was the lone exception — proof
 *        photos were uploaded (a JS-built URI, which is why a literal search missed the pair) and DISPLAYED
 *        in the room-detail modal, with no way for anyone to remove a wrong shot. A missing member of an
 *        established family is an oversight, not a decision, so it was built in the family's own idiom.
 *
 *   `owner.welcome` → **A CONTROL.** ⚠️ Real, honest, three-test onboarding that nobody was ever sent to.
 *        🔬 Both invite-acceptance paths landed on `owner.dashboard`; the nav has 23 entries and this was
 *        not one. Its four cards each link to a page already in that nav, so its ONLY value is first-run
 *        SEQUENCING — which is worth nothing unless a new owner is routed through it. Now the owner-invite
 *        branch lands there; the owner-TEAM invite deliberately does not (a sub-user has no payout account).
 *
 *   `owner.messages.show` → ⚠️⚠️ **REMOVED, THEN RESTORED THE SAME DAY — THE DECISION WAS WRONG.** Every
 *        measurement behind the removal was true (no view, component, Mailable, notification, test or JS
 *        NAMED it; 710 notification rows carrying no such URL) and the conclusion was still false: 🔬 three
 *        `OwnerInboxIsolationTest` cases reach it **by URL**, which a name-search cannot see. ⚠️ And what
 *        they pin is an ISOLATION BOUNDARY, not a deep link — with the GET gone the same URL answered
 *        **405** from the POST reply route on the identical URI, replacing an owner-scoped 404 with an
 *        unscoped refusal. ⚠️ **SWEEP THE ROUTE, NOT THE TEXT.**
 */

// ── 1. ⚠️⚠️ THE HOUSEKEEPING PHOTO CAN NOW BE REMOVED — and only by someone who may ──────────────────
function hkTaskWithPhoto(array $s): array
{
    Storage::fake('public');

    $task = HousekeepingTask::query()->create([
        'organization_id' => $s['org']->id,
        'property_id' => $s['property']->id,
        'unit_id' => $s['units'][0]->id,
        'date' => now()->toDateString(),
        'status' => 'pending',
        'type' => 'departure',
    ]);

    $media = Media::query()->create([
        'mediable_type' => $task->getMorphClass(),
        'mediable_id' => $task->getKey(),
        'collection' => 'hk_proof',
        'disk' => 'public',
        'path' => 'housekeeping/'.$task->getKey().'/proof.jpg',
        'mime' => 'image/jpeg',
        'size' => 1234,
        'alt' => 'Housekeeping proof',
    ]);

    Storage::disk('public')->put((string) $media->path, 'bytes');

    return [$task, $media];
}

it('⚠️⚠️ offers the delete on the board where the photo is shown, and removing it takes the bytes too', function () {
    $s = seedScenario(1);
    [$task, $media] = hkTaskWithPhoto($s);
    $hk = operatorUser($s, [], 'Housekeeping');

    // ⚠️ THE URL COMES OFF THE PAGE. The board builds it in Alpine from the two ids, exactly as it builds
    //    the upload URL beside it, so the test asserts the page really offers that builder and then walks it.
    $html = $this->actingAs($hk)->get(route('app.housekeeping'))->assertOk()->getContent();
    // ⚠️⚠️ ASSERT THE GATED FORM, NOT THE ALPINE HELPER. `photoDeleteUrl` is DEFINED in the page's x-data
    //    for every viewer — it is a function, not a control — so searching for the name alone is green for
    //    a role that can see nothing. 🔬 Measured: the helper name appears once (its definition) for a role
    //    without `housekeeping.manage` and twice (definition + the `:action` call) for one with it, while
    //    "Remove photo" appears 0 vs 2. The label is the control; the helper is not.
    expect($html)->toContain(':action="photoDeleteUrl(detail.id, photo.id)"')
        ->and($html)->toContain('Remove photo');

    $this->actingAs($hk)
        ->delete(route('app.housekeeping.photos.destroy', [$task->getKey(), $media->getKey()]))
        ->assertRedirect();

    // ⚠️ READ BOTH BACK — the row AND the bytes. A delete that leaves the file is not a delete.
    expect(Media::query()->whereKey($media->getKey())->exists())->toBeFalse()
        ->and(Storage::disk('public')->exists((string) $media->path))->toBeFalse();
});

it('⚠️ #30 — hides the delete from a role without housekeeping.manage, and the route refuses it', function () {
    $s = seedScenario(1);
    [$task, $media] = hkTaskWithPhoto($s);

    // 🔬 Front Desk holds `housekeeping.view` but NOT `housekeeping.manage` — it can read the board.
    $fd = operatorUser($s, [], 'Front Desk');
    $html = $this->actingAs($fd)->get(route('app.housekeeping'))->assertOk()->getContent();
    expect($html)->not->toContain('Remove photo')                        // the control's label
        ->and($html)->not->toContain(':action="photoDeleteUrl')          // and the control itself
        ->and($html)->not->toContain('Capture / upload')                 // its sibling, same gate
        ->and($html)->toContain('photoDeleteUrl(taskId, mediaId)');      // ⚠️ POSITIVE CONTROL: the page
    // rendered; only the control is gone.

    $this->actingAs($fd)
        ->delete(route('app.housekeeping.photos.destroy', [$task->getKey(), $media->getKey()]))
        ->assertForbidden();

    expect(Media::query()->whereKey($media->getKey())->exists())->toBeTrue()
        ->and(Storage::disk('public')->exists((string) $media->path))->toBeTrue();
});

// ── 2. ⚠️⚠️ THE ORPHAN THAT WAS NOT ONE — removed, and restored the same day ────────────────────────
it('⚠️⚠️ keeps the owner thread GET, because it is the owner-scoped half of a LIVE uri', function () {
    // ⚠️⚠️ THIS TEST EXISTS BECAUSE THIS PASS GOT THE DECISION WRONG AND THE SUITE CAUGHT IT.
    //    `owner.messages.show` was removed as an unreferenced orphan on a census that was TRUE and a
    //    conclusion that was FALSE: no view, component, Mailable, notification or JS named it, and 710
    //    persisted notification rows carried no such URL — but 🔬 three tests in `OwnerInboxIsolationTest`
    //    reach it BY URL (`->get('/owner/messages/'.$id)`), which a search for the route NAME cannot see.
    //    ⚠️ SWEEP THE ROUTE, NOT THE TEXT.
    // ⚠️⚠️ AND THE COST OF THE REMOVAL WAS NOT A DEAD LINK, IT WAS AN ISOLATION BOUNDARY: 🔬 with the GET
    //    gone, the same URL returned **405**, because the POST reply route occupies the identical URI. The
    //    two-inbox contract says a cross-owner thread is "genuinely a 404 through the portal … not a 403
    //    that leaks its existence" — and an unscoped 405 is neither.
    expect(Route::has('owner.messages.show'))
        ->toBeTrue('removing this replaces an owner-scoped 404 with an unscoped 405 — see routes/web.php')
        ->and(Route::has('owner.messages'))->toBeTrue();

    // ⚠️ AND THE MECHANISM, NOT JUST THE ROUTE: the GET and the POST share a URI, so the GET must be the one
    //    that answers a browser. If the router ever stops matching it, this is what tells you.
    $get = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($r): bool => $r->getName() === 'owner.messages.show');
    $post = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($r): bool => $r->getName() === 'owner.messages.reply');

    expect($get?->uri())->toBe($post?->uri(), 'the two halves must stay on the same URI — that is the point')
        ->and($get?->methods())->toContain('GET')
        ->and($post?->methods())->toContain('POST');
});

// ── 3. ⚠️⚠️ THE ONBOARDING PAGE NOW HAS ITS DOOR — and it is the right one ───────────────────────────
it('⚠️⚠️ lands a newly-invited OWNER on the welcome flow, and a returning owner on their dashboard', function () {
    Mail::fake();
    $s = seedScenario(1);
    $r = app(OwnerInviteService::class)
        ->invite('Wren Alder', 'wren@example.test', [$s['units'][0]->id], 18.0, 'owner');

    // ⚠️ THE DOOR. Before this pass `owner.welcome` was reachable only by typing the URL — a page built for
    //    a journey nobody was routed through. The invite is that journey.
    $this->post('/invite/'.$r['token'], ['password' => 'Wren-strong-pass1!', 'password_confirmation' => 'Wren-strong-pass1!'])
        ->assertRedirect(route('owner.welcome'));

    // ⚠️ AND IT IS NOT A TRAP: the page renders for the owner who just arrived, and its own exit is real.
    $this->get(route('owner.welcome'))->assertOk()
        ->assertSee('Go to your dashboard')
        ->assertSee(route('owner.dashboard'));

    // ⚠️⚠️ THE OTHER HALF OF THE GATE — a RETURNING owner is not re-onboarded. Without this, "always send
    //    them to welcome" would pass the assertion above and be wrong.
    $this->post('/logout');
    $this->post('/login', ['email' => 'wren@example.test', 'password' => 'Wren-strong-pass1!'])
        ->assertRedirect('/owner');
});

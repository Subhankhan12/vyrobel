<?php

use App\Models\Amenity;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Unit;
use App\Models\UnitRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// OWNER ADD-UNIT REQUEST (Phase 2 · Sub-gate 6) — an owner REQUESTS a unit; they never create billable
// inventory. The request persists owner-scoped + self-only, and is visible to the operator (who creates the
// real unit as their own action, not built here).

/** A linked owner-portal user owning unit[$index]. */
if (! function_exists('urOwner')) {
    function urOwner(array $s, int $index, string $name): array
    {
        $unit = $s['units'][$index];
        $owner = ownUnit($unit);
        $user = User::factory()->create(['name' => $name]);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return ['user' => $user, 'owner' => $owner];
    }
}

// ── 1. Submitting persists a PENDING, owner-scoped request (and creates NO unit) ──────────────────
it('files a pending unit request for the owner and creates no unit', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');
    $unitsBefore = Unit::query()->count();

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'notes' => 'Two-bed near the marina.',
    ])->assertRedirect(route('owner.add-unit'))->assertSessionHas('unit_request_done');

    $request = UnitRequest::query()->where('owner_id', $a['owner']->id)->firstOrFail();
    expect((string) $request->requested_name)->toBe('Villa Marisol')
        ->and((string) $request->requested_kind)->toBe('villa')
        ->and((string) $request->status)->toBe('pending')
        ->and(Unit::query()->count())->toBe($unitsBefore);   // NO inventory created

    // Following the redirect shows the DONE confirmation (owner-scoped read-back of the just-filed request)…
    $this->actingAs($a['user'])->get(route('owner.add-unit'))
        ->assertOk()->assertSee('Request received')->assertSee('Villa Marisol');

    // …and a later visit shows the wizard again, with the request under "Your requests".
    $this->actingAs($a['user'])->get('/owner/add-unit')
        ->assertOk()->assertSee('Your requests')->assertSee('Villa Marisol')->assertSee('Pending review');
});

// ── 2. Owner-scoped — an owner sees only their OWN requests ───────────────────────────────────────
it('shows an owner only their own requests', function () {
    $s = seedScenario(2);
    $a = urOwner($s, 0, 'Amara Okafor');
    $b = urOwner($s, 1, 'Bruno Vale');

    $this->actingAs($a['user'])->post('/owner/add-unit', ['requested_name' => 'Amara Loft', 'requested_kind' => 'apartment']);
    $this->actingAs($b['user'])->post('/owner/add-unit', ['requested_name' => 'Bruno Cabin', 'requested_kind' => 'room']);

    $this->actingAs($a['user'])->get('/owner/add-unit')
        ->assertOk()->assertSee('Amara Loft')->assertDontSee('Bruno Cabin');
});

// ── 3. Operator-VISIBLE, gated on units.view ──────────────────────────────────────────────────────
it('shows the request (with the wizard detail) to an operator and gates the screen on units.view', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');
    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Operatorseen', 'requested_kind' => 'villa',
        'location' => 'Hvar', 'bedrooms' => 3, 'bathrooms' => 2, 'sleeps' => 6, 'nightly_rate' => '190',
    ]);

    // A Hotel Manager (has units.view) sees the pending request AND the rich detail the owner submitted…
    $manager = operatorUser($s, [], 'Hotel Manager');
    $this->actingAs($manager)->get('/app/unit-requests')
        ->assertOk()->assertSee('Villa Operatorseen')->assertSee('Pending')
        ->assertSee('Hvar')                          // real captured detail — not a "—"
        ->assertSee('3 bd · 2 ba · sleeps 6')
        ->assertSee('€190.00/night');                // the PROPOSED rate, integer minor formatted (#17)

    // …an Accountant (no units.view) is refused.
    $accountant = operatorUser($s, [], 'Accountant');
    $this->actingAs($accountant)->get('/app/unit-requests')->assertForbidden();
});

// ── 4. Self-only — filed against the actor; a non-linked user can't submit ────────────────────────
it('is self-only — a non-linked user cannot file a request', function () {
    $s = seedScenario(1);
    urOwner($s, 0, 'Amara Okafor');

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->post('/owner/add-unit', ['requested_name' => 'Ghost Unit', 'requested_kind' => 'room'])
        // ⚠️ R-GATE (founder, 2026-09-19): refused at the realm door before any owner resolution, so a 403
        //    about the AREA rather than a 404 about the resource. The row assertion below is unchanged.
        ->assertForbidden();
    expect(UnitRequest::query()->where('requested_name', 'Ghost Unit')->exists())->toBeFalse();

    $this->actingAs($stranger)->get('/owner/add-unit')->assertForbidden();
});

// ── 5. An invalid (non-enum) kind is rejected ─────────────────────────────────────────────────────
it('rejects a requested kind outside the real unit enum', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/add-unit', ['requested_name' => 'Studio X', 'requested_kind' => 'studio'])
        ->assertRedirect()->assertSessionHasErrors('requested_kind');

    expect(UnitRequest::query()->where('owner_id', $a['owner']->id)->exists())->toBeFalse();
});

// ── 6. Help renders honestly — the real message route, no faked ticket queue ──────────────────────
it('renders a static Help page with the real message route and no faked tickets', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->get('/owner/help')
        ->assertOk()
        ->assertSee('Message your manager')          // the REAL owner↔operator inbox
        ->assertSee('Owner FAQs')
        ->assertDontSee('support request')           // no faked ticket queue
        ->assertDontSee('VYR-');                     // no fabricated ticket numbers
});

// ═══ GATE 3 — the Add-Unit WIZARD (exact design + a rich, REAL pending request) ═══════════════════

// ── 7. A rich submission persists every wizard field + amenities + a real photo (creates NO unit) ──
it('files a RICH request from the wizard — location, capacity, proposed money, amenities and a photo', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');
    $wifi = Amenity::firstOrCreate(['name' => 'WiFi'], ['icon' => 'wifi', 'category' => 'connectivity']);
    $pool = Amenity::firstOrCreate(['name' => 'Pool'], ['icon' => 'pool', 'category' => 'leisure']);
    $unitsBefore = Unit::query()->count();

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol',
        'requested_kind' => 'villa',
        'location' => 'Split',
        'street_address' => '12 Riva Promenade',
        'bedrooms' => 3, 'bathrooms' => 2, 'sleeps' => 6,
        'min_nights' => 4,
        'nightly_rate' => '180.50',   // MoneyInput → 18050 minor (#17)
        'cleaning_fee' => '45',
        'amenities' => [$wifi->id, $pool->id],
        'photos' => [UploadedFile::fake()->image('cover.jpg', 1200, 800)],
    ])->assertRedirect(route('owner.add-unit'))->assertSessionHas('unit_request_done');

    $r = UnitRequest::query()->where('owner_id', $a['owner']->id)->firstOrFail();
    expect((string) $r->location)->toBe('Split')
        ->and((string) $r->street_address)->toBe('12 Riva Promenade')
        ->and($r->bedrooms)->toBe(3)->and($r->bathrooms)->toBe(2)->and($r->sleeps)->toBe(6)
        ->and($r->min_nights)->toBe(4)
        ->and($r->requested_rate_minor)->toBe(18050)   // integer minor units, string-parsed (#17)
        ->and($r->cleaning_fee_minor)->toBe(4500)
        ->and((string) $r->currency)->toBe('EUR')
        ->and((string) $r->status)->toBe('pending')
        ->and(Unit::query()->count())->toBe($unitsBefore);   // still NO inventory created

    // Amenities attached through the SHARED polymorphic pivot (real catalog ids).
    expect($r->amenities()->pluck('id')->sort()->values()->all())
        ->toEqual(collect([$wifi->id, $pool->id])->sort()->values()->all());

    // The photo is a real media row — cover, a GENERATED (never client) filename, on the public disk.
    $photo = $r->photos()->firstOrFail();
    expect($photo->is_cover)->toBeTrue()
        ->and((string) $photo->collection)->toBe('photos')
        ->and((string) $photo->path)->not->toContain('cover.jpg');
    Storage::disk('public')->assertExists($photo->path);
});

// ── 8. Money is parsed via MoneyInput — junk is a validation error, not a silent drop (#17) ───────
it('rejects an unparseable rate rather than silently dropping it', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Bad Rate Unit', 'requested_kind' => 'apartment', 'nightly_rate' => '18o',
    ])->assertRedirect()->assertSessionHasErrors('nightly_rate');

    expect(UnitRequest::query()->where('requested_name', 'Bad Rate Unit')->exists())->toBeFalse();
});

// ── 9. Amenities must be REAL catalog rows — a fabricated id is rejected (#39) ─────────────────────
it('rejects a fabricated amenity id — only real catalog rows attach', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Amenity Test', 'requested_kind' => 'apartment',
        'amenities' => ['01JZZZNOTAREALAMENITYID000'],
    ])->assertRedirect()->assertSessionHasErrors('amenities.0');

    expect(UnitRequest::query()->where('requested_name', 'Amenity Test')->exists())->toBeFalse();
});

// ── 10. Photo upload is hardened — a non-image is refused ──────────────────────────────────────────
it('refuses a non-image upload', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Doc Unit', 'requested_kind' => 'apartment',
        'photos' => [UploadedFile::fake()->create('evil.pdf', 40, 'application/pdf')],
    ])->assertRedirect()->assertSessionHasErrors('photos.0');

    expect(UnitRequest::query()->where('requested_name', 'Doc Unit')->exists())->toBeFalse();
});

// ── 11. The wizard renders exactly, on REAL data — real commission %, real amenity catalog, #20 POST ─
it('renders the 4-step wizard with the real commission and the real amenity catalog', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');   // ownUnit() gives a real 15% owner-wide agreement
    Amenity::firstOrCreate(['name' => 'WiFi'], ['icon' => 'wifi', 'category' => 'connectivity']);

    $response = $this->actingAs($a['user'])->get('/owner/add-unit')->assertOk();
    $response->assertSee('The basics')
        ->assertSee('Space &amp; capacity', false)
        ->assertSee('Photos')
        ->assertSee('Rate &amp; terms', false)
        ->assertSee('Live preview')
        ->assertSee('Submit for review')
        ->assertSee(route('owner.add-unit.submit'), false)   // a real controller POST (never /livewire/update, #20)
        ->assertSee('15% commission')                        // the REAL agreement rate — not a fabricated figure (#39)
        ->assertSee('WiFi');                                 // the real amenity catalog
    assertNoRawBlade($response);
});

// ── 12. #39 — no agreement ⇒ an honest commission line, NEVER a fabricated percentage ──────────────
it('shows an honest commission line when the owner has no agreement (never a fabricated %)', function () {
    $s = seedScenario(1);
    // A linked owner WITHOUT any management agreement.
    $owner = Owner::factory()->create(['payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    $user = User::factory()->create(['name' => 'Nora Vance']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);

    $this->actingAs($user)->get('/owner/add-unit')
        ->assertOk()
        ->assertSee('Your team confirms the commission')   // the honest line
        ->assertDontSee('% commission');                    // NEVER a fabricated percentage
});

// ── 13. A wizard must not be submittable from a step that isn't its LAST ───────────────────────────
// Enter inside any text field triggers IMPLICIT submission against the step-4 submit button even while that
// step is hidden. From step 1 that files a REAL request on the operator's desk off nothing but a name — the
// server requires only requested_name + requested_kind, and both are present (kind defaults). Browser-proven
// both ways: before the guard, Enter on step 1 fired a submit; after it, step 1 fires none and step 4 still
// submits. Keyboard users are the ones who reach for Enter, so this is their accidental-request path.
it('guards the wizard against implicit Enter submission before the final step', function () {
    $s = seedScenario(1);
    $a = urOwner($s, 0, 'Amara Okafor');

    $html = (string) $this->actingAs($a['user'])->get('/owner/add-unit')->assertOk()->getContent();

    expect($html)->toContain('@keydown.enter="if (step !== 4) $event.preventDefault()"');
});

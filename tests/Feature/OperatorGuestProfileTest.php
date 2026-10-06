<?php

use App\Models\Guest;
use App\Models\Note;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use App\Services\FolioService;
use App\Services\GuestService;
use App\Support\Guest\GuestSpend;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE OPERATOR GUEST PROFILE — the CRM detail (Wave 2 · Sub-gate 5).
//
// What is actually load-bearing here, and therefore what these tests defend:
//   • ⚠️ NO TOAST-ONLY SAVE. The design's "Save changes" fired a toast and persisted NOTHING. Test 4 posts
//     the form and then re-reads the page and the DB: if the write were fake, it fails.
//   • TENANT isolation + the /app role gate (invariant #26) — and this page carries contact PII, so the gate
//     matters more here than almost anywhere.
//   • THE PROFILE AND THE LIST AGREE, because both read the SHARED GuestSpend / GuestHistory /
//     ReservationStage. Test 7 renders BOTH pages and asserts the same figures — the drift the shared
//     helpers exist to prevent.
//   • The design's fabrications are ABSENT: no "Completed" status, no "Booking.com" brand, no review panel.
//
// ⚠️ FIXTURE EMAILS ARE CHOSEN: assertNoRawBlade() scans for literal '@if', '@empty' &c., so a guest at
// `sam@ifonly.com` would false-positive the HELPER. Domains below cannot collide.

beforeEach(function () {
    // book() always stays 2026-09-01→03 (the window seedScenario publishes availability for); freeze
    // mid-stay so stages are deterministic. TestCase::tearDown() resets Carbon's test-now.
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00'));
});

function profileGuest(array $extra = []): Guest
{
    return Guest::create(array_merge(['first_name' => 'Ada', 'last_name' => 'Lovelace'], $extra));
}

function profileStay(array $s, int $unitIndex, Guest $guest, string $status, string $checkIn = '2026-09-01', string $checkOut = '2026-09-03'): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);

    Reservation::query()->whereKey($reservation->getKey())->update([
        'lead_guest_id' => $guest->id,
        'status' => $status,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
    ]);

    return $reservation->fresh();
}

// ── 1. THE POINT OF THE PAGE — real guest, real flags, real tags, real notes, real stays ─────────
it('renders the real guest with contact, flags, tags, notes and every stay', function () {
    $s = seedScenario(2);
    $guest = profileGuest([
        'email' => 'ada.lovelace@example.test',
        'phone' => '+353860000001',
        'nationality' => 'IE',
        'is_vip' => true,
    ]);

    $stay = profileStay($s, 0, $guest, 'checked_in');
    postRoom($stay, 30000);

    $service = app(GuestService::class);
    $service->attachTag($guest, 'Corporate');
    $service->addNote($guest, 'Prefers the quiet side of the building.', User::factory()->create(['name' => 'Marco Staff']));

    $response = $this->actingAs(operatorUser($s))->get('/app/guests/'.$guest->id);

    $response->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('ada.lovelace@example.test')            // PII — this surface may show it (decision 4)
        ->assertSee('+353860000001')
        ->assertSee('VIP')                                   // the real boolean column
        ->assertSee('Corporate')                             // the real tag via the shared pivot
        ->assertSee('Prefers the quiet side of the building.')   // the real note
        ->assertSee('Marco Staff')                           // its real author
        // nationality → name, via the shared helper — from ICU (`ext-intl`); GitHub's image leaves it out on purpose
        // (DEPLOYMENT §7.5.1b), and there the documented fallback, the code, is shown in the same place
        ->assertSee(extension_loaded('intl') ? 'Ireland' : '<span class="vy-sep">·</span><span>IE</span>', false)
        ->assertSee($stay->reference)                        // the real stay
        ->assertSee('€300.00');                              // the real money

    assertNoRawBlade($response);
});

// ── 2. TENANT ISOLATION — another org's guest is a 404, not a render ─────────────────────────────
it('404s on another organization’s guest rather than rendering their profile', function () {
    $other = seedScenario(1);
    $foreign = profileGuest(['first_name' => 'Foreign', 'last_name' => 'Guest', 'email' => 'foreign@elsewhere.test']);

    $s = seedScenario(1);   // a second org — the tenant context now points here

    // Guest is BelongsToOrganization and the id is resolved through the global scope AFTER the tenant
    // middleware (never route-model-bound) — so a cross-org id cannot resolve at all.
    $this->actingAs(operatorUser($s))->get('/app/guests/'.$foreign->id)->assertNotFound();
});

// ── 3. THE /app ROLE GATE (invariant #26) — an owner never reaches a page full of guest PII ──────
it('never renders the profile for an owner-role user', function () {
    $s = seedScenario(1);
    $guest = profileGuest(['email' => 'ada.lovelace@example.test']);

    $response = $this->actingAs(ownerRoleUser($s, 0))->get('/app/guests/'.$guest->id);

    $response->assertRedirect(route('owner.dashboard'));
    $response->assertDontSee('ada.lovelace@example.test');
});

// ── 4. ⚠️ THE SAVE IS REAL — the design's fired a toast and persisted NOTHING ────────────────────
it('persists a contact edit through the real POST and shows it on reload', function () {
    $s = seedScenario(1);
    $guest = profileGuest(['email' => 'old.address@example.test', 'phone' => '+353860000001']);
    $operator = operatorUser($s);

    $response = $this->actingAs($operator)->post('/app/guests/'.$guest->id, [
        'first_name' => 'Ada',
        'last_name' => 'King',
        'email' => 'ada.king@example.test',
        'phone' => '+353860000002',
        'is_vip' => '1',
    ]);

    // PRG back to the profile with the flash the view renders.
    $response->assertRedirect(route('app.guests.show', $guest->id))->assertSessionHas('guest_notice');

    // IT SURVIVED THE REQUEST — in the DB…
    $fresh = Guest::query()->findOrFail($guest->id);
    expect($fresh->last_name)->toBe('King')
        ->and($fresh->email)->toBe('ada.king@example.test')
        ->and($fresh->is_vip)->toBeTrue();

    // …and on the page an operator actually reloads. A toast-only save passes neither of these.
    $this->actingAs($operator)->get('/app/guests/'.$guest->id)
        ->assertOk()
        ->assertSee('Ada King')
        ->assertSee('ada.king@example.test')
        ->assertDontSee('old.address@example.test');
});

it('clears a flag when its checkbox is unticked, not just when it is ticked', function () {
    $s = seedScenario(1);
    $guest = profileGuest(['is_vip' => true, 'risk_flag' => true]);

    // An unticked checkbox posts NOTHING — so a naive controller would leave both flags on forever, and an
    // operator could never clear a risk flag they had set by mistake.
    $this->actingAs(operatorUser($s))->post('/app/guests/'.$guest->id, [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    $fresh = Guest::query()->findOrFail($guest->id);
    expect($fresh->is_vip)->toBeFalse()->and($fresh->risk_flag)->toBeFalse();
});

it('rejects a malformed email with a visible error instead of a silent no-op', function () {
    $s = seedScenario(1);
    $guest = profileGuest(['email' => 'good@example.test']);

    $response = $this->actingAs(operatorUser($s))->post('/app/guests/'.$guest->id, [
        'first_name' => 'Ada',
        'email' => 'not-an-email',
    ]);

    // validate() is used here precisely BECAUSE this view renders the $errors bag — on a flash-only screen
    // a ValidationException would redirect with an error bag nothing displays (the invisible no-op the
    // sibling reservation controllers hand-check to avoid).
    $response->assertSessionHasErrors('email');
    expect(Guest::query()->findOrFail($guest->id)->email)->toBe('good@example.test');
});

// ── 5. NOTES + TAGS write for real, from the page ────────────────────────────────────────────────
it('adds a note and a tag through the real POSTs and renders them', function () {
    $s = seedScenario(1);
    $guest = profileGuest();
    $operator = operatorUser($s);

    $this->actingAs($operator)->post('/app/guests/'.$guest->id.'/notes', ['body' => 'Late arrival — hold the room.'])
        ->assertRedirect(route('app.guests.show', $guest->id));
    $this->actingAs($operator)->post('/app/guests/'.$guest->id.'/tags', ['name' => 'Repeat'])
        ->assertRedirect(route('app.guests.show', $guest->id));

    expect(Note::query()->where('notable_id', $guest->id)->count())->toBe(1)
        ->and($guest->fresh()->tags()->count())->toBe(1);

    $this->actingAs($operator)->get('/app/guests/'.$guest->id)
        ->assertOk()->assertSee('Late arrival — hold the room.')->assertSee('Repeat');

    // Untag — through the real DELETE. The catalog entry survives (it is the workspace's vocabulary).
    $tag = Tag::query()->where('name', 'Repeat')->firstOrFail();
    $this->actingAs($operator)->delete('/app/guests/'.$guest->id.'/tags', ['tag' => $tag->id])
        ->assertRedirect(route('app.guests.show', $guest->id));

    expect($guest->fresh()->tags()->count())->toBe(0)
        ->and(Tag::query()->where('name', 'Repeat')->count())->toBe(1);
});

it('404s rather than detaching another organization’s tag', function () {
    $other = seedScenario(1);
    $foreignTag = Tag::create(['name' => 'ForeignTag9', 'color' => '#000000']);

    $s = seedScenario(1);
    $guest = profileGuest();

    // The tag id comes from the request body, so it is re-resolved through the tenant scope — never trusted.
    $this->actingAs(operatorUser($s))
        ->delete('/app/guests/'.$guest->id.'/tags', ['tag' => $foreignTag->id])
        ->assertNotFound();
});

// ── 6. AN ANONYMIZED GUEST CANNOT BE RE-POPULATED WITH PII ───────────────────────────────────────
it('refuses to edit an anonymized guest rather than quietly un-erasing them', function () {
    $s = seedScenario(1);
    $guest = profileGuest(['email' => 'ada.lovelace@example.test']);
    $stay = profileStay($s, 0, $guest, 'checked_out');
    postRoom($stay, 30000);

    $guest->anonymize();

    $response = $this->actingAs(operatorUser($s))->post('/app/guests/'.$guest->id, [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada.lovelace@example.test',
    ]);

    $response->assertSessionHas('guest_error');

    // The erase holds: the row is kept ONLY so the financial history referencing it stays intact.
    $fresh = Guest::query()->findOrFail($guest->id);
    expect($fresh->first_name)->toBeNull()->and($fresh->email)->toBeNull();

    // And the page says so instead of offering a form that would fail.
    $this->actingAs(operatorUser($s))->get('/app/guests/'.$guest->id)
        ->assertOk()
        ->assertSee('Anonymized guest')
        ->assertSee('€300.00');   // the history it carries survives
});

// ── 7. ⚠️ THE PROFILE AND THE LIST AGREE — the whole point of the shared definitions ─────────────
it('shows the same billed, balance and stay count as the guests list, from the shared helpers', function () {
    $s = seedScenario(3);
    $guest = profileGuest();

    $first = profileStay($s, 0, $guest, 'checked_out', '2026-08-01', '2026-08-03');
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($first);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));
    $folios->recordPayment($folio, moneyEur(10000));      // billed 300, paid 100 → balance 200

    $second = profileStay($s, 1, $guest, 'confirmed', '2026-09-20', '2026-09-22');
    postRoom($second, 20000);                             // billed 200, unpaid → balance 200

    profileStay($s, 2, $guest, 'cancelled', '2026-10-01', '2026-10-03');   // never happened

    $operator = operatorUser($s);
    $list = $this->actingAs($operator)->get('/app/guests');
    $profile = $this->actingAs($operator)->get('/app/guests/'.$guest->id);

    // Both pages, the same figures — because both read GuestSpend and GuestHistory rather than deriving.
    foreach ([$list, $profile] as $response) {
        $response->assertOk()
            ->assertSee('€500.00')    // BILLED — 300 + 200, never netted against the payment
            ->assertSee('€400.00');   // OPEN BALANCE — beside it, never folded in
    }

    // …and they reconcile to the ledger the helper reads.
    $buckets = GuestSpend::for($guest->fresh());
    expect($buckets['EUR']['billed_minor'])->toBe(50000)
        ->and($buckets['EUR']['balance_minor'])->toBe(40000);

    // 3 bookings, 2 stays — the cancelled one is not a stay, on BOTH screens.
    expect($guest->reservations()->count())->toBe(3);
    $profile->assertSee('2 stays');
});

// ── 8. THE DESIGN'S FABRICATIONS ARE ABSENT ──────────────────────────────────────────────────────
it('renders the real vocabulary and honest states — no fabricated review or brand (#87)', function () {
    $s = seedScenario(1);
    $guest = profileGuest();
    $stay = profileStay($s, 0, $guest, 'checked_out', '2026-08-01', '2026-08-03');
    // The real source enum has no brand — this is an OTA booking, not a "Booking.com" one.
    Reservation::query()->whereKey($stay->getKey())->update(['source' => 'ota']);

    $response = $this->actingAs(operatorUser($s))->get('/app/guests/'.$guest->id);

    $response->assertOk()
        // The real vocabulary, from the shared helpers.
        ->assertSee('Past')
        ->assertSee('OTA')
        // "Completed" is NOT in the status enum; the OTA brand is not modelled on any table.
        ->assertDontSee('Completed')
        ->assertDontSee('Booking.com')
        // #87 — the review panel is now REAL (reviews module #76) with an HONEST empty state (this guest has
        // none), never a fabricated rating.
        //
        // ⚠️⚠️ THE NEEDLE WAS `'5.0'`, AND IT WAS THE ONLY REASON THIS TEST WAS RED — the product was right all
        // along. Three characters is too small a target: the ONE occurrence in 213 KB of markup was inside a
        // shipped CSS comment from the a11y token work — *"rgb(147,42,7) clears the darkest sampled tint at
        // 5.06"* — a CONTRAST RATIO, in `components/layouts/operator.blade.php`. Blade comments are stripped,
        // but CSS comments SHIP (§N.15.3), so the layout carried `5.06` into every operator page and `'5.0'`
        // matched the substring. The assertion was therefore asserting nothing about reviews at all.
        //
        // ⚠️ DERIVED FROM THE WAY THE VIEW WRITES A RATING, not typed by hand: the panel renders
        // `aria-label="{rating} out of 5 stars"` and the bold figure beside it ONLY when a review exists. So
        // that phrase is present exactly when a rating is on screen and cannot collide with a ratio, a price
        // or a version. The companion test below seeds a REAL review and proves the phrase appears — without
        // that, this `assertDontSee` would be the same phantom in a longer costume.
        ->assertSee('Latest review')
        ->assertSee('No reviews yet')
        ->assertDontSee('out of 5 stars')
        // #87 — the preferences flow is now REAL; the hint is truthful (front desk + housekeeping do read it).
        ->assertSee('Shown to housekeeping')
        // Omitted by precedent: no billing backend reads subscriptions.status.
        ->assertDontSee('Workspace paused');
});

// ── 8b. ⚠️⚠️ THE NEEDLE CAN REGISTER A POSITIVE — otherwise 8's assertDontSee proves nothing ──────
it('renders a real review rating, which is what makes the no-fabrication assertion meaningful', function () {
    $s = seedScenario(1);
    $guest = profileGuest();
    $stay = profileStay($s, 0, $guest, 'checked_out', '2026-08-01', '2026-08-03');

    // A REAL review by this guest — the only thing that puts a rating on the page.
    Review::create([
        'organization_id' => $s['org']->id,
        'reservation_id' => $stay->getKey(),
        'guest_id' => $guest->getKey(),
        'rating' => 5,
        'body' => 'A genuinely lovely stay.',
        'status' => 'published',
    ]);

    $html = $this->actingAs(operatorUser($s))->get('/app/guests/'.$guest->id)->assertOk()->getContent();

    // ⚠️ THE PROBE RULE: the phrase test 8 forbids DOES appear when a rating is genuinely rendered, so its
    // absence there is evidence rather than an accident of wording.
    expect($html)->toContain('out of 5 stars')
        ->and($html)->toContain('5.0')          // the real figure, rendered by number_format($rating, 1)
        ->and($html)->not->toContain('No reviews yet');
});

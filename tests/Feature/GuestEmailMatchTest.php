<?php

declare(strict_types=1);

use App\Models\Guest;
use App\Models\Reservation;
use App\Services\RateAndRestrictionService;
use App\Support\Guest\GuestEmailMatch;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

uses(DatabaseTransactions::class);

// DEC-r (2026-11-11, his "I like your suggestions"): the New reservation wizard created a NEW guest even when the typed email already belonged to one
// (the walk made four "Lena Walk"s). It now OFFERS the existing guest — found by the email's blind index, shown by NAME only (#28), never the email.

const GEM_EMAIL = 'lena.walk@example.test';

/** a priced scenario with an operator who may create reservations, and an existing guest holding GEM_EMAIL */
function gem_world(): array
{
    $s = seedScenario(2);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-30', Money::of(10000, 'EUR'));
    $guest = Guest::create(['first_name' => 'Lena', 'last_name' => 'Walk', 'email' => GEM_EMAIL, 'phone' => '+351900111222']);

    // ⚠️ not `guest`: seedScenario() already holds a default guest under that key and `+` keeps the left one
    return $s + ['operator' => operatorUser($s, [], 'Front Desk'), 'lena' => $guest];
}

function gem_lookup(array $w, string $email, ?object $as = null): TestResponse
{
    return test()->actingAs($as ?? $w['operator'])->postJson(route('app.reservations.guest-match'), ['email' => $email]);
}

function gem_booking(array $w, array $overrides = []): array
{
    return array_merge(['check_in' => '2026-09-02', 'check_out' => '2026-09-05', 'room_type_id' => $w['roomType']->id, 'unit_id' => $w['units'][0]->id,
        'guest_first_name' => 'Lena', 'guest_last_name' => 'Walk', 'guest_email' => GEM_EMAIL, 'adults' => '2', 'children' => '0', 'source' => 'direct'], $overrides);
}

it('⚠️⚠️ the lookup answers with the guest\'s NAME and stay history — never the email, never the phone', function () {
    $w = gem_world();

    $res = gem_lookup($w, GEM_EMAIL)->assertOk();
    $body = $res->getContent();

    expect($res->json('matches'))->toHaveCount(1)
        ->and($res->json('matches.0.id'))->toBe((string) $w['lena']->id)
        ->and($res->json('matches.0.name'))->toBe('Lena Walk')
        ->and($res->json('matches.0.history'))->toBeString()
        ->and(array_keys($res->json('matches.0')))->toBe(['id', 'name', 'history'])   // nothing else rides along
        ->and($body)->not->toContain('lena.walk')->and($body)->not->toContain('example.test')->and($body)->not->toContain('351900');
    expect((string) $res->headers->get('Cache-Control'))->toContain('no-store');
});

it('finds the guest whatever the email\'s case or stray spaces, and no one for an email nobody holds or a malformed one', function () {
    $w = gem_world();

    expect(gem_lookup($w, '  LENA.Walk@Example.TEST ')->json('matches'))->toHaveCount(1)
        ->and(gem_lookup($w, 'nobody@example.test')->json('matches'))->toBe([])
        ->and(gem_lookup($w, 'not-an-email')->json('matches'))->toBe([])
        ->and(GuestEmailMatch::for(''))->toBe([]);
});

it('⚠️⚠️ never offers another organisation\'s guest, an erased guest, or one whose erasure is scheduled', function () {
    $w = gem_world();
    $other = gem_world();   // a second organisation holding the SAME email on its own guest

    // a permitted actor in organisation A: only A's guest comes back (the 404-vs-403 trap: the actor is PERMITTED, the isolation is the scope).
    // (the test process holds the LAST scenario's organisation as its tenant, so it is set back to A for the request)
    app(TenantContext::class)->set((string) $w['org']->id);
    $ids = array_column(gem_lookup($w, GEM_EMAIL)->json('matches'), 'id');
    expect($ids)->toBe([(string) $w['lena']->id])->and($ids)->not->toContain((string) $other['lena']->id);

    $w['lena']->forceFill(['scheduled_deletion_at' => now()->addDays(10)])->save();
    expect(gem_lookup($w, GEM_EMAIL)->json('matches'))->toBe([]);

    $w['lena']->forceFill(['scheduled_deletion_at' => null])->save();

    // ⚠️ the guard on its OWN: a row marked erased whose email index is still there (a half-finished erase). A real `anonymize()` clears the
    // index, so the check below alone could not tell whether `anonymized_at` is read — this one can (mutation GM-M2).
    DB::table('guests')->where('id', $w['lena']->id)->update(['anonymized_at' => now()]);
    expect(gem_lookup($w, GEM_EMAIL)->json('matches'))->toBe([]);
    DB::table('guests')->where('id', $w['lena']->id)->update(['anonymized_at' => null]);
    expect(gem_lookup($w, GEM_EMAIL)->json('matches'))->toHaveCount(1);

    $w['lena']->anonymize();
    expect(gem_lookup($w, GEM_EMAIL)->json('matches'))->toBe([]);
});

it('offers up to three of the duplicates that already exist, each with its own history', function () {
    $w = gem_world();
    foreach (['Lena', 'Lena', 'Lena'] as $i => $first) {
        Guest::create(['first_name' => $first, 'last_name' => 'Walk'.$i, 'email' => GEM_EMAIL]);
    }

    expect(gem_lookup($w, GEM_EMAIL)->json('matches'))->toHaveCount(GuestEmailMatch::LIMIT);
});

it('⚠️ is gated like the booking itself: a role without reservations.create gets 403, a visitor is sent to sign in, and it is throttled', function () {
    $w = gem_world();
    $housekeeper = operatorUser($w, [], 'Housekeeping');   // holds no reservations.create

    gem_lookup($w, GEM_EMAIL, $housekeeper)->assertForbidden();
    auth()->logout();
    test()->postJson(route('app.reservations.guest-match'), ['email' => GEM_EMAIL])->assertRedirect();   // a visitor is sent to sign in; no names

    $codes = [];
    for ($i = 0; $i < 62; $i++) {
        $codes[] = gem_lookup($w, GEM_EMAIL)->status();
    }
    expect($codes)->toContain(429);
});

it('⚠️⚠️ the server\'s own net: booking with a typed email that belongs to a guest creates NOTHING and names the guest only — until "Create a new one" is chosen', function () {
    $w = gem_world();
    $guests = Guest::query()->count();

    $res = test()->actingAs($w['operator'])->post(route('app.reservations.store'), gem_booking($w));
    $res->assertRedirect();
    expect(Guest::query()->count())->toBe($guests)                          // no second Lena Walk
        ->and(Reservation::query()->count())->toBe(0);
    $flash = json_encode(session()->all());
    expect($flash)->toContain('A guest with this email already exists (Lena Walk)')
        ->and($flash)->not->toContain('lena.walk@example.test')              // the email is not flashed back (#28)
        ->and(old('guest_email'))->toBeNull();

    // the operator chose "Create a new one": the page sends guest_create_anyway and a new guest is made
    test()->actingAs($w['operator'])->post(route('app.reservations.store'), gem_booking($w, ['guest_create_anyway' => '1']))->assertRedirect();
    expect(Guest::query()->count())->toBe($guests + 1);
});

it('a different email, or none, is untouched by the net (no offer, no refusal)', function () {
    $w = gem_world();

    test()->actingAs($w['operator'])->post(route('app.reservations.store'), gem_booking($w, ['guest_email' => 'someone.else@example.test']))->assertRedirect();
    test()->actingAs($w['operator'])->post(route('app.reservations.store'), gem_booking($w, ['guest_email' => '', 'guest_first_name' => 'Walk-in', 'guest_last_name' => 'Two', 'unit_id' => $w['units'][1]->id]))->assertRedirect();

    expect(Guest::query()->where('first_name', 'Lena')->count())->toBe(2)   // the original and the new one with a DIFFERENT email — no offer, no refusal
        ->and(Reservation::query()->count())->toBe(2);
});

it('⚠️ "Use this guest": an existing_guest_id books onto them, but never onto an erased guest or one whose erasure is scheduled', function () {
    $w = gem_world();

    test()->actingAs($w['operator'])->post(route('app.reservations.store'), gem_booking($w, ['existing_guest_id' => (string) $w['lena']->id, 'guest_email' => '']))->assertRedirect();
    expect(Reservation::query()->where('lead_guest_id', $w['lena']->id)->count())->toBe(1);

    $w['lena']->forceFill(['scheduled_deletion_at' => now()->addDays(5)])->save();
    test()->actingAs($w['operator'])->post(route('app.reservations.store'), gem_booking($w, ['existing_guest_id' => (string) $w['lena']->id, 'guest_email' => '', 'unit_id' => $w['units'][1]->id]))->assertRedirect();
    expect(Reservation::query()->count())->toBe(1)   // refused: nothing was added
        ->and(json_encode(session()->all()))->toContain('could not be found');
});

it('the wizard page carries the offer: the lookup address, the card, the hidden "create anyway" flag — and no email in it', function () {
    $w = gem_world();
    $html = (string) test()->actingAs($w['operator'])->get(route('app.reservations.create').'?check_in=2026-09-02&check_out=2026-09-05')->assertOk()->getContent();

    expect($html)->toContain('guestMatchUrl')->and($html)->toContain('reservations\\/guest-match')   // the address, in the page's JSON
        ->and($html)->toContain('name="guest_create_anyway"')
        ->and($html)->toContain('A guest with this email already exists')
        ->and($html)->toContain('Use this guest')->and($html)->toContain('Create a new one')
        ->and($html)->not->toContain('lena.walk');
});

<?php

use App\Models\Guest;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Review;
use App\Models\Unit;
use App\Models\User;
use App\Services\AssignmentService;
use App\Support\Guest\GuestName;
use App\Support\Reviews\ReviewList;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER REVIEWS + STOREFRONT DISPLAY (Phase 5 · Sub-gate 4). Proven: an owner sees ONLY their OWN units' reviews
// (owner-scoped, mutation-proven A can't see B's); the public review list shows PUBLISHED reviews only (a
// pending/private one never appears) with a #28 first-name byline; anonymize de-identifies the public byline.

if (! function_exists('revLinkedOwner')) {
    function revLinkedOwner(array $s, int $unitIndex): array
    {
        $user = User::factory()->create();
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
        $s['units'][$unitIndex]->update(['owner_id' => $owner->id]);

        return ['user' => $user, 'owner' => $owner, 'unit' => $s['units'][$unitIndex]];
    }
}

if (! function_exists('revPublishedOn')) {
    function revPublishedOn(array $s, Unit $unit, string $body, string $in = '2026-09-05', string $out = '2026-09-08', string $status = 'published', string $visibility = 'public'): Review
    {
        $res = app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
        );

        return Review::create([
            'guest_id' => $s['guest']->getKey(),
            'reservation_id' => $res->getKey(),
            'unit_id' => $unit->getKey(),
            'room_type_id' => $unit->room_type_id,
            'rating' => 5,
            'tags' => [],
            'body' => $body,
            'visibility' => $visibility,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }
}

// ── OWNER ISOLATION (mutation-proven): an owner sees ONLY their own units' reviews ────────────────
it('shows an owner only their own units’ reviews', function () {
    $s = seedScenario(2);
    $a = revLinkedOwner($s, 0);
    $b = revLinkedOwner($s, 1);
    revPublishedOn($s, $a['unit'], 'A-unit review AAAA');
    revPublishedOn($s, $b['unit'], 'B-unit review BBBB');

    $this->actingAs($a['user'])->get('/owner/reviews')
        ->assertOk()->assertSee('A-unit review AAAA')->assertDontSee('B-unit review BBBB');

    $this->actingAs($b['user'])->get('/owner/reviews')
        ->assertOk()->assertSee('B-unit review BBBB')->assertDontSee('A-unit review AAAA');
});

// ── The public review list is PUBLISHED-only (pending/private never appear) ───────────────────────
it('lists only published reviews for the public/owner surfaces', function () {
    $s = seedScenario(1);
    revPublishedOn($s, $s['units'][0], 'Published AAAA', '2026-09-01', '2026-09-02', 'published', 'public');
    revPublishedOn($s, $s['units'][0], 'Pending BBBB', '2026-09-02', '2026-09-03', 'pending', 'public');
    revPublishedOn($s, $s['units'][0], 'Private CCCC', '2026-09-03', '2026-09-04', 'pending', 'private');

    $list = ReviewList::forRoomType((string) $s['roomType']->getKey());
    $bodies = array_column($list, 'body');
    expect($bodies)->toContain('Published AAAA');
    expect($bodies)->not->toContain('Pending BBBB');
    expect($bodies)->not->toContain('Private CCCC');
});

// ── #28 — the public byline is first name + last initial only (never full contact) ────────────────
it('shows a first-name + initial byline, de-identified after anonymize', function () {
    seedScenario(1);   // sets the tenant context for the guest create
    $guest = Guest::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.test']);
    expect(GuestName::reviewByline($guest))->toBe('Ada L.');

    $guest->forceFill(['first_name' => null, 'last_name' => null])->save();   // as anonymize() leaves it
    expect(GuestName::reviewByline($guest->fresh()))->toBe('A guest');
});

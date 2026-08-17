<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\User;
use App\Notifications\Operator\NewBookingNotification;
use App\Notifications\Owner\OwnerMessageReceivedNotification;
use App\Notifications\Owner\OwnerNewBookingNotification;
use App\Notifications\Owner\OwnerReviewPostedNotification;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\InboxService;
use App\Services\NotificationPreferences;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// OWNER FIX 2 — three owner-audience notification types (owner audit, page 16 item 26).
//
// ⚠️⚠️ THE AUDIENCE MODEL FORCED THE DESIGN, AND TEST 5 IS WHY. `NotificationPreferences::CATALOG` models
// `audience` as a SINGLE STRING — there is no set, and no type serves two portals. `new_booking` and
// `new_review` already exist as `operator`, so "give the owner these too" could NOT mean reusing those keys:
// it would have meant flipping their audience and silently stranding every operator who relies on them. The
// owner gets its own keys; test 5 pins the operator's originals so a future "tidy-up" that merges them reds.
//
// ⚠️ #28 IS THE POINT OF TESTS 2/3/4. A notification is a NEW surface for a leak and it is PUSHED — an owner
// does not choose to open it, and the email channel can mirror it into an inbox. So each body is asserted
// POSITIVELY (it carries the unit / rating / reference an owner needs) and NEGATIVELY (it carries no guest
// name, email or phone). A negative alone could pass on an empty string.

const ONT_EMAIL = 'ont.guest@example.test';

const ONT_PHONE = '+351 912 000 111';

/** An owner who owns unit 0, plus a guest carrying REAL contact detail for the #28 assertions to bite on. */
function ontCast(): array
{
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $owner = Owner::query()->where('user_id', $user->getKey())->firstOrFail();

    // ⚠️ THE #28 FIXTURE TRAP: without real contact detail on the guest, "the body contains no email" would
    // pass even if the body echoed the guest wholesale. The value must exist to be absent.
    $s['guest']->forceFill(['email' => ONT_EMAIL, 'phone' => ONT_PHONE, 'first_name' => 'Zaraphina', 'last_name' => 'Quillfeather'])->save();

    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-06-01', '2026-06-30');

    return ['s' => $s, 'user' => $user, 'owner' => $owner, 'unit' => $s['units'][0]];
}

/**
 * The owner's own feed rows for ONE notification class, read raw.
 *
 * ⚠️ FILTERED ON THE `type` COLUMN, NOT ON THE PAYLOAD. FeedNotification::toArray() returns data() verbatim,
 * so there is no `event_key` inside the JSON to match on — my first version filtered on one and every lookup
 * silently returned an empty set, which looks exactly like "the notification never fired".
 *
 * @param  class-string  $class
 * @return list<array<string, mixed>>
 */
function ontFeed(User $user, string $class): array
{
    return DB::table('notifications')
        ->where('notifiable_id', $user->getKey())
        ->where('type', $class)
        ->orderByDesc('created_at')
        ->get()
        ->map(fn ($r): array => json_decode((string) $r->data, true) ?: [])
        ->values()
        ->all();
}

/** Book unit 0 through the certified path — the real `reservation.confirmed` event. */
function ontBook(array $f, string $in = '2026-06-10', string $out = '2026-06-13'): object
{
    return app(AssignmentService::class)->createBooking(
        ['property_id' => $f['s']['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out,
            'lead_guest_id' => $f['s']['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $f['s']['roomType']->id, 'unit_id' => $f['unit']->id, 'rate_plan_id' => $f['s']['ratePlan']->id,
            'check_in' => $in, 'check_out' => $out, 'price_minor' => 30000, 'currency' => 'EUR']],
    );
}

/** ⚠️ Assert a body says something useful AND nothing about the guest. */
function ontAssertPiiSafe(array $data): void
{
    $blob = json_encode($data) ?: '';

    expect($blob)->not->toContain(ONT_EMAIL)
        ->and($blob)->not->toContain(ONT_PHONE)
        ->and($blob)->not->toContain('Zaraphina')
        ->and($blob)->not->toContain('Quillfeather');
}

// ── 1. ⚠️⚠️ A BOOKING ON THE OWNER'S UNIT NOTIFIES THAT OWNER ─────────────────────────────────────────
it('notifies the owner when a booking is confirmed on their unit', function () {
    $f = ontCast();

    $reservation = ontBook($f);

    $rows = ontFeed($f['user'], OwnerNewBookingNotification::class);
    expect($rows)->toHaveCount(1);

    // ⚠️ POSITIVE: the row is genuinely useful — the unit and the reference the owner can look up.
    $data = $rows[0];
    expect($data['title'])->toContain((string) $f['unit']->code)
        ->and($data['context'])->toContain((string) $reservation->reference)
        ->and($data['url'])->toBe(route('owner.bookings', [], false));

    // ⚠️ NEGATIVE: #28 — nothing about the person.
    ontAssertPiiSafe($data);
});

// ── 2. ⚠️⚠️ A PUBLISHED REVIEW NOTIFIES THE OWNER — rating + unit, no reviewer ─────────────────────────
it('notifies the owner when a review is published on their unit, naming no reviewer', function () {
    $f = ontCast();
    $reservation = ontBook($f, '2026-06-02', '2026-06-04');

    // ⚠️ THROUGH THE CERTIFIED SERVICE, so the REAL `review.submitted` event fires — a hand-built row would
    // record nothing and this test would prove only that Review::create() works. The stay must be completed
    // first, which is why the dates above are in the past relative to the check.
    DB::table('reservations')->where('id', $reservation->getKey())
        ->update(['status' => 'checked_out']);

    app(ReviewService::class)->submit($f['s']['guest'], $reservation->fresh(), [
        'rating' => 5,
        // ⚠️ The body deliberately carries a name AND a phone number — see the #28 assertion below.
        'body' => 'Zaraphina had a lovely stay — call me on '.ONT_PHONE,
        'visibility' => 'public',
    ]);

    $rows = ontFeed($f['user'], OwnerReviewPostedNotification::class);
    expect($rows)->toHaveCount(1);

    $data = $rows[0];
    expect($data['title'])->toContain('5★')
        ->and($data['title'])->toContain((string) $f['unit']->code);

    // ⚠️ The review BODY deliberately contains a name and a phone number. If the notification echoed the body
    // — the obvious "helpful" implementation — this is what would leak, and this assertion is what catches it.
    ontAssertPiiSafe($data);
});

// ── 3. ⚠️⚠️ AN OPERATOR MESSAGE NOTIFIES THE OWNER — and the owner's OWN reply does not ────────────────
it('notifies the owner when their operator messages them, but never about their own reply', function () {
    $f = ontCast();

    $thread = MessageThread::create([
        'organization_id' => $f['s']['org']->id,
        'property_id' => $f['s']['property']->id,
        'owner_id' => $f['owner']->getKey(),
        'channel' => 'manual',
        'subject' => 'September statement',
        'status' => 'open',
        'last_message_at' => now(),
    ]);

    app(InboxService::class)->sendOutbound($thread, 'Your statement is ready.');

    $rows = ontFeed($f['user'], OwnerMessageReceivedNotification::class);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['context'])->toBe('September statement');
    ontAssertPiiSafe($rows[0]);

    // ⚠️ THE OWNER'S OWN MESSAGE MUST NOT NOTIFY THEM. It is recorded `inbound`, which this listener does not
    // handle — proven by the count staying at 1 rather than by reading the code.
    app(InboxService::class)->postOwnerMessage($thread, 'Thanks!');
    expect(ontFeed($f['user'], OwnerMessageReceivedNotification::class))->toHaveCount(1);
});

// ── 4. ⚠️⚠️ ISOLATION — an owner is never notified about another owner's unit ──────────────────────────
it('does not notify an owner about a booking on a unit they do not own', function () {
    $f = ontCast();

    // A second owner in the same org, owning nothing on this reservation.
    $otherUser = User::factory()->create();
    Membership::create(['organization_id' => $f['s']['org']->id, 'user_id' => $otherUser->id, 'status' => 'active']);
    Owner::factory()->create(['user_id' => $otherUser->id, 'payout_currency' => 'EUR']);

    ontBook($f);

    // ⚠️ THE PREMISE: the real owner DID get one, so an empty feed for the other is isolation, not a dead path.
    expect(ontFeed($f['user'], OwnerNewBookingNotification::class))->toHaveCount(1)
        ->and(ontFeed($otherUser, OwnerNewBookingNotification::class))->toHaveCount(0);
});

// ── 5. ⚠️⚠️ THE OPERATOR WAS NOT STRANDED — their keys are untouched, and still fire ───────────────────
it('leaves the operator new_booking and new_review keys operator-audience and still firing', function () {
    $f = ontCast();

    // ⚠️ THE CATALOGUE CONTRACT: the operator keys still exist and still belong to the operator.
    expect(NotificationPreferences::CATALOG['new_booking']['audience'])->toBe('operator')
        ->and(NotificationPreferences::CATALOG['new_review']['audience'])->toBe('operator')
        // …and the three owner keys are genuinely owner-audience, so the prefs matrix will show them.
        ->and(NotificationPreferences::CATALOG['owner_new_booking']['audience'])->toBe('owner')
        ->and(NotificationPreferences::CATALOG['owner_review_posted']['audience'])->toBe('owner')
        ->and(NotificationPreferences::CATALOG['owner_message']['audience'])->toBe('owner');

    // ⚠️ AND IT STILL DELIVERS — a contract assertion alone would not catch a broken dispatch.
    $operator = operatorUser($f['s'], [], 'Hotel Manager');
    ontBook($f);

    expect(ontFeed($operator, NewBookingNotification::class))->toHaveCount(1);
});

// ── 6. ⚠️ PREFERENCES REALLY GATE THE OWNER'S NEW TYPES (they are not always-on) ───────────────────────
it('suppresses an owner notification when that owner turns its in-app channel off', function () {
    $f = ontCast();

    // ⚠️ save() writes the WHOLE owner catalogue, defaulting anything absent to off — that is its contract,
    // not a quirk of this call. Only `owner_new_booking` is asserted below.
    app(NotificationPreferences::class)->save($f['user'], 'owner', ['owner_new_booking' => ['in_app' => false]]);

    ontBook($f);

    // ⚠️ RAW: the toggle genuinely suppressed delivery — the gate runs in FeedNotification::via().
    expect(ontFeed($f['user'], OwnerNewBookingNotification::class))->toHaveCount(0)
        // …and the type is not accidentally always-on, which would make the toggle cosmetic.
        ->and(NotificationPreferences::CATALOG['owner_new_booking']['always_on'])->toBeFalse();
});

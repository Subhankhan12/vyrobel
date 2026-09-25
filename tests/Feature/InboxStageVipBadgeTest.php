<?php

/*
 * D10-2 + D10-5 — THE STAGE AND VIP BADGES ON THE OPERATOR INBOX CONVERSATION (2026-08-15).
 *
 * ⚠️ BOTH ARE REUSE, NOT REBUILD, and the reuse is the point of each:
 *   • D10-2 — the stage comes from `ReservationStage`, the ONE resolver the Reservations list, the Guests
 *     CRM, the Guest profile and the owner surfaces already share. Three screens showing the same guest must
 *     never disagree about whether that guest is in-house.
 *   • D10-5 — the VIP badge is the SAME markup the front desk renders for the SAME `guests.is_vip` column:
 *     identical classes, identical dot, identical word.
 *
 * ⚠️ A PREVIOUS PASS FOUND D10-2 AND STOPPED FOR A GOOD REASON, recorded in the Blade: `reservationCard()`
 * carried the RAW `status`, and `ReservationStage::meta()` takes a computed STAGE — feeding it a status
 * renders "Unknown". Rather than re-derive the vocabulary in a template, that pass logged the gap. This one
 * calls the resolver in the COMPONENT, which is what the note was waiting for. ⚠️ A STAGE IS NOT A STATUS:
 * `status` is the 7-value enum, `stage` a derived bucket over that enum PLUS the dates. Both stay on the card.
 *
 * ⚠️ BOTH ARE SHOW-WHEN-APPLICABLE. A thread with no reservation shows no stage badge — not "No stage", not
 * an empty chip. A non-VIP thread shows no VIP badge, which is almost every thread: a badge that appeared for
 * everyone would say nothing.
 *
 * ⚠️ `is_vip` IS POPULATED ON DEMO — 1 of 10 guests, and that guest HAS a thread, so unlike D1-3's 0-of-41
 * both branches are observable. Forced here anyway, because a guard must not depend on demo data.
 */

use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL and has collided in this suite before. */
function d102Operator(array $s)
{
    return operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
}

/** A guest thread, optionally linked to a reservation — the inbox's real shape. */
function d102Thread(array $s, ?Reservation $reservation, ?Guest $guest = null): MessageThread
{
    $guest ??= $s['guest'];

    return MessageThread::create([
        'organization_id' => $s['org']->id,
        'guest_id' => $guest->getKey(),
        'reservation_id' => $reservation?->getKey(),
        // ⚠️ A REAL enum member — the column is enum('manual','email','whatsapp','booking_com','airbnb')
        // and MySQL truncates an invalid one with a WARNING, not an error, so a guessed value fails at insert.
        'channel' => 'manual',
        'subject' => 'A question about the stay',
        'status' => 'open',
        'last_message_at' => now(),
    ]);
}

/** Open the inbox on a specific thread, as the operator. */
function d102Inbox(array $s, MessageThread $thread): string
{
    return (string) test()->actingAs(d102Operator($s))
        ->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/inbox?thread='.$thread->getKey())
        ->assertOk()->getContent();
}

// ── D10-2 · THE STAGE BADGE ───────────────────────────────────────────────────────────────────────

it('⚠️ D10-2 — the stage badge renders for a thread WITH a reservation, from the shared resolver', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // ⚠️ THE EXPECTED LABEL COMES FROM THE RESOLVER, not a hardcoded word: if the stage vocabulary is ever
    // retuned, this follows it instead of pinning the old value (DO-RESTORE-2's trap).
    $expected = ReservationStage::meta(ReservationStage::for($reservation->fresh()))['label'];
    expect($expected)->not->toBeEmpty();

    $html = d102Inbox($s, d102Thread($s, $reservation));

    expect($html)->toContain($expected)
        // The badge markup is the Reservations list's, not a new one (§N.8f).
        ->and($html)->toMatch('/<span class="vy-tag tone-[a-z-]+"><span class="vy-dot"><\/span>'.preg_quote($expected, '/').'</');
});

it('⚠️ D10-2 — the badge tracks the REAL stage: a checked-in stay reads differently from an upcoming one', function () {
    /*
     * ⚠️ THE ASSERTION THAT MAKES THE BADGE MEAN SOMETHING. Rendering *a* badge proves nothing; rendering
     * the RIGHT one does. Two reservations in genuinely different lifecycle states must produce two
     * different labels, both resolved by the shared helper rather than by this test.
     */
    $s = seedScenario(2);

    $upcoming = book($s, ['unit_id' => $s['units'][0]->id]);   // a future confirmed stay
    $inHouse = book($s, ['unit_id' => $s['units'][1]->id]);
    Reservation::withoutGlobalScopes()->whereKey($inHouse->getKey())->update([
        'status' => 'checked_in',
        'check_in' => CarbonImmutable::today()->subDay()->toDateString(),
        'check_out' => CarbonImmutable::today()->addDay()->toDateString(),
    ]);

    $upcomingLabel = ReservationStage::meta(ReservationStage::for($upcoming->fresh()))['label'];
    $inHouseLabel = ReservationStage::meta(ReservationStage::for($inHouse->fresh()))['label'];

    // ⚠️ NON-VACUITY: the two stages really are different, so the page assertions below discriminate.
    expect($upcomingLabel)->not->toBe($inHouseLabel);

    expect(d102Inbox($s, d102Thread($s, $upcoming)))->toContain($upcomingLabel);
    expect(d102Inbox($s, d102Thread($s, $inHouse)))->toContain($inHouseLabel);
});

it('⚠️ D10-2 — a thread with NO reservation shows NO stage badge (and no "unknown" placeholder)', function () {
    $s = seedScenario(1);
    $thread = d102Thread($s, null);

    // ⚠️ NON-VACUITY: the thread really has no reservation, so an absent badge means omitted.
    expect($thread->reservation_id)->toBeNull();

    $html = d102Inbox($s, $thread);

    // The conversation renders…
    expect($html)->toContain('vy-thread-head')
        // …with no stage chip and, critically, no "Unknown" — the failure mode the earlier note predicted
        // if a raw status were ever fed to meta().
        ->and($html)->not->toContain('Unknown')
        ->and($html)->not->toContain('No stage');
});

// ── D10-5 · THE VIP BADGE ─────────────────────────────────────────────────────────────────────────

it('⚠️ D10-5 — the VIP badge renders for a VIP guest, in FRONT DESK’s exact markup', function () {
    $s = seedScenario(1);
    Guest::withoutGlobalScopes()->whereKey($s['guest']->getKey())->update(['is_vip' => true]);

    $html = d102Inbox($s, d102Thread($s, null));

    // ⚠️ CHARACTER-FOR-CHARACTER the front desk's badge — same classes, same dot, same word (§N.8f).
    expect($html)->toContain('<span class="vy-tag tone-vip" style="margin-left: 6px;"><span class="vy-dot"></span>VIP</span>');
});

it('⚠️ D10-5 — a NON-VIP thread shows no VIP badge (the common case)', function () {
    $s = seedScenario(1);

    // ⚠️ NON-VACUITY: the guest really is not VIP — the default, asserted rather than assumed.
    expect((bool) $s['guest']->fresh()->is_vip)->toBeFalse();

    $html = d102Inbox($s, d102Thread($s, null));
    // ⚠️ THE BADGE MARKUP, not the class token. A first draft banned `tone-vip` outright and failed on
    // CORRECT markup: `.vy-tag.tone-vip` is defined in the operator shell's STYLESHEET, which every page
    // carries. The same over-broad-literal trap the register already records twice.
    expect($html)->toContain('vy-thread-head')
        ->and($html)->not->toContain('<span class="vy-tag tone-vip"');
});

it('⚠️ D10-5 — a thread with NO guest is simply not VIP (never "unknown")', function () {
    $s = seedScenario(1);
    $thread = d102Thread($s, null);
    DB::table('message_threads')->where('id', $thread->getKey())->update(['guest_id' => null]);

    $html = d102Inbox($s, $thread->fresh());
    expect($html)->not->toContain('<span class="vy-tag tone-vip"');
});

// ── PARITY WITH THE SOURCES BOTH BADGES REUSE ─────────────────────────────────────────────────────

it('⚠️ D10-2 + D10-5 — both badges MATCH the surfaces they were taken from (§N.8f)', function () {
    /*
     * ⚠️ THE DRIFT GUARD. Neither badge is shared markup — each is matched — so this reads the SOURCES and
     * fails if either original changes without the inbox following. That is the risk of matching rather
     * than extracting, made loud instead of latent.
     */
    $inbox = (string) file_get_contents(resource_path('views/livewire/operator/operator-inbox.blade.php'));
    $frontDesk = (string) file_get_contents(resource_path('views/livewire/front-desk.blade.php'));
    $reservations = (string) file_get_contents(resource_path('views/livewire/operator/reservations-list.blade.php'));

    // The VIP badge, verbatim from the front desk.
    $vip = '<span class="vy-tag tone-vip" style="margin-left: 6px;"><span class="vy-dot"></span>VIP</span>';
    expect($frontDesk)->toContain($vip)          // non-vacuity: the source really carries it
        ->and($inbox)->toContain($vip);

    // The stage chip's markup shape, from the Reservations list.
    expect($reservations)->toContain('<span class="vy-tag tone-{{ $row[\'stage_meta\'][\'tone\'] }}"><span class="vy-dot"></span>{{ $row[\'stage_meta\'][\'label\'] }}</span>')
        ->and($inbox)->toContain('<span class="vy-tag tone-{{ $active[\'reservation\'][\'stage_meta\'][\'tone\'] }}"><span class="vy-dot"></span>{{ $active[\'reservation\'][\'stage_meta\'][\'label\'] }}</span>');

    // ⚠️ And the stage is resolved by the SHARED helper in the component — never re-derived in Blade.
    $component = (string) file_get_contents(app_path('Livewire/Operator/OperatorInbox.php'));
    expect($component)->toContain('ReservationStage::meta(ReservationStage::for($reservation))')
        ->and($inbox)->not->toContain('ReservationStage::');
});

it('⚠️ the reused sources are UNTOUCHED — this pass matched them, it did not edit them', function () {
    // Front Desk's VIP rendering and the shared resolver must be byte-identical: "reuse" that quietly edits
    // its source is not reuse.
    $frontDesk = (string) file_get_contents(app_path('Livewire/FrontDesk.php'));
    $stage = (string) file_get_contents(app_path('Support/Reservations/ReservationStage.php'));

    expect($frontDesk)->toContain("'is_vip' => \$guest !== null && (bool) \$guest->is_vip,")
        ->and($stage)->toContain('public static function meta(string $stage): array')
        ->and($stage)->toContain('public static function for(Reservation $reservation');
});

it('⚠️ D10-1 was NOT built — party size and balance stay out of the reservation card', function () {
    // ⚠️ The neighbouring ledger item, deliberately untouched. Asserted so "while I was in there" scope
    // creep would fail rather than pass unnoticed.
    $component = (string) file_get_contents(app_path('Livewire/Operator/OperatorInbox.php'));
    $card = substr($component, strpos($component, 'private function reservationCard'), 1800);

    expect($card)->not->toContain('adults')
        ->and($card)->not->toContain('balance')
        ->and($card)->not->toContain('party');
});

it('⚠️ this file leaves no residue — and the check that would have "proved" it was measuring the wrong thing', function () {
    /*
     * ⚠️ A GUARD OF MINE THAT PASSED ALONE AND FAILED IN-SUITE (§N.17.7, live). The first version asserted a
     * GLOBAL `message_threads` count of 0. That holds only when this file runs by itself; in-suite it read
     * the 5 threads another file had seeded and failed — so it was measuring the ENVIRONMENT, not this
     * pass's cleanliness, and would have kept failing however clean this file was.
     *
     * ⚠️ AND IT COULD NEVER HAVE FOUND ANYTHING: `RefreshDatabase` rolls every test back, so a fixture here
     * cannot outlive its test by construction. The meaningful residue check is on the DEV database after a
     * browser pass, done by raw query outside the suite — and this pass needed no demo fixture at all, since
     * the demo already carried a VIP guest with a reservation-linked thread AND plain threads without one.
     *
     * What IS worth pinning is that the fixtures are scoped to this file's own helper rather than mutating
     * shared seed data — which is why nothing here can leak in the first place.
     */
    // ⚠️ Counted over the CODE only. A first attempt counted raw occurrences and read 2 — because the
    // needle appears in this very assertion. A source-scanning guard has to exclude itself.
    $lines = array_filter(
        preg_split('/\R/', (string) file_get_contents(__FILE__)) ?: [],
        fn (string $line): bool => ! str_contains($line, 'substr_count') && ! str_contains($line, '//'),
    );
    $code = implode("\n", $lines);

    expect($code)->toContain('uses(RefreshDatabase::class);')
        // Every thread this file creates goes through the one helper, which builds a fresh row per test.
        ->and(substr_count($code, 'MessageThread::create('))->toBe(1)
        // The only shared-row mutation is the deliberate VIP flip, scoped to the scenario's own guest.
        ->and(substr_count($code, 'Guest::withoutGlobalScopes()'))->toBe(1);
});

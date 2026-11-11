<?php

declare(strict_types=1);

use App\Exceptions\Owner\UnitAssignmentRefused;
use App\Models\AuditEvent;
use App\Models\DomainEvent;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerExpense;
use App\Models\OwnerInvite;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\Owner\OwnerNewBookingNotification;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\MaintenanceService;
use App\Services\OwnerAttributionService;
use App\Services\OwnerInviteService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Services\UnitOwnerAssignmentService;
use App\Support\I18n\LocalDate;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\Support\Pdf\OwnerStatementDocument;
use App\Support\Schedule\BoardDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ DEC-p (2026-11-12) — an operator gives an EXISTING owner a unit that has no owner, FROM A CHOSEN DAY. REAL MONEY: a unit's owner decides who
// is paid for its stays. What these tests hold is ONE rule — THE PAST IS NEVER RE-ATTRIBUTED, and nothing already posted, statemented or paid moves by
// a cent (#17) — together with the narrow set of things that may be given at all. Every date is relative to TODAY (the day it is at the property),
// because the feature refuses a day that has passed.
//
// ⚠️ Every refusal keeps a PERMITTED actor beside it (CLAUDE.md #30): a 403/404/redirect-with-error proves nothing if the fixture was simply broken.

/** The day `$n` days from today at the scenario's property (UTC). */
function dpDay(int $n): string
{
    return BoardDate::today('UTC')->addDays($n)->toDateString();
}

/**
 * One organisation: a property with `$units` units (none owned), prices published around today, an ACTIVE owner "Amara Okafor" with an owner-wide 18%
 * agreement that began long ago, and an operator who holds `users.manage`.
 *
 * @return array<string, mixed>
 */
function dpWorld(int $units = 2): array
{
    $s = seedScenario($units);
    app(AvailabilityService::class)->recompute($s['roomType']->id, dpDay(-60), dpDay(200));
    foreach ($s['units'] as $i => $unit) {
        $unit->update(['name' => 'Seaside '.($i + 1)]);
    }

    $owner = Owner::factory()->create(['name' => 'Amara Okafor', 'payout_currency' => 'EUR', 'status' => 'active']);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 18.00, 'effective_from' => dpDay(-400), 'effective_to' => null]);

    return $s + ['owner' => $owner, 'admin' => operatorUser($s, [], 'Property Owner')];
}

/** A booking on one specific unit, `$in`/`$out` days from today, with its room charge posted (as every entry point does) — NOT attributed yet. */
function dpBook(array $s, Unit $unit, int $in, int $out, int $priceMinor = 20000, bool $collect = false): Reservation
{
    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => dpDay($in), 'check_out' => dpDay($out), 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => dpDay($in), 'check_out' => dpDay($out),
            'price_minor' => $priceMinor, 'currency' => 'EUR', 'unit_id' => $unit->id]],
    );
    postRoom($reservation, $priceMinor);
    if ($collect) {
        collectFolio($reservation);
    }

    return $reservation->refresh();
}

/** POST the operator's "Give a unit" form (the route the dialog posts to). */
function dpGive(array $s, Unit $unit, ?string $since = null, ?User $as = null, array $extra = [], ?Owner $owner = null)
{
    return test()->actingAs($as ?? $s['admin'])->post(route('app.owners.units.assign', ($owner ?? $s['owner'])->id), array_merge([
        '_form' => 'give', 'unit_id' => $unit->id, 'since' => $since ?? dpDay(0), 'current_password' => 'password',
    ], $extra));
}

/** Everything the rule says must not move: every ledger row (all owners), every statement, every payout. */
function dpSnapshot(): array
{
    return [
        'ledger' => OwnerLedgerEntry::withoutOwnerScope()->orderBy('id')->get()->map(fn (OwnerLedgerEntry $e): array => [
            (string) $e->id, (string) $e->owner_id, (string) $e->unit_id, (string) $e->reservation_id, (string) $e->entry_type, (string) $e->direction,
            (int) $e->amount_minor, $e->statement_id !== null ? (string) $e->statement_id : null, (int) $e->attribution_version, (bool) $e->prior_period_adjustment,
        ])->all(),
        'statements' => OwnerStatement::withoutGlobalScopes()->orderBy('id')->get()->map(fn (OwnerStatement $st): array => [
            (string) $st->id, (string) $st->status, (int) $st->gross_minor, (int) $st->commission_minor, (int) $st->net_source_minor, (int) $st->net_payable_minor, (bool) $st->locked,
        ])->all(),
        'payouts' => Payout::withoutGlobalScopes()->orderBy('id')->get()->map(fn (Payout $p): array => [(string) $p->id, (string) $p->status, (int) $p->amount_minor])->all(),
    ];
}

function dpLedgerCount(?string $reservationId = null): int
{
    return OwnerLedgerEntry::withoutOwnerScope()->when($reservationId !== null, fn ($q) => $q->where('reservation_id', $reservationId))->count();
}

// ── dpu01 · ONLY A UNIT WITH NO OWNER ─────────────────────────────────────────────────────────────────────────────────────────────────────────
it('dpu01 refuses a unit that already has an owner, says whose it is, and writes nothing', function () {
    $s = dpWorld(2);
    $current = ownUnit($s['units'][0]);   // another owner (and agreement) on Seaside 1
    $current->update(['name' => 'Bruno Castellane']);

    dpGive($s, $s['units'][0])->assertRedirect()->assertSessionHas('owners_error');
    expect(session('owners_error'))->toContain('Seaside 1')->toContain('Bruno Castellane')->toContain('already belongs');

    expect((string) $s['units'][0]->refresh()->owner_id)->toBe((string) $current->id)
        ->and($s['units'][0]->owner_since)->toBeNull()
        ->and(AuditEvent::query()->where('action', 'unit.owner_assigned')->count())->toBe(0);

    // the PERMITTED counterpart: the same actor, the same owner, a unit with no owner → given
    dpGive($s, $s['units'][1])->assertSessionHas('owners_notice');
    expect((string) $s['units'][1]->refresh()->owner_id)->toBe((string) $s['owner']->id);
});

// ── dpu02 · A KNOWN AGREEMENT COVERS THE UNIT ON THE DAY ──────────────────────────────────────────────────────────────────────────────────────
it('dpu02 refuses when no agreement covers the unit on the day — and gives it for an owner-wide or a per-unit agreement', function () {
    $s = dpWorld(3);

    // no agreement at all
    $bare = Owner::factory()->create(['name' => 'Bare Owner', 'payout_currency' => 'EUR', 'status' => 'active']);
    dpGive($s, $s['units'][0], dpDay(0), null, [], $bare)->assertSessionHas('owners_error');
    expect(session('owners_error'))->toContain('Bare Owner has no agreement that covers')->toContain('Give Bare Owner an agreement');

    // an agreement that only begins AFTER the day
    ManagementAgreement::factory()->create(['owner_id' => $bare->id, 'unit_id' => null, 'effective_from' => dpDay(30), 'effective_to' => null]);
    dpGive($s, $s['units'][0], dpDay(5), null, [], $bare)->assertSessionHas('owners_error');

    // an ENDED agreement does not count
    $gone = Owner::factory()->create(['name' => 'Gone Owner', 'payout_currency' => 'EUR', 'status' => 'active']);
    ManagementAgreement::factory()->create(['owner_id' => $gone->id, 'unit_id' => null, 'effective_from' => dpDay(-100), 'effective_to' => null, 'status' => 'ended']);
    dpGive($s, $s['units'][0], dpDay(0), null, [], $gone)->assertSessionHas('owners_error');

    // a per-unit agreement for a DIFFERENT unit does not cover this one
    $other = Owner::factory()->create(['name' => 'Other Owner', 'payout_currency' => 'EUR', 'status' => 'active']);
    ManagementAgreement::factory()->create(['owner_id' => $other->id, 'unit_id' => $s['units'][2]->id, 'effective_from' => dpDay(-100), 'effective_to' => null]);
    dpGive($s, $s['units'][0], dpDay(0), null, [], $other)->assertSessionHas('owners_error');

    expect(Unit::query()->whereNotNull('owner_id')->count())->toBe(0);

    // the PERMITTED counterparts: this unit's own agreement, and the owner-wide one
    dpGive($s, $s['units'][2], dpDay(0), null, [], $other)->assertSessionHas('owners_notice');
    dpGive($s, $s['units'][0])->assertSessionHas('owners_notice');
    expect((string) $s['units'][2]->refresh()->owner_id)->toBe((string) $other->id)
        ->and((string) $s['units'][0]->refresh()->owner_id)->toBe((string) $s['owner']->id);
});

// ── dpu03 · THE GATE, THE ISOLATION, THE PASSWORD, THE PAUSE ───────────────────────────────────────────────────────────────────────────────────
it('dpu03 gates on users.manage and keeps a permitted actor beside every refusal', function () {
    $foreign = seedScenario(1);
    $foreignOwner = Owner::factory()->create(['name' => 'Foreign Owner', 'payout_currency' => 'EUR', 'status' => 'active']);
    $foreignUnit = $foreign['units'][0];

    $s = dpWorld(1);
    $unit = $s['units'][0];

    // not users.manage → 403 on the ROUTE, nothing written (Front Desk and Hotel Manager both lack it)
    foreach (['Front Desk', 'Hotel Manager'] as $role) {
        dpGive($s, $unit, null, operatorUser($s, [], $role))->assertForbidden();
    }
    expect($unit->refresh()->owner_id)->toBeNull();

    // another organisation's owner → 404 (the permitted admin, a real unit of THIS org)
    dpGive($s, $unit, null, null, [], $foreignOwner)->assertNotFound();
    expect($unit->refresh()->owner_id)->toBeNull();

    // a wrong / missing password → refused with the reason, nothing written
    dpGive($s, $unit, null, null, ['current_password' => 'not-my-password'])->assertSessionHasErrors('current_password');
    dpGive($s, $unit, null, null, ['current_password' => ''])->assertSessionHasErrors('current_password');
    expect($unit->refresh()->owner_id)->toBeNull();

    // the PERMITTED actor, the right password → given
    dpGive($s, $unit)->assertSessionHas('owners_notice');
    expect((string) $unit->refresh()->owner_id)->toBe((string) $s['owner']->id);
});

it('dpu03b a unit outside the actor\'s properties is a 404 — and one inside them is given', function () {
    $s = dpWorld(1);
    $second = secondProperty($s);
    $rt = RoomType::create(['property_id' => $second->id, 'name' => 'Far Std', 'kind' => 'room']);
    $far = Unit::create(['property_id' => $second->id, 'room_type_id' => $rt->id, 'code' => 'FAR1']);

    $scoped = operatorUser($s, [], 'Property Owner');
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);

    dpGive($s, $far, null, $scoped)->assertNotFound();
    expect($far->refresh()->owner_id)->toBeNull();

    dpGive($s, $s['units'][0], null, $scoped)->assertSessionHas('owners_notice');
    expect((string) $s['units'][0]->refresh()->owner_id)->toBe((string) $s['owner']->id);
});

it('dpu03c a paused (read-only) workspace refuses it', function () {
    $s = dpWorld(1);
    pauseWorkspace($s);

    dpGive($s, $s['units'][0])->assertRedirect()->assertSessionHas('owners_error');
    expect(session('owners_error'))->toContain('read-only')
        ->and($s['units'][0]->refresh()->owner_id)->toBeNull();
});

// ── dpu04 · NOTHING ALREADY POSTED, STATEMENTED OR PAID MOVES ─────────────────────────────────────────────────────────────────────────────────
it('dpu04 leaves every ledger row, statement and payout byte-identical', function () {
    $s = dpWorld(2);

    // another owner, a collected stay, a FINALIZED statement and a payout — money already settled
    $bruno = ownUnit($s['units'][0]);
    $paid = dpBook($s, $s['units'][0], -12, -10, 30000, true);
    app(OwnerAttributionService::class)->attributeReservation($paid);
    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($bruno->id, dpDay(-40), dpDay(-1)));
    app(OwnerPayoutService::class)->createFromStatement($statement);

    // and, on the unit that has no owner: a past stay and a future stay (not worked out for anyone)
    dpBook($s, $s['units'][1], -8, -6, 20000, true);
    dpBook($s, $s['units'][1], 15, 17, 20000);

    // an unposted, unrelated one too, so "identical" is not "empty"
    $before = dpSnapshot();
    expect(count($before['ledger']))->toBeGreaterThan(0)->and(count($before['statements']))->toBe(1)->and(count($before['payouts']))->toBe(1);

    dpGive($s, $s['units'][1], dpDay(10))->assertSessionHas('owners_notice');

    // every row that existed is exactly as it was; the ONLY addition is the new owner's lines for the stay that starts after the day
    $after = dpSnapshot();
    expect($after['statements'])->toBe($before['statements'])
        ->and($after['payouts'])->toBe($before['payouts'])
        ->and(array_slice($after['ledger'], 0, 0))->toBe([]);
    $beforeIds = array_column($before['ledger'], 0);
    $kept = array_values(array_filter($after['ledger'], fn (array $r): bool => in_array($r[0], $beforeIds, true)));
    expect($kept)->toBe($before['ledger'])
        ->and(count($after['ledger']) - count($before['ledger']))->toBeGreaterThan(0);

    // …and every added line belongs to the NEW owner, for the future stay only
    $added = array_values(array_filter($after['ledger'], fn (array $r): bool => ! in_array($r[0], $beforeIds, true)));
    foreach ($added as $row) {
        expect($row[1])->toBe((string) $s['owner']->id)->and($row[7])->toBeNull();   // not on any statement
    }
});

// ── dpu05 · A PAST STAY IS NEVER RE-ATTRIBUTED — not at assignment, and not by the backfill command either ───────────────────────────────────
it('dpu05 a stay that began before the day stays at 0 ledger rows after assignment AND after owners:backfill-attribution', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];

    $past = dpBook($s, $unit, -10, -8, 20000, true);          // over and paid
    $beforeTheDay = dpBook($s, $unit, 3, 5, 20000);           // booked, starts BEFORE the day (the 10th)
    $afterTheDay = dpBook($s, $unit, 12, 14, 20000);          // starts AFTER the day
    expect(dpLedgerCount())->toBe(0);

    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_notice');

    expect(dpLedgerCount($past->id))->toBe(0)
        ->and(dpLedgerCount($beforeTheDay->id))->toBe(0)
        ->and(dpLedgerCount($afterTheDay->id))->toBeGreaterThan(0);

    // ⚠️ THE BACKFILL CANNOT UNDO IT: it asks "is any stay on an owned unit still unworked-out?", and a stay that began before the day is not the owner's
    $rows = dpLedgerCount();
    $this->artisan('owners:backfill-attribution')->assertSuccessful();
    $this->artisan('owners:backfill-attribution')->assertSuccessful();

    expect(dpLedgerCount())->toBe($rows)
        ->and(dpLedgerCount($past->id))->toBe(0)
        ->and(dpLedgerCount($beforeTheDay->id))->toBe(0);
    expect(app(OwnerAttributionService::class)->needsAttribution($past))->toBeFalse()
        ->and(app(OwnerAttributionService::class)->needsAttribution($beforeTheDay->refresh()))->toBeFalse();

    // the control: a unit given with NO day (an invite's or a request's) is still the owner's for every stay — the backfill still works as it always did
    $unit->forceFill(['owner_since' => null])->save();
    expect(app(OwnerAttributionService::class)->needsAttribution($past->refresh()))->toBeTrue();
});

// ── dpu06 · A LIVE STAY IS NEVER CUT IN TWO ────────────────────────────────────────────────────────────────────────────────────────────────────
it('dpu06 refuses a day inside a live stay, names its dates, and accepts the day it ends', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];
    $stay = dpBook($s, $unit, 5, 9);

    dpGive($s, $unit, dpDay(7))->assertSessionHas('owners_error');
    expect(session('owners_error'))->toContain('A guest is staying in “Seaside 1” from')->toContain('Pick');
    expect($unit->refresh()->owner_id)->toBeNull();

    // a stay that is IN HOUSE across the day is live too
    $stay->update(['status' => 'checked_in']);
    dpGive($s, $unit, dpDay(6))->assertSessionHas('owners_error');

    // the PERMITTED counterparts: the day the stay ends (it begins nothing across it), and a cancelled stay does not count
    dpGive($s, $unit, dpDay(9))->assertSessionHas('owners_notice');
    expect((string) $unit->refresh()->owner_id)->toBe((string) $s['owner']->id)
        ->and($unit->owner_since?->toDateString())->toBe(dpDay(9))
        ->and(dpLedgerCount($stay->id))->toBe(0);   // it ended on the day — never the owner's

    $s2 = dpWorld(1);
    $cancelled = dpBook($s2, $s2['units'][0], 5, 9);
    app(AssignmentService::class)->cancelReservation($cancelled, 'test');
    dpGive($s2, $s2['units'][0], dpDay(7))->assertSessionHas('owners_notice');
});

// ── dpu07 · A STAY BOOKED BEFORE THE ASSIGNMENT, STARTING AFTER THE DAY, IS WORKED OUT — AND RECONCILES TO THE CENT (#17) ──────────────────────
it('dpu07 works out a future stay booked earlier, and credit + commission reconcile to the room charge at every ratio', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];

    $stays = [];
    foreach ([[11, 13, 20000], [14, 17, 33333], [18, 19, 10001], [20, 23, 99999]] as [$in, $out, $price]) {
        $stays[] = [dpBook($s, $unit, $in, $out, $price), $price];
    }
    expect(dpLedgerCount())->toBe(0);

    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_notice');
    expect(session('owners_notice'))->toContain('4 stays from then on were worked out for Amara Okafor');

    foreach ($stays as [$reservation, $price]) {
        $rows = OwnerLedgerEntry::withoutOwnerScope()->where('reservation_id', $reservation->id)->get();
        $gross = (int) $rows->where('entry_type', 'room_revenue')->sum('amount_minor');
        $commission = (int) $rows->where('entry_type', 'commission')->sum('amount_minor');
        $net = $rows->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());

        expect($gross)->toBe($price)                                  // Σ credit == the folio's room charge
            ->and($commission)->toBe((int) round($price * 0.18))      // 18.00% of it (allocate keeps the remainder exact)
            ->and($net)->toBe($price - $commission)                   // owner net + the operator's commission == the room charge, to the cent
            ->and($rows->every(fn (OwnerLedgerEntry $e): bool => (string) $e->owner_id === (string) $s['owner']->id))->toBeTrue();
    }
});

// ── dpu08 · A BOOKING MADE AFTER THE ASSIGNMENT ────────────────────────────────────────────────────────────────────────────────────────────────
it('dpu08 a booking made after the assignment is worked out only if its stay starts on or after the day', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];
    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_notice');

    $early = dpBook($s, $unit, 3, 5);   // starts before the day → the operator's
    $late = dpBook($s, $unit, 10, 12);  // starts ON the day → the owner's
    foreach ([$early, $late] as $r) {
        app(OwnerAttributionService::class)->attributeReservation($r->refresh());   // exactly what every booking entry point does
    }

    expect(dpLedgerCount($early->id))->toBe(0)
        ->and((int) OwnerLedgerEntry::withoutOwnerScope()->where('reservation_id', $late->id)->where('entry_type', 'room_revenue')->sum('amount_minor'))->toBe(20000);

    // a unit with no day at all (every unit before DEC-p) is the owner's for every stay — the old behaviour, unchanged
    $u2 = Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'code' => 'OLD1']);
    app(AvailabilityService::class)->recompute($s['roomType']->id, dpDay(-60), dpDay(200));   // the room type now has a second unit
    $old = ownUnit($u2);
    $r = dpBook($s, $u2, 3, 5);
    app(OwnerAttributionService::class)->attributeReservation($r);
    expect((int) OwnerLedgerEntry::withoutOwnerScope()->where('reservation_id', $r->id)->where('owner_id', $old->id)->where('entry_type', 'room_revenue')->sum('amount_minor'))->toBe(20000);
});

// ── dpu09 · A MOVE RESPECTS THE DAY ────────────────────────────────────────────────────────────────────────────────────────────────────────────
it('dpu09 moving a booking onto the unit follows the day: a stay before it stays the operator\'s, one after it becomes the owner\'s', function () {
    $s = dpWorld(2);
    [$operatorUnit, $given] = $s['units'];
    dpGive($s, $given, dpDay(10))->assertSessionHas('owners_notice');

    $before = dpBook($s, $operatorUnit, 3, 5);
    $after = dpBook($s, $operatorUnit, 11, 13);
    expect(dpLedgerCount())->toBe(0);

    foreach ([$before, $after] as $r) {
        $line = ReservationUnit::query()->where('reservation_id', $r->id)->firstOrFail();
        app(AssignmentService::class)->assignUnit($line, (string) $given->id);   // the certified move → the re-attribution listener runs
    }

    expect(dpLedgerCount($before->id))->toBe(0)
        ->and((int) OwnerLedgerEntry::withoutOwnerScope()->where('reservation_id', $after->id)->where('entry_type', 'room_revenue')->sum('amount_minor'))->toBe(20000);
});

// ── dpu10 · AN UNCOLLECTED STAY IS HELD, THEN CLAIMED ONCE PAID — AND THE STATEMENT AND ITS PDF AGREE TO THE CENT ───────────────────────────────
it('dpu10 holds an uncollected swept stay, claims it once collected, and the statement equals its PDF figures', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];
    $stay = dpBook($s, $unit, 12, 14, 20000);   // pay on arrival: charged, not paid
    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_notice');

    $svc = app(OwnerStatementService::class);
    $owner = $s['owner'];

    // a period covering the stay, finalised while the guest has NOT paid → nothing is claimed (the collection gate) and the lines stay unstamped
    $held = $svc->finalize($svc->generateDraft($owner->id, dpDay(10), dpDay(20)));
    expect((int) $held->gross_minor)->toBe(0)->and((int) $held->net_payable_minor)->toBe(0)
        ->and(OwnerLedgerEntry::withoutOwnerScope()->where('reservation_id', $stay->id)->whereNotNull('statement_id')->count())->toBe(0)
        ->and(OwnerLedgerEntry::withoutOwnerScope()->where('reservation_id', $stay->id)->count())->toBeGreaterThan(0);

    // the guest pays → the NEXT statement claims it (the straggler path), and gross − commission == net_payable, to the cent
    collectFolio($stay);
    $claimed = $svc->finalize($svc->generateDraft($owner->id, dpDay(21), dpDay(40)));
    // (a line dated BEFORE the statement's own period is a carry-forward — the certified straggler path — so it lands in the adjustments bucket)
    expect((int) $claimed->net_payable_minor)->toBe(16400)                          // 20000 room charge − 18% commission (3600)
        ->and((int) $claimed->net_source_minor)->toBe(16400)
        ->and((int) $claimed->adjustments_minor)->toBe(16400)
        ->and(OwnerLedgerEntry::withoutOwnerScope()->where('reservation_id', $stay->id)->whereNotNull('statement_id')->get()->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor()))->toBe(16400);

    // the PDF is built from the same figures: its net is the statement's, and its component lines sum to it exactly
    $data = OwnerStatementDocument::data($claimed);
    expect($data['net_display'])->toBe(MoneyFormatter::display(16400, 'EUR'));
    expect((int) OwnerStatementLine::query()->where('owner_statement_id', $claimed->id)->where('line_type', '!=', 'net')->sum('amount_minor'))->toBe(16400);
});

// ── dpu11 · IDEMPOTENT ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
it('dpu11 a second give, and a second sweep, add nothing', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];
    $stay = dpBook($s, $unit, 12, 14);
    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_notice');

    $rows = dpLedgerCount();
    $audits = AuditEvent::query()->where('action', 'unit.owner_assigned')->count();

    // the same form again: the unit now HAS an owner → refused, nothing added
    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_error');
    // and the certified attribution called again is a no-op
    app(OwnerAttributionService::class)->attributeReservation($stay->refresh());

    expect(dpLedgerCount())->toBe($rows)
        ->and(AuditEvent::query()->where('action', 'unit.owner_assigned')->count())->toBe($audits);
});

// ── dpu12 · THE TRAIL HOLDS IDS AND THE DAY — NO NAME, NO FIGURE, NO ADDRESS ────────────────────────────────────────────────────────────────────
it('dpu12 records unit.owner_assigned in the audit log and the business events with ids and the day only', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];
    $s['owner']->update(['email' => 'amara.private@example.test', 'phone' => '+351 900 000 000']);
    dpBook($s, $unit, 12, 14);
    Notification::fake();   // after the booking: only what GIVING the unit sends is in question

    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_notice');

    $audit = AuditEvent::query()->where('action', 'unit.owner_assigned')->sole();
    expect((string) $audit->subject_id)->toBe((string) $unit->id)
        ->and($audit->actor_id)->toBe((string) $s['admin']->id)
        ->and($audit->before)->toBe(['owner_id' => null, 'owner_since' => null])
        ->and(collect(array_keys($audit->after))->sort()->values()->all())->toBe(['owner_id', 'owner_since', 'stays_waiting', 'stays_worked_out', 'unit_id'])
        ->and($audit->after['owner_since'])->toBe(dpDay(10))
        ->and($audit->after['owner_id'])->toBe((string) $s['owner']->id);

    $event = DomainEvent::query()->where('event_type', 'unit.owner_assigned')->sole();
    expect($event->payload)->toBe(['unit_id' => (string) $unit->id, 'owner_id' => (string) $s['owner']->id, 'owner_since' => dpDay(10)]);

    foreach ([json_encode($audit->after), json_encode($audit->before), json_encode($event->payload)] as $blob) {
        expect($blob)->not->toContain('Amara')->not->toContain('amara.private')->not->toContain('900 000')->not->toContain('Seaside');
    }

    // giving a unit tells the owner nothing by itself (no unit-assigned message exists; the new-booking notice is for bookings)
    Notification::assertNothingSent();
});

// ── dpu13 · THE OWNER SEES THE UNIT — AND NO OTHER OWNER DOES — AND ONLY THE STAYS THAT ARE THEIRS ──────────────────────────────────────────
it('dpu13 the new owner sees the unit and the stays from the day on in the portal, and no other owner does', function () {
    $s = dpWorld(2);
    $amaraUser = ownerActor($s['owner'], $s);
    $bruno = ownUnit($s['units'][1]);
    $brunoUser = ownerActor($bruno, $s);

    $past = dpBook($s, $s['units'][0], -10, -8, 20000, true);
    $before = dpBook($s, $s['units'][0], 3, 5);
    $after = dpBook($s, $s['units'][0], 12, 14);
    dpGive($s, $s['units'][0], dpDay(10))->assertSessionHas('owners_notice');

    // each user's session is fresh: owner scope is request-scoped
    $this->actingAs($amaraUser)->get('/owner/units')->assertOk()->assertSee('Seaside 1')->assertDontSee('Seaside 2');
    $this->actingAs($brunoUser)->get('/owner/units')->assertOk()->assertSee('Seaside 2')->assertDontSee('Seaside 1');

    $page = $this->actingAs($amaraUser)->get('/owner/bookings')->assertOk();
    $page->assertSee($after->reference)->assertDontSee($before->reference)->assertDontSee($past->reference);
    $this->actingAs($brunoUser)->get('/owner/bookings')->assertOk()->assertDontSee($after->reference);
});

// ── dpu14 · THE DAY: TODAY OR LATER, WITHIN A YEAR ─────────────────────────────────────────────────────────────────────────────────────────────
it('dpu14 refuses a day that has passed or is more than 365 days away, and accepts today and the last day allowed', function () {
    $s = dpWorld(3);

    dpGive($s, $s['units'][0], dpDay(-1))->assertSessionHas('owners_error');
    expect(session('owners_error'))->toContain('Pick today or a later day');
    dpGive($s, $s['units'][0], dpDay(366))->assertSessionHas('owners_error');
    expect(session('owners_error'))->toContain('within the next 365 days');
    dpGive($s, $s['units'][0], '2026-02-30')->assertSessionHasErrors('since');
    dpGive($s, $s['units'][0], 'next friday')->assertSessionHasErrors('since');
    expect($s['units'][0]->refresh()->owner_id)->toBeNull();

    // the PERMITTED counterparts — the boundaries themselves
    dpGive($s, $s['units'][0], dpDay(0))->assertSessionHas('owners_notice');
    dpGive($s, $s['units'][1], dpDay(365))->assertSessionHas('owners_notice');
    expect($s['units'][0]->refresh()->owner_since?->toDateString())->toBe(dpDay(0))
        ->and($s['units'][1]->refresh()->owner_since?->toDateString())->toBe(dpDay(365));

    // no day typed → today at the property
    dpGive($s, $s['units'][2], '')->assertSessionHas('owners_notice');
    expect($s['units'][2]->refresh()->owner_since?->toDateString())->toBe(dpDay(0));
});

// ── dpu15 · AN ACTIVE, NON-ARCHIVED OWNER ───────────────────────────────────────────────────────────────────────────────────────────────────────
it('dpu15 refuses an owner who has not accepted their invitation, and an archived one', function () {
    $s = dpWorld(2);

    $pending = Owner::factory()->create(['name' => 'Pending Pat', 'payout_currency' => 'EUR', 'status' => 'inactive']);
    ManagementAgreement::factory()->create(['owner_id' => $pending->id, 'unit_id' => null, 'effective_from' => dpDay(-100), 'effective_to' => null]);
    dpGive($s, $s['units'][0], dpDay(0), null, [], $pending)->assertSessionHas('owners_error');
    expect(session('owners_error'))->toContain('Pending Pat')->toContain('Only an active owner');

    // archived (removed) → not found at all
    $removed = Owner::factory()->create(['name' => 'Removed Rae', 'payout_currency' => 'EUR', 'status' => 'active']);
    ManagementAgreement::factory()->create(['owner_id' => $removed->id, 'unit_id' => null, 'effective_from' => dpDay(-100), 'effective_to' => null]);
    $removed->delete();
    dpGive($s, $s['units'][0], dpDay(0), null, [], $removed)->assertNotFound();

    expect(Unit::query()->whereNotNull('owner_id')->count())->toBe(0);

    // the service says the same without the route (it is the one rule)
    expect(app(UnitOwnerAssignmentService::class)->refusal($s['units'][0], $pending))->toContain('Only an active owner')
        ->and(app(UnitOwnerAssignmentService::class)->refusal($s['units'][0], $s['owner']))->toBeNull();

    dpGive($s, $s['units'][0])->assertSessionHas('owners_notice');
});

// ── dpu16 · STAYS ALREADY WORKED OUT FOR A DIFFERENT (REMOVED) OWNER ────────────────────────────────────────────────────────────────────────────
it('dpu16 refuses a unit whose future stays are already worked out for a removed owner — their lines stay theirs', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];
    $old = ownUnit($unit);
    $old->update(['name' => 'Old Olu']);
    $stay = dpBook($s, $unit, 12, 14);
    app(OwnerAttributionService::class)->attributeReservation($stay);
    $oldRows = dpLedgerCount($stay->id);
    expect($oldRows)->toBeGreaterThan(0);

    // the owner is removed (the real route): the unit has no owner again — but the ledger keeps what was worked out
    $this->actingAs($s['admin'])->post(route('app.owners.remove', $old->id), ['current_password' => 'password', 'confirm_name' => 'Old Olu'])->assertRedirect(route('app.owners'));
    expect($unit->refresh()->owner_id)->toBeNull()->and($unit->owner_since)->toBeNull();

    $before = dpSnapshot();
    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_error');
    expect(session('owners_error'))->toContain('already worked out for another owner (Old Olu)');
    expect($unit->refresh()->owner_id)->toBeNull()->and(dpSnapshot())->toBe($before);

    // the PERMITTED counterpart: a day AFTER those stays have gone has none of them ahead of it
    dpGive($s, $unit, dpDay(15))->assertSessionHas('owners_notice');
    expect(dpLedgerCount($stay->id))->toBe($oldRows);   // the removed owner's lines are untouched
});

// ── dpu17 · A STALE DAY NEVER TRAVELS TO THE NEXT OWNER ─────────────────────────────────────────────────────────────────────────────────────────
it('dpu17 removing an owner, or accepting an invite, clears the day — an invite makes the owner the owner for every stay', function () {
    $s = dpWorld(2);
    dpGive($s, $s['units'][0], dpDay(5))->assertSessionHas('owners_notice');
    expect($s['units'][0]->refresh()->owner_since?->toDateString())->toBe(dpDay(5));

    $this->actingAs($s['admin'])->post(route('app.owners.remove', $s['owner']->id), ['current_password' => 'password', 'confirm_name' => 'Amara Okafor'])->assertRedirect(route('app.owners'));
    expect($s['units'][0]->refresh()->owner_id)->toBeNull()->and($s['units'][0]->owner_since)->toBeNull();

    // a stale day left on an unowned unit (however it got there) is overwritten when an invite is accepted
    $s['units'][1]->forceFill(['owner_since' => dpDay(30)])->save();
    Mail::fake();
    $invited = app(OwnerInviteService::class)->invite('Nova Marsh', 'nova@example.test', [(string) $s['units'][1]->id], 18.0, 'owner');
    $invite = OwnerInvite::query()->findOrFail($invited['invite']->id);
    app(OwnerInviteService::class)->accept($invite, 'A-long-Passw0rd!');

    expect($s['units'][1]->refresh()->owner_id)->not->toBeNull()
        ->and($s['units'][1]->owner_since)->toBeNull();
    // and its stays from before that old stale day are the owner's again (the old behaviour)
    $r = dpBook($s, $s['units'][1], 3, 5);
    app(OwnerAttributionService::class)->attributeReservation($r);
    expect(dpLedgerCount($r->id))->toBeGreaterThan(0);
});

// ── dpu18 · THE OWNER'S PAGE ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────
it('dpu18 the owner\'s page offers "Give a unit" to users.manage only, states the effect, and shows the day on the unit', function () {
    $s = dpWorld(2);
    $owned = ownUnit(Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'code' => 'X9', 'name' => 'Already Owned']));

    $page = $this->actingAs($s['admin'])->get(route('app.owners.show', $s['owner']->id))->assertOk();
    $page->assertSee('Give a unit')->assertSee('Seaside 1')->assertSee('Seaside 2')->assertDontSee('Already Owned')
        ->assertSee('payouts for this unit go to Amara Okafor', false)
        ->assertSee('Stays that start before then stay with you.')
        ->assertSee(route('app.owners.units.assign', $s['owner']->id), false);
    assertNoRawBlade($page);

    // a person who can READ owners but not manage them gets neither the button nor the form
    $reader = operatorUser($s, [], 'Hotel Manager');
    $this->actingAs($reader)->get(route('app.owners.show', $s['owner']->id))->assertOk()
        ->assertDontSee('Give a unit')->assertDontSee(route('app.owners.units.assign', $s['owner']->id), false);

    // once given, the unit says since when — "From" while the day is ahead
    dpGive($s, $s['units'][0], dpDay(10))->assertSessionHas('owners_notice');
    $this->actingAs($s['admin'])->get(route('app.owners.show', $s['owner']->id))->assertOk()
        ->assertSee('Seaside 1')->assertSee('From '.BoardDate::today('UTC')->addDays(10)->translatedFormat(LocalDate::pattern('j M Y')));
});

it('dpu18b an owner who has not accepted their invitation is offered no unit to be given', function () {
    $s = dpWorld(1);
    $s['owner']->update(['status' => 'inactive']);

    $this->actingAs($s['admin'])->get(route('app.owners.show', $s['owner']->id))->assertOk()->assertDontSee('Give a unit');
});

// ── dpu19 · THE SAME CUT IN THE TWO OTHER READERS OF "WHO OWNS IT" ───────────────────────────────────────────────────────────────────────────────
it('dpu19 a repair resolved before the day is the operator\'s cost; one resolved from the day on is the owner\'s', function () {
    $s = dpWorld(2);
    $svc = app(MaintenanceService::class);

    dpGive($s, $s['units'][0], dpDay(10))->assertSessionHas('owners_notice');    // the day is ahead: a repair resolved today is dated before it
    dpGive($s, $s['units'][1], dpDay(0))->assertSessionHas('owners_notice');     // the day is today: a repair resolved today is the owner's

    foreach ($s['units'] as $unit) {
        $ticket = $svc->open(['unit_id' => $unit->id, 'title' => 'Leak '.$unit->code, 'category' => 'plumbing', 'priority' => 'low']);
        $svc->start($ticket);
        $svc->resolve($ticket->refresh(), Money::of(15000, 'EUR'));
    }

    expect(OwnerExpense::withoutOwnerScope()->where('unit_id', $s['units'][0]->id)->count())->toBe(0)
        ->and(OwnerExpense::withoutOwnerScope()->where('unit_id', $s['units'][1]->id)->where('owner_id', $s['owner']->id)->count())->toBe(1);
    expect(ownerNet($s['owner']->id))->toBe(-15000);
});

it('dpu20 the new-booking notice goes to the owner only for a stay that starts on or after the day', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];
    $amaraUser = ownerActor($s['owner'], $s);
    dpGive($s, $unit, dpDay(10))->assertSessionHas('owners_notice');

    Notification::fake();
    dpBook($s, $unit, 3, 5);
    Notification::assertNotSentTo($amaraUser, OwnerNewBookingNotification::class);

    dpBook($s, $unit, 12, 14);
    Notification::assertSentTo($amaraUser, OwnerNewBookingNotification::class);
});

// ── dpu21 · THE SERVICE IS THE ONE RULE ──────────────────────────────────────────────────────────────────────────────────────────────────────────
it('dpu21 the service re-checks under its own lock — a unit given a moment ago is refused, not overwritten', function () {
    $s = dpWorld(1);
    $unit = $s['units'][0];
    $svc = app(UnitOwnerAssignmentService::class);

    $svc->assign((string) $unit->id, (string) $s['owner']->id, dpDay(2));
    $second = Owner::factory()->create(['name' => 'Second Owner', 'payout_currency' => 'EUR', 'status' => 'active']);
    ManagementAgreement::factory()->create(['owner_id' => $second->id, 'unit_id' => null, 'effective_from' => dpDay(-100), 'effective_to' => null]);

    // the caller still holds the OLD copy of the unit (no owner) — the service reads the row again
    expect(fn () => $svc->assign((string) $unit->id, (string) $second->id, dpDay(3)))->toThrow(UnitAssignmentRefused::class);
    expect((string) $unit->refresh()->owner_id)->toBe((string) $s['owner']->id)->and($unit->owner_since?->toDateString())->toBe(dpDay(2));
    expect(AuditEvent::query()->where('action', 'unit.owner_assigned')->count())->toBe(1);
});

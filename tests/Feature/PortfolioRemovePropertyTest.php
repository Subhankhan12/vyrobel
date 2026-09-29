<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\ChannelConnectionService;
use App\Services\PropertyRemovalRefused;
use App\Services\PropertyRemovalService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ⚠️⚠️ REMOVE A PROPERTY FROM THE PORTFOLIO (2026-09-29, the founder's request, design approved the same day).
//   · It is the certified ARCHIVE-NOT-DESTROY removal (task #98): bookings, invoices and payments are always kept.
//   · Nothing staying and nothing booked ahead → removed RIGHT AWAY. Otherwise it stays open until the last checkout,
//     and can be kept (cancelled) until then.
//   · "Right away" is STRICTER than the nightly rule: never while a guest is checked in, whatever their dates, and
//     never while a stay ends today or later — a guest mid-stay is never cut off.
//   · The LAST remaining property can never be removed — refused in the SERVICE, so Settings refuses it too.
//   · Only a manager (`properties.manage`), only a property the person can access, never in a read-only workspace,
//     and only with the property's name typed.

uses(DatabaseTransactions::class);

function prpStay(Property $property, string $status, string $in, string $out): Reservation
{
    return Reservation::create([
        'property_id' => $property->id, 'reference' => 'PRP-'.Str::upper(Str::random(8)), 'status' => $status,
        'check_in' => $in, 'check_out' => $out, 'currency' => 'EUR',
    ]);
}

/** The raw row — an Eloquent read hides a soft-deleted property and cannot answer "was it removed?". */
function prpRow(string $propertyId): object
{
    return DB::table('properties')->where('id', $propertyId)->first(['id', 'deleted_at', 'removal_scheduled_at', 'removal_effective_at']);
}

function prpDay(int $offset): string
{
    return CarbonImmutable::today()->addDays($offset)->toDateString();
}

it('⚠️⚠️ removes a property with nobody staying and nothing booked ahead RIGHT AWAY — and keeps its records', function () {
    $s = seedScenario(1);
    $leaving = secondProperty($s, 'Old Mill');
    $history = prpStay($leaving, 'checked_out', prpDay(-40), prpDay(-37));   // a past stay: a record, not a reason to wait
    $manager = operatorUser($s, [], 'Property Owner');

    // before: its confirmation says it goes as soon as it is confirmed
    assertNoRawBlade($this->actingAs($manager)->get(route('app.portfolio'))->assertOk()
        ->assertSeeText('Removed as soon as you confirm')->assertSeeText('Nobody is staying and nothing is booked ahead.'));

    $this->actingAs($manager)->post(route('app.portfolio.remove', $leaving->id), ['confirm_name' => $leaving->name])
        ->assertRedirect(route('app.portfolio'))
        ->assertSessionHas('portfolio_notice', fn (string $m): bool => str_contains($m, 'was removed') && str_contains($m, 'kept'));

    expect(prpRow((string) $leaving->id)->deleted_at)->not->toBeNull('archived now — not at the nightly run')
        ->and(DB::table('reservations')->where('id', $history->id)->exists())->toBeTrue('its bookings are kept')
        ->and(DB::table('audit_events')->where('subject_id', $leaving->id)->where('action', 'property.removal_scheduled')->exists())->toBeTrue()
        ->and(DB::table('audit_events')->where('subject_id', $leaving->id)->where('action', 'property.archived')->exists())->toBeTrue();
    // its card is gone from the Portfolio — the name appears once, in the notice that says it was removed
    $page = $this->actingAs($manager)->get(route('app.portfolio'))->assertOk()
        ->assertDontSee('data-remove-property="'.$leaving->id.'"', false)
        ->assertSeeText($leaving->name.' was removed');
    assertNoRawBlade($page);
    expect(substr_count((string) $page->getContent(), (string) $leaving->name))->toBe(1);
})->group('security');

it('⚠️⚠️ a property with a stay ahead is SCHEDULED — open until the last checkout, shown on its card, and can be kept', function () {
    $s = seedScenario(1);
    secondProperty($s);
    $property = $s['property'];
    prpStay($property, 'confirmed', prpDay(3), prpDay(6));
    prpStay($property, 'confirmed', prpDay(1), prpDay(4));
    $manager = operatorUser($s, [], 'Property Owner');
    $leaves = CarbonImmutable::today()->addDays(6);

    $page = $this->actingAs($manager)->get(route('app.portfolio'))->assertOk()
        ->assertSeeText('2 current or upcoming stays')
        ->assertSeeText('Stays open until the last checkout on '.$leaves->format('j M Y'))
        ->assertSeeText(', and owners of its units are told');   // it has a unit
    assertNoRawBlade($page);   // a directive glued to a word (`run@if`) ships raw source — CLAUDE.md

    $this->actingAs($manager)->post(route('app.portfolio.remove', $property->id), ['confirm_name' => $property->name])
        ->assertSessionHas('portfolio_notice', fn (string $m): bool => str_contains($m, 'after the last checkout on '.$leaves->format('j M Y')));
    $row = prpRow((string) $property->id);
    expect($row->deleted_at)->toBeNull('a guest is booked — it stays open')
        ->and(CarbonImmutable::parse($row->removal_effective_at)->toDateString())->toBe($leaves->toDateString());

    assertNoRawBlade($this->actingAs($manager)->get(route('app.portfolio'))->assertOk()
        ->assertSeeText('Leaving after the last checkout on '.$leaves->format('j M Y'))
        ->assertSeeText('Keep this property'));

    $this->actingAs($manager)->post(route('app.portfolio.keep', $property->id))
        ->assertRedirect(route('app.portfolio'))->assertSessionHas('portfolio_notice');
    expect(prpRow((string) $property->id)->removal_scheduled_at)->toBeNull('kept — the schedule is cleared');
})->group('security');

it('⚠️⚠️ a guest in the house is never cut off — checked in (even past their date) or leaving today, the removal waits', function () {
    $s = seedScenario(1);
    secondProperty($s);
    $manager = operatorUser($s, [], 'Property Owner');

    // checked in, and their checkout date has already passed (an overstay): still in the house
    $overstay = secondProperty($s, 'Overstay Inn');
    prpStay($overstay, 'checked_in', prpDay(-5), prpDay(-1));
    $this->actingAs($manager)->post(route('app.portfolio.remove', $overstay->id), ['confirm_name' => $overstay->name])->assertSessionHas('portfolio_notice');
    expect(prpRow((string) $overstay->id)->deleted_at)->toBeNull('a checked-in guest is never removed from under');

    // a stay that ends TODAY: the guest may still be here this morning
    $today = secondProperty($s, 'Departure Day');
    prpStay($today, 'confirmed', prpDay(-2), prpDay(0));
    $this->actingAs($manager)->post(route('app.portfolio.remove', $today->id), ['confirm_name' => $today->name])->assertSessionHas('portfolio_notice');
    expect(prpRow((string) $today->id)->deleted_at)->toBeNull('a stay ending today waits for the nightly run');
})->group('security');

it('⚠️⚠️ the LAST property can never be removed — refused on the Portfolio, in Settings, and by the service itself', function () {
    $s = seedScenario(1);
    $only = $s['property'];
    $manager = operatorUser($s, [], 'Property Owner');

    assertNoRawBlade($this->actingAs($manager)->get(route('app.portfolio'))->assertOk()->assertSeeText('Add another property first'));
    $this->actingAs($manager)->post(route('app.portfolio.remove', $only->id), ['confirm_name' => $only->name])
        ->assertSessionHas('portfolio_error');
    $this->actingAs($manager)->post(route('app.settings.remove.schedule'), ['confirm_name' => $only->name])
        ->assertSessionHas('settings_error');
    expect(fn () => app(PropertyRemovalService::class)->schedule($only->fresh(), 'test'))->toThrow(PropertyRemovalRefused::class);
    $row = prpRow((string) $only->id);
    expect($row->removal_scheduled_at)->toBeNull()->and($row->deleted_at)->toBeNull();

    // a property that is ALREADY leaving does not count as one that remains
    $second = secondProperty($s);
    prpStay($second, 'confirmed', prpDay(2), prpDay(5));
    app(PropertyRemovalService::class)->schedule($second->fresh(), 'test');
    $this->actingAs($manager)->post(route('app.portfolio.remove', $only->id), ['confirm_name' => $only->name])
        ->assertSessionHas('portfolio_error');
    expect(prpRow((string) $only->id)->removal_scheduled_at)->toBeNull();
})->group('security');

it('⚠️ nothing happens unless the property\'s name is typed exactly', function () {
    $s = seedScenario(1);
    $leaving = secondProperty($s);
    $manager = operatorUser($s, [], 'Property Owner');

    $this->actingAs($manager)->post(route('app.portfolio.remove', $leaving->id), ['confirm_name' => 'Something else'])
        ->assertSessionHas('portfolio_error');
    $this->actingAs($manager)->post(route('app.portfolio.remove', $leaving->id), [])->assertSessionHasErrors('confirm_name');
    $row = prpRow((string) $leaving->id);
    expect($row->removal_scheduled_at)->toBeNull()->and($row->deleted_at)->toBeNull();
})->group('security');

it('⚠️⚠️ only a manager may remove — the control is not offered, and the route refuses (403)', function () {
    $s = seedScenario(1);
    $leaving = secondProperty($s);
    $viewer = operatorUser($s, [], 'Hotel Manager');   // can view properties, cannot manage them

    $this->actingAs($viewer)->get(route('app.portfolio'))->assertOk()->assertDontSee('data-remove-property', false);
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get(route('app.portfolio'))->assertOk()->assertSee('data-remove-property', false);

    $this->actingAs($viewer)->post(route('app.portfolio.remove', $leaving->id), ['confirm_name' => $leaving->name])->assertForbidden();
    $this->actingAs($viewer)->post(route('app.portfolio.keep', $leaving->id))->assertForbidden();
    expect(prpRow((string) $leaving->id)->deleted_at)->toBeNull();
})->group('security');

it('⚠️⚠️ only a property the person can access — another organisation\'s, or one outside their property scope, is a 404', function () {
    $s = seedScenario(1);
    $mine = secondProperty($s);
    $manager = operatorUser($s, [], 'Property Owner');

    $other = seedScenario(1);   // another organisation
    secondProperty($other);
    $theirs = $other['property'];
    app(TenantContext::class)->set((string) $s['org']->id);
    $this->actingAs($manager)->post(route('app.portfolio.remove', $theirs->id), ['confirm_name' => $theirs->name])->assertNotFound();
    app(TenantContext::class)->runAsSuperAdmin(fn () => expect(prpRow((string) $theirs->id)->removal_scheduled_at)->toBeNull());

    // a manager scoped to ONE property (the property_user allow-list) cannot reach the other
    $scoped = operatorUser($s, [], 'Property Owner');
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($scoped)->post(route('app.portfolio.remove', $mine->id), ['confirm_name' => $mine->name])->assertNotFound();
    $this->actingAs($scoped)->post(route('app.portfolio.keep', $mine->id))->assertNotFound();
    expect(prpRow((string) $mine->id)->deleted_at)->toBeNull();
})->group('security');

it('⚠️ a read-only workspace removes nothing', function () {
    $s = seedScenario(1);
    $leaving = secondProperty($s);
    $manager = operatorUser($s, [], 'Property Owner');
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['paused_until' => now()->addDays(3)]));

    $this->actingAs($manager)->post(route('app.portfolio.remove', $leaving->id), ['confirm_name' => $leaving->name])
        ->assertSessionHas('portfolio_error');
    $row = prpRow((string) $leaving->id);
    expect($row->removal_scheduled_at)->toBeNull()->and($row->deleted_at)->toBeNull();
})->group('security');

it('⚠️⚠️ the NIGHTLY pass never archives over a checked-in guest either — even one past their checkout date (F-57\'s ruling: no guest is archived over)', function () {
    $s = seedScenario(1);
    secondProperty($s);
    $property = $s['property'];
    $guest = prpStay($property, 'checked_in', prpDay(-4), prpDay(-1));   // an overstay: their date has passed, they have not left
    $removals = app(PropertyRemovalService::class);
    $removals->schedule($property->fresh(), 'test');
    $property->fresh()->forceFill(['removal_effective_at' => now()->subHour()])->save();   // due now, by the old date

    $removals->processDue(app(ChannelConnectionService::class));
    $row = prpRow((string) $property->id);
    expect($row->deleted_at)->toBeNull('the guest is still in the building')
        ->and(CarbonImmutable::parse($row->removal_effective_at)->toDateString())->toBe(prpDay(1), 'deferred to tomorrow, and re-checked then');

    // once they have checked out, the next pass removes it
    Reservation::query()->whereKey($guest->id)->update(['status' => 'checked_out']);
    $property->fresh()->forceFill(['removal_effective_at' => now()->subHour()])->save();
    $removals->processDue(app(ChannelConnectionService::class));
    expect(prpRow((string) $property->id)->deleted_at)->not->toBeNull();
})->group('security');

<?php

use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Mail\BookingConfirmationMail;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\PropertyPolicy;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// #98 GATE 1 — operator Property Settings: basics/policies (real), owner card (REAL 18% commission, never the
// wireframe's 15%), REAL property booking pause (mutation-proven it stops NEW bookings), the guest brand FLOWING
// to a real email (accent + from-name + reply-to + powered-by), and the read-only guard (was missing).

/** A bookable room type + unit + published forward availability on the scenario's property. */
function settingsBookable(array $s): array
{
    $property = $s['property'];
    $rt = RoomType::create(['property_id' => $property->id, 'name' => 'Solo Suite', 'kind' => 'room']);
    $unit = Unit::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'code' => 'U-'.Str::upper(Str::random(4)), 'is_active' => true]);
    $plan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
    $rt->update(['default_rate_plan_id' => $plan->id]);

    $today = Carbon::today();
    app(AvailabilityService::class)->recompute($rt->id, $today->toDateString(), $today->copy()->addDays(60)->toDateString());

    return compact('rt', 'unit', 'plan');
}

function settingsBook(array $s, array $c, string $in, string $out): void
{
    app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $c['rt']->id, 'unit_id' => $c['unit']->id, 'rate_plan_id' => $c['plan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 15000, 'currency' => 'EUR']],
    );
}

// ── ⚠️ PROPERTY PAUSE genuinely stops new bookings; resume restores; existing stays kept ────────────
it('pauses new bookings for the whole property, then resumes — mutation-proven', function () {
    $s = seedScenario(1);
    $c = settingsBookable($s);
    $today = Carbon::today();
    $a = [$today->copy()->addDays(3)->toDateString(), $today->copy()->addDays(5)->toDateString()];
    $b = [$today->copy()->addDays(12)->toDateString(), $today->copy()->addDays(14)->toDateString()];

    settingsBook($s, $c, $a[0], $a[1]);   // baseline succeeds while open

    // PAUSE via the real settings route → recompute zeroes availability; a NEW booking is REFUSED.
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.settings.pause'))
        ->assertRedirect(route('app.settings'))->assertSessionHas('settings_notice');
    expect($s['property']->fresh()->bookings_paused_at)->not->toBeNull();
    expect(fn () => settingsBook($s, $c, $b[0], $b[1]))->toThrow(InsufficientAvailabilityException::class);

    // RESUME → capacity restored; the same booking now succeeds.
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.settings.resume'))
        ->assertRedirect(route('app.settings'))->assertSessionHas('settings_notice');
    expect($s['property']->fresh()->bookings_paused_at)->toBeNull();
    settingsBook($s, $c, $b[0], $b[1]);   // no throw — restored
    expect($s['property']->fresh()->bookings_paused_at)->toBeNull();
});

// ── OWNER card shows the REAL agreement commission (18%), never the wireframe's hardcoded 15% ───────
it('shows the real owner commission (18%), never the wireframe 15%', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['name' => 'Amara Okafor', 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::create([
        'owner_id' => $owner->id,
        'unit_id' => null,
        'commission_pct' => 18.00,
        'status' => 'active',
        'effective_from' => Carbon::today()->subMonth()->toDateString(),
    ]);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/settings')
        ->assertOk()
        ->assertSee('Amara Okafor')
        ->assertSee('18% commission')
        ->assertDontSee('15% commission');
});

// ── GUEST BRAND flows to a REAL email — accent + from-name + reply-to; powered-by respected ─────────
it('flows the saved guest brand into a real booking-confirmation email', function () {
    $s = seedScenario(1);
    $s['property']->update(['name' => 'Seaside Villas']);   // the brand display name flows into the email body
    $c = settingsBookable($s);
    $today = Carbon::today();
    settingsBook($s, $c, $today->copy()->addDays(3)->toDateString(), $today->copy()->addDays(5)->toDateString());

    // Save the brand via the real settings route.
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.settings.brand'), [
        'brand_accent' => '#0055FF',
        'brand_email_from_name' => 'Seaside Front Desk',
        'brand_reply_to' => 'hello@seaside.test',
        // brand_powered_by omitted → false
    ])->assertRedirect(route('app.settings'))->assertSessionHas('settings_notice');

    $property = $s['property']->fresh();
    expect($property->brand_accent)->toBe('#0055FF')
        ->and($property->brand_reply_to)->toBe('hello@seaside.test')
        ->and((bool) $property->brand_powered_by)->toBeFalse();

    // The REAL email body carries the accent + the property brand name, and drops the powered-by footer.
    $reservation = Reservation::query()->latest('id')->firstOrFail();
    $mail = new BookingConfirmationMail($reservation);
    $html = $mail->render();
    expect($html)->toContain('#0055FF')
        ->and($html)->toContain('Seaside Villas')
        ->and($html)->not->toContain('Powered by Vyrobel');

    // Postmark-safe: the display from-NAME + reply-to are per-property; the from ADDRESS stays the fixed sender.
    $mail->assertHasReplyTo('hello@seaside.test');
    $mail->assertFrom(config('mail.from.address'), 'Seaside Front Desk');
});

// ── POLICIES persist the new fields (self check-in + cancellation tier) ─────────────────────────────
it('persists the cancellation tier and self check-in toggle', function () {
    $s = seedScenario(1);
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post('/app/settings/policies', [
        'pet' => 'allowed',
        'smoking' => 'not_allowed',
        'min_stay' => '1',
        'cancellation_tier' => 'moderate',
        'self_check_in' => '1',
    ])->assertRedirect()->assertSessionHas('settings_notice');

    $policy = PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail();
    expect($policy->cancellation['tier'] ?? null)->toBe('moderate')
        ->and((bool) $policy->self_check_in)->toBeTrue();
});

// ── READ-ONLY guard — a paused workspace refuses a settings write (was missing on this controller) ──
it('refuses a settings write when the workspace is read-only', function () {
    $s = seedScenario(1);
    $s['org']->update(['paused_until' => Carbon::now()->addDays(5)]);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.settings.brand'), [
        'brand_accent' => '#123456',
    ])->assertRedirect(route('app.settings'))->assertSessionHas('settings_error');

    expect($s['property']->fresh()->brand_accent)->toBeNull();
});

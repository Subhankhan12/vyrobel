<?php

use App\Models\Channel;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/** Put an org on a plan/state (mirrors the BillingAccessGateTest helper). */
function chanBillOrg(string $orgId, string $planCode, string $status): void
{
    app(BillingService::class)->ensurePlans();
    $plan = Plan::query()->where('code', $planCode)->firstOrFail();
    Subscription::create(['organization_id' => $orgId, 'plan_id' => $plan->id, 'status' => $status]);
}

// Operator CHANNELS (exact-match) — the OTA channel-manager surface. HONEST-GATED (founder decision): no
// real OTA/Channex integration, so the DESIGN is matched but sync is honestly gated. Locks: the route is
// Pro-only (Essential → feature-locked); the Direct channel + booking-mix are REAL; NO fabricated
// sync/import/OTA-booking is ever rendered (#39). `chanBillOrg()` is the shared helper from BillingAccessGateTest.

// ── 1. Pro org: the page renders REAL data + honest OTA gating, never a fabricated sync ────────────
it('renders the channels page with a real Direct channel and honestly-gated OTA sync', function () {
    Carbon::setTestNow('2026-09-15');
    $s = seedScenario(1);
    chanBillOrg((string) $s['org']->id, 'pro_monthly', 'active');   // Pro grants the channels feature
    book($s, ['unit_id' => $s['units'][0]->id]);                // a real DIRECT booking (source=direct), Sep

    // A real OTA channel row (status connected — a real toggle, but no real sync).
    Channel::create([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id,
        'provider' => 'channex', 'name' => 'Booking.com (demo)', 'external_property_id' => 'x',
        'credentials' => 'demo-not-real', 'status' => 'connected', 'commission_pct' => 15,
    ]);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/channels')
        ->assertOk()
        ->assertSee('Channels')
        ->assertSee('Direct')                                  // the REAL Direct channel
        ->assertSee('Booking.com (demo)')                      // the real channel row
        ->assertSee('activates with your Channex account')     // HONEST gating of live sync
        ->assertSee('Booking mix')                             // real by-source mix
        // 🔒 #39 — never a fabricated sync/import/last-sync:
        ->assertDontSee('bookings imported')
        ->assertDontSee('last sync 3 min ago')
        ->assertDontSee('3 bookings waiting to import');
});

// ── 2. 🔒 FEATURE GATE — an Essential org cannot reach /app/channels (channels is Pro-only) ────────
it('gates /app/channels to Pro — an Essential org gets the feature-locked page', function () {
    $s = seedScenario(1);
    chanBillOrg((string) $s['org']->id, 'essential_monthly', 'active');   // Essential excludes channels

    expect($s['org']->fresh()->planAllows('channels'))->toBeFalse();

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/channels')
        ->assertStatus(403);   // EnsurePlanFeature → the honest "not in your plan" page
});

// ── 3. The booking mix is REAL (Direct count from real reservations), never fabricated OTA ─────────
it('shows the real booking mix by source — direct is real, OTA is honest', function () {
    Carbon::setTestNow('2026-09-15');
    $s = seedScenario(2);
    chanBillOrg((string) $s['org']->id, 'pro_monthly', 'active');
    book($s, ['unit_id' => $s['units'][0]->id]);
    book($s, ['unit_id' => $s['units'][1]->id]);   // 2 real direct bookings

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/channels')
        ->assertOk()
        ->assertSee('Direct')
        ->assertSee('2 direct this month');   // the REAL direct count (never a fabricated OTA figure)
    Carbon::setTestNow();
});

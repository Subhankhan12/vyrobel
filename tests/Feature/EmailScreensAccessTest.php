<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// EMAIL SCREENS ACCESS (Phase 3 · Sub-gate 5) — the Email Templates + Email Previews screens are back-office
// tools, behind the #26 EnsureBackOfficeAccess gate like /security. An operator sees them; an owner (who holds a
// Membership but NO back-office role) is bounced to /owner — the same perimeter every /app route inherits.

it('lets a back-office operator open the email templates + previews screens', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Manager']);
    book($s, ['unit_id' => $s['units'][0]->id]);   // a real reservation → the previews have sample data to render

    $this->actingAs($operator)->get('/app/email-templates')->assertOk()->assertSee('Email templates');
    $this->actingAs($operator)->get('/app/email-previews')->assertOk();
    $this->actingAs($operator)->get('/app/email-previews/booking-confirmation')->assertOk()->assertSee('Booking confirmed');
});

it('bounces an owner from the operator email screens (invariant #26)', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s);

    $this->actingAs($owner)->get('/app/email-templates')->assertRedirect('/owner');
    $this->actingAs($owner)->get('/app/email-previews')->assertRedirect('/owner');
});

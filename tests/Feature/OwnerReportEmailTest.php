<?php

use App\Mail\OwnerReportMail;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// "Email to me" (completeness sweep · Wave A · GATE 4) — the Reports page queues the owner's latest FINALIZED
// statement PDF to their OWN inbox. Real send (no fake "Soon"): SELF-ONLY (no recipient in the request),
// owner-scoped, CAP_EXPORTS-gated, and honest when there is nothing to send.

/** A linked owner (portal user) with ONE finalized statement. */
function emailReportOwner(array $s, int $unitIndex, string $email): array
{
    $user = User::factory()->create(['email' => $email]);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return compact('user', 'owner', 'unit');
}

// ── 1. The owner emails their latest statement — queued to their OWN address, PDF attached ─────────
it('queues the latest statement PDF to the owner’s own inbox', function () {
    Mail::fake();
    $s = seedScenario(1);
    $a = emailReportOwner($s, 0, 'amara@self.test');

    $this->actingAs($a['user'])->post(route('owner.reports.email'))
        ->assertRedirect()
        ->assertSessionHas('owner_notice');

    Mail::assertQueued(OwnerReportMail::class, function (OwnerReportMail $mail) {
        return $mail->hasTo('amara@self.test')
            && $mail->pdfBytes !== ''
            && str_starts_with($mail->pdfBytes, '%PDF');
    });
});

// ── 2. Honest when there's nothing to send — no finalized statement → no mail, honest flash ────────
it('sends nothing and flashes honestly when there is no finalized statement', function () {
    Mail::fake();
    $s = seedScenario(1);

    // A linked owner with NO statement.
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);

    $this->actingAs($user)->post(route('owner.reports.email'))
        ->assertRedirect()
        ->assertSessionHas('owner_notice');

    Mail::assertNothingQueued();
});

// ── 3. CAP_EXPORTS gate — a view-only sub-user cannot email; an accountant can ─────────────────────
it('gates the email behind CAP_EXPORTS', function () {
    Mail::fake();
    $s = seedScenario(1);
    $a = emailReportOwner($s, 0, 'amara@self.test');

    // accountant (CAP_EXPORTS) may send; view-only may NOT.
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'accountant'))->post(route('owner.reports.email'))->assertRedirect();
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'view_only'))->post(route('owner.reports.email'))->assertForbidden();
});

// ── 4. Fail closed — a non-linked user has no owner → 404, nothing sent ────────────────────────────
it('fails closed for a non-linked user', function () {
    Mail::fake();
    $s = seedScenario(1);
    emailReportOwner($s, 0, 'amara@self.test');

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    // ⚠️ R-GATE (founder, 2026-09-19): a NON-LINKED user is now refused at the realm door by
    //    EnsureOwnerPortalAccess, BEFORE any owner resolution runs — so this is a 403 about the AREA, not a
    //    404 about the resource. ⚠️ Cross-OWNER 404s above are untouched: an owner passes the gate and the
    //    scope still withholds another owner's rows.
    $this->actingAs($stranger)->post(route('owner.reports.email'))->assertForbidden();
    Mail::assertNothingQueued();
});

<?php

use App\Models\AgreementAmendment;
use App\Models\AgreementAmendmentAcknowledgment;
use App\Models\AuditEvent;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// Owner Agreement exact-match (#82) — the REAL amendment-ACKNOWLEDGMENT flow that replaces the wireframe's
// fake "Review & sign" e-signature. Locks: a real audited consent record, the new terms applied via the
// certified effective-dating (never a history rewrite), owner isolation, idempotency, and the real PDFs.

/** A portal user who is the (primary/co-owner) linked user of a fresh owner with an 18% portfolio agreement. */
function amendmentOwner(array $s, int $unitIndex): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $s['units'][$unitIndex]->update(['owner_id' => $owner->id]);

    $agreement = ManagementAgreement::factory()->create([
        'owner_id' => $owner->id,
        'unit_id' => null,
        'commission_pct' => 18.00,
        'effective_from' => '2026-01-01',
        'effective_to' => null,
        'status' => 'active',
    ]);

    return compact('user', 'owner', 'agreement');
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-07-16 12:00:00'));
});

// ── 1. Acknowledging records consent AND applies the new effective-dated terms ───────────────────
it('records a real acknowledgment and supersedes the agreement via effective-dating', function () {
    $s = seedScenario(1);
    $o = amendmentOwner($s, 0);

    // A future-dated amendment: 18% → 17% effective 1 Oct 2026 (no already-attributed night is touched).
    $amendment = AgreementAmendment::factory()->create([
        'owner_id' => $o['owner']->id,
        'version' => '2026 rate-card',
        'effective_date' => '2026-10-01',
        'new_terms' => ['commission_pct' => 17.00],
        'status' => AgreementAmendment::STATUS_PUBLISHED,
    ]);

    $this->actingAs($o['user'])
        ->post(route('owner.agreement.amendments.acknowledge', $amendment->id))
        ->assertRedirect();

    // A REAL consent record — who / when / which version.
    $ack = AgreementAmendmentAcknowledgment::query()->where('amendment_id', $amendment->id)->first();
    expect($ack)->not->toBeNull()
        ->and((string) $ack->acknowledged_by)->toBe((string) $o['user']->id)
        ->and((string) $ack->acknowledged_version)->toBe('2026 rate-card')
        ->and($ack->acknowledged_at)->not->toBeNull();

    expect($amendment->fresh()->status)->toBe(AgreementAmendment::STATUS_APPLIED);

    // The certified effective-dating: the old agreement is bounded the day before; a superseding row carries
    // the new 17% from the effective date. History is NOT rewritten.
    expect($o['agreement']->fresh()->effective_to?->toDateString())->toBe('2026-09-30');

    $new = ManagementAgreement::query()
        ->where('owner_id', $o['owner']->id)
        ->whereNull('unit_id')
        ->whereDate('effective_from', '2026-10-01')
        ->first();
    expect($new)->not->toBeNull()
        ->and((string) $new->commission_pct)->toBe('17.00')
        ->and($new->effective_to)->toBeNull();

    // Resolution proves it: today still 18% (the change is future); on/after the effective date, 17%.
    expect((string) ManagementAgreement::resolveFor((string) $o['owner']->id, null, '2026-07-16')->commission_pct)->toBe('18.00')
        ->and((string) ManagementAgreement::resolveFor((string) $o['owner']->id, null, '2026-10-01')->commission_pct)->toBe('17.00');

    // #20 — the acknowledgment is audited (WHO/version), never the changed values verbatim.
    expect(AuditEvent::query()->where('action', 'owner.agreement.amendment_acknowledged')->count())->toBe(1);
});

// ── 2. Idempotent — a double-submit records consent once ─────────────────────────────────────────
it('is idempotent — a second acknowledge does not record a second consent', function () {
    $s = seedScenario(1);
    $o = amendmentOwner($s, 0);
    $amendment = AgreementAmendment::factory()->create([
        'owner_id' => $o['owner']->id,
        'effective_date' => '2026-10-01',
        'new_terms' => ['commission_pct' => 17.00],
        'status' => AgreementAmendment::STATUS_PUBLISHED,
    ]);

    $this->actingAs($o['user'])->post(route('owner.agreement.amendments.acknowledge', $amendment->id))->assertRedirect();
    // The second POST 404s (the amendment is no longer PUBLISHED) — and crucially records nothing new.
    $this->actingAs($o['user'])->post(route('owner.agreement.amendments.acknowledge', $amendment->id))->assertNotFound();

    expect(AgreementAmendmentAcknowledgment::query()->where('amendment_id', $amendment->id)->count())->toBe(1)
        ->and(ManagementAgreement::query()->where('owner_id', $o['owner']->id)->whereDate('effective_from', '2026-10-01')->count())->toBe(1);
});

// ── 3. Owner isolation — a co-owner cannot acknowledge ANOTHER owner's amendment ─────────────────
it('refuses to acknowledge another owner’s amendment (owner-scoped 404)', function () {
    $s = seedScenario(2);
    $a = amendmentOwner($s, 0);
    $b = amendmentOwner($s, 1);

    $bAmendment = AgreementAmendment::factory()->create([
        'owner_id' => $b['owner']->id,
        'effective_date' => '2026-10-01',
        'new_terms' => ['commission_pct' => 17.00],
        'status' => AgreementAmendment::STATUS_PUBLISHED,
    ]);

    // A (a real co-owner, holds CAP_ACTIONS on their OWN owner) tries B's amendment → the explicit owner
    // scoping 404s BEFORE any write.
    $this->actingAs($a['user'])->post(route('owner.agreement.amendments.acknowledge', $bAmendment->id))->assertNotFound();

    expect(AgreementAmendmentAcknowledgment::query()->count())->toBe(0)
        ->and($bAmendment->fresh()->status)->toBe(AgreementAmendment::STATUS_PUBLISHED);
});

// ── 4. The page surfaces the real amendment + the honest framing + the relay ─────────────────────
it('renders the amendment card with honest acknowledgment framing and the operator relay', function () {
    $s = seedScenario(1);
    $o = amendmentOwner($s, 0);
    AgreementAmendment::factory()->create([
        'owner_id' => $o['owner']->id,
        'title' => '2026 rate-card & fee schedule',
        'effective_date' => '2026-10-01',
        'new_terms' => ['commission_pct' => 17.00],
        'status' => AgreementAmendment::STATUS_PUBLISHED,
    ]);

    $this->actingAs($o['user'])->get('/owner/agreement')
        ->assertOk()
        ->assertSee('ready to acknowledge')
        ->assertSee('Review &amp; acknowledge', false)
        ->assertSee('2026 rate-card & fee schedule')
        // Honest framing — never a claim of a legally-binding e-signature.
        ->assertSee('not a legally-binding e-signature')
        ->assertDontSee('Review & sign')
        // The relay points at the owner→operator Messages surface, never owner→guest.
        ->assertSee(route('owner.messages'), false);
});

// ── 5. The agreement + amendment PDFs download for a co-owner ────────────────────────────────────
it('downloads the agreement PDF and an acknowledged amendment PDF', function () {
    $s = seedScenario(1);
    $o = amendmentOwner($s, 0);
    $amendment = AgreementAmendment::factory()->create([
        'owner_id' => $o['owner']->id,
        'effective_date' => '2026-10-01',
        'new_terms' => ['commission_pct' => 17.00],
        'status' => AgreementAmendment::STATUS_PUBLISHED,
    ]);

    $this->actingAs($o['user'])->get(route('owner.agreement.pdf'))
        ->assertOk()->assertHeader('content-type', 'application/pdf');

    $this->actingAs($o['user'])->post(route('owner.agreement.amendments.acknowledge', $amendment->id))->assertRedirect();

    $this->actingAs($o['user'])->get(route('owner.agreement.amendments.pdf', $amendment->id))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
});

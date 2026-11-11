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

// ── ⚠️ D-AMEND-PLANNED (found 2026-10-03 by the owners-module review; fixed 2026-11-11, his "do all") ──────────────────────────
// An acknowledged amendment was written open-ended, ignored a change already planned for later, dropped a fixed end date, and bounded
// only ONE of the agreements covering its day — so two open-ended owner-wide agreements could cover the same days and the payout maths
// took whichever the database returned. It now goes through the ONE rule that changes an owner's owner-wide terms (CommissionChange).

/** How many owner-wide agreements GOVERN a day (active and covering it). Exactly one is the rule; more is the defect. */
function amendCovering(string $ownerId, string $date): int
{
    return ManagementAgreement::query()->where('owner_id', $ownerId)->whereNull('unit_id')->where('status', 'active')->effectiveOn($date)->count();
}

function amendAck(array $o, string $effective, float $pct): void
{
    $amendment = AgreementAmendment::factory()->create([
        'owner_id' => $o['owner']->id, 'version' => 'v-'.$effective, 'effective_date' => $effective,
        'new_terms' => ['commission_pct' => $pct], 'status' => AgreementAmendment::STATUS_PUBLISHED,
    ]);
    test()->actingAs($o['user'])->post(route('owner.agreement.amendments.acknowledge', $amendment->id))->assertRedirect();
}

it('⚠️⚠️ an amendment ends the day before a change ALREADY PLANNED for later — never open-ended over it', function () {
    $s = seedScenario(1);
    $o = amendmentOwner($s, 0);   // 18% from 2026-01-01, open-ended

    // the owner already agreed 15% from 1 Dec (a planned change), bounding the 18% at 30 Nov
    $o['agreement']->update(['effective_to' => '2026-11-30']);
    $planned = ManagementAgreement::factory()->create(['owner_id' => $o['owner']->id, 'unit_id' => null, 'commission_pct' => 15.00,
        'effective_from' => '2026-12-01', 'effective_to' => null, 'status' => 'active']);

    amendAck($o, '2026-10-01', 17.00);   // a NEW amendment, 17% from 1 Oct

    $new = ManagementAgreement::query()->where('owner_id', $o['owner']->id)->whereDate('effective_from', '2026-10-01')->firstOrFail();
    expect($new->effective_to?->toDateString())->toBe('2026-11-30')   // stops where the planned change starts — NOT null
        ->and($planned->fresh()->effective_from->toDateString())->toBe('2026-12-01');

    // every day has exactly ONE governing agreement, and the right rate governs it
    foreach (['2026-09-30', '2026-10-01', '2026-11-30', '2026-12-01', '2027-03-01'] as $day) {
        expect(amendCovering((string) $o['owner']->id, $day))->toBe(1, "on $day");
    }
    expect((string) ManagementAgreement::resolveFor((string) $o['owner']->id, null, '2026-09-30')->commission_pct)->toBe('18.00')
        ->and((string) ManagementAgreement::resolveFor((string) $o['owner']->id, null, '2026-11-30')->commission_pct)->toBe('17.00')
        ->and((string) ManagementAgreement::resolveFor((string) $o['owner']->id, null, '2026-12-01')->commission_pct)->toBe('15.00');   // the planned change still governs its own start
})->group('security');

it('⚠️ a fixed-term agreement keeps its end date through an amendment', function () {
    $s = seedScenario(1);
    $o = amendmentOwner($s, 0);
    $o['agreement']->update(['effective_to' => '2027-06-30']);   // a fixed term

    amendAck($o, '2026-10-01', 17.00);

    $new = ManagementAgreement::query()->where('owner_id', $o['owner']->id)->whereDate('effective_from', '2026-10-01')->firstOrFail();
    expect($new->effective_to?->toDateString())->toBe('2027-06-30')   // the replaced agreement's end is carried, not dropped
        ->and($o['agreement']->fresh()->effective_to?->toDateString())->toBe('2026-09-30')
        ->and(amendCovering((string) $o['owner']->id, '2026-12-01'))->toBe(1);
});

it('⚠️ agreements an older write left STACKED on the same day are all bounded, not just one', function () {
    $s = seedScenario(1);
    $o = amendmentOwner($s, 0);
    // an older write left a second open-ended owner-wide agreement beside the first
    $stacked = ManagementAgreement::factory()->create(['owner_id' => $o['owner']->id, 'unit_id' => null, 'commission_pct' => 19.00,
        'effective_from' => '2026-03-01', 'effective_to' => null, 'status' => 'active']);
    expect(amendCovering((string) $o['owner']->id, '2026-09-01'))->toBe(2);

    amendAck($o, '2026-10-01', 17.00);

    // both ended the day before; from 1 Oct exactly one governs
    expect($o['agreement']->fresh()->effective_to?->toDateString())->toBe('2026-09-30')
        ->and($stacked->fresh()->effective_to?->toDateString())->toBe('2026-09-30')
        ->and(amendCovering((string) $o['owner']->id, '2026-10-01'))->toBe(1)
        ->and(amendCovering((string) $o['owner']->id, '2026-12-01'))->toBe(1);
});

it('⚠️ an agreement that would only have STARTED on the effective day is ENDED, not left active with backwards dates', function () {
    $s = seedScenario(1);
    $o = amendmentOwner($s, 0);
    $later = ManagementAgreement::factory()->create(['owner_id' => $o['owner']->id, 'unit_id' => null, 'commission_pct' => 16.00,
        'effective_from' => '2026-10-01', 'effective_to' => null, 'status' => 'active']);

    amendAck($o, '2026-10-01', 17.00);

    expect($later->fresh()->status)->toBe('ended')
        ->and($later->fresh()->effective_to?->toDateString())->toBe('2026-09-30')
        ->and(amendCovering((string) $o['owner']->id, '2026-10-01'))->toBe(1);
});

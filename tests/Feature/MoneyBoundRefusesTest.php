<?php

use App\Models\OwnerPayoutPreference;
use App\Models\RateGuardrail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ══ A BOUND THAT FAILS OPEN — 2026-08-31 ════════════════════════════════════════════════════════════
//
// ⚠️ `MoneyInput::toMinor()` returns NULL for anything unparseable OR above its cap. Two callers turned that
// NULL into a silent default, so a bound the owner believed they had set did not exist:
//
//   · the payout MINIMUM      `… ?? 0`   → "€50,000,000" became NO MINIMUM, and the owner was told "updated"
//   · the rate GUARDRAILS     `: null`   → a typo in the floor became NO FLOOR, and the owner was told
//                                          "Guardrails saved — your manager prices within these limits"
//
// ⚠️ `?? 0` on a money input is the worst default there is, because ZERO IS A LEGAL AMOUNT — nothing
// downstream can tell an intended "none" from a silently-dropped figure. Both now REFUSE, naming the limit.
//
// ⚠️ THE OTHER ELEVEN `toMinor()` CALLERS WERE CHECKED and already refuse on NULL. One more,
// OwnerUnitRequestController, deliberately returns a THREE-state result (null = blank, false = unreadable)
// and its caller checks `=== false` — correct, and left alone.

function mbrOwner(): array
{
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->changePasswordTo('secret-Passw0rd!');

    return ['user' => $user->fresh(), 'ownerId' => (string) $s['units'][0]->fresh()->owner_id, 's' => $s];
}

function mbrPrefPayload(string $minimum): array
{
    return [
        'current_password' => 'secret-Passw0rd!',
        'frequency' => 'monthly', 'payout_day' => 1, 'minimum' => $minimum, 'paused' => '0',
    ];
}

// ── 1. ⚠️⚠️ AN ABSURD MINIMUM IS REFUSED, AND THE REFUSAL NAMES THE LIMIT ──────────────────────────
it('refuses a payout minimum above the limit instead of silently storing none', function () {
    $a = mbrOwner();

    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), mbrPrefPayload('50000000'))
        ->assertSessionHasErrors('minimum');

    $errors = session('errors')->get('minimum');
    expect(str_contains($errors[0], '€1,000,000'))->toBeTrue('the refusal must name the limit, not just refuse');

    // ⚠️ AND NOTHING WAS WRITTEN — the old code stored a row with minimum_minor = 0.
    expect(OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->exists())->toBeFalse();
});

it('refuses an unreadable payout minimum', function () {
    $a = mbrOwner();

    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), mbrPrefPayload('lots'))
        ->assertSessionHasErrors('minimum');

    expect(OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->exists())->toBeFalse();
});

// ── 2. ⚠️ "0 = NONE" SURVIVES — the legitimate case the old conflation hid behind ───────────────────
it('still accepts zero as an explicit no-minimum', function () {
    $a = mbrOwner();

    // ⚠️ A BLANK is not in this list: `minimum` carries a pre-existing `required` rule, so an empty field is
    //    refused before any of this runs. "None" is expressed as 0, which is what both pages submit.
    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), mbrPrefPayload(''))
        ->assertSessionHasErrors('minimum');

    foreach (['0', '0.00'] as $none) {
        OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->delete();

        $this->actingAs($a['user'])->from(route('owner.payout-method'))
            ->post(route('owner.payouts.preferences'), mbrPrefPayload($none))
            ->assertSessionHasNoErrors();

        $pref = OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->firstOrFail();
        expect($pref->minimum_minor)->toBe(0, "'{$none}' must mean an explicit none");
    }
});

it('accepts a real minimum at and below the limit', function () {
    $a = mbrOwner();

    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), mbrPrefPayload('1000000'))
        ->assertSessionHasNoErrors();

    expect(OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->firstOrFail()->minimum_minor)
        ->toBe(100_000_000);
});

// ── 3. ⚠️ BOTH PATHS REFUSE IDENTICALLY — the free-text field and the radio set ─────────────────────
// ⚠️ The asymmetry is how this hid: the Payouts page offers a bounded radio set that could never trip it,
// while the Payout Method page is free text. A forged POST from either now meets the same rule.
it('refuses identically whichever page the post claims to come from', function () {
    $a = mbrOwner();

    foreach ([route('owner.payout-method'), route('owner.payouts')] as $from) {
        $this->actingAs($a['user'])->from($from)
            ->post(route('owner.payouts.preferences'), mbrPrefPayload('50000000'))
            ->assertSessionHasErrors('minimum');
    }

    expect(OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->exists())->toBeFalse();
});

// ── 4. ⚠️⚠️ THE SECOND INSTANCE — a rate guardrail whose bound silently vanished ────────────────────
it('refuses an unreadable guardrail floor instead of storing no floor', function () {
    $a = mbrOwner();

    $this->actingAs($a['user'])->from(route('owner.rates'))
        ->post(route('owner.rates.guardrails'), [
            'pricing_mode' => 'dynamic', 'auto_approve_pct' => '8', 'floor' => 'one fifty', 'ceiling' => '600',
        ])->assertSessionHasErrors('floor');

    // ⚠️ The old code stored the row with floor_minor = NULL and said "Guardrails saved".
    expect(RateGuardrail::query()->where('owner_id', $a['ownerId'])->exists())->toBeFalse();
});

it('still accepts a blank guardrail as no bound', function () {
    $a = mbrOwner();

    $this->actingAs($a['user'])->from(route('owner.rates'))
        ->post(route('owner.rates.guardrails'), [
            'pricing_mode' => 'dynamic', 'auto_approve_pct' => '8', 'floor' => '', 'ceiling' => '600',
        ])->assertSessionHasNoErrors();

    $g = RateGuardrail::query()->where('owner_id', $a['ownerId'])->firstOrFail();
    expect($g->floor_minor)->toBeNull()
        ->and((int) $g->ceiling_minor)->toBe(60000);
});

// ══ C3 · THE OWNER'S PAYOUT-PREFERENCE CHANGE WAS INVISIBLE — 2026-08-31 ════════════════════════════
//
// ⚠️ `paused` lives in ONE column. The OPERATOR changing it wrote `owner.payouts_paused`; the OWNER changing
// the same column wrote nothing — one state change, two histories. Closed here.

use App\Models\AuditEvent;
use App\Models\Owner;

it('records the owner’s payout-preference change with complete before and after', function () {
    $a = mbrOwner();

    // First save — no row existed, so `before` is genuinely NULL and that is the fact worth recording.
    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), mbrPrefPayload('500'))
        ->assertSessionHasNoErrors();

    $first = AuditEvent::query()->where('action', 'owner.payout_preferences_updated')->sole();
    expect($first->subject_id)->toBe($a['ownerId'])
        ->and($first->before)->toBeNull()
        ->and($first->after['frequency'])->toBe('monthly')
        ->and($first->after['payout_day'])->toBe(1)
        ->and($first->after['minimum_minor'])->toBe(50000)
        ->and($first->after['paused'])->toBeFalse();

    // Second save — the owner pauses and raises the minimum. BOTH sides must be present.
    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), array_merge(mbrPrefPayload('2500'), ['paused' => '1']))
        ->assertSessionHasNoErrors();

    $second = AuditEvent::query()->where('action', 'owner.payout_preferences_updated')
        ->orderByDesc('created_at')->orderByDesc('id')->first();

    expect($second->before['paused'])->toBeFalse()
        ->and($second->after['paused'])->toBeTrue()             // ⚠️ the pause is visible as a TRANSITION
        ->and($second->before['minimum_minor'])->toBe(50000)
        ->and($second->after['minimum_minor'])->toBe(250000);
});

// ── ⚠️ A NO-OP IS NOT AN EVENT — the permission-matrix precedent ────────────────────────────────────
it('records nothing when the owner re-saves identical preferences', function () {
    $a = mbrOwner();

    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), mbrPrefPayload('500'))->assertSessionHasNoErrors();
    expect(AuditEvent::query()->where('action', 'owner.payout_preferences_updated')->count())->toBe(1);

    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), mbrPrefPayload('500'))->assertSessionHasNoErrors();

    expect(AuditEvent::query()->where('action', 'owner.payout_preferences_updated')->count())->toBe(1);
});

// ── ⚠️ THE TWO SIDES TELL ONE STORY — the owner's row and the operator's are reconcilable ──────────
it('leaves the owner’s and the operator’s pause records reconcilable on the same owner', function () {
    $a = mbrOwner();

    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), array_merge(mbrPrefPayload('500'), ['paused' => '1']))
        ->assertSessionHasNoErrors();

    $rows = AuditEvent::query()
        ->whereIn('action', ['owner.payout_preferences_updated', 'owner.payouts_paused'])
        ->where('subject_id', $a['ownerId'])->get();

    // Both actions carry the SAME subject, so a reader can order every pause on one owner regardless of
    // which side made it — which is what "one story" means here.
    expect($rows)->toHaveCount(1)
        // ⚠️ The MORPH ALIAS, not the FQCN — `getMorphClass()` resolves through the morph map, and asserting
        //    the class name would have been asserting something the column never holds.
        ->and($rows->first()->subject_type)->toBe((new Owner)->getMorphClass());
});

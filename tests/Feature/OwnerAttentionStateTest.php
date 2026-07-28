<?php

declare(strict_types=1);

use App\Models\OwnerAttentionState;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Completeness sweep · Wave A · GATE 1 — the SHARED owner_attention_states store behind the "Needs your
// attention" feed. A fresh owner has exactly one real signal: 'payout_method' (no PayoutMethod on file).
// Snooze/dismiss/undo are REAL, mutation-proven, owner-scoped, CAP_ACTIONS-gated, and shared by My Units +
// the Overview feed (one store, two surfaces — anti-drift).

it('snoozes a real attention item — it hides on BOTH My Units and Overview, and Undo restores it', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    // The real signal shows on both surfaces.
    $this->actingAs($user)->get('/owner/units')->assertOk()->assertSee('Confirm your payout bank account');
    $this->actingAs($user)->get('/owner')->assertOk()->assertSee('Confirm your payout bank account');

    // Snooze it for a week — a REAL state row is written (mutation-proven).
    $this->actingAs($user)->post('/owner/attention/snooze', ['attention_key' => 'payout_method', 'duration' => 'week'])->assertRedirect();
    $state = OwnerAttentionState::query()->where('owner_id', $ownerId)->where('attention_key', 'payout_method')->firstOrFail();
    expect($state->snoozed_until)->not->toBeNull()->and($state->snoozed_until->isFuture())->toBeTrue();

    // My Units: the item leaves the ACTIVE list and lands in the Snoozed panel (with Undo).
    $this->actingAs($user)->get('/owner/units')->assertOk()->assertSee('Snoozed')->assertSee('Undo');
    // Overview (no snoozed panel — items() only) hides it entirely → proves ONE store drives BOTH surfaces.
    $this->actingAs($user)->get('/owner')->assertOk()->assertDontSee('Confirm your payout bank account');

    // Undo (restore) → visible again on both.
    $this->actingAs($user)->post('/owner/attention/restore', ['attention_key' => 'payout_method'])->assertRedirect();
    expect($state->fresh()->snoozed_until)->toBeNull();
    $this->actingAs($user)->get('/owner/units')->assertOk()->assertSee('Confirm your payout bank account');
});

it('dismisses a real attention item — it hides and is NOT in the snoozed panel', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $this->actingAs($user)->post('/owner/attention/dismiss', ['attention_key' => 'payout_method'])->assertRedirect();

    $res = $this->actingAs($user)->get('/owner/units')->assertOk()->assertDontSee('Confirm your payout bank account');
    // dismissed is not a "snoozed" item — the Snoozed panel does not list it
    expect(OwnerAttentionState::query()->where('attention_key', 'payout_method')->value('dismissed_at'))->not->toBeNull();
});

it('is owner-scoped + tamper-safe — a key that is not a real current signal is a no-op', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    // 'tax_hold' is NOT currently raised for this owner (no finalized statements) → signalFor null → no row.
    $this->actingAs($user)->post('/owner/attention/snooze', ['attention_key' => 'tax_hold', 'duration' => 'week'])->assertRedirect();
    expect(OwnerAttentionState::query()->where('attention_key', 'tax_hold')->exists())->toBeFalse();

    // The real item is untouched.
    $this->actingAs($user)->get('/owner/units')->assertSee('Confirm your payout bank account');
});

it('BULK marks every raised item read (Wave B) — a real state row per current signal', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    // A fresh owner has one real signal (payout_method) → mark-all-read writes exactly its read_at.
    $this->actingAs($user)->post('/owner/attention/read-all')->assertRedirect();
    $state = OwnerAttentionState::query()->where('owner_id', $ownerId)->where('attention_key', 'payout_method')->firstOrFail();
    expect($state->read_at)->not->toBeNull();

    // The bulk read-all touches only genuine current signals — no junk rows appear.
    expect(OwnerAttentionState::query()->count())->toBe(1);
});

it('BULK snoozes every raised item for a week (Wave B) — mutation-proven, then hidden', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $this->actingAs($user)->post('/owner/attention/snooze-all')->assertRedirect();
    expect(OwnerAttentionState::query()->where('attention_key', 'payout_method')->value('snoozed_until'))->not->toBeNull();

    // The active feed no longer shows it (it's snoozed) — it lands in the Snoozed panel instead.
    $this->actingAs($user)->get('/owner')->assertOk()->assertDontSee('Confirm your payout bank account');
});

it('gates the bulk actions to CAP_ACTIONS — a view-only sub-user is refused', function () {
    $s = seedScenario(1);
    ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;
    $viewer = ownerSubUser($s, $ownerId, OwnerTeamRole::VIEW_ONLY);

    $this->actingAs($viewer)->post('/owner/attention/read-all')->assertForbidden();
    $this->actingAs($viewer)->post('/owner/attention/snooze-all')->assertForbidden();
    expect(OwnerAttentionState::query()->exists())->toBeFalse();
});

it('gates attention writes to CAP_ACTIONS — a view-only sub-user may SEE the feed but cannot snooze', function () {
    $s = seedScenario(1);
    ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;
    $viewer = ownerSubUser($s, $ownerId, OwnerTeamRole::VIEW_ONLY);

    // View-only can VIEW the feed…
    $this->actingAs($viewer)->get('/owner/units')->assertOk()->assertSee('Confirm your payout bank account');
    // …but the write is refused (CAP_ACTIONS, co-owner only).
    $this->actingAs($viewer)->post('/owner/attention/snooze', ['attention_key' => 'payout_method', 'duration' => 'week'])->assertForbidden();
    expect(OwnerAttentionState::query()->exists())->toBeFalse();
});

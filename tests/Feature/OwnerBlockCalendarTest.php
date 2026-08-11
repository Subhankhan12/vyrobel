<?php

use App\Models\Availability;
use App\Models\InventoryBlock;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER BLOCK CALENDAR (Phase 2 · Sub-gate 2) — the owner holds/releases dates on their OWN units, routed
// through the certified AssignmentService (single inventory write path).
//   • block/release PERSIST and move real availability (via the inventory_block.* events → recompute);
//   • THE OWNER-SCOPE GUARD (on a WRITE): an owner CANNOT block/release another owner's unit (the unit is
//     resolved from the owner's own units, never a request id) — mutation-proven;
//   • the block RESPECTS existing reservations via the REAL rule (assertUnitFree), not a reinvented check.

/** A linked owner-portal user owning unit[$index]. Guarded + uniquely named so it can't collide across files. */
if (! function_exists('blkOwner')) {
    function blkOwner(array $s, int $index, string $name): array
    {
        $unit = $s['units'][$index];
        $owner = ownUnit($unit);

        $user = User::factory()->create(['name' => $name]);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return ['user' => $user, 'owner' => $owner, 'unit' => $unit];
    }
}

/** The `blocked` count on the room-type's availability row for a date (0 when no row/no block). */
function blockedCount(string $roomTypeId, string $date): int
{
    $row = Availability::query()->where('room_type_id', $roomTypeId)->whereDate('date', $date)->first();

    return $row === null ? 0 : (int) $row->blocked;
}

// ── 1. BLOCK persists (reason='owner', note) AND moves real availability ──────────────────────────
it('blocks the owner’s own dates and reduces availability', function () {
    $s = seedScenario(1);
    $a = blkOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $a['unit']->id, 'first_night' => '2026-10-01', 'last_night' => '2026-10-02', 'reason' => 'Personal stay',
    ])->assertRedirect()->assertSessionHas('block_notice');

    $block = InventoryBlock::query()->where('unit_id', $a['unit']->id)->first();
    expect($block)->not->toBeNull()
        ->and($block->reason)->toBe('owner')          // the REAL enum value, never an invented one
        ->and($block->notes)->toBe('Personal stay');   // the granular reason lives in notes

    // Both held nights show as blocked on the room type's availability (the real recompute ran).
    expect(blockedCount((string) $a['unit']->room_type_id, '2026-10-01'))->toBe(1)
        ->and(blockedCount((string) $a['unit']->room_type_id, '2026-10-02'))->toBe(1)
        ->and(blockedCount((string) $a['unit']->room_type_id, '2026-10-03'))->toBe(0);   // half-open — the 3rd is open
});

// ── 2. RELEASE removes the block and restores availability ────────────────────────────────────────
it('releases a block and restores availability', function () {
    $s = seedScenario(1);
    $a = blkOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $a['unit']->id, 'first_night' => '2026-10-01', 'last_night' => '2026-10-02', 'reason' => 'Maintenance',
    ]);
    $block = InventoryBlock::query()->where('unit_id', $a['unit']->id)->firstOrFail();
    expect(blockedCount((string) $a['unit']->room_type_id, '2026-10-01'))->toBe(1);

    $this->actingAs($a['user'])->delete('/owner/calendar/blocks/'.$block->id)
        ->assertRedirect()->assertSessionHas('block_notice');

    expect(InventoryBlock::query()->where('unit_id', $a['unit']->id)->exists())->toBeFalse()   // soft-deleted → gone
        ->and(blockedCount((string) $a['unit']->room_type_id, '2026-10-01'))->toBe(0);          // freed again
});

// ── 3. THE GUARD — an owner CANNOT block another owner's unit (mutation-proven) ───────────────────
it('rejects blocking another owner’s unit and writes nothing', function () {
    $s = seedScenario(2);
    $a = blkOwner($s, 0, 'Amara Okafor');
    $b = blkOwner($s, 1, 'Bruno Vale');

    // A tampers with the request unit id to point at B's unit → 403, nothing written.
    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $b['unit']->id, 'first_night' => '2026-10-01', 'last_night' => '2026-10-02', 'reason' => 'Personal stay',
    ])->assertForbidden();

    expect(InventoryBlock::query()->where('unit_id', $b['unit']->id)->exists())->toBeFalse();
});

// ── 4. RESPECTS existing reservations via the REAL rule (assertUnitFree) ──────────────────────────
it('refuses to block a date that already has a guest booking', function () {
    $s = seedScenario(1);
    $a = blkOwner($s, 0, 'Amara Okafor');
    book($s, ['unit_id' => $a['unit']->id]);   // an active reservation on 2026-09-01..03

    // Blocking a night inside that stay is rejected by blockInventory's assertUnitFree — the real rule, not a
    // reinvented check — and surfaces as a friendly message, with NO block written.
    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $a['unit']->id, 'first_night' => '2026-09-01', 'last_night' => '2026-09-02', 'reason' => 'Personal stay',
    ])->assertRedirect()->assertSessionHas('block_error');

    expect(InventoryBlock::query()->where('unit_id', $a['unit']->id)->exists())->toBeFalse();
});

// ── 5. RELEASE is owner-scoped too — an owner can't release another owner's block ─────────────────
it('rejects releasing another owner’s block and leaves it intact', function () {
    $s = seedScenario(2);
    $a = blkOwner($s, 0, 'Amara Okafor');
    $b = blkOwner($s, 1, 'Bruno Vale');

    $this->actingAs($b['user'])->post('/owner/calendar/block', [
        'unit_id' => $b['unit']->id, 'first_night' => '2026-10-01', 'last_night' => '2026-10-02', 'reason' => 'Personal stay',
    ]);
    $bBlock = InventoryBlock::query()->where('unit_id', $b['unit']->id)->firstOrFail();

    $this->actingAs($a['user'])->delete('/owner/calendar/blocks/'.$bBlock->id)->assertForbidden();

    expect(InventoryBlock::query()->where('unit_id', $b['unit']->id)->exists())->toBeTrue();   // untouched
});

// ── 6. The screen is owner-scoped; a non-linked user fails closed ─────────────────────────────────
it('shows only the owner’s own units and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = blkOwner($s, 0, 'Amara Okafor');
    $b = blkOwner($s, 1, 'Bruno Vale');
    // Distinctive codes — a 2-char code like "U2" is too generic a needle and can collide with incidental
    // page content (ULIDs, hex, SVG paths), making assertSee/assertDontSee order-dependently flaky.
    $a['unit']->update(['code' => 'AOWNEDBLK']);
    $b['unit']->update(['code' => 'BOWNEDBLK']);

    $this->actingAs($a['user'])->get('/owner/calendar')
        ->assertOk()
        ->assertSee('AOWNEDBLK')        // A's own unit is pickable…
        ->assertDontSee('BOWNEDBLK');   // …B's is not

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    $this->actingAs($stranger)->get('/owner/calendar')
        ->assertOk()->assertSee('No units are linked');   // empty owner set → nothing to block
});

// ── 7. A REPEAT expands into N REAL individual blocks sharing one series_id (never a fake series) ───
it('expands a weekly repeat into real individual blocks under one series id', function () {
    $s = seedScenario(1);
    $a = blkOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $a['unit']->id, 'first_night' => '2026-10-05', 'last_night' => '2026-10-06',
        'reason' => 'Personal stay', 'repeat_freq' => 'weekly', 'repeat_count' => 3,
    ])->assertRedirect()->assertSessionHas('block_notice')->assertSessionHas('block_undo_series');

    // THREE real blocks, one per weekly occurrence, all sharing ONE non-null series_id.
    $blocks = InventoryBlock::query()->where('unit_id', $a['unit']->id)->orderBy('start_date')->get();
    expect($blocks)->toHaveCount(3)
        ->and($blocks->pluck('series_id')->unique()->filter()->count())->toBe(1)
        ->and($blocks->pluck('start_date')->map(fn ($d) => $d->toDateString())->all())
        ->toBe(['2026-10-05', '2026-10-12', '2026-10-19']);   // +7 days each — REAL, not a phantom parent

    // Each occurrence moved real availability (the certified recompute ran per block).
    expect(blockedCount((string) $a['unit']->room_type_id, '2026-10-05'))->toBe(1)
        ->and(blockedCount((string) $a['unit']->room_type_id, '2026-10-12'))->toBe(1)
        ->and(blockedCount((string) $a['unit']->room_type_id, '2026-10-19'))->toBe(1);
});

// ── 8. A series occurrence on a taken night is SKIPPED + reported honestly (never silently) ─────────
it('skips a series occurrence that collides with an existing hold and reports the real count', function () {
    $s = seedScenario(1);
    $a = blkOwner($s, 0, 'Amara Okafor');

    // Pre-hold the SECOND weekly occurrence's nights (2026-10-12..13) — that occurrence must be skipped.
    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $a['unit']->id, 'first_night' => '2026-10-12', 'last_night' => '2026-10-13', 'reason' => 'Maintenance',
    ]);

    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $a['unit']->id, 'first_night' => '2026-10-05', 'last_night' => '2026-10-06',
        'reason' => 'Seasonal hold', 'repeat_freq' => 'weekly', 'repeat_count' => 3,
    ])->assertRedirect()->assertSessionHas('block_notice');

    // Only 2 of the 3 series occurrences were created (the 12th collided with the pre-hold) — a REAL outcome.
    $series = InventoryBlock::query()->where('unit_id', $a['unit']->id)->whereNotNull('series_id')->orderBy('start_date')->get();
    expect($series)->toHaveCount(2)
        ->and($series->pluck('start_date')->map(fn ($d) => $d->toDateString())->all())->toBe(['2026-10-05', '2026-10-19'])
        ->and(InventoryBlock::query()->where('unit_id', $a['unit']->id)->count())->toBe(3);   // pre-hold + 2 series
});

// ── 9. Releasing a series removes EVERY block in it (owner-scoped) ─────────────────────────────────
it('releases a whole series and refuses another owner’s series', function () {
    $s = seedScenario(2);
    $a = blkOwner($s, 0, 'Amara Okafor');
    $b = blkOwner($s, 1, 'Bruno Vale');

    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $a['unit']->id, 'first_night' => '2026-10-05', 'last_night' => '2026-10-06',
        'reason' => 'Personal stay', 'repeat_freq' => 'monthly', 'repeat_count' => 2,
    ]);
    $seriesId = InventoryBlock::query()->where('unit_id', $a['unit']->id)->value('series_id');
    expect(InventoryBlock::query()->where('series_id', $seriesId)->count())->toBe(2);

    // Owner B cannot release A's series (owner-scope guard) — nothing removed.
    $this->actingAs($b['user'])->delete('/owner/calendar/series/'.$seriesId)->assertForbidden();
    expect(InventoryBlock::query()->where('series_id', $seriesId)->count())->toBe(2);

    // Owner A releases the whole series — both blocks gone, availability restored.
    $this->actingAs($a['user'])->delete('/owner/calendar/series/'.$seriesId)
        ->assertRedirect()->assertSessionHas('block_notice');
    expect(InventoryBlock::query()->where('series_id', $seriesId)->exists())->toBeFalse()
        ->and(blockedCount((string) $a['unit']->room_type_id, '2026-10-05'))->toBe(0);
});

// ── 10. The block reason is validated against the real set — junk is rejected, nothing written ─────
it('rejects a reason outside the allowed set', function () {
    $s = seedScenario(1);
    $a = blkOwner($s, 0, 'Amara Okafor');

    $this->actingAs($a['user'])->post('/owner/calendar/block', [
        'unit_id' => $a['unit']->id, 'first_night' => '2026-10-01', 'last_night' => '2026-10-02', 'reason' => 'Whatever I like',
    ])->assertSessionHasErrors('reason');

    expect(InventoryBlock::query()->where('unit_id', $a['unit']->id)->exists())->toBeFalse();
});

// ── EVERY BLOCKABLE DAY IS A REAL KEYBOARD-OPERABLE CONTROL ───────────────────────────────────────
// Owner-23 found the Owner-10 lockout again, in a SECOND copy of the grid: an open day was a <div>
// carrying only @mousedown/@mouseenter, with no tabindex, no role and no key handler anywhere on the
// cells. Selecting dates was therefore mouse-drag ONLY — and selecting is the first half of BLOCKING,
// which takes inventory off-sale through OwnerBlockService. A keyboard user was locked out of a
// money-affecting action on its own dedicated page. Every open day is now a real <button> with a
// spoken date, aria-pressed, and a keyboard select path (see pickSel in the view).
it('renders every blockable day as a real keyboard-operable button', function () {
    $s = seedScenario(1);
    $a = blkOwner($s, 0, 'Amara Okafor');

    $html = $this->actingAs($a['user'])->get('/owner/calendar')->assertOk()->getContent();

    // The month must actually contain open days, or this guard would pass on an empty grid.
    expect(substr_count($html, 'vy-cal-open'))->toBeGreaterThan(1);

    // Every open cell is a BUTTON, never a div: no `<div ... vy-cal-open` may survive.
    expect($html)->not->toContain('<div class="vy-cal-cell vy-cal-open"');
    expect(preg_match('/<button[^>]*class="vy-cal-cell vy-cal-open"/', $html))->toBe(1);

    // …and it carries a spoken name + a keyboard activation path.
    expect($html)->toContain('Select to block this day.')
        // ⚠️ Assert the ATTRIBUTE, not the bare expression. The first version of this needle was satisfied
        // by a CODE COMMENT that quoted the same expression, so deleting the real binding left it green.
        ->and($html)->toContain('@click="$event.detail === 0');
});

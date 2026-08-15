<?php

/*
 * DO9-1 — THE ADD-UNIT `notes` FIELD (2026-08-15).
 *
 * ⚠️ A WIRED COLUMN NOBODY COULD REACH. `OwnerUnitRequestController` has validated `notes` as
 * `['nullable','string','max:2000']` and `OwnerUnitRequestService` has persisted it all along — the wizard
 * simply had no input, so the only way to populate it was a hand-crafted POST. This pass adds the textarea
 * and changes NEITHER the validation nor the persistence: it reaches them.
 *
 * ⚠️ THE LEDGER WAS ACCURATE THIS TIME — worth recording after DO13-4 (4 paths were 9), D3-4 (1 consumer was
 * 3) and D1-3 ("populated" was 0-of-41). Confirmed anyway, before writing a line.
 *
 * ⚠️ AND THE ROUND-TRIP ALREADY EXISTS. A note nobody reads would be a half-feature, so the operator side was
 * checked BEFORE building: `OperatorUnitRequests::details()` folds `notes` into the request's detail line and
 * `unit-requests.blade.php` renders it `{{ $r['details'] }}` — auto-escaped. So owner free text reaches an
 * operator's browser as inert text, which is exactly what a stored-XSS check must prove rather than assume.
 *
 * ⚠️ OPTIONAL, BECAUSE THE RULE IS `nullable`. A field added to a wizard must not become a new obstacle in
 * front of filing a request — submitting with the box empty still files. Asserted both ways below.
 */

use App\Models\Membership;
use App\Models\UnitRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level const in a Pest file is GLOBAL and has collided in this suite before. */
const DO91_NOTE = 'Gate code changes monthly — the cleaners need the current one.';
const DO91_XSS = '<script>alert(1)</script><img src=x onerror=alert(2)>';

/** An owner who can file a unit request (the shape OwnerUnitRequestTest uses, inlined so this stands alone). */
function do91Owner(array $s): array
{
    $owner = ownUnit($s['units'][0]);
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);

    return ['user' => $user, 'owner' => $owner];
}

it('⚠️ DO9-1 — the wizard RENDERS the notes textarea, in the final step, bound to the backend’s field name', function () {
    $s = seedScenario(1);
    $a = do91Owner($s);

    $html = (string) $this->actingAs($a['user'])->get(route('owner.add-unit'))->assertOk()->getContent();

    // ⚠️ The NAME is what the controller validates and the service persists — a textarea named anything else
    // would render perfectly and silently never reach the column.
    expect($html)->toContain('name="notes"')
        ->toContain('id="au-notes"')
        ->toContain('Anything else we should know?')
        ->toContain('(optional)')                     // stated, not just permitted
        ->toContain('maxlength="2000"');              // mirrors the server rule

    // Reuses the wizard's own field idiom rather than a new one (§N.8f).
    expect($html)->toContain('class="vy-au-input" rows="3"');
});

it('⚠️ DO9-1 — submitting WITH a note PERSISTS it (raw-query verified)', function () {
    $s = seedScenario(1);
    $a = do91Owner($s);

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'notes' => DO91_NOTE,
    ])->assertRedirect(route('owner.add-unit'))->assertSessionHas('unit_request_done');

    // ⚠️ RAW QUERY — a check that read back through the same model/scope that wrote it would be weaker
    // evidence than reading the column itself (§N.18.1).
    $stored = DB::table('unit_requests')->where('owner_id', $a['owner']->id)->value('notes');
    expect($stored)->toBe(DO91_NOTE);
});

it('⚠️ DO9-1 — submitting WITHOUT a note still files the request (optional, not a new obstacle)', function () {
    $s = seedScenario(1);
    $a = do91Owner($s);

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa',
    ])->assertRedirect(route('owner.add-unit'))->assertSessionHasNoErrors()->assertSessionHas('unit_request_done');

    // ⚠️ NON-VACUITY: the request really was created — the assertion is "filed WITHOUT a note", not "nothing
    // happened".
    $row = DB::table('unit_requests')->where('owner_id', $a['owner']->id)->first();
    expect($row)->not->toBeNull()
        ->and($row->notes)->toBeNull();
});

it('⚠️ DO9-1 — an EMPTY-STRING note files cleanly too', function () {
    $s = seedScenario(1);
    $a = do91Owner($s);

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'notes' => '',
    ])->assertSessionHasNoErrors();

    expect(DB::table('unit_requests')->where('owner_id', $a['owner']->id)->count())->toBe(1);
});

it('⚠️ DO9-1 — the server rule is still authoritative: over 2000 characters is REJECTED', function () {
    // The `maxlength` attribute is a courtesy; a client can post past it. The server rule was already there
    // and this pass did not touch it — asserted so "I added an input" cannot quietly become "I relaxed a rule".
    $s = seedScenario(1);
    $a = do91Owner($s);

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'notes' => str_repeat('a', 2001),
    ])->assertSessionHasErrors('notes');

    expect(DB::table('unit_requests')->count())->toBe(0);
});

it('⚠️ DO9-1 — an HTML/script note reaches the OPERATOR as INERT TEXT (stored-XSS)', function () {
    /*
     * ⚠️ THE SECURITY CASE, and the reason the round-trip had to be checked before building: this is owner
     * free text rendered into an operator's browser. If the review used {!! !!} anywhere, adding the input
     * would have opened a stored-XSS path from an owner to the staff who review their requests.
     */
    $s = seedScenario(1);
    $a = do91Owner($s);

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'notes' => DO91_XSS,
    ])->assertSessionHasNoErrors();

    // ⚠️ NON-VACUITY: the payload really is stored verbatim — the escaping must happen at RENDER, not by the
    // write silently sanitising it (which would also be fine security-wise but is not what is claimed here).
    expect(DB::table('unit_requests')->value('notes'))->toBe(DO91_XSS);

    $operator = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
    $html = (string) $this->actingAs($operator)->get('/app/unit-requests')->assertOk()->getContent();

    // The note is visible to the reviewer…
    expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        // …and NOT as live markup.
        ->and($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->not->toContain('<img src=x onerror=alert(2)>');
});

it('⚠️ DO9-1 — the operator review renders the detail line ESCAPED, never raw', function () {
    // Reads the SOURCE of the display path: a future edit to `{!! !!}` would reopen the hole even if the
    // rendered assertion above happened to pass on some other page state.
    $blade = (string) file_get_contents(resource_path('views/livewire/operator/unit-requests.blade.php'));

    expect($blade)->toContain("{{ \$r['details'] }}")     // non-vacuity: this IS the display path
        ->and($blade)->not->toContain("{!! \$r['details'] !!}");
});

it('⚠️ DO9-1 — the validation and the persistence are UNCHANGED (this pass only reached them)', function () {
    // ⚠️ The whole premise of the item: the backend was already right. If this test ever fails, someone
    // "fixed" a rule that never needed fixing.
    $controller = (string) file_get_contents(app_path('Http/Controllers/Owner/OwnerUnitRequestController.php'));
    $service = (string) file_get_contents(app_path('Services/OwnerUnitRequestService.php'));

    expect($controller)->toContain("'notes' => ['nullable', 'string', 'max:2000']")
        ->and($controller)->toContain("'notes' => \$validated['notes'] ?? null")
        ->and($service)->toContain("'notes' => \$data['notes'] ?? null");
});

it('⚠️ DO9-1 — the wizard’s other fields still submit identically', function () {
    // A field added to a multi-step form must not disturb the rest of it.
    $s = seedScenario(1);
    $a = do91Owner($s);

    $this->actingAs($a['user'])->post('/owner/add-unit', [
        'requested_name' => 'Villa Marisol', 'requested_kind' => 'villa', 'location' => 'Faro',
        'bedrooms' => 3, 'bathrooms' => 2, 'sleeps' => 6, 'min_nights' => 4, 'notes' => DO91_NOTE,
    ])->assertSessionHasNoErrors();

    $row = UnitRequest::query()->where('owner_id', $a['owner']->id)->firstOrFail();
    expect((string) $row->requested_name)->toBe('Villa Marisol')
        ->and((string) $row->location)->toBe('Faro')
        ->and((int) $row->bedrooms)->toBe(3)
        ->and((int) $row->sleeps)->toBe(6)
        ->and((string) $row->notes)->toBe(DO91_NOTE);
});

it('⚠️ DO9-1 — no fixture residue (raw query, independent of any scope)', function () {
    expect((int) DB::table('unit_requests')->count())->toBe(0);
});

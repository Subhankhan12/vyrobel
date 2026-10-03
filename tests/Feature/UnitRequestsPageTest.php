<?php

declare(strict_types=1);

use App\Models\Owner;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\UnitRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE UNIT REQUESTS PAGE (2026-10-03, the founder: "all the sort of work you have done in the previous pages … in this
// module … make sure everything works very well"). An owner asks for a unit; the operator approves it into one of their
// properties (creating the real unit) or declines it. Each test failed first against the page as it stood.

function urpRequest(array $s, string $name = 'Sea View 2B', string $status = 'pending', ?Owner $owner = null): UnitRequest
{
    if ($owner === null) {
        $owner = Owner::query()->where('user_id', ownerRoleUser($s)->id)->firstOrFail();
    }

    return UnitRequest::create(['organization_id' => $s['org']->id, 'owner_id' => $owner->id, 'requested_name' => $name,
        'requested_kind' => 'apartment', 'sleeps' => 4, 'requested_rate_minor' => 12000, 'currency' => 'EUR', 'status' => $status]);
}

function urpPage(User $actor, string $query = ''): string
{
    return (string) test()->actingAs($actor)->get(route('app.unit-requests').$query)->assertOk()->getContent();
}

function urpXPath(string $html): DOMXPath
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

function urpScoped(array $s): User
{
    $scoped = operatorUser($s, [], 'Hotel Manager');
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);

    return $scoped;
}

// ── DEC-k — a unit is approved only into the properties the person works in ──────────────────────

it('⚠️⚠️ approves a request only into a property the person works in — another property is not found, and nothing is created (DEC-k)', function () {
    $s = seedScenario(1);
    $far = secondProperty($s);
    $farType = RoomType::create(['property_id' => $far->id, 'name' => 'Far', 'kind' => 'room']);
    $request = urpRequest($s);
    $scoped = urpScoped($s);

    // 🔬 before: the property was found through the tenant only — a 302 and a unit created in a property they cannot open
    test()->actingAs($scoped)->post(route('app.unit-requests.approve', $request->id), ['property_id' => $far->id, 'room_type_id' => $farType->id])
        ->assertNotFound();
    expect(Unit::query()->where('room_type_id', $farType->id)->exists())->toBeFalse()
        ->and($request->fresh()->status)->toBe('pending');

    // their own property still takes it
    test()->actingAs($scoped)->post(route('app.unit-requests.approve', $request->id), ['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id])
        ->assertRedirect();
    expect($request->fresh()->status)->toBe('approved');
})->group('security');

it('offers in the approve dialog only the properties the approve would accept', function () {
    $s = seedScenario(1);
    $far = secondProperty($s, 'Faraway House');
    RoomType::create(['property_id' => $far->id, 'name' => 'Far', 'kind' => 'room']);
    urpRequest($s);

    // 🔬 before: every property in the organisation was offered, so picking one the person cannot open was a click away
    $xp = urpXPath(urpPage(urpScoped($s)));
    $offered = array_map(fn ($o) => trim($o->textContent), iterator_to_array($xp->query('//select[@name="property_id"]/option[@value!=""]')));
    // …and their own property IS offered — the list is narrowed, not emptied
    expect($offered)->toBe([(string) $s['property']->name]);
});

it('⚠️ refuses to approve a request whose owner was removed — it would create a unit owned by an archived owner', function () {
    $s = seedScenario(1);
    $request = urpRequest($s);
    $request->owner->delete();   // removed (archived) after asking
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 🔬 the review: the approve went through, creating a unit for an owner who no longer exists
    test()->actingAs($manager)->post(route('app.unit-requests.approve', $request->id), ['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id])
        ->assertRedirect()->assertSessionHas('unit_request_error', 'This request’s owner has been removed — it can only be declined.');
    expect($request->fresh()->status)->toBe('pending')
        ->and(Unit::query()->where('name', 'Sea View 2B')->exists())->toBeFalse();

    // the page offers Decline only, and says why
    $html = urpPage($manager);
    expect($html)->toContain('Owner removed')
        ->and($html)->not->toContain("review('approve', \$el)")
        ->and($html)->toContain("review('decline', \$el)");
});

it('gives the review dialogs only to a role that may review', function () {
    $s = seedScenario(1);
    urpRequest($s);

    $desk = urpPage(operatorUser($s, [], 'Front Desk'));
    expect($desk)->not->toContain('vyo-ur-approve-t')->not->toContain('vyo-ur-decline-t');
});

it('says honestly whether the owner is told — and never that they were when nothing was sent', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s, 'Rui Monteiro');   // no portal login
    $request = urpRequest($s, 'Quiet Studio', 'pending', $owner);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 🔬 the review: the flash said "the owner has been notified" right after the dialog said nothing would be sent
    test()->actingAs($manager)->post(route('app.unit-requests.decline', $request->id), ['note' => 'Not this season'])
        ->assertRedirect()->assertSessionHas('unit_request_notice', 'Request for “Quiet Studio” declined.');
});

// ── The list — chips that count what clicking them shows ─────────────────────────────────────────

it('lists the requests behind chips that count what clicking them shows — waiting ones first', function () {
    $s = seedScenario(1);
    $owner = Owner::query()->where('user_id', ownerRoleUser($s)->id)->firstOrFail();
    urpRequest($s, 'Waiting Loft', 'pending', $owner);
    urpRequest($s, 'Approved Villa', 'approved', $owner);
    urpRequest($s, 'Declined Cabin', 'declined', $owner);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $xp = urpXPath(urpPage($manager));
    $chips = array_map(fn ($a) => trim((string) preg_replace('/\s+/u', ' ', $a->textContent)), iterator_to_array($xp->query('//*[contains(@class, "vyo-ur-chips")]//a')));
    expect($chips)->toBe(['Waiting 1', 'Approved 1', 'Declined 1', 'All 3']);

    // the waiting ones by default
    $default = urpPage($manager);
    expect($default)->toContain('Waiting Loft')->not->toContain('Approved Villa');

    $approved = urpPage($manager, '?status=approved');
    expect($approved)->toContain('Approved Villa')->not->toContain('Waiting Loft')->not->toContain('Declined Cabin');
});

// ── The review — Approve and Decline in the shared centred dialog ────────────────────────────────

it('⚠️ reviews a request in the shared centred dialog — Approve and Decline, each with its two buttons at one height', function () {
    $s = seedScenario(1);
    urpRequest($s);
    $xp = urpXPath(urpPage(operatorUser($s, [], 'Hotel Manager')));

    foreach (['vyo-ur-approve-t' => ['Cancel', 'Create unit'], 'vyo-ur-decline-t' => ['Cancel', 'Decline request']] as $title => $buttons) {
        $dialog = $xp->query('//*[@role="dialog" and @aria-labelledby="'.$title.'"]')->item(0);
        expect($dialog)->toBeInstanceOf(DOMElement::class, $title)
            ->and(preg_split('/\s+/', $dialog->parentNode->getAttribute('class')))->toContain('vy-modal-backdrop')
            ->and(preg_split('/\s+/', $dialog->getAttribute('class')))->toContain('vyo-dlg');
        $foot = $xp->query('.//*[contains(concat(" ", normalize-space(@class), " "), " vy-modal-foot ")]', $dialog)->item(0);
        expect(array_map(fn ($b) => trim($b->textContent), iterator_to_array($xp->query('.//button', $foot))))->toBe($buttons);
    }
});

it('⚠️ reopens Approve for the same request when the room type is missing, and says so', function () {
    $s = seedScenario(1);
    $request = urpRequest($s);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $html = (string) test()->actingAs($manager)->from(route('app.unit-requests'))->followingRedirects()
        ->post(route('app.unit-requests.approve', $request->id), ['_form' => 'approve', '_request' => $request->id, 'property_id' => $s['property']->id])
        ->assertOk()->getContent();

    // 🔬 before: the form closed and the page said nothing
    expect($html)->toContain('approveOpen: true')
        ->and($html)->toContain('Choose the room type the unit joins.');
});

it('⚠️ uses no inline style — every display is a class, and every class has a rule', function () {
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/livewire/operator/unit-requests.blade.php')));
    expect(substr_count($source, 'style="'))->toBe(0);

    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    preg_match_all('/\sclass="([^"]*)"/', $source, $m);
    $missing = [];
    foreach ($m[1] as $attr) {
        $attr = (string) preg_replace('/\{\{.*?\}\}|@[a-z]+\s*\(.*?\)|@end[a-z]+/s', ' ', $attr);
        foreach (preg_split('/\s+/', $attr, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (! str_ends_with($token, '-') && preg_match('/^vyo?-[a-z0-9-]+$/', $token)
                && preg_match('/\.'.preg_quote($token, '/').'(?![a-zA-Z0-9_-])/', $css) !== 1) {
                $missing[] = $token;
            }
        }
    }
    expect(array_values(array_unique($missing)))->toBe([]);
});

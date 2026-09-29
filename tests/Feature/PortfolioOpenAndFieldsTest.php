<?php

declare(strict_types=1);

use App\Models\Property;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

// ⚠️ PORTFOLIO — OPEN, AND THE DIALOGS' FIELDS (2026-09-29, the founder's report; design approved the same day).
//   · "Open" on a card did nothing visible: it reused the top-bar switcher, which sends you BACK to where you were, so
//     the page reloaded in place and only a small "Working here" tag moved. Open now takes you INTO the property — its
//     Dashboard, saying so. The top-bar switcher keeps its behaviour, and Open can go to the Dashboard only.
//   · The Add and Edit dialogs' Type, Status and Time zone were bare browser dropdowns. Type and Status are now choice
//     tiles, Time zone a searchable list with each zone's offset — and every one still posts the SAME field.

uses(DatabaseTransactions::class);

it('⚠️⚠️ Open takes you INTO the property — its Dashboard, working in it, and it says so', function () {
    $s = seedScenario(1);
    $other = secondProperty($s, 'Harbour Lofts');
    $manager = operatorUser($s, [], 'Property Owner');

    // the card's Open carries the destination
    $this->actingAs($manager)->get(route('app.portfolio'))->assertOk()->assertSee('name="open" value="dashboard"', false);

    $this->actingAs($manager)->post(route('app.property.switch'), ['property_id' => $other->id, 'open' => 'dashboard'])
        ->assertRedirect(route('app.dashboard'))
        ->assertSessionHas('current_property_id', (string) $other->id);

    $this->actingAs($manager)->get(route('app.dashboard'))->assertOk()
        ->assertSeeText('You’re now working in '.$other->name);
})->group('security');

it('⚠️ the top-bar switcher still keeps you where you are', function () {
    $s = seedScenario(1);
    $other = secondProperty($s);
    $manager = operatorUser($s, [], 'Property Owner');

    $this->actingAs($manager)->from(route('app.calendar'))
        ->post(route('app.property.switch'), ['property_id' => $other->id])
        ->assertRedirect(route('app.calendar'))
        ->assertSessionHas('current_property_id', (string) $other->id);
})->group('security');

it('⚠️⚠️ Open can go to the Dashboard ONLY — any other destination is refused, and nothing switches (no open redirect)', function () {
    $s = seedScenario(1);
    $other = secondProperty($s);
    $manager = operatorUser($s, [], 'Property Owner');

    foreach (['https://evil.example/steal', '//evil.example', 'settings'] as $where) {
        $response = $this->actingAs($manager)->from(route('app.portfolio'))
            ->post(route('app.property.switch'), ['property_id' => $other->id, 'open' => $where]);
        $response->assertSessionHasErrors('open')->assertRedirect(route('app.portfolio'));
        expect(session('current_property_id'))->not->toBe((string) $other->id, "open={$where} must not switch");
    }
})->group('security');

it('⚠️ Add: Type is three choice tiles and Time zone a searchable list with offsets — no bare dropdowns — and it still saves', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');

    $page = $this->actingAs($manager)->get(route('app.portfolio'))->assertOk()
        ->assertSee('data-combobox', false)
        ->assertSee('Europe/Lisbon', false)
        ->assertSee('aria-label="Search time zones"', false);   // the search box inside the list is named
    $html = (string) $page->getContent();
    expect(substr_count($html, 'type="radio" name="type" value="hotel"'))->toBeGreaterThanOrEqual(1, 'Type is choice tiles')
        ->and(str_contains($html, '<select name="type"'))->toBeFalse('the bare Type dropdown is gone')
        ->and((bool) preg_match('/UTC[+−-]\d/u', $html))->toBeTrue('each zone carries its offset');
    assertNoRawBlade($page);

    $this->actingAs($manager)->post(route('app.portfolio.store'), [
        '_form' => 'add', 'name' => 'Tile Test House', 'type' => 'guest_house', 'timezone' => 'Asia/Baku', 'location' => 'Baku',
    ])->assertRedirect(route('app.portfolio'));
    $made = DB::table('properties')->where('organization_id', $s['org']->id)->where('name', 'Tile Test House')->first(['type', 'timezone']);
    expect($made)->not->toBeNull()->and($made->type)->toBe('guest_house')->and($made->timezone)->toBe('Asia/Baku');
})->group('security');

it('⚠️ Edit: Status is an Active | Inactive toggle, Time zone the same searchable list — and saving still updates every field', function () {
    $s = seedScenario(1);
    $property = $s['property'];
    $manager = operatorUser($s, [], 'Property Owner');

    $html = (string) $this->actingAs($manager)->get(route('app.portfolio'))->assertOk()->getContent();
    expect(str_contains($html, 'type="radio" name="status" value="inactive"'))->toBeTrue('Status is a toggle')
        ->and(str_contains($html, '<select name="status"'))->toBeFalse('the bare Status dropdown is gone');

    $this->actingAs($manager)->post(route('app.portfolio.update'), [
        'property_id' => $property->id, 'name' => 'Renamed House', 'type' => 'villa', 'timezone' => 'America/New_York', 'status' => 'inactive',
    ])->assertRedirect(route('app.portfolio'));
    $row = DB::table('properties')->where('id', $property->id)->first(['name', 'type', 'timezone', 'status']);
    expect($row->name)->toBe('Renamed House')->and($row->type)->toBe('villa')
        ->and($row->timezone)->toBe('America/New_York')->and($row->status)->toBe('inactive');
})->group('security');

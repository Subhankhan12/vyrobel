<?php

use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 2d — the Rates grid gained a read-only room-type filter (?rt=), narrowing the grid to one room type
// while the dropdown still offers the full set. A tampered/unknown id falls through to "all".

it('narrows the rates grid to one room type via ?rt=, offering the full set in the filter', function () {
    $s = seedScenario(1);   // creates room type "Std"
    $other = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Deluxe Suite', 'kind' => 'room']);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $session = ['current_property_id' => $s['property']->id];

    // Unfiltered → both room types render.
    $this->actingAs($manager)->withSession($session)->get(route('app.rates'))
        ->assertOk()->assertSee('Std')->assertSee('Deluxe Suite');

    // ?rt=<other> → only that room type's row (but the filter dropdown still lists both).
    $this->actingAs($manager)->withSession($session)->get(route('app.rates', ['rt' => $other->id]))
        ->assertOk()
        ->assertSee('Deluxe Suite')
        ->assertSee('1 room type');   // the subtitle reflects the narrowed grid

    // A bogus rt id falls through to "all" (no silent empty).
    $this->actingAs($manager)->withSession($session)->get(route('app.rates', ['rt' => 'nope']))
        ->assertOk()->assertSee('Std')->assertSee('Deluxe Suite');
});

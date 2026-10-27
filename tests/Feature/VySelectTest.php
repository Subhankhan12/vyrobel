<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\User;
use App\Support\Time\TimezoneOptions;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * ⚠️ THE VYROBEL SELECT, FIRST ON /app/profile (2026-10-09, his request: "the drop-downs are looking very basic … make me very
 * cool drop-downs … first work on this page … I will check it first"). `<x-select>` replaces the page's two native selects,
 * Language and Timezone; the rest of the app waits for his approval.
 *
 * 🔬 And a defect it closes: the Timezone select offered FIVE zones while the `timezone` rule accepts every one, so a person
 * elsewhere (he is in Baku) saw "Europe/Lisbon" — no option matched, so the first showed — and saving the form for any other
 * reason wrote Lisbon over their zone.
 *
 * ⚠️ WHAT THESE CAN AND CANNOT SEE: the markup, the options, the saved value and the component's rules. The behaviour — opening,
 * search, keyboard, the live clocks, centring on the choice — is a browser fact: 🖥 walked 2026-10-09 (VySelect, profile).
 *
 * ⚠️ 2026-10-10 — REBUILT ON THE ONE ENGINE (his approval: "the same sort of select … in the entire application"). The field is
 * now a REAL select (the engine opens and draws its panel; SelectEngineTest guards the engine), so these read a select, its
 * <optgroup>s and data-hint / data-clock where they read a hidden input and an Alpine button before.
 */
uses(DatabaseTransactions::class);

// The offsets below are summer ones (Lisbon UTC+1). The page reads the offset at the moment it renders, so the clock is held
// on a summer day — written 2026-10-27, after this file went red on its own when Lisbon left summer time (OPEN D-TEST-LISBON-DST).
beforeEach(fn () => $this->travelTo(CarbonImmutable::parse('2026-07-15 12:00:00', 'UTC')));

function selectStaff(array $s, ?string $timezone = null): User
{
    $user = User::factory()->create(['status' => 'active', 'timezone' => $timezone, 'locale' => 'en']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $s['org'], 'Hotel Manager');
}

/** The personal-details form as the page renders it. */
function personalForm(string $html): string
{
    preg_match('#<form method="POST" action="[^"]*/app/profile" class="vy-card">.*?</form>#s', $html, $m);

    return $m[0] ?? '';
}

it('renders Language and Timezone as the rich select — a real select, named by its label, with the saved value chosen', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(selectStaff($s, 'Europe/Lisbon'))->get(route('app.profile'))->assertOk()->getContent();
    $form = personalForm($html);

    expect($form)->not->toBe('')
        // A REAL select carries the value: it posts with the form before (or without) a script.
        // Named by the page's own label[for] (a real select needs nothing more); the label's id names the open list.
        ->and($form)->toContain('<label class="vy-field-label" id="locale-label" for="locale">Language</label>')
        ->and($form)->toMatch('#<select name="locale" id="locale"\s+data-labelledby="locale-label"#')
        // `data-search` itself, never matched inside data-search-placeholder.
        ->and($form)->toMatch('#<select name="timezone" id="tz"\s+data-labelledby="tz-label"[^>]*\sdata-search(?=[\s>])#s')
        ->and($form)->toMatch('#<option value="en"[^>]*selected[^>]*>English</option>#')
        ->and($form)->toMatch('#<option value="Europe/Lisbon"[^>]*data-hint="UTC\+1"[^>]*data-clock="Europe/Lisbon"[^>]*selected[^>]*>Lisbon</option>#')
        // Regions as <optgroup>s; the device's own zone offered; the hint drawn over the field, server-rendered.
        ->and($form)->toContain('<optgroup label="Europe">')
        ->and($form)->toContain('data-suggest-device-zone')
        ->and($form)->toContain('<span class="vys-deco" aria-hidden="true"><span class="vys-deco-hint">UTC+1</span>');
});

it('⚠️ offers every zone the server accepts, and shows a zone outside the old five as itself — never Lisbon', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(selectStaff($s, 'Asia/Baku'))->get(route('app.profile'))->assertOk()->getContent();
    $form = personalForm($html);

    // The bug: no option matched Baku, so the first ("Europe/Lisbon") showed and would have been saved.
    expect($form)->toMatch('#<option value="Asia/Baku"[^>]*selected[^>]*>Baku</option>#')
        ->and($form)->not->toMatch('#<option value="Europe/Lisbon"[^>]*selected#')
        ->and($form)->toContain('<span class="vys-deco-hint">UTC+4</span>');

    expect(count(TimezoneOptions::choices()))->toBe(count(DateTimeZone::listIdentifiers()));
});

it('saves a zone outside the old five', function () {
    $s = seedScenario(1);
    $user = selectStaff($s, 'Europe/Lisbon');

    $this->actingAs($user)->post(route('app.profile.update'), [
        'name' => 'Sel Staff', 'email' => 'sel-staff-'.uniqid().'@x.test', 'locale' => 'es', 'timezone' => 'Asia/Baku',
    ])->assertRedirect(route('app.profile'))->assertSessionHas('profile_notice');

    expect($user->refresh()->timezone)->toBe('Asia/Baku')->and($user->locale)->toBe('es');
});

it('labels every zone by its city, region and offset, and keeps a saved zone the list lacks', function () {
    // The one offset definition, shared with the Portfolio's zone lists (`TimezoneOptions::all()`).
    expect(TimezoneOptions::offsetLabel(0))->toBe('UTC+0')
        ->and(TimezoneOptions::offsetLabel(4 * 3600))->toBe('UTC+4')
        ->and(TimezoneOptions::offsetLabel(5 * 3600 + 1800))->toBe('UTC+5:30')
        ->and(TimezoneOptions::offsetLabel(-3 * 3600))->toBe('UTC−3');

    $options = collect(TimezoneOptions::choices());
    expect($options->firstWhere('value', 'America/Argentina/Buenos_Aires')['label'])->toBe('Buenos Aires, Argentina')
        ->and($options->firstWhere('value', 'Asia/Baku')['group'])->toBe('Asia')
        ->and($options->firstWhere('value', 'Asia/Baku')['clock'])->toBe('Asia/Baku')
        // A typed "-3" must find a UTC−3 zone: the keywords carry an ASCII minus too.
        ->and($options->firstWhere('value', 'America/Sao_Paulo')['keywords'])->toContain('utc-3');

    // ⚠️ An option list must never be what changes a stored value: a saved alias outside the canonical list is kept.
    expect(collect(TimezoneOptions::choices('Asia/Calcutta'))->pluck('value'))->toContain('Asia/Calcutta')
        ->and(collect(TimezoneOptions::choices('Not/AZone'))->pluck('value'))->not->toContain('Not/AZone');
});

it('draws the profile on the one engine, included once', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(selectStaff($s, 'Europe/Lisbon'))->get(route('app.profile'))->assertOk()->getContent();

    expect(substr_count($html, 'window.VySelect = {'))->toBe(1)
        ->and(substr_count($html, '/* ── The closed field'))->toBe(1)
        // No Alpine panel of its own any more: one engine for every drop-down.
        ->and($html)->not->toContain('<template x-teleport="body">');
});

<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\User;
use App\Support\Time\TimezoneOptions;
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
 */
uses(DatabaseTransactions::class);

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

it('renders Language and Timezone as the new select — no native select left in the form', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(selectStaff($s, 'Europe/Lisbon'))->get(route('app.profile'))->assertOk()->getContent();
    $form = personalForm($html);

    expect($form)->not->toBe('')
        ->and($form)->not->toContain('<select')
        // The form value: a hidden input carrying the saved value, so the form posts it before (or without) Alpine.
        ->and($form)->toContain('<input type="hidden" name="locale" value="en"')
        ->and($form)->toContain('<input type="hidden" name="timezone" value="Europe/Lisbon"')
        // The field: a combobox named by its visible label, server-rendered with the chosen label.
        ->and($form)->toMatch('#<button type="button" id="tz"[^>]*role="combobox"[^>]*aria-controls="tz-list"#s')
        ->and($form)->toContain('aria-labelledby="tz-label tz"')
        ->and($form)->toMatch('#<span class="vys-label"[^>]*>Lisbon</span>#');
});

it('⚠️ offers every zone the server accepts, and shows a zone outside the old five as itself — never Lisbon', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(selectStaff($s, 'Asia/Baku'))->get(route('app.profile'))->assertOk()->getContent();
    $form = personalForm($html);

    // The bug: no option matched Baku, so the first ("Europe/Lisbon") showed and would have been saved.
    expect($form)->toContain('<input type="hidden" name="timezone" value="Asia/Baku"')
        ->and($form)->toMatch('#<span class="vys-label"[^>]*>Baku</span>#')
        ->and($form)->toMatch('#<span class="vys-hint"[^>]*>UTC\+4</span>#');

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

it('floats the list above every card, centres the choice on opening, and styles itself once', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(selectStaff($s, 'Europe/Lisbon'))->get(route('app.profile'))->assertOk()->getContent();

    // ⚠️ Teleported to <body>: each card is a backdrop-filter layer, and a list left inside one is painted under the next card.
    expect(substr_count($html, '<template x-teleport="body">'))->toBe(2)
        // ⚠️ One frame after the tick, and LAYOUT offsets: a hidden list does not scroll, and the 98.5% opening scale makes a
        //    screen rect 1.5% short (the walk saw Lisbon open near the bottom, then 195px off).
        ->and($html)->toContain('this.$nextTick(() => requestAnimationFrame(() => {')
        ->and($html)->toContain('list.scrollTop = (row.offsetTop - list.offsetTop) - (list.clientHeight - row.offsetHeight) / 2;')
        // Two selects, one style block (its heading; `.vys-panel {` itself appears twice — the reduced-motion rule repeats it).
        ->and(substr_count($html, '/* ── The Vyrobel select (components/select.blade.php)'))->toBe(1);
});

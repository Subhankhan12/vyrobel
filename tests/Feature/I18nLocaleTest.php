<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetVisitorLocale;
use App\Models\Guest;
use App\Models\Organization;
use App\Services\BookingEngineService;
use App\Support\I18n\Locales;
use App\Support\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ I18N-01 (2026-10-27) — whose language a request speaks, and which languages are ever applied. The founder's rulings:
// the public pages start in English (never the browser's language); each account gets its own language; a language is
// applied only once it is FINISHED (config('app.available_locales')). These tests pretend German is finished.

function i18nProbeRoute(): void
{
    Route::middleware('web')->get('/__i18n/probe', fn () => app()->getLocale().'|'.Carbon::now()->locale);
}

function i18nGermanFinished(): void
{
    config(['app.available_locales' => ['en', 'de']]);
}

it('⚠️⚠️ applies only FINISHED languages — a planned one that is not finished falls back to English', function () {
    config(['app.available_locales' => ['en']]);

    expect(Locales::resolve('de'))->toBe('en')
        ->and(Locales::resolve(null, '', 'de'))->toBe('en')
        ->and(Locales::isPlanned('de'))->toBeTrue()
        ->and(Locales::isPlanned('zz'))->toBeFalse()
        ->and(count(Locales::planned()))->toBe(28)
        ->and(Locales::offered())->toBe(['en', 'pt', 'es', 'fr']);

    i18nGermanFinished();
    expect(Locales::resolve('de'))->toBe('de')
        ->and(Locales::resolve('pt', 'de'))->toBe('de')          // pt is planned, not finished: the next candidate wins
        ->and(Locales::offered())->toContain('de');
});

it('⚠️⚠️ starts a signed-out visitor in ENGLISH whatever the browser says — and remembers a language they pick', function () {
    i18nGermanFinished();
    i18nProbeRoute();

    test()->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get('/__i18n/probe')->assertSeeText('en|');

    $picked = test()->get('/__i18n/probe?lang=de');
    $picked->assertSeeText('de|')->assertCookie(SetVisitorLocale::COOKIE, 'de');
    expect((string) $picked->headers->get('Vary'))->toContain('Cookie');

    test()->get('/__i18n/probe?lang=zz')->assertSeeText('en|')->assertCookieMissing(SetVisitorLocale::COOKIE);
    test()->withCookie(SetVisitorLocale::COOKIE, 'de')->get('/__i18n/probe')->assertSeeText('de|');
});

it('⚠️⚠️ gives a signed-in person THEIR OWN language, then their organisation\'s — and ignores ?lang= for them', function () {
    i18nGermanFinished();
    i18nProbeRoute();
    $s = seedScenario(1);

    $mine = operatorUser($s, ['locale' => 'de'], 'Front Desk');
    test()->actingAs($mine)->get('/__i18n/probe?lang=en')->assertSeeText('de|');

    $none = operatorUser($s, ['locale' => null], 'Front Desk');
    test()->actingAs($none)->get('/__i18n/probe')->assertSeeText('en|');

    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['default_locale' => 'de']));
    test()->actingAs($none->fresh())->get('/__i18n/probe')->assertSeeText('de|');

    $unfinished = operatorUser($s, ['locale' => 'pt'], 'Front Desk');   // planned, not finished → the organisation's
    test()->actingAs($unfinished)->get('/__i18n/probe')->assertSeeText('de|');
});

it('⚠️ reads a guest\'s OWN column for the guest realm', function () {
    i18nGermanFinished();
    i18nProbeRoute();
    $s = seedScenario(1);
    $guest = Guest::create(['first_name' => 'Gi', 'last_name' => 'Language', 'email' => 'i18n-'.Str::lower(Str::random(8)).'@example.test', 'language' => 'de']);

    test()->actingAs($guest, 'guest')->get('/__i18n/probe')->assertSeeText('de|');
    expect($guest->preferredLocale())->toBe('de');

    $guest->forceFill(['language' => null])->save();
    expect($guest->fresh()->preferredLocale())->toBe('en');
});

it('⚠️⚠️ sends mail in the RECIPIENT\'s language — HasLocalePreference on both kinds of account', function () {
    i18nGermanFinished();
    $s = seedScenario(1);
    $user = operatorUser($s, ['locale' => 'de'], 'Front Desk');

    expect($user)->toBeInstanceOf(HasLocalePreference::class)
        ->and($user->preferredLocale())->toBe('de')
        ->and(new Guest)->toBeInstanceOf(HasLocalePreference::class);

    config(['app.available_locales' => ['en']]);
    expect($user->preferredLocale())->toBe('en');   // the choice is kept; the screens stay English until German is finished
});

it('⚠️ moves Carbon\'s month and day names with the language — Serbian in Cyrillic', function () {
    config(['app.available_locales' => ['en', 'de', 'sr']]);
    $date = Carbon::create(2026, 8, 5);

    app()->setLocale('de');
    expect($date->translatedFormat('F'))->toBe('August')->and(Carbon::create(2026, 10, 5)->translatedFormat('F'))->toBe('Oktober');
    app()->setLocale('sr');
    expect(Carbon::create(2026, 10, 5)->translatedFormat('F'))->toMatch('/\p{Cyrillic}/u');
    app()->setLocale('en');
    expect(Carbon::create(2026, 10, 5)->translatedFormat('F'))->toBe('October');
});

it('⚠️ applies the language on Livewire update requests too — SetLocale is in the group Livewire\'s update route runs', function () {
    $route = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => str_contains((string) $r->getName(), 'livewire.update'));
    expect($route)->not->toBeNull();

    $kernelGroups = app(Kernel::class)->getMiddlewareGroups();
    expect($route->gatherMiddleware())->toContain('web')
        ->and($kernelGroups['web'])->toContain(SetLocale::class)->toContain(SetVisitorLocale::class);
});

it('keeps every saved choice pickable that was pickable before — and a guest may store only a planned language', function () {
    $s = seedScenario(1);
    $guest = Guest::create(['first_name' => 'Gi', 'last_name' => 'Stored', 'email' => 'i18n-'.Str::lower(Str::random(8)).'@example.test']);

    test()->actingAs($guest, 'guest')->post(route('guest.account.preferences'), ['language' => 'zz-junk'])->assertRedirect();
    expect($guest->fresh()->language)->toBeNull();
    test()->actingAs($guest, 'guest')->post(route('guest.account.preferences'), ['language' => 'de'])->assertRedirect();
    expect($guest->fresh()->language)->toBe('de');
});

it('⚠️⚠️ carries the language a guest BOOKED in onto the guest — and leaves an English booking exactly as before', function () {
    i18nGermanFinished();
    $s = publicPropertyScenario(units: 2);
    config(['services.payments.gateway' => 'manual']);
    $engine = app(BookingEngineService::class);

    app()->setLocale('de');
    $q1 = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'de@example.test');
    $de = $engine->book($q1, 'offline', ['email' => 'de@example.test', 'first_name' => 'Greta', 'last_name' => 'B']);
    app()->setLocale('en');
    $q2 = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-04', '2026-09-06', 2, 0, 'en@example.test');
    $en = $engine->book($q2, 'offline', ['email' => 'en@example.test', 'first_name' => 'Ed', 'last_name' => 'C']);

    $guestOf = fn (array $r) => app(TenantContext::class)->runAsSuperAdmin(fn () => Guest::query()->whereKey($r['reservation']->lead_guest_id)->first());
    expect($de['status'])->toBe('confirmed')->and($guestOf($de)->language)->toBe('de')->and($guestOf($de)->preferredLocale())->toBe('de')
        ->and($en['status'])->toBe('confirmed')->and($guestOf($en)->language)->toBeNull();
});

it('⚠️ shows the language menu only when there is a second finished language — never to someone signed in', function () {
    config(['app.available_locales' => ['en']]);
    expect((string) test()->get('/login')->getContent())->not->toContain('vy-langmenu');   // English only: nothing new on the page

    i18nGermanFinished();
    $html = (string) test()->get('/login')->getContent();
    expect($html)->toContain('class="vy-langmenu"')->toContain('>Deutsch</option>')->toContain('>English</option>');
});

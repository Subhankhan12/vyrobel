<?php

declare(strict_types=1);

use App\Providers\HorizonServiceProvider;
use App\Support\Ops\QueueDashboard;
use Illuminate\Support\Facades\Route;

// ⚠️⚠️ VY-33 (audit of 2026-11-24, read in code) — THE QUEUE DASHBOARD'S ROUTES ARE NOT REGISTERED ON A HOST WITH NO REDIS.
//
// Horizon was auto-discovered, so `/horizon` and its API were registered on production — which runs a database queue drained by a
// panel cron and has no Redis. It is now (1) left out of package discovery (`composer.json` → extra.laravel.dont-discover) and (2)
// registered by AppServiceProvider only when `queue.default` is `redis` (QueueDashboard::wanted()). This suite runs the `sync` queue —
// the same "not Redis" answer production gives — so the routes must not be here at all.

it('⚠️⚠️ VY-33: with a queue that is not Redis (the suite\'s, and production\'s), not one Horizon route is registered', function () {
    expect(config('queue.default'))->not->toBe('redis')
        ->and(QueueDashboard::wanted())->toBeFalse();

    $horizon = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'horizon.') || str_starts_with($route->uri(), 'horizon'))
        ->map(fn ($route): string => $route->uri())
        ->values()->all();

    expect($horizon)->toBe([]);
    $this->get('/horizon')->assertNotFound();
    $this->get('/horizon/api/stats')->assertNotFound();
});

it('VY-33: QueueDashboard answers yes for a Redis queue and no for every other', function (string $queue, bool $wanted) {
    config(['queue.default' => $queue]);

    expect(QueueDashboard::wanted())->toBe($wanted);
})->with([
    ['redis', true],
    ['database', false],
    ['sync', false],
]);

it('⚠️ VY-33: Horizon cannot come back through package discovery or the providers list', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['extra']['laravel']['dont-discover'] ?? [])->toContain('laravel/horizon');

    // the app's providers list registers only what it always wants; the Horizon pair is AppServiceProvider's, conditional
    $providers = require base_path('bootstrap/providers.php');
    expect($providers)->not->toContain(HorizonServiceProvider::class)
        ->and($providers)->not->toContain(Laravel\Horizon\HorizonServiceProvider::class);

    // and the package manifest the framework reads (rebuilt by `package:discover`) names no Horizon provider
    $manifest = require base_path('bootstrap/cache/packages.php');
    expect(array_key_exists('laravel/horizon', $manifest))->toBeFalse();
});

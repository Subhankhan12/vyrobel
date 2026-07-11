<?php

use App\Http\Middleware\SetTenantForWebRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Resolve the active organization from the authenticated user on every web request,
        // syncing TenantContext + spatie team id. CLI/queue/tests use programmatic context.
        $middleware->web(append: [
            SetTenantForWebRequest::class,
        ]);

        // Inbound provider webhooks are stateless and signature-authenticated, not session/form
        // posts — exempt them from CSRF (there is no token to send). The public booking-engine JSON
        // API (machine clients: `/quote`, `/book`) is likewise token-authenticated by contract and
        // stays exempt. The STYLED public checkout form (design pass · Booking Screen 3) is a
        // session, browser-rendered POST — it is deliberately NOT exempt, so it is session-CSRF-
        // protected (it carries @csrf) on top of the group's `throttle:booking` rate limit.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'book/*/quote',
            'book/*/book',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

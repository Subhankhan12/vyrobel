<?php

declare(strict_types=1);

/**
 * Deployment security switches (audit fix pass 2026-08-08).
 *
 * ⚠️ THESE LIVE IN A CONFIG FILE FOR A LOAD-BEARING REASON. `env()` only reads the .env file while it is
 * loaded — and `php artisan config:cache`, which the deploy sequence in docs/DEPLOYMENT.md runs, makes
 * Laravel SKIP loading .env entirely. An `env()` call anywhere outside config/ then returns NULL in
 * production, silently. Both switches below were originally written as `env()` calls in
 * AppServiceProvider/bootstrap and would have evaluated to false on any config-cached deploy — i.e. HTTPS
 * forcing and trusted proxies would have been dead exactly where they matter. PHPStan (larastan's
 * noEnvCallsOutsideOfConfig) caught it; verified afterwards against a real `config:cache` run.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Force the https:// scheme on generated URLs
    |--------------------------------------------------------------------------
    |
    | Every generated URL — form actions, redirects, signed password-reset and email-verification links,
    | and the asset URLs inside transactional emails — is built from the REQUEST scheme. Behind a
    | TLS-terminating proxy that scheme reads `http`, so links would be emitted as http:// on an https://
    | site: mixed content in the browser, and http links inside guest email.
    |
    | Defaults to on in production only, so local http development is untouched.
    |
    */

    'force_https' => (bool) env('FORCE_HTTPS', env('APP_ENV') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Trusted reverse proxies / load balancers
    |--------------------------------------------------------------------------
    |
    | EMPTY = trust nothing, which is the SAFE default: a spoofed X-Forwarded-For is ignored, so there is
    | no bypass. But behind a real load balancer, trusting nothing breaks three controls QUIETLY:
    |
    |   • EnforceIpAllowlist compares the PROXY's address, so the operator IP allowlist blocks everyone;
    |   • per-IP rate limiting collapses into a single bucket for the whole internet;
    |   • $request->isSecure() reads false, suppressing HSTS and defeating the secure session cookie.
    |
    | Use '*' when the app is only reachable through the load balancer, or a comma-separated list of
    | proxy addresses/CIDRs.
    |
    */

    'trusted_proxies' => (string) env('TRUSTED_PROXIES', ''),

];

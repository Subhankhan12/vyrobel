<?php

use App\Http\Middleware\EnsurePlatformAdminTwoFactor;
use App\Support\Retention\RetentionPolicy;
use Illuminate\Support\Facades\Route;

// ══ THE KNOWN LIMITS — PINNED TO THE CODE THAT MAKES THEM TRUE ════════════════════════════════════
//
// ⚠️⚠️ A LIMITS DOCUMENT THAT GOES STALE IS WORSE THAN NONE. `docs/KNOWN-LIMITS.md` states six things as
// present fact; each is tied here to the route, config or class that makes it so. If the product gains a
// capability the document still denies — or loses one it claims — this file goes red instead of the
// document quietly becoming a lie.
//
//   ✅ PROVABLE, AND PROVEN BELOW
//      · a named route exists, or does not;
//      · a config value is what the document says;
//      · the in-product telling is present in the view that must carry it.
//
//   ⛔ NOT PROVABLE HERE
//      · that Stripe forbids changing an account's country — that is Stripe's behaviour, not ours, and the
//        document says so rather than asserting it as our rule;
//      · that a customer READS any of it.

// ── L1 · one payment account per organisation, country fixed at creation ─────────────────────────
it('⚠️ L1 — takes no country when creating a connected account, and offers no way to change one', function () {
    $iface = file_get_contents(app_path('Payments/Stripe/StripeClientInterface.php'));

    // ⚠️ The precise fact: we never choose the country — there is no parameter for it.
    expect($iface)->toContain('function createConnectAccount(string $email, array $metadata = []');
    expect($iface)->not->toContain('country');

    // One account per org, and no route that clears or replaces it.
    expect(Route::has('app.billing.connect.start'))->toBeTrue();
    foreach (['app.billing.connect.reset', 'app.billing.connect.change-country', 'app.billing.connect.disconnect'] as $absent) {
        expect(Route::has($absent))->toBeFalse();
    }
});

it('⚠️ L1 — warns about the country BEFORE the account exists, not after', function () {
    $billing = file_get_contents(resource_path('views/livewire/operator/billing.blade.php'));

    expect($billing)->toContain('the country cannot be changed later');
    expect($billing)->toContain('cannot be changed');

    // ⚠️ THE PLACEMENT IS THE POINT: it renders only while no account exists — at the moment of the
    //    irreversible choice. A warning shown afterwards would be a receipt, not a warning.
    $at = strpos($billing, 'the country cannot be changed later');
    $guard = strrpos(substr($billing, 0, (int) $at), "@if (! \$connect['connected'])");
    expect($guard)->not->toBeFalse();
});

// ── L2 · no OTA channel can be connected ─────────────────────────────────────────────────────────
it('⚠️ L2 — binds the fake channel client, so no live sync exists to have stopped', function () {
    // ⚠️ "Never started" rather than "stopped": the real client has never been the bound default.
    // ⚠️ Cast deliberately — phpunit.xml supplies this as an env STRING ('1'), so a strict `toBeTrue()`
    //    would fail on the type rather than on the fact, which is what the document actually claims.
    expect((bool) config('services.channex.fake'))->toBeTrue();
    expect(Route::has('app.channels'))->toBeTrue();          // the surface exists…
    expect(Route::has('webhooks.channex'))->toBeTrue();      // …and so does the pipeline
});

// ── L3 · a guest can erase, but cannot export ────────────────────────────────────────────────────
it('⚠️ L3 — offers guest erasure and no guest export', function () {
    // The half that EXISTS — and the document must not deny it, as an earlier record wrongly did.
    expect(Route::has('guest.account.delete'))->toBeTrue();
    expect(Route::has('guest.account.delete.undo'))->toBeTrue();

    // The half that does not.
    $guestExport = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r) => str_starts_with((string) $r->uri(), 'guest/') && str_contains((string) $r->uri(), 'export'))
        ->count();
    expect($guestExport)->toBe(0);

    // ⚠️ What the OPERATOR can give a guest instead — named in the document, so it must exist.
    expect(Route::has('app.guests.bulk.export'))->toBeTrue();
});

// ── L4 · retention windows are fixed in code ─────────────────────────────────────────────────────
it('⚠️ L4 — fixes every retention window in code, with no per-tenant knob', function () {
    $windows = RetentionPolicy::WINDOWS;

    expect($windows)->toHaveKey('failed_jobs');
    expect($windows['failed_jobs']['days'])->toBe(7);
    expect($windows['sessions']['days'])->toBe(30);

    // ⚠️ The limit itself: the windows are constants, so nothing can vary them per organisation.
    $ref = new ReflectionClass(RetentionPolicy::class);
    expect($ref->getConstant('WINDOWS'))->not->toBeFalse();

    // And the two stores deliberately never pruned.
    expect(RetentionPolicy::NEVER_PRUNED)->toContain('audit_events');
    expect(RetentionPolicy::NEVER_PRUNED)->toContain('domain_events');
});

// ── L5 · the gap is the MANDATE, not the feature ─────────────────────────────────────────────────
it('⚠️ L5 — implements two-factor, enforces it for platform admins, and cannot require it of staff', function () {
    // The feature EXISTS — stating "no 2FA" would be wrong.
    expect(Route::has('two-factor.challenge'))->toBeTrue();
    expect(class_exists(EnsurePlatformAdminTwoFactor::class))->toBeTrue();

    // ⚠️ And no organisation-level requirement exists anywhere.
    foreach (['app.settings.two-factor', 'app.settings.require-2fa'] as $absent) {
        expect(Route::has($absent))->toBeFalse();
    }
});

// ── L6 · the toggle census — the number the document states ──────────────────────────────────────
//
// ⚠️ This pins the COUNT, because the count is the part that has moved every time it was measured. It is
// deliberately a direct search per permission, not one clever pattern — a pattern narrower than the
// codebase's idioms produced a wrong figure five separate times in this sequence.
it('⚠️ L6 — has exactly the number of unenforced permissions the document states', function () {
    $catalogue = config('authorization.roles', []);
    $perms = [];
    foreach ($catalogue as $granted) {
        if (! is_array($granted)) {
            continue;
        }
        foreach ($granted as $p) {
            if ($p !== '*') {
                $perms[$p] = true;
            }
        }
    }

    $haystack = '';
    foreach (['app', 'routes', 'resources'] as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir), FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php'], true)) {
                $haystack .= (string) file_get_contents($file->getPathname());
            }
        }
    }

    $unenforced = array_values(array_filter(
        array_keys($perms),
        fn (string $p): bool => ! str_contains($haystack, $p)
    ));
    sort($unenforced);

    // ⚠️ The document names all eleven. If one gains a gate — or a new phantom appears — this reds.
    expect($unenforced)->toBe([
        'addons.manage', 'amenities.manage', 'availability.manage', 'availability.view',
        'corporate_accounts.manage', 'fees.manage', 'guest_documents.manage', 'invoices.issue',
        'invoices.view', 'payments.view', 'restrictions.manage', 'taxes.manage',
    ]);

    // ⚠️ AND THE POINT THAT MATTERS: the writes are not open. The coarser gates the document names must
    //    still be the ones actually protecting them.
    $routes = file_get_contents(base_path('routes/web.php'));
    expect($routes)->toContain("'/settings/tax-fees'");
    expect($routes)->toContain('can:properties.manage');
    expect($routes)->toContain('can:settings.manage');
});

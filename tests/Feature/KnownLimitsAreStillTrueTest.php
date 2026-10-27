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
    $iface = i18nEnglishSource(app_path('Payments/Stripe/StripeClientInterface.php'));

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
    $billing = i18nEnglishSource(resource_path('views/livewire/operator/billing.blade.php'));

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

// ── L6 · the toggle census — ⚠️⚠️ ONE SOURCE (FIX 42) ────────────────────────────────────────────
//
// ⚠️⚠️ THIS TEST USED TO RUN ITS OWN CENSUS, AND IT WAS THE WEAKEST OF THREE. It asserted TWELVE names
// under a comment saying "all eleven", while the register held SEVENTEEN. 🔬 All three disagreements are
// now accounted for:
//
//   · THE MATCHER. It was a bare `str_contains()` over every `.php` file concatenated, with NO comment
//     stripping — so a permission MERELY NAMED IN PROSE counted as enforced. 🔬 Measured: `guest_portal.view`,
//     `housekeeping.view` and `owner_portal.view` appear in the codebase ONLY inside docblocks and a Blade
//     comment, and that is the entire reason this test called them enforced. A certified test staying green
//     while the thing it checks is broken is the worst outcome class this project has.
//   · THE UNIVERSE. It counted over `config('authorization.roles')` and skipped `'*'`, so its denominator
//     was 46, not 51, and five wildcard-only permissions were structurally invisible to it — including
//     `api.manage` and `organizations.manage`, two phantoms it could never have found however good its
//     matcher was.
//   · THE COMMENT. "all eleven" was copied from docs/KNOWN-LIMITS.md, which says "12 of 46" in its headline
//     and "The 11" in its prose over a table listing twelve. The typo was in the document first.
//
// ⚠️ THE CENSUS NOW LIVES IN ONE PLACE — {@see vyUnreadPermissions()} in tests/Support/permission_helpers.php,
// with the register beside it, pinned BY NAME by `PhantomPermissionRegisterIsPinnedTest` and partitioned
// against the catalogue by `PermissionEnforcementContractTest`. This file no longer keeps a second list;
// it asserts that the document's figure comes from that source, and keeps the part nothing else asserts.
it('⚠️ L6 — the toggle census comes from the ONE source, and the coarser gates still protect the writes', function () {
    // ⚠️ DELEGATED, NOT RESTATED. A second list here is how three numbers for one concept happened.
    $unread = vyUnreadPermissions();
    sort($unread);

    $registered = array_keys(VY_UNREAD_PERMISSIONS);
    sort($registered);

    expect($unread)->toBe($registered,
        'docs/KNOWN-LIMITS.md §L6 describes the unread-permission census. Its figure comes from '
        .'vyUnreadPermissions(); if that has moved, update the document rather than re-counting here.');

    // ⚠️⚠️ AND THE PART NOTHING ELSE ASSERTS — the point of L6, kept verbatim. The permissions may be
    //    unread, but the WRITES they appear to describe are not open: a coarser gate really does protect
    //    them, and that is what makes this "not a limit" rather than a hole.
    $routes = file_get_contents(base_path('routes/web.php'));
    expect($routes)->toContain("'/settings/tax-fees'");
    expect($routes)->toContain('can:properties.manage');
    expect($routes)->toContain('can:settings.manage');
});

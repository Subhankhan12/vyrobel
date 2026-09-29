<?php

declare(strict_types=1);

use App\Livewire\MaintenanceList;
use App\Models\EmailTemplate;
use App\Models\MaintenanceSetting;
use App\Services\GuestAccountService;
use App\Support\Maintenance\MaintenanceSla;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 2: controls and signals that were WIRED TO NOTHING
 * (#1, #16, #18, #23). Each showed the user a state or an affordance the code did not act on.
 */

/**
 * #1 — the verify-email template's on/off switch did nothing.
 *
 * Six of the seven templates gate their send on EmailTemplateResolver::enabled(); verify-email was the ONLY
 * one that ignored it, so the editor could show "○ Off" while the mail kept sending.
 */
it('honours the operator’s off switch for the verify-email template', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $guest = $s['guest'];
    $guest->forceFill(['email' => 'claimer@example.test', 'password' => null])->save();

    Mail::fake();

    // ON (no row = enabled by default) → it sends.
    app(GuestAccountService::class)->sendClaimLink($guest->fresh());
    Mail::assertSentCount(1);

    // OFF → it must not.
    EmailTemplate::query()->updateOrCreate(
        ['organization_id' => $s['org']->id, 'template_key' => 'verify-email'],
        ['enabled' => false],
    );

    app(GuestAccountService::class)->sendClaimLink($guest->fresh());
    Mail::assertSentCount(1);   // still 1 — the switch is real now

    expect($reservation->exists)->toBeTrue();   // the booking-tied precondition held throughout
});

/**
 * #16 — every ticket on a fresh org read "No SLA target set".
 *
 * The board resolved settings as `first() ?? new MaintenanceSetting`, and an UNSAVED Eloquent model carries
 * no SCHEMA defaults — so the real 4/8/48/168h targets never applied until someone opened Settings. The SLA
 * signal was dead from install.
 */
it('applies the real SLA defaults on a fresh org instead of reporting no target', function () {
    $s = seedScenario(1);

    // Nothing has created a settings row — exactly the state a newly provisioned org is in.
    expect(MaintenanceSetting::query()->count())->toBe(0);

    Livewire::actingAs(operatorUser($s))->test(MaintenanceList::class)->assertOk();

    $setting = MaintenanceSetting::query()->firstOrFail();

    expect((int) $setting->sla_urgent_hours)->toBe(4)
        ->and(MaintenanceSla::targetHours($setting, 'urgent'))->toBe(4);
});

/**
 * #18 — the Guests CRM showed Add / Message / Add tag to roles that cannot use them.
 *
 * The SERVER gate always held (this was never a security hole), but a view-only role was invited into a 403.
 * The Erase block already gated its control correctly; these three now match it.
 */
it('hides the guest write controls from a role that cannot use them', function () {
    $s = seedScenario(1);

    $blade = file_get_contents(resource_path('views/livewire/operator/guests-list.blade.php'));

    // All three write affordances sit behind the same permission the routes require.
    expect(substr_count($blade, "@can('guests.manage')"))->toBeGreaterThanOrEqual(2);

    // And the manager still sees them.
    // ⚠️ THROUGH THE REAL ROUTE, and the BUTTON itself (2026-09-29). This used `Livewire::test()`, which skips the web
    //    middleware that sets the permission team — so `@can` answered a silent FALSE (invariant #30's note) and the
    //    button never rendered. The assertion passed anyway on the Add guest DIALOG's heading, which was not gated.
    //    Gating the dialog too (the Guests rebuild) exposed it; a probe confirmed team=NULL → can()=false there.
    $this->actingAs(operatorUser($s))->get('/app/guests')->assertOk()
        ->assertSee('@click="addOpen = true"', false);
});

/**
 * #23 — the guest account offered a language setting that changed nothing.
 *
 * SetLocale read `users.locale` on the WEB guard only. Guests are a separate realm with their own
 * `guests.language` column, which nothing ever consulted — the control saved a value that did nothing.
 */
it('reads the guest realm’s own language preference, not just the web guard’s', function () {
    $middleware = file_get_contents(app_path('Http/Middleware/SetLocale.php'));

    expect($middleware)->toContain("user('guest')?->language")
        // still self-only: the acting subject's own preference, never a request-supplied value
        ->and($middleware)->not->toContain('$request->input')
        ->and($middleware)->not->toContain('$request->query');
});

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAdmin;
use App\Support\Platform\AdminReason;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformSwitches;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ADMIN 01 · PHASE 1 — the platform KILL SWITCHES (see {@see PlatformSwitches}): view them all, flip one with a
 * written reason, audited.
 *
 * ⚠️⚠️ TURNING OFF A SIGN-IN SWITCH NEEDS A SECOND, EXPLICIT CONFIRMATION — enforced HERE, server-side, not just
 * by a checkbox in the form. The page shows how many accounts it would lock out (no password, and that provider
 * is their only way in) before the confirmation is asked for.
 */
class SwitchController extends Controller
{
    public function __construct(private readonly PlatformAuditLogger $audit) {}

    public function index(): View
    {
        $catalogue = PlatformSwitches::catalogue();
        $lockouts = [];
        foreach ($catalogue as $key => $meta) {
            if ($meta['kind'] === 'signin' && $meta['provider'] !== null) {
                $lockouts[$key] = PlatformSwitches::lockoutCount($meta['provider']);
            }
        }

        $updaterIds = collect(PlatformSwitches::states())->pluck('updated_by')->filter()->unique()->values()->all();
        $updaters = PlatformAdmin::query()->whereIn('id', $updaterIds)->pluck('name', 'id');

        $this->audit->record('platform.switches.view');

        return view('admin.switches.index', [
            'catalogue' => $catalogue,
            'states' => PlatformSwitches::states(),
            'lockouts' => $lockouts,
            'updaters' => $updaters,
        ]);
    }

    public function update(Request $request, string $switch): RedirectResponse
    {
        $catalogue = PlatformSwitches::catalogue();
        abort_unless(array_key_exists($switch, $catalogue), 404);

        $validated = $request->validate(['on' => ['required', 'boolean']]);
        $reason = AdminReason::from($request);
        $on = (bool) $validated['on'];
        $meta = $catalogue[$switch];

        $lockout = null;
        if (! $on && $meta['kind'] === 'signin' && $meta['provider'] !== null) {
            $lockout = PlatformSwitches::lockoutCount($meta['provider']);

            // ⚠️⚠️ THE SECOND CONFIRMATION. Always required to turn a sign-in switch OFF, even at zero — the
            //    count can change between loading the page and pressing the button.
            if (! $request->boolean('confirm_lockout')) {
                throw ValidationException::withMessages([
                    'confirm_lockout' => $lockout === 0
                        ? 'Tick the box to confirm — no account relies on this provider alone right now, but anyone who does by the time it is off will be locked out.'
                        : "Confirm that you understand this locks out {$lockout} ".Str::plural('account', $lockout).' whose only way in is this provider.',
                ]);
            }
        }

        /** @var PlatformAdmin $admin */
        $admin = Auth::guard('admin')->user();
        $before = PlatformSwitches::enabled($switch);

        PlatformSwitches::set($switch, $on, $admin);

        $this->audit->record(
            'platform.switch.'.($on ? 'on' : 'off'),
            array_filter(['switch' => $switch, 'was_on' => $before, 'lockout_count' => $lockout], fn ($v) => $v !== null),
            'platform_switch',
            $switch,
            reason: $reason,
        );

        return redirect()->route('admin.switches.index')
            ->with('admin_notice', $meta['label'].' is now '.($on ? 'ON' : 'OFF').'. It takes effect on the next request.');
    }
}

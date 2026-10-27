<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCurrentPropertyForWebRequest;
use App\Models\Property;
use App\Support\Property\PropertyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Phase 6a · multi-property — the topbar property switcher write (POST → session → PRG), the locked
 * operator-mutation pattern applied to a working-context change rather than a domain write.
 *
 * The selection is stored in the session as `current_property_id` (a property id or
 * {@see PropertyContext::ALL}). It is VALIDATED against the user's accessible set here — a foreign or
 * stale id is rejected (422) rather than stored, so the switcher can only ever move you between
 * properties you may work in. The {@see SetCurrentPropertyForWebRequest}
 * middleware re-validates on the next request too (defence in depth), so even a smuggled session
 * value never takes effect.
 */
class PropertySwitchController extends Controller
{
    /** Where "Open" may take you after switching — the ONLY destinations accepted (never a URL). */
    private const DESTINATIONS = ['dashboard' => 'app.dashboard'];

    public function update(Request $request): RedirectResponse
    {
        $context = app(PropertyContext::class);

        $accessible = $context->accessibleIds();
        $allowed = array_merge([PropertyContext::ALL], $accessible);

        $data = $request->validate([
            'property_id' => ['required', 'string', 'in:'.implode(',', $allowed)],
            // ⚠️ 2026-09-29 — where to go AFTER switching. The Portfolio's "Open" reused this switcher and so came BACK to
            //    the Portfolio: the page reloaded in place and only a small tag moved — "Open does nothing". A card that
            //    says Open takes you INTO the property. ⚠️ A FIXED LIST, never a URL: an arbitrary destination here would
            //    be an open redirect. The top-bar switcher sends none and keeps you where you are.
            'open' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::DESTINATIONS))],
        ]);

        session(['current_property_id' => $data['property_id']]);

        $destination = self::DESTINATIONS[(string) ($data['open'] ?? '')] ?? null;   // only a listed name, never a URL
        if ($destination !== null && $data['property_id'] !== PropertyContext::ALL) {
            $name = (string) Property::query()->whereKey($data['property_id'])->value('name');

            return redirect()->route($destination)->with('property_notice', __('php/http/controllers/operator/property_switch_controller.now_working_in', ['name' => $name]));
        }

        return redirect()->back();
    }
}

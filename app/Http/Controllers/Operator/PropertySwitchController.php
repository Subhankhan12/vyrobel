<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCurrentPropertyForWebRequest;
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
    public function update(Request $request): RedirectResponse
    {
        $context = app(PropertyContext::class);

        $accessible = $context->accessibleIds();
        $allowed = array_merge([PropertyContext::ALL], $accessible);

        $data = $request->validate([
            'property_id' => ['required', 'string', 'in:'.implode(',', $allowed)],
        ]);

        session(['current_property_id' => $data['property_id']]);

        return redirect()->back();
    }
}

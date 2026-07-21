<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCurrentPropertyForWebRequest;
use App\Livewire\Operator\OperatorSettings;
use App\Models\Organization;
use App\Models\Property;
use App\Services\SettingsService;
use App\Support\Property\PropertyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Operator SETTINGS writes (Wave 4 · Sub-gate 3) — three guarded writers over the CERTIFIED
 * {@see SettingsService}, via the locked operator-mutation pattern (POST → controller → service → PRG),
 * exactly as {@see ReservationLifecycleController} / {@see RateActionController}. Only the BACKED slices are
 * here; every unbacked field on the design is stubbed "Soon" in the view, never a toast-only fake save.
 *
 * PERMISSION (#30) — deliberately split by section, so the write matches the catalog:
 *   • property basics + stay policies → `can:properties.manage` (Property Owner / Group Admin).
 *   • organization settings          → `can:settings.manage`   (Property Owner only).
 * The route middleware enforces each; {@see OperatorSettings} gates the matching form
 * off the SAME permission so a Group Admin never sees an org form they cannot submit. Front Desk holds none
 * of these and never reaches the screen.
 *
 * TENANT: the property is resolved through the BelongsToOrganization global scope (`firstOrFail`), and the
 * organization is resolved FROM that property (never a request-supplied id) — a cross-tenant write is
 * impossible by construction.
 */
class SettingsActionController extends Controller
{
    public function updateProperty(Request $request, SettingsService $settings): RedirectResponse
    {
        $property = $this->currentProperty();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'star_rating' => ['nullable', 'integer', 'between:1,5'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $settings->updateProperty($property, $data);

        return $this->back('Property details saved.');
    }

    public function updatePolicies(Request $request, SettingsService $settings): RedirectResponse
    {
        $property = $this->currentProperty();

        $data = $request->validate([
            'pet' => ['required', 'in:allowed,on_request,not_allowed'],
            'smoking' => ['required', 'in:allowed,on_request,not_allowed'],
            'min_stay' => ['required', 'integer', 'between:1,365'],
            'max_stay' => ['nullable', 'integer', 'between:1,365'],
        ]);

        $settings->updatePolicies($property, $data);

        return $this->back('Stay policies saved.');
    }

    public function updateOrganization(Request $request, SettingsService $settings): RedirectResponse
    {
        $property = $this->currentProperty();
        $organization = Organization::query()->findOrFail($property->organization_id);

        $data = $request->validate([
            'default_currency' => ['required', 'string', 'size:3'],
            'default_locale' => ['required', 'string', 'max:10'],
            'billing_email' => ['nullable', 'email', 'max:255'],
        ]);

        $settings->updateOrganization($organization, $data);

        return $this->back('Organization settings saved.');
    }

    /**
     * The property being edited = the operator's CURRENT property context (Phase 6a · multi-property),
     * tenant-scoped. Runs in the `/app` group where {@see SetCurrentPropertyForWebRequest}
     * has activated the {@see PropertyContext}, so the write lands in the SELECTED property — never an
     * arbitrary `->first()`. A missing context (org with no property) 404s, exactly as `firstOrFail` did.
     */
    private function currentProperty(): Property
    {
        return Property::query()->findOrFail(app(PropertyContext::class)->id());
    }

    private function back(string $notice): RedirectResponse
    {
        return redirect()->to(route('app.settings'))->with('settings_notice', $notice);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCurrentPropertyForWebRequest;
use App\Livewire\Operator\OperatorSettings;
use App\Models\Fee;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Tax;
use App\Services\PropertyBookingPauseService;
use App\Services\PropertyRemovalService;
use App\Services\SettingsService;
use App\Support\Operator\OperatorWorkspace;
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
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $property = $this->currentProperty();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'star_rating' => ['nullable', 'integer', 'between:1,5'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:active,inactive'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
        ]);

        // Merge the address parts into the existing JSON (preserve any other keys, e.g. a stored region).
        $existing = is_array($property->address) ? $property->address : [];
        $data['address'] = array_merge($existing, [
            'line1' => $data['address_line'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
        ]);

        $settings->updateProperty($property, $data);

        return $this->back('Property details saved.');
    }

    public function updatePolicies(Request $request, SettingsService $settings): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $property = $this->currentProperty();

        $data = $request->validate([
            'pet' => ['required', 'in:allowed,on_request,not_allowed'],
            'smoking' => ['required', 'in:allowed,on_request,not_allowed'],
            'min_stay' => ['required', 'integer', 'between:1,365'],
            'max_stay' => ['nullable', 'integer', 'between:1,365'],
            'cancellation_tier' => ['sometimes', 'in:flexible,moderate,strict'],
            'deposit_required' => ['sometimes', 'boolean'],
            'self_check_in' => ['sometimes', 'boolean'],
        ]);

        // Assemble the JSON policy columns; self-check-in is a bool. ⚠️ #33 — the door CODE is guest-only and is
        // NEVER stored/shown here; this toggle only records whether smart-lock self-check-in is offered.
        if ($request->filled('cancellation_tier')) {
            $data['cancellation'] = ['tier' => $data['cancellation_tier']];
        }
        $data['deposit'] = ['required' => $request->boolean('deposit_required')];
        $data['self_check_in'] = $request->boolean('self_check_in');

        $settings->updatePolicies($property, $data);

        return $this->back('Stay policies saved.');
    }

    /**
     * Update the property's GUEST-FACING BRAND (task #98) — accent / logo / email from-name / reply-to /
     * powered-by. The from ADDRESS is fixed (verified sender, Postmark-safe) and never accepted here. The logo
     * is stored on the PUBLIC disk so transactional emails can reach it by absolute URL.
     */
    public function updateBrand(Request $request, SettingsService $settings): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $property = $this->currentProperty();

        $data = $request->validate([
            'brand_accent' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'brand_email_from_name' => ['nullable', 'string', 'max:120'],
            'brand_reply_to' => ['nullable', 'email', 'max:190'],
            'brand_powered_by' => ['sometimes', 'boolean'],
            // ⚠️ RASTER ONLY — an SVG can carry <script> and, served from the app origin (public disk), would be
            // stored XSS. The wireframe says "SVG or PNG"; we deliberately drop SVG for safety (raster logos only).
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $attributes = [
            'brand_accent' => $data['brand_accent'] ?? null,
            'brand_email_from_name' => $data['brand_email_from_name'] ?? null,
            'brand_reply_to' => $data['brand_reply_to'] ?? null,
            'brand_powered_by' => $request->boolean('brand_powered_by'),
        ];

        if ($request->hasFile('logo')) {
            $attributes['brand_logo_path'] = $request->file('logo')->store('brand-logos', 'public');
        }

        $settings->updateBrand($property, $attributes);

        return $this->back('Guest-facing brand saved.');
    }

    /**
     * Add a custom FEE to the property (task #98 GATE 2 · Add-a-fee) — per-stay / per-night / per-guest, and
     * optional-at-booking or always-applied. A non-optional fee then posts to every folio (via the shared
     * StayEstimator rule the reservation folio uses). `properties.manage`-gated on the route.
     */
    public function addFee(Request $request, SettingsService $settings): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $property = $this->currentProperty();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'basis' => ['required', 'in:per_stay,per_night,per_guest'],
            'amount' => ['required', 'numeric', 'min:0', 'max:100000'],
            'optional' => ['sometimes', 'boolean'],
        ]);

        $mode = match ($data['basis']) {
            'per_night' => 'per_night',
            'per_guest' => 'per_person',
            default => 'flat',
        };

        $settings->createFee($property, $data['name'], $mode, (int) round(((float) $data['amount']) * 100), $request->boolean('optional'));

        return $this->back('Fee added.');
    }

    /**
     * Schedule the property's REMOVAL (task #98 GATE 2 · ⚠️ archive-not-destroy). Requires typing the exact
     * property name to confirm. The property stays live until the last checkout, then is soft-deleted (records
     * retained). Cancellable via {@see cancelRemoval}. `properties.manage`-gated on the route + audited.
     */
    public function scheduleRemoval(Request $request, PropertyRemovalService $removals): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $property = $this->currentProperty();

        $request->validate([
            'confirm_name' => ['required', 'string'],
        ]);

        if (trim((string) $request->input('confirm_name')) !== trim((string) $property->name)) {
            return redirect()->to(route('app.settings'))->with('settings_error', 'The name you typed didn’t match — removal was not scheduled.');
        }

        $removals->schedule($property, (string) auth()->id());

        return $this->back('Removal scheduled — you can cancel any time before it runs.');
    }

    /** Cancel a scheduled property removal (task #98 GATE 2). */
    public function cancelRemoval(PropertyRemovalService $removals): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $removals->cancel($this->currentProperty());

        return $this->back('Scheduled removal cancelled — this property stays.');
    }

    /** Pause new bookings for the property (task #98) — real: fans out to units via the certified availability spine. */
    public function pauseBookings(PropertyBookingPauseService $pause): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $pause->pause($this->currentProperty());

        return $this->back('New bookings paused — existing stays keep running.');
    }

    /** Resume new bookings for the property. */
    public function resumeBookings(PropertyBookingPauseService $pause): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $pause->resume($this->currentProperty());

        return $this->back('New bookings resumed.');
    }

    public function updateOrganization(Request $request, SettingsService $settings): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

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
     * #104b GATE 2 — the Settings hub's Invoice details + numbering CONFIG (legal entity / VAT number / billing
     * city / series prefix / next number / auto-issue). Org-scoped, `settings.manage`, audited, read-only-guarded.
     * ⚠️ CONFIG only — the certified issuance + SAF-T export are honestly deferred (never fabricated).
     */
    public function updateInvoiceDetails(Request $request, SettingsService $settings): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $property = $this->currentProperty();
        $organization = Organization::query()->findOrFail($property->organization_id);

        $data = $request->validate([
            'billing_company' => ['nullable', 'string', 'max:160'],
            'billing_vat_id' => ['nullable', 'string', 'max:40'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'invoice_series_prefix' => ['nullable', 'string', 'max:16'],
            'invoice_next_number' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'invoice_auto_issue' => ['sometimes', 'in:0,1'],
        ]);

        $settings->updateInvoiceDetails($organization, $data);

        return $this->back('Invoice details saved.');
    }

    /**
     * Batch C · GATE 4 — edit the property's TAXES & FEES (the "Editing · Soon" gap). Each posted id → a
     * bounded {@see SettingsService::updateTax}/{@see SettingsService::updateFee} write (rate clamped 0–100,
     * amount ≥ 0 integer minor #17). Tenant-safe: Tax/Fee are BelongsToOrganization, so a cross-org id
     * resolves to null and is skipped — never edited. `properties.manage`-gated on the route.
     */
    public function updateTaxesAndFees(Request $request, SettingsService $settings): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        // The property being edited — the lookup is scoped to THIS property (not just the org), so a
        // cross-property id (a sibling property in the same org, which a property-restricted user may not
        // access — Phase 6a's property_user pivot) resolves to null and is skipped: no cross-property IDOR.
        $property = $this->currentProperty();

        $validated = $request->validate([
            'taxes' => ['array'],
            'taxes.*' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'tax_inclusive' => ['array'],
            'tax_inclusive.*' => ['nullable', 'in:0,1'],
            'fees' => ['array'],
            'fees.*' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        // #104 GATE 2c — the per-tax Incl./Added toggle (a hidden 0 + a checkbox 1 → present for every rendered
        // percentage tax). Read alongside the rate so both persist in one save.
        $inclusiveById = $validated['tax_inclusive'] ?? [];

        foreach (($validated['taxes'] ?? []) as $id => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $tax = Tax::query()->where('property_id', $property->id)->find((string) $id);   // property-scoped
            if ($tax instanceof Tax) {
                $inclusive = array_key_exists($id, $inclusiveById) ? ((string) $inclusiveById[$id] === '1') : null;
                $settings->updateTax($tax, (float) $value, $inclusive);
            }
        }

        foreach (($validated['fees'] ?? []) as $id => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $fee = Fee::query()->where('property_id', $property->id)->find((string) $id);   // property-scoped
            if ($fee instanceof Fee) {
                $settings->updateFee($fee, (float) $value);
            }
        }

        return $this->back('Taxes & fees saved.');
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

    /**
     * Read-only guard (task #98 — was missing here, unlike peer operator controllers): a paused/lapsed
     * workspace refuses every settings write. Returns a redirect to short-circuit, or null to proceed.
     */
    private function guardReadOnly(): ?RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return redirect()->to(route('app.settings'))->with('settings_error', 'Your workspace is read-only right now — changes are paused.');
        }

        return null;
    }

    private function back(string $notice): RedirectResponse
    {
        return redirect()->to(route('app.settings'))->with('settings_notice', $notice);
    }
}

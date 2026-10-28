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
use App\Models\User;
use App\Services\PropertyBookingPauseService;
use App\Services\PropertyRemovalRefused;
use App\Services\PropertyRemovalService;
use App\Services\PropertyScopeService;
use App\Services\SettingsService;
use App\Support\Billing\StripeTaxIdSync;
use App\Support\Guest\PropertySupport;
use App\Support\I18n\LocalNumber;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

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

        return $this->back(__('php/http/controllers/operator/settings_action_controller.property_details_saved'));
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
            'cancellation_type' => ['sometimes', 'in:flexible,moderate,strict'],
            'free_until_days' => ['sometimes', 'nullable', 'integer', 'between:0,365'],
            'deposit_required' => ['sometimes', 'boolean'],
            'self_check_in' => ['sometimes', 'boolean'],
        ]);

        // Assemble the JSON policy columns; self-check-in is a bool. ⚠️ #33 — the door CODE is guest-only and is
        // NEVER stored/shown here; this toggle only records whether smart-lock self-check-in is offered.
        //
        // ⚠️ These arrays are MERGED onto the stored column by SettingsService (see POLICY_JSON_FIELDS) — send
        // only the keys this form owns. `free_until_days` is the load-bearing one: CancellationPolicy and the
        // storefront read it, so it is submitted explicitly rather than left to be clobbered by a sibling key.
        $cancellation = [];
        if ($request->filled('cancellation_type')) {
            $cancellation['type'] = $data['cancellation_type'];
        }
        if ($request->has('free_until_days')) {
            $days = $data['free_until_days'] ?? null;
            $cancellation['free_until_days'] = $days === null ? null : (int) $days;
        }
        if ($cancellation !== []) {
            $data['cancellation'] = $cancellation;
        }

        $data['deposit'] = ['required' => $request->boolean('deposit_required')];
        $data['self_check_in'] = $request->boolean('self_check_in');

        $settings->updatePolicies($property, $data);

        return $this->back(__('php/http/controllers/operator/settings_action_controller.stay_policies_saved'));
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

        $old = $property->brand_logo_path;
        $new = null;
        if ($request->hasFile('logo')) {
            $new = $request->file('logo')->store('brand-logos', 'public');
            $attributes['brand_logo_path'] = $new;
        }

        try {
            $settings->updateBrand($property, $attributes);
        } catch (Throwable $e) {
            // saved for a change that did not happen — nobody's file. ⚠️ Unless the property row already names it: the
            // update commits before its audit row is written, so a failure THERE leaves the new logo in use
            if (is_string($new) && ! DB::table('properties')->where('brand_logo_path', $new)->exists()) {
                Storage::disk('public')->delete($new);
            }
            throw $e;
        }
        // ⚠️ ADMIN 06 — the REPLACED logo goes once the new one is saved: before this, every replacement left the old
        // file on the public disk forever, reachable by its URL, with no row naming it.
        if (is_string($new) && is_string($old) && $old !== '' && $old !== $new) {
            $this->removeReplacedLogo($old);
        }

        return $this->back(__('php/http/controllers/operator/settings_action_controller.brand_saved'));
    }

    /**
     * Remove a logo no property uses any more — never one another property still shows (a demo seed can share one
     * file between tenants, so the check reads EVERY organisation, not the tenant scope). A disk that will not remove
     * it is logged with the path, the only record of the file once no row names it; the save itself stands.
     */
    private function removeReplacedLogo(string $path): void
    {
        try {
            if (DB::table('properties')->where('brand_logo_path', $path)->exists()) {
                return;
            }
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                return;   // already gone
            }
            // delete it, then confirm it is gone: a disk that refuses reports false, or leaves the file behind
            if ($disk->delete($path) && $disk->missing($path)) {
                return;
            }
            Log::warning('ADMIN 06 — a replaced brand logo could not be removed; remove it by hand.', ['disk' => 'public', 'path' => $path]);
        } catch (Throwable $e) {
            report($e);
            Log::warning('ADMIN 06 — a replaced brand logo could not be removed; remove it by hand.', ['disk' => 'public', 'path' => $path, 'why' => $e::class]);
        }
    }

    /**
     * Update the property's GUEST SUPPORT CONTACT (DO-G13-1) — the phone guests may ring, a SOFT reply norm and
     * the real opening hours. All three are optional and an emptied box is stored as NULL, which withdraws the
     * affordance from the guest surface entirely (never a blank row, never "not provided").
     *
     * ⚠️ THE NORM IS A BUCKET, NOT A SENTENCE. `support_reply_norm` is validated against
     * {@see PropertySupport::REPLY_NORMS} — the operator chooses from a fixed catalogue whose wording always
     * begins "usually", so a guarantee ("within 1 hour, promised") cannot be submitted through this form even by
     * hand-posting. That is the §N.8d / `DO-EMAIL-9` class closed structurally rather than by asking politely.
     *
     * ⚠️ HOURS ARE THE OPERATOR'S OWN STATEMENT. Free text, because a real front desk's hours are not an enum —
     * but nothing here defaults to, suggests or hardcodes "24/7": if a property is round-the-clock it is because
     * someone typed that, and if the box is empty the guest page shows no hours line at all.
     *
     * TENANT: the property is the operator's CURRENT context resolved through the tenant scope, exactly as every
     * other writer on this controller — a request-supplied id is never accepted, so no cross-org write exists.
     */
    public function updateSupport(Request $request, SettingsService $settings): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $property = $this->currentProperty();

        $data = $request->validate([
            // Digits with the usual separators — permissive enough for any international format, tight enough
            // that a sentence ("call the desk") cannot be stored where a dial link will be built.
            'support_phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9][0-9 ()\-.]{5,}$/'],
            'support_reply_norm' => ['nullable', 'string', Rule::in(array_keys(PropertySupport::REPLY_NORMS))],
            'support_hours' => ['nullable', 'string', 'max:120'],
        ]);

        // ⚠️ Explicit nulls, not "skip the blanks" — clearing a box must WITHDRAW the channel (see updateSupport).
        $settings->updateSupport($property, [
            'support_phone' => $this->blankToNull($data['support_phone'] ?? null),
            'support_reply_norm' => $this->blankToNull($data['support_reply_norm'] ?? null),
            'support_hours' => $this->blankToNull($data['support_hours'] ?? null),
        ]);

        return $this->back(__('php/http/controllers/operator/settings_action_controller.support_saved'));
    }

    /** '' and '   ' are as unset as null — a whitespace-only value must not draw an empty affordance. */
    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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

        LocalNumber::mergeInto($request, ['amount']);   // D-MONEY-COMMA: "150,50" read in the page's language before `numeric`
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

        return $this->back(__('php/http/controllers/operator/settings_action_controller.fee_added'));
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
            return redirect()->to(route('app.settings'))->with('settings_error', __('php/http/controllers/operator/settings_action_controller.removal_name_mismatch'));
        }

        try {
            $removals->schedule($property, (string) auth()->id());
        } catch (PropertyRemovalRefused $e) {
            return redirect()->to(route('app.settings'))->with('settings_error', __('php/http/controllers/operator/settings_action_controller.removal_not_scheduled', ['reason' => $e->getMessage()]));
        }

        return $this->back(__('php/http/controllers/operator/settings_action_controller.removal_scheduled'));
    }

    /** Cancel a scheduled property removal (task #98 GATE 2). */
    public function cancelRemoval(PropertyRemovalService $removals): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $removals->cancel($this->currentProperty());

        return $this->back(__('php/http/controllers/operator/settings_action_controller.removal_cancelled'));
    }

    /** Pause new bookings for the property (task #98) — real: fans out to units via the certified availability spine. */
    public function pauseBookings(PropertyBookingPauseService $pause): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $pause->pause($this->currentProperty());

        return $this->back(__('php/http/controllers/operator/settings_action_controller.bookings_paused'));
    }

    /** Resume new bookings for the property. */
    public function resumeBookings(PropertyBookingPauseService $pause): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $pause->resume($this->currentProperty());

        return $this->back(__('php/http/controllers/operator/settings_action_controller.bookings_resumed'));
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

        return $this->back(__('php/http/controllers/operator/settings_action_controller.organization_saved'));
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

        // DO-BILL-1 — the VAT number is editable from HERE too, not only from Billing. Both writers must reach
        // Stripe, or which screen the operator happened to use would decide whether they get reverse charge.
        StripeTaxIdSync::push($organization->fresh() ?? $organization);

        return $this->back(__('php/http/controllers/operator/settings_action_controller.invoice_saved'));
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

        LocalNumber::mergeInto($request, ['taxes.*', 'fees.*']);   // D-MONEY-COMMA: "150,50" read in the page's language before `numeric`
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

        return $this->back(__('php/http/controllers/operator/settings_action_controller.taxes_fees_saved'));
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
     * P-7 · CLUSTER J — set a staff member's PER-PROPERTY SCOPE (the pivot that had no writer).
     *
     * ⚠️ THE POINT OF THE WHOLE CLUSTER: until this existed, `property_user` could only gain a row for a
     * user who already had one, so no operator was ever scoped and EVERYONE fell through to the ratified
     * "empty pivot ⇒ full org" rule (#38). The fallback is correct; its universality was the defect.
     *
     * ⚠️ GATED `users.manage` ON THE ROUTE (#30 — authorization is a route fact about an actor). Scoping is
     * access administration, so it sits with user management, not with `properties.manage`: a property
     * manager should not be able to decide which properties OTHER people may see.
     *
     * ⚠️ REFUSES TO CHANGE YOUR OWN SCOPE. Not a nicety: the first thing a mis-click here does otherwise is
     * lock the administrator out of the property they are standing in, and the screen to undo it is the one
     * they just lost. Someone else with `users.manage` must do it — the same separation `LeaveService` uses
     * to make self-approval impossible.
     */
    public function updateTeamScope(Request $request, PropertyScopeService $scopes): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        // ⚠️ RE-AUTH (2026-08-30): team scope decides WHICH PROPERTIES a user can reach — an access change,
        // not a preference. Bare `current_password`: /app runs on the default `web` guard (verified).
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [],
            ['current_password' => __('php/http/controllers/operator/settings_action_controller.attr_current_password')],
        );

        $validated = $request->validate([
            'user_id' => ['required', 'string'],
            'scope' => ['required', 'in:all,selected'],
            'properties' => ['array'],
            'properties.*' => ['string'],
        ]);

        $orgId = (string) app(TenantContext::class)->id();

        // ⚠️ RESOLVED FROM THE ACTOR'S OWN ORGANISATION, never from the posted id alone. `User` is not
        // tenant-scoped (a user may belong to several orgs), so the membership filter IS the boundary here.
        $target = User::query()
            ->whereHas('memberships', fn ($q) => $q->where('organization_id', $orgId))
            ->find($validated['user_id']);

        if (! $target instanceof User) {
            return $this->back(__('php/http/controllers/operator/settings_action_controller.member_not_found'));
        }

        if ((string) $target->getKey() === (string) (auth()->id() ?? '')) {
            return $this->back(__('php/http/controllers/operator/settings_action_controller.cannot_change_own_access'));
        }

        $ids = $validated['scope'] === 'all' ? [] : array_values($validated['properties'] ?? []);

        if ($validated['scope'] === 'selected' && $ids === []) {
            return $this->back(__('php/http/controllers/operator/settings_action_controller.choose_properties'));
        }

        $applied = $scopes->setScope($target, $ids);

        return $this->back($applied === []
            ? __('php/http/controllers/operator/settings_action_controller.access_all', ['name' => $target->name])
            : trans_choice('php/http/controllers/operator/settings_action_controller.access_scoped', count($applied), ['name' => $target->name, 'count' => count($applied)]));
    }

    /**
     * Read-only guard (task #98 — was missing here, unlike peer operator controllers): a paused/lapsed
     * workspace refuses every settings write. Returns a redirect to short-circuit, or null to proceed.
     */
    private function guardReadOnly(): ?RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return redirect()->to(route('app.settings'))->with('settings_error', __('php/http/controllers/operator/settings_action_controller.read_only'));
        }

        return null;
    }

    private function back(string $notice): RedirectResponse
    {
        return redirect()->to(route('app.settings'))->with('settings_notice', $notice);
    }
}

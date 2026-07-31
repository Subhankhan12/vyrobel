<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fee;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\Tax;
use App\Support\Branding\PropertyBrand;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Support\Arr;

/**
 * The write path for the operator SETTINGS surface (Wave 4 · Sub-gate 3) — the FIRST code in `app/` to
 * write `properties` / `property_policies` / `organizations` config columns (until now the only writer was
 * the demo seeder; docs/WAVE4-INVENTORY.md flagged "nothing in app/ writes them"). Extends the existing
 * models — it does NOT introduce a parallel settings store (the generic `Setting` model stays unused; these
 * are real, typed columns).
 *
 * Each method whitelists the columns it may touch (a settings POST can never mass-assign a foreign key or a
 * status the form does not own), writes them, and records ONE audit event naming WHICH fields changed — not
 * their values. That mirrors the guest-write discipline: the audit trail is plaintext and must not become a
 * second copy of anything sensitive (org `billing_email` is business config, not guest PII, but "record the
 * field, not the value" is the safe habit and keeps the trail uniform).
 *
 * TENANT-safe by construction: Property / PropertyPolicy are BelongsToOrganization (org auto-fills on save,
 * and the caller resolves the model through the tenant scope); Organization IS the tenant row and is
 * resolved by the caller from the active tenant context, never a request id.
 */
class SettingsService
{
    /** @var list<string> */
    public const PROPERTY_FIELDS = ['name', 'timezone', 'star_rating', 'check_in_time', 'check_out_time', 'status', 'address'];

    /** @var list<string> */
    public const POLICY_FIELDS = ['pet', 'smoking', 'min_stay', 'max_stay', 'self_check_in', 'cancellation', 'deposit'];

    /** @var list<string> */
    public const ORG_FIELDS = ['default_currency', 'default_locale', 'billing_email'];

    /** @var list<string> — the guest-facing brand (task #98); the from ADDRESS is fixed (never per-property). */
    public const BRAND_FIELDS = ['brand_accent', 'brand_logo_path', 'brand_email_from_name', 'brand_reply_to', 'brand_powered_by'];

    public function __construct(private AuditEventRecorder $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateProperty(Property $property, array $attributes): void
    {
        $changes = Arr::only($attributes, self::PROPERTY_FIELDS);
        $property->update($changes);

        $this->audit->record('property.settings_updated', $property, null, ['fields' => array_keys($changes)]);
    }

    /**
     * Upsert the property's single policy row (one config row per property). `firstOrNew` sets `property_id`
     * from the relation and `organization_id` auto-fills on save via BelongsToOrganization.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updatePolicies(Property $property, array $attributes): void
    {
        $changes = Arr::only($attributes, self::POLICY_FIELDS);

        /** @var PropertyPolicy $policy */
        $policy = $property->policies()->firstOrNew([]);
        $policy->fill($changes)->save();

        $this->audit->record('property.policies_updated', $property, null, ['fields' => array_keys($changes)]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateOrganization(Organization $organization, array $attributes): void
    {
        $changes = Arr::only($attributes, self::ORG_FIELDS);
        $organization->update($changes);

        $this->audit->record('organization.settings_updated', $organization, null, ['fields' => array_keys($changes)]);
    }

    /** The org-level invoice CONFIG fields (legal entity/VAT/city already exist; the numbering is #104b GATE 2). */
    public const INVOICE_FIELDS = ['billing_company', 'billing_vat_id', 'billing_city', 'invoice_series_prefix', 'invoice_next_number', 'invoice_auto_issue'];

    /**
     * #104b GATE 2 — persist the Settings hub's Invoice details + numbering CONFIG on the ONE store (the org),
     * through the same audited path. ⚠️ This is CONFIG only: the certified numbered-invoice ISSUANCE + emailed PDF
     * + SAF-T (PT) export are honestly deferred (no pipeline is fabricated, #39) — the auto-issue flag records
     * intent, it does not itself issue an invoice.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateInvoiceDetails(Organization $organization, array $attributes): void
    {
        $changes = Arr::only($attributes, self::INVOICE_FIELDS);
        if (isset($changes['invoice_next_number'])) {
            $changes['invoice_next_number'] = max(1, (int) $changes['invoice_next_number']);
        }
        $changes['invoice_auto_issue'] = (bool) ($attributes['invoice_auto_issue'] ?? false);

        $organization->update($changes);
        $this->audit->record('organization.invoice_details_updated', $organization, null, ['fields' => array_keys($changes)]);
    }

    /**
     * Bounded edit of a property TAX (Batch C · GATE 4) — a percentage tax updates `rate` (clamped 0–100);
     * a fixed tax updates `amount_minor` (≥ 0, integer minor from the entered major units, #17). The mode /
     * kind is NEVER changed — only the editable figure. Tenant-safe: the caller resolves the Tax under the
     * org scope, so a cross-org id is never reached here.
     */
    public function updateTax(Tax $tax, float $value, ?bool $inclusive = null): void
    {
        $changes = (string) $tax->mode === 'percentage'
            ? ['rate' => max(0.0, min(100.0, $value))]
            : ['amount_minor' => max(0, (int) round($value * 100))];

        // #104 GATE 2c — the VAT Incl./Added toggle now PERSISTS (it was rendered read-only, but it drives the
        // folio math via StayEstimator::taxApplies: an inclusive percentage tax is informational, an exclusive
        // one is charged). Only meaningful on a percentage tax; a per-night/flat tax has no inclusive concept.
        if ($inclusive !== null && (string) $tax->mode === 'percentage') {
            $changes['inclusive'] = $inclusive;
        }

        $tax->update($changes);
        $this->audit->record('tax.updated', $tax, null, ['fields' => array_keys($changes)]);
    }

    /**
     * Update the property's GUEST-FACING BRAND (task #98) — accent / logo / email from-name / reply-to /
     * powered-by. The from ADDRESS is NOT here: it stays the fixed verified sender (Postmark-safe). These flow
     * to the real guest emails + portal + review page via {@see PropertyBrand}.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateBrand(Property $property, array $attributes): void
    {
        $changes = Arr::only($attributes, self::BRAND_FIELDS);
        $property->update($changes);

        $this->audit->record('property.brand_updated', $property, null, ['fields' => array_keys($changes)]);
    }

    /**
     * Create a custom property FEE (task #98 GATE 2) — the "Add a fee" flow. `mode` is the wireframe's basis
     * (per-stay → flat, per-night → per_night, per-guest → per_person); `optional` = offered-at-booking vs
     * always-applied. Integer minor amount (#17). Tenant-safe: Fee is BelongsToOrganization (org auto-fills).
     */
    public function createFee(Property $property, string $name, string $mode, int $amountMinor, bool $optional): Fee
    {
        $fee = new Fee([
            'property_id' => $property->getKey(),
            'name' => $name,
            'kind' => 'other',
            'mode' => $mode,
            'amount_minor' => max(0, $amountMinor),
            'currency' => (string) $property->currency,
            'taxable' => false,
            'optional' => $optional,
        ]);
        $fee->save();

        $this->audit->record('fee.created', $fee, null, ['fields' => ['name', 'mode', 'amount_minor', 'optional']]);

        return $fee;
    }

    /** Bounded edit of a property FEE (Batch C · GATE 4) — same rule as {@see updateTax}. */
    public function updateFee(Fee $fee, float $value): void
    {
        $changes = (string) $fee->mode === 'percentage'
            ? ['rate' => max(0.0, min(100.0, $value))]
            : ['amount_minor' => max(0, (int) round($value * 100))];

        $fee->update($changes);
        $this->audit->record('fee.updated', $fee, null, ['fields' => array_keys($changes)]);
    }
}

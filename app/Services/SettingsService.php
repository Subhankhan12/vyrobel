<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fee;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\Tax;
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
    public const PROPERTY_FIELDS = ['name', 'timezone', 'star_rating', 'check_in_time', 'check_out_time', 'status'];

    /** @var list<string> */
    public const POLICY_FIELDS = ['pet', 'smoking', 'min_stay', 'max_stay'];

    /** @var list<string> */
    public const ORG_FIELDS = ['default_currency', 'default_locale', 'billing_email'];

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

    /**
     * Bounded edit of a property TAX (Batch C · GATE 4) — a percentage tax updates `rate` (clamped 0–100);
     * a fixed tax updates `amount_minor` (≥ 0, integer minor from the entered major units, #17). The mode /
     * kind is NEVER changed — only the editable figure. Tenant-safe: the caller resolves the Tax under the
     * org scope, so a cross-org id is never reached here.
     */
    public function updateTax(Tax $tax, float $value): void
    {
        $changes = (string) $tax->mode === 'percentage'
            ? ['rate' => max(0.0, min(100.0, $value))]
            : ['amount_minor' => max(0, (int) round($value * 100))];

        $tax->update($changes);
        $this->audit->record('tax.updated', $tax, null, ['fields' => array_keys($changes)]);
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

<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\SettingsActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Fee;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\Tax;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator SETTINGS (Wave 4 · Sub-gate 3) — ONE coherent settings surface (the design's "Operator Settings"
 * and "Property Settings" are de-conflicted into this single screen, so tax/policy config has ONE write path,
 * not two that could drift). READ view; every write is a guarded POST →
 * {@see SettingsActionController} → {@see SettingsService}.
 *
 * Honesty triage (invariant #19) — ONLY the columns that genuinely back a field are editable; everything the
 * design draws over no column is stubbed "Soon" (never a toast-only fake save — the design's saveGuard:()=>{}
 * trap the audit named). BACKED + WIRED: property name/timezone/star-rating/check-in-out/status
 * (`properties`), stay policies pet/smoking/min-stay/max-stay (`property_policies`), org
 * currency/locale/billing-email (`organizations`). READ-ONLY (real data, editing deferred): the configured
 * taxes + fees. STUBBED "Soon" (net-new subsystems, NOT polish): branding/white-label, invoice numbering,
 * notification routing.
 *
 * TENANT-scoped (Property / PropertyPolicy / Tax / Fee are BelongsToOrganization); `/app` behind
 * EnsureBackOfficeAccess (#26); the route carries `can:properties.manage` (#30). The org section is
 * additionally gated `can:settings.manage` — a Group Admin manages the property but not org-level settings.
 */
#[Layout('components.layouts.operator')]
class OperatorSettings extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    public function render(): View
    {
        $property = $this->currentProperty();
        $policy = $property?->policies()->first();
        $organization = $property !== null ? Organization::query()->find($property->organization_id) : null;

        return view('livewire.operator.operator-settings', [
            'viewer' => $this->viewer(),
            'subtitle' => $property !== null ? (string) $property->name : 'No property configured',
            'hasProperty' => $property !== null,
            'canManageProperty' => (bool) auth()->user()?->can('properties.manage'),
            'canManageOrg' => (bool) auth()->user()?->can('settings.manage'),
            'property' => $property !== null ? $this->propertyForm($property) : [],
            'policy' => $this->policyForm($policy),
            'org' => $organization !== null ? $this->orgForm($organization) : [],
            'timezones' => timezone_identifiers_list(),
            'taxes' => $this->taxes(),
            'fees' => $this->fees(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyForm(Property $property): array
    {
        return [
            'name' => (string) $property->name,
            'timezone' => (string) $property->timezone,
            'star_rating' => $property->star_rating,
            'check_in_time' => $this->timeValue($property->check_in_time),
            'check_out_time' => $this->timeValue($property->check_out_time),
            'status' => (string) $property->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function policyForm(?PropertyPolicy $policy): array
    {
        if ($policy === null) {
            return ['pet' => 'not_allowed', 'smoking' => 'not_allowed', 'min_stay' => 1, 'max_stay' => null];
        }

        return [
            'pet' => (string) $policy->pet,
            'smoking' => (string) $policy->smoking,
            'min_stay' => $policy->min_stay ?? 1,
            'max_stay' => $policy->max_stay,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orgForm(Organization $organization): array
    {
        return [
            'default_currency' => (string) $organization->default_currency,
            'default_locale' => (string) $organization->default_locale,
            'billing_email' => (string) ($organization->billing_email ?? ''),
        ];
    }

    /**
     * Configured taxes — REAL, read-only. Rate/amount shown as the tax actually stores them.
     *
     * @return list<array{name: string, kind: string, basis: string}>
     */
    private function taxes(): array
    {
        return Tax::query()->orderBy('name')->get()->map(fn (Tax $tax): array => [
            'id' => (string) $tax->id,
            'name' => (string) $tax->name,
            'kind' => ucfirst(str_replace('_', ' ', (string) $tax->kind)),
            'mode' => (string) $tax->mode,   // Batch C · GATE 4 — drives the editor's % vs amount input.
            'percentage' => (string) $tax->mode === 'percentage',
            'rate' => rtrim(rtrim((string) $tax->rate, '0'), '.'),
            'amount' => number_format((int) $tax->amount_minor / 100, 2, '.', ''),
            'currency' => (string) $tax->currency,
            'basis' => (string) $tax->mode === 'percentage'
                ? rtrim(rtrim((string) $tax->rate, '0'), '.').'%'
                : $this->money((int) $tax->amount_minor, (string) $tax->currency),
        ])->all();
    }

    /**
     * @return list<array{name: string, basis: string}>
     */
    private function fees(): array
    {
        return Fee::query()->orderBy('name')->get()->map(fn (Fee $fee): array => [
            'id' => (string) $fee->id,
            'name' => (string) $fee->name,
            'percentage' => (string) $fee->mode === 'percentage',
            'rate' => rtrim(rtrim((string) $fee->rate, '0'), '.'),
            'amount' => number_format((int) $fee->amount_minor / 100, 2, '.', ''),
            'currency' => (string) $fee->currency,
            'basis' => (string) $fee->mode === 'percentage'
                ? rtrim(rtrim((string) $fee->rate, '0'), '.').'%'
                : $this->money((int) $fee->amount_minor, (string) $fee->currency),
        ])->all();
    }

    /** A `time` column ("15:00:00") as an H:i value for a <input type="time">, or '' when null. */
    private function timeValue(mixed $time): string
    {
        return is_string($time) && strlen($time) >= 5 ? substr($time, 0, 5) : '';
    }
}

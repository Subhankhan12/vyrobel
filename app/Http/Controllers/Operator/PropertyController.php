<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Services\ChannelConnectionService;
use App\Services\PropertyRemovalRefused;
use App\Services\PropertyRemovalService;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Operator PROPERTY CRUD (Phase 6a · Step 2 · Portfolio) — create + edit a property in the org, via the
 * locked operator-mutation pattern (POST → controller → PRG).
 *
 * PERMISSION (#30): both writes are route-gated `can:properties.manage` (Super Admin / Property Owner /
 * Group Admin) — deliberately NOT Front Desk or Hotel Manager (who hold `properties.view` only). The
 * Portfolio view gates the "Add property"/"Edit" affordances off the SAME permission so a viewer never
 * sees a form they cannot submit.
 *
 * TENANT: Property is BelongsToOrganization, so the new row's `organization_id` is auto-filled from the
 * active tenant context — a created property CANNOT belong to another org. An edit resolves the target by
 * a tenant-scoped `findOrFail` from a string id (never a route-model-bound model — invariant #20), so a
 * cross-org id 404s.
 *
 * HONESTY: creating a property makes the shell only — a brand-new property has NO room types or units, so
 * the Portfolio derives an honest "Finish setup to go live" state (it is not operational until inventory
 * exists). Nothing about occupancy/billing is fabricated. "Billed per unit" is a Phase-7 subscription
 * concept (no billing subsystem exists yet), so no charge is shown or taken.
 */
class PropertyController extends Controller
{
    /** The three property kinds the Portfolio offers (a subset of the real `properties.type` enum). */
    private const TYPES = ['villa', 'hotel', 'guest_house'];

    public function store(Request $request): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return redirect()->route('app.portfolio')->with('portfolio_error', 'Workspace paused — adding a property is read-only until '.app(OperatorWorkspace::class)->resumeLabel().'.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'location' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        // Currency follows the org's default — a property is billed/priced in the org's currency, never a
        // request-supplied value. Organization is resolved from the active tenant, not from the request.
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());

        $property = Property::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'slug' => $this->uniqueSlug($data['name']),
            'currency' => (string) ($org->default_currency ?? 'EUR'),
            'timezone' => $data['timezone'] ?? 'UTC',
            'address' => isset($data['location']) && $data['location'] !== '' ? ['city' => $data['location']] : null,
            'status' => 'active',
        ]);

        // If the creator works under the property_user allow-list (they have pivot rows), add the new
        // property to it — otherwise they'd create a property they immediately couldn't select. A creator
        // with no pivot rows already sees the whole org, so nothing to do.
        $user = $request->user();
        if ($user !== null && $user->propertyAssignments()->exists()) {
            PropertyAssignment::create([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'property_id' => $property->id,
            ]);
        }

        return redirect()->to(route('app.portfolio'))
            // ⚠️ P-6 · Gate 1 — WAS "add units, rates & channels — to go live". All three were impossible: room
            // types and rate plans had no creation path at all, and units only behind an owner's request. The
            // first two are real now; ⚠️ CHANNELS ARE STILL NOT, so they are no longer instructed. A signposted
            // step a tenant cannot take is worse than no signpost — they conclude they are failing.
            ->with('portfolio_notice', $property->name.' added to your portfolio. Next: add a room type and a unit, then a rate plan with prices — then it can take bookings.');
    }

    public function update(Request $request): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return redirect()->route('app.portfolio')->with('portfolio_error', 'Workspace paused — editing a property is read-only until '.app(OperatorWorkspace::class)->resumeLabel().'.');
        }

        $data = $request->validate([
            'property_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'timezone' => ['required', 'timezone'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $property = Property::query()->findOrFail($data['property_id']);   // tenant-scoped → cross-org 404s

        $property->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'timezone' => $data['timezone'],
            'status' => $data['status'],
        ]);

        return redirect()->to(route('app.portfolio'))->with('portfolio_notice', $property->name.' updated.');
    }

    /**
     * ⚠️⚠️ REMOVE a property from the Portfolio (2026-09-29) — the certified ARCHIVE-NOT-DESTROY removal
     * ({@see PropertyRemovalService}): bookings, invoices and payments are always kept. Nothing staying and nothing booked
     * ahead → removed right away; otherwise it stays open until the last checkout and can be kept until then.
     * `properties.manage` on the route; only a property this person can ACCESS (the property_user allow-list, #38 — an id
     * outside it is a 404, never a 403 that confirms it exists); never in a read-only workspace; the name typed to confirm.
     * Every transition is audited by the service.
     */
    public function remove(Request $request, string $property, PropertyRemovalService $removals, ChannelConnectionService $channels): RedirectResponse
    {
        $target = $this->accessibleProperty($property);
        if ($refused = $this->refusedWhileReadOnly()) {
            return $refused;
        }

        $request->validate(['confirm_name' => ['required', 'string']]);
        $name = (string) $target->name;
        if (trim((string) $request->input('confirm_name')) !== trim($name)) {
            return redirect()->route('app.portfolio')->with('portfolio_error', 'The name you typed didn’t match “'.$name.'” — nothing was removed.');
        }

        try {
            $removals->schedule($target, (string) auth()->id());
        } catch (PropertyRemovalRefused $e) {
            return redirect()->route('app.portfolio')->with('portfolio_error', $name.' was not removed. '.$e->getMessage());
        }

        $target->refresh();
        if ($removals->removeIfClear($target, $channels)) {
            return redirect()->route('app.portfolio')->with('portfolio_notice', $name.' was removed. Its bookings, invoices and payments are kept.');
        }

        return redirect()->route('app.portfolio')->with('portfolio_notice', $name.' will be removed after the last checkout on '
            .$target->removal_effective_at?->format('j M Y').'. It stays open until then — you can keep it any time before.');
    }

    /** KEEP a property whose removal is scheduled — cancels the schedule; nothing else changes. Same gates as {@see remove()}. */
    public function keep(string $property, PropertyRemovalService $removals): RedirectResponse
    {
        $target = $this->accessibleProperty($property);
        if ($refused = $this->refusedWhileReadOnly()) {
            return $refused;
        }

        $removals->cancel($target);

        return redirect()->route('app.portfolio')->with('portfolio_notice', $target->name.' stays — its removal is cancelled.');
    }

    /** A property this person can reach — the PropertyContext allow-list (pivot-or-all), then the tenant scope. 404 otherwise. */
    private function accessibleProperty(string $id): Property
    {
        abort_unless(in_array($id, app(PropertyContext::class)->accessibleIds(), true), 404);

        return Property::query()->findOrFail($id);
    }

    private function refusedWhileReadOnly(): ?RedirectResponse
    {
        return app(OperatorWorkspace::class)->isReadOnly()
            ? redirect()->route('app.portfolio')->with('portfolio_error', 'Your workspace is read-only right now — nothing was changed.')
            : null;
    }

    /** A unique, URL-safe slug for the storefront path, disambiguated within the org if needed. */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) !== '' ? Str::slug($name) : 'property';
        $slug = $base;
        $n = 1;
        while (Property::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }
}

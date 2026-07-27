<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerUnitRequestController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Amenity;
use App\Models\Organization;
use App\Models\Owner;
use App\Models\Scopes\OwnerScope;
use App\Models\UnitRequest;
use App\Services\OwnerUnitRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Add a Unit (Phase 2 · Sub-gate 6 → rebuilt to the wireframe as a 4-step WIZARD, My Units · GATE 3). The
 * owner REQUESTS a new unit; they never create one — a unit changes the operator's live inventory/availability/
 * billing, so the operator reviews the request and creates it as their own action. This is the READ side (the
 * wizard form + the owner's own requests); the write goes POST → {@see OwnerUnitRequestController} (never
 * `/livewire/update` — the owner-scope bypass boundary, #20).
 *
 * Everything the wizard shows is REAL (#39): the "Managed by … X% commission / you keep ~Y" panel reads the
 * owner's OWN active management agreement (no agreement → no fabricated %); the amenity chips render the real
 * global catalog; the live preview reflects the owner's OWN entered values, honestly badged "Pending review"
 * (a proposed unit, never a live/priced one). SELF-ONLY / owner-scoped: {@see UnitRequest} carries the owner
 * scope, so the requests list is the authenticated owner's own and the write files against the owner resolved
 * by user_id. A non-linked user fails closed.
 */
#[Layout('components.layouts.owner')]
class OwnerAddUnit extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $service = app(OwnerUnitRequestService::class);
        $user = auth()->user();

        try {
            $owner = $user === null ? throw new ModelNotFoundException : $service->owner($user);
        } catch (ModelNotFoundException) {
            return view('livewire.owner.add-unit', [
                'viewer' => $this->viewer(),
                'linked' => false,
                'kinds' => OwnerUnitRequestService::KINDS,
                'amenities' => [],
                'operatorName' => 'your management team',
                'commissionPct' => null,
                'currency' => 'EUR',
                'currencySymbol' => $this->currencySymbol('EUR'),
                'firstName' => 'there',
                'requests' => [],
                'done' => null,
                'limits' => $this->limits(),
            ]);
        }

        $currency = (string) ($owner->payout_currency ?: 'EUR');

        return view('livewire.owner.add-unit', [
            'viewer' => $this->viewer(),
            'linked' => true,
            'kinds' => OwnerUnitRequestService::KINDS,
            'amenities' => $service->amenityCatalog()->map(fn (Amenity $a): array => [
                'id' => (string) $a->id,
                'name' => (string) $a->name,
                'icon' => (string) ($a->icon ?? ''),
            ])->all(),
            'operatorName' => $this->operatorName($owner),
            'commissionPct' => $this->commissionPct($owner),
            'currency' => $currency,
            'currencySymbol' => $this->currencySymbol($currency),
            'firstName' => $this->firstName(),
            'requests' => $service->requests()->map(fn (UnitRequest $r): array => [
                'name' => (string) $r->requested_name,
                'kind' => OwnerUnitRequestService::KINDS[$r->requested_kind],
                'status_meta' => $this->requestStatusMeta((string) $r->status),
                'when' => $r->created_at?->format('j M Y'),
            ])->all(),
            'done' => $this->doneData(),
            'limits' => $this->limits(),
        ]);
    }

    /** The operating org's name for the "Managed by …" copy — real, never a placeholder brand. */
    private function operatorName(Owner $owner): string
    {
        $name = Organization::query()->whereKey($owner->organization_id)->value('name');

        return is_string($name) && $name !== '' ? $name : 'your management team';
    }

    /**
     * The owner's REAL commission rate from their active owner-wide management agreement (unit_id null). Null when
     * there is no agreement / no rate set — the view then shows an honest "your team confirms it" line instead of
     * a fabricated percentage (#39).
     */
    private function commissionPct(Owner $owner): ?float
    {
        $pct = $owner->agreements()
            ->whereNull('unit_id')
            ->where('status', 'active')
            ->orderByDesc('effective_from')
            ->value('commission_pct');

        return $pct === null ? null : (float) $pct;
    }

    /** The authenticated user's first name for the "Thanks, {name}" confirmation. */
    private function firstName(): string
    {
        $first = strtok(trim($this->viewer()['name']), ' ');

        return $first === false ? 'there' : $first;
    }

    /**
     * The just-submitted request carried through the PRG redirect → renders the "done" confirmation instead of
     * the wizard. The id comes from the flash, but the fields are read back OWNER-SCOPED from the DB ({@see
     * UnitRequest} carries {@see OwnerScope}), so the done state can only ever confirm a
     * request the authenticated owner ACTUALLY owns — a stale or cross-account flash simply falls through to the
     * wizard. Null on a normal visit.
     *
     * @return array{id: string, name: string, location: string}|null
     */
    private function doneData(): ?array
    {
        $done = session('unit_request_done');
        if (! is_array($done) || ! isset($done['id'])) {
            return null;
        }

        $request = UnitRequest::query()->whereKey((string) $done['id'])->first();
        if ($request === null) {
            return null;   // not this owner's request (or gone) → show the wizard, never a foreign confirmation
        }

        return [
            'id' => (string) $request->id,
            'name' => (string) $request->requested_name,
            'location' => (string) ($request->location ?? ''),
        ];
    }

    /**
     * The server-authoritative stepper bounds, surfaced so the client steppers can't run past what the controller
     * will accept (defence in depth — the POST is validated regardless).
     *
     * @return array<string, int>
     */
    private function limits(): array
    {
        return [
            'bedrooms' => OwnerUnitRequestService::MAX_BEDROOMS,
            'bathrooms' => OwnerUnitRequestService::MAX_BATHROOMS,
            'sleeps' => OwnerUnitRequestService::MAX_SLEEPS,
            'min_nights' => OwnerUnitRequestService::MAX_MIN_NIGHTS,
            'photos' => OwnerUnitRequestService::MAX_PHOTOS,
        ];
    }

    /**
     * @return array{label: string, tone: string}
     */
    private function requestStatusMeta(string $status): array
    {
        return match ($status) {
            'approved' => ['label' => 'Approved', 'tone' => 'paid'],
            'declined' => ['label' => 'Declined', 'tone' => 'draft'],
            default => ['label' => 'Pending review', 'tone' => 'pending'],
        };
    }
}

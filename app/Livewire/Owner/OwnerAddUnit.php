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
use App\Support\I18n\LocalDate;
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
                'kinds' => OwnerUnitRequestService::kindLabels(),
                'amenities' => [],
                'operatorName' => __('php/livewire/owner/owner_add_unit.your_management_team'),
                'commissionPct' => null,
                // ⚠️ THE NOT-LINKED BRANCH HAS NO TENANT. There is deliberately no owner record here, so
                // there is no tenant currency to resolve and naming one would be an assertion, not a fact.
                // The platform default is the honest stand-in and it is READ, never typed.
                'currency' => $platformCurrency = (string) config('billing.default_currency', 'EUR'),
                'currencySymbol' => $this->currencySymbol($platformCurrency),
                'firstName' => __('php/livewire/owner/owner_add_unit.there'),
                'requests' => [],
                'done' => null,
                'draft' => null,
                'limits' => $this->limits(),
            ]);
        }

        $currency = (string) ($owner->payout_currency ?: config('billing.default_currency', 'EUR'));
        $kinds = OwnerUnitRequestService::kindLabels();

        return view('livewire.owner.add-unit', [
            'viewer' => $this->viewer(),
            'linked' => true,
            'kinds' => $kinds,
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
                // A draft may genuinely have no name yet — that is the state, not a missing value, so it is
                // labelled rather than rendered as an empty row.
                'name' => filled($r->requested_name) ? (string) $r->requested_name : __('php/livewire/owner/owner_add_unit.untitled_draft'),
                'kind' => $kinds[$r->requested_kind],
                'status_meta' => $this->requestStatusMeta((string) $r->status),
                'when' => $r->created_at?->translatedFormat(LocalDate::pattern('j M Y')),
                // DO9-2 — only a DRAFT is resumable; a filed request has no continue link.
                'resume_url' => $r->isDraft() ? route('owner.add-unit', ['draft' => $r->id]) : null,
                // DO9-3 — only a PENDING request can be retracted. ⚠️ This drives the CONTROL only; the
                // decision is re-made under a row lock in the service, so hiding the button is convenience,
                // never the gate (a stale page must not be able to withdraw an approved request).
                'withdraw_url' => (string) $r->status === 'pending' ? route('owner.add-unit.withdraw', ['unitRequest' => $r->id]) : null,
            ])->all(),
            'done' => $this->doneData(),
            'draft' => $this->draftData($service),
            'limits' => $this->limits(),
        ]);
    }

    /** The operating org's name for the "Managed by …" copy — real, never a placeholder brand. */
    private function operatorName(Owner $owner): string
    {
        $name = Organization::query()->whereKey($owner->organization_id)->value('name');

        return is_string($name) && $name !== '' ? $name : __('php/livewire/owner/owner_add_unit.your_management_team');
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

        return $first === false ? __('php/livewire/owner/owner_add_unit.there') : $first;
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
     * DO9-2 — the wizard PRE-FILLED from one of this owner's drafts, resolved from `?draft={id}`.
     *
     * ⚠️ Owner-scoped AND draft-only through the service's {@see OwnerUnitRequestService::findDraft()}: a
     * foreign id, an already-filed request id, or junk all resolve to null and simply render an empty wizard —
     * the resume link can never open (or later overwrite) someone else's request, or one the operator has
     * already seen.
     *
     * ⚠️ PHOTOS ARE NOT RE-OFFERED as pre-filled input — a file picker cannot be pre-populated by any browser.
     * Photos already uploaded stay attached to the draft row and are re-attached to the submitted request; the
     * view says so rather than leaving the owner to guess whether their uploads survived.
     *
     * @return array<string, mixed>|null
     */
    private function draftData(OwnerUnitRequestService $service): ?array
    {
        $id = request()->query('draft');
        $draft = $service->findDraft(is_string($id) ? $id : null);

        if (! $draft instanceof UnitRequest) {
            return null;
        }

        return [
            'id' => (string) $draft->id,
            'requested_name' => (string) ($draft->requested_name ?? ''),
            'requested_kind' => (string) $draft->requested_kind,
            'location' => (string) ($draft->location ?? ''),
            'street_address' => (string) ($draft->street_address ?? ''),
            'bedrooms' => (int) $draft->bedrooms,
            'bathrooms' => (int) $draft->bathrooms,
            'sleeps' => (int) $draft->sleeps,
            'min_nights' => (int) ($draft->min_nights ?? 2),
            // Money back out as the plain typed shape the wizard's text inputs use (never a formatted string
            // the owner would have to re-clean); null → empty, never a fabricated 0.
            'nightly_rate' => $draft->requested_rate_minor === null ? '' : $this->plainAmount((int) $draft->requested_rate_minor),
            'cleaning_fee' => $draft->cleaning_fee_minor === null ? '' : $this->plainAmount((int) $draft->cleaning_fee_minor),
            'notes' => (string) ($draft->notes ?? ''),
            'amenities' => $draft->amenities()->pluck('amenities.id')->map(fn ($id): string => (string) $id)->all(),
            'photo_count' => $draft->photos()->count(),
            'saved_at' => $draft->updated_at?->translatedFormat(LocalDate::pattern('j M Y, H:i')),
        ];
    }

    /** Integer minor units → the plain "180" / "180.50" text the wizard's rate fields accept back. */
    private function plainAmount(int $minor): string
    {
        return $minor % 100 === 0 ? (string) intdiv($minor, 100) : number_format($minor / 100, 2, '.', '');
    }

    /**
     * @return array{label: string, tone: string}
     */
    private function requestStatusMeta(string $status): array
    {
        return match ($status) {
            'approved' => ['label' => __('php/livewire/owner/owner_add_unit.status_approved'), 'tone' => 'paid'],
            'declined' => ['label' => __('php/livewire/owner/owner_add_unit.status_declined'), 'tone' => 'draft'],
            // ⚠️ DO9-2 — a draft MUST be named here. The `default` arm below reads "Pending review", so without
            // this case a saved draft would tell the owner it was already with the operator — the exact
            // opposite of the truth, and on the one screen where that distinction is the whole feature.
            'draft' => ['label' => __('php/livewire/owner/owner_add_unit.status_draft'), 'tone' => 'draft'],
            // ⚠️ DO9-3 — the same trap, one state later: without this arm a request the owner just retracted
            // would fall through to "Pending review" and read as still live with the operator.
            'withdrawn' => ['label' => __('php/livewire/owner/owner_add_unit.status_withdrawn'), 'tone' => 'draft'],
            default => ['label' => __('php/livewire/owner/owner_add_unit.status_pending'), 'tone' => 'pending'],
        };
    }
}

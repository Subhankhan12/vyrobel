<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Http\Middleware\SetCurrentPropertyForWebRequest;
use App\Models\Property;
use App\Support\Property\PropertyContext;
use Livewire\Attributes\Locked;

/**
 * Resolves the operator's current PROPERTY working-context inside a full-page Livewire component
 * (Phase 6a · multi-property). Replaces the pre-6a `Property::query()->orderBy('name')->first()`,
 * which silently returned an ARBITRARY property once an org ran more than one.
 *
 * WHY capture in mount() and hold a string id, not read the context in render(): the initial page
 * load runs through the `/app` group where {@see SetCurrentPropertyForWebRequest}
 * activates the {@see PropertyContext} — but a Livewire wire-action re-render hits `/livewire/update`,
 * which is NOT in the `/app` group, so the context is INACTIVE there. Capturing the resolved values at
 * mount and holding them as props keeps render() correct across wire updates — the same reason the
 * scoped-model-as-prop trap (invariant #20) says hold a string id and resolve in render().
 *
 * The props are {@see Locked}: cross-property access WITHIN an org is not caught by tenant scope, so a
 * client-tamperable public prop could smuggle a non-assigned property id onto a wire update. Locked
 * props are server-set only — the client cannot change them — and they were already bounded to the
 * user's accessible set by {@see PropertyContext::activate()} at mount.
 */
trait ResolvesCurrentProperty
{
    /** The concrete working-property id ('' when the org has no accessible property). */
    #[Locked]
    public string $currentPropertyId = '';

    /** Whether the operator selected the "all properties" roll-up (aggregate reads honour it). */
    #[Locked]
    public bool $viewingAllProperties = false;

    /**
     * The property ids to READ: every accessible property when "all" is active, else just the
     * current one. The single source the reads branch on, so no screen re-implements all-vs-one.
     *
     * @var list<string>
     */
    #[Locked]
    public array $currentReadIds = [];

    public function mountResolvesCurrentProperty(): void
    {
        $ctx = app(PropertyContext::class);
        $this->currentPropertyId = (string) ($ctx->id() ?? '');
        $this->viewingAllProperties = $ctx->isAll();
        $this->currentReadIds = $ctx->readIds();
    }

    /**
     * The current property as a tenant-scoped model, or null when the org has none. Resolved fresh in
     * render() from the held string id (never a scoped-model prop — invariant #20).
     */
    protected function currentProperty(): ?Property
    {
        if ($this->currentPropertyId === '') {
            return null;
        }

        return Property::query()->whereKey($this->currentPropertyId)->first();
    }
}

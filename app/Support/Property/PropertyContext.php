<?php

declare(strict_types=1);

namespace App\Support\Property;

use App\Http\Middleware\SetCurrentPropertyForWebRequest;
use App\Support\Tenancy\TenantContext;
use Closure;

/**
 * Holds the current PROPERTY working-context for a back-office request (Phase 6a · multi-property).
 *
 * A tenant (organization) may run N properties. Before Phase 6a every operator screen resolved
 * "the property" as `Property::query()->orderBy('name')->first()` — which silently returns an
 * ARBITRARY property once there is more than one (a wrong-property read, or worse a wrong-property
 * WRITE: a booking landing in the wrong property). This context replaces that: the operator picks a
 * working property with the topbar switcher, and every property-scoped read/write resolves through
 * {@see id()} instead of `->first()`.
 *
 * It is a WORKING context WITHIN a tenant, NOT a security boundary between orgs — tenant scope
 * ({@see TenantContext}) still isolates organizations. What this adds is
 * property RESOLUTION inside an org, plus an OPTIONAL access restriction (the `property_user`
 * pivot): {@see accessibleIds()} is the set the user may work in — their pivot properties if they
 * have any, otherwise every property in the org (empty pivot ⇒ full org, so no existing user is
 * locked out and single-property behaviour is unregressed).
 *
 * Bound as a singleton (see AppServiceProvider). It is INACTIVE by default — CLI/queue/tests run
 * with no property context (they see all properties, subject to tenant scope). It is ACTIVATED only
 * by {@see SetCurrentPropertyForWebRequest} on the `/app` group, after the tenant is set.
 *
 * "All properties" ({@see isAll()}) is an explicit ROLL-UP selection honoured by aggregate READ
 * surfaces only (Dashboard / Portfolio / the Tier-B list screens). {@see id()} ALWAYS returns a
 * concrete property (the default when "all" is active), so a property-scoped WRITE always has a sane,
 * single target — you never write into "all".
 */
class PropertyContext
{
    public const ALL = 'all';

    private bool $active = false;

    /** @var list<string> */
    private array $accessibleIds = [];

    /** The raw selection: a concrete property id, self::ALL, or null (no properties). */
    private ?string $selection = null;

    /**
     * Activate the context for a request. $selection is the raw session selection (a property id,
     * self::ALL, or null); it is normalised against $accessibleIds so a stale/foreign id can never
     * leak — an invalid selection falls back to the first accessible property.
     *
     * @param  list<string>  $accessibleIds
     */
    public function activate(array $accessibleIds, ?string $selection): void
    {
        $this->active = true;
        $this->accessibleIds = array_values(array_unique($accessibleIds));
        $this->selection = $this->normalise($selection);
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->accessibleIds = [];
        $this->selection = null;
    }

    public function active(): bool
    {
        return $this->active;
    }

    /**
     * The concrete current property id — ALWAYS a single property (the default when "all" is
     * selected), or null only when the org has no accessible property. Property-scoped reads and
     * every property-scoped WRITE resolve through this.
     */
    public function id(): ?string
    {
        if ($this->selection !== null && $this->selection !== self::ALL) {
            return $this->selection;
        }

        return $this->accessibleIds[0] ?? null;
    }

    /**
     * Whether the operator selected the "all properties" roll-up. Honoured by aggregate READ
     * surfaces only; a write still targets {@see id()}.
     */
    public function isAll(): bool
    {
        return $this->selection === self::ALL && $this->accessibleIds !== [];
    }

    /**
     * The property ids to READ when "all" is active (every accessible property); otherwise just the
     * selected one. This is the single helper the Tier-B list screens + Dashboard use to decide
     * their read scope, so none of them re-implement the all-vs-one branch.
     *
     * @return list<string>
     */
    public function readIds(): array
    {
        if ($this->isAll()) {
            return $this->accessibleIds;
        }

        $id = $this->id();

        return $id === null ? [] : [$id];
    }

    /**
     * The full set of properties the user may work in (their pivot properties, or the whole org when
     * the pivot is empty). Drives the switcher's option list and bounds every selection.
     *
     * @return list<string>
     */
    public function accessibleIds(): array
    {
        return $this->accessibleIds;
    }

    /** The raw selection (a property id or self::ALL) — for the switcher to mark the active option. */
    public function selection(): ?string
    {
        return $this->selection;
    }

    /**
     * Run a callback under an explicit property context, restoring the previous state after (mainly
     * for tests / programmatic property-scoped reads).
     *
     * @param  list<string>  $accessibleIds
     *
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @return TReturn
     */
    public function forProperty(array $accessibleIds, ?string $selection, Closure $callback): mixed
    {
        $previousActive = $this->active;
        $previousAccessible = $this->accessibleIds;
        $previousSelection = $this->selection;

        $this->activate($accessibleIds, $selection);

        try {
            return $callback();
        } finally {
            $this->active = $previousActive;
            $this->accessibleIds = $previousAccessible;
            $this->selection = $previousSelection;
        }
    }

    /**
     * Coerce a raw selection into a valid one: keep self::ALL and any accessible id; otherwise fall
     * back to the first accessible property (never a foreign/stale id).
     */
    private function normalise(?string $selection): ?string
    {
        if ($selection === self::ALL) {
            return self::ALL;
        }

        if ($selection !== null && in_array($selection, $this->accessibleIds, true)) {
            return $selection;
        }

        return $this->accessibleIds[0] ?? null;
    }
}

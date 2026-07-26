<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Controllers\Admin\CustomerController;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * SUPER ADMIN · BUILD 1 · GATE 2 — REAL customer (organization) reads for the platform console.
 *
 * ONLY builds queries; it does NOT open the bypass. The caller ({@see CustomerController})
 * runs these INSIDE {@see PlatformQuery::read()}, so they execute under the audited, admin-guard-gated,
 * scope-restored `runAsSuperAdmin` context.
 *
 * 🔒 #28 — PLATFORM-OPERATOR-JUDICIOUS: this surfaces OPERATIONAL facts about a tenant business (name, status,
 * plan, property/staff COUNTS, billing company). It deliberately does NOT expose end-guests' personal/contact
 * PII — the platform operator is looking at a customer account, not surveilling that customer's guests.
 */
class PlatformCustomers
{
    /** Customers per page (audit fix P2-1 — the list is paginated so it scales past a handful of orgs). */
    public const PER_PAGE = 25;

    /**
     * The customer list — every org with its plan, status and key operational counts, PAGINATED (search/status
     * carried into the page links). Each page's counts are computed for just that page's org ids (no N+1).
     *
     * Each page item is a row array: {id, name, slug, status, plan, properties, members, created_at}.
     *
     * @param  array{search?: ?string, status?: ?string}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Organization::query()->orderBy('name');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, ['active', 'trialing', 'suspended', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(self::PER_PAGE, ['id', 'name', 'slug', 'status', 'created_at'])->withQueryString();

        $ids = collect($paginator->items())->pluck('id')->all();
        $propertyCounts = $this->countByOrg(Property::query(), $ids);
        $memberCounts = $this->countByOrg(Membership::query()->where('status', 'active'), $ids);
        $plans = $this->currentPlanNames($ids);

        return $paginator->through(fn (Organization $org) => [
            'id' => (string) $org->id,
            'name' => (string) $org->name,
            'slug' => (string) $org->slug,
            'status' => (string) $org->status,
            'plan' => $plans[$org->id] ?? null,
            'properties' => (int) ($propertyCounts[$org->id] ?? 0),
            'members' => (int) ($memberCounts[$org->id] ?? 0),
            'created_at' => $org->created_at,
        ]);
    }

    /**
     * One customer's detail — org facts + its subscription/plan + operational counts. Read-only.
     *
     * @return array{org: Organization, subscription: ?Subscription, plan_name: ?string, billing_label: string, properties: int, members: int}
     */
    public function detail(Organization $org): array
    {
        $subscription = $org->currentSubscription();

        return [
            'org' => $org,
            'subscription' => $subscription,
            'plan_name' => $subscription?->plan?->name,
            'billing_label' => $org->billingStatus()->label(),
            'properties' => Property::query()->where('organization_id', $org->id)->count(),
            'members' => Membership::query()->where('organization_id', $org->id)->where('status', 'active')->count(),
        ];
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $ids
     * @return array<string, int>
     */
    private function countByOrg($query, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $query->whereIn('organization_id', $ids)
            ->selectRaw('organization_id, COUNT(*) as aggregate')
            ->groupBy('organization_id')
            ->pluck('aggregate', 'organization_id')
            ->map(fn ($v): int => (int) $v)
            ->all();
    }

    /**
     * The current plan name per org (latest subscription). One query, no N+1.
     *
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    private function currentPlanNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $latest = Subscription::query()
            ->withoutGlobalScopes()
            ->whereIn('organization_id', $ids)
            ->with('plan:id,name')
            ->latest()
            ->get(['id', 'organization_id', 'plan_id', 'created_at']);

        $out = [];
        foreach ($latest as $subscription) {
            // First (latest) per org wins.
            if (! isset($out[$subscription->organization_id]) && $subscription->plan !== null) {
                $out[$subscription->organization_id] = (string) $subscription->plan->name;
            }
        }

        return $out;
    }
}

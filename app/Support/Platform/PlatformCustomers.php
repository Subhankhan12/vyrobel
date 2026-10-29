<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Controllers\Admin\CustomerController;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\PlatformAdminAction;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Authorization\RoleLabel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * SUPER ADMIN · BUILD 1 · GATE 2 → ADMIN 01 · PHASE 2 — the TENANT DIRECTORY, its detail and its triage queue.
 *
 * ONLY builds queries; it does NOT open the bypass. The caller ({@see CustomerController}) runs these INSIDE
 * {@see PlatformQuery::read()} — read-only at the connection, both refusal scopes lifted, audited.
 *
 * ⚠️⚠️ METADATA ONLY. Everything here is account and billing metadata about a Vyrobel CUSTOMER — name, plan, status,
 * signup source, counts, the account holders' names and sign-in times. Never a guest, a stay, a folio or a payment:
 * those belong to the hotel, and the console reaches them only through break-glass (Phase 3).
 *
 * ⚠️ ONE DEFINITION PER BUCKET. The triage page's counts and the directory's filtered lists use the same
 * {@see applyBucket()}, so "3 unverified" on one screen cannot become 2 rows on the next.
 */
class PlatformCustomers
{
    /** Customers per page (audit fix P2-1 — the list is paginated so it scales past a handful of orgs). */
    public const PER_PAGE = 25;

    /**
     * The triage buckets — key => [label, definition shown on screen]. ⚠️ I18N-01 — the English here is the fallback for
     * callers that still read the constant; what a person reads comes from {@see bucketLabel()} / {@see bucketDefinition()}.
     */
    public const BUCKETS = [
        'unverified' => [
            'Unverified for 7+ days',
            'Created more than 7 days ago, and none of its users has confirmed an email address.',
        ],
        'never_signed_in' => [
            'Never signed in',
            'None of its users has ever completed a sign-in. ⚠️ The sign-up session itself is not a sign-in, so this means "never came back after signing up".',
        ],
        'via_x' => [
            'Created with X',
            'Signed up through X. X does not prove an email address the way Google does — the accepted squatting risk. Surfaced for review, never acted on automatically.',
        ],
    ];

    public const SORTS = ['name', 'created', 'last_login', 'properties', 'users'];

    public const STATUSES = ['active' => 'Active', 'trialing' => 'On a free trial', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled', 'archived' => 'Archived'];

    /** ADMIN 03 · F9 — a subscription's state in words, never the raw Stripe-style value. */
    public const SUBSCRIPTION_STATES = ['trialing' => 'On a free trial', 'active' => 'Active — billed', 'past_due' => 'Past due', 'canceled' => 'Cancelled', 'incomplete' => 'Incomplete', 'paused' => 'Paused'];

    /** I18N-01 — a triage bucket's name, in the reader's language. The bucket CODE is what the queries and URLs use. */
    public static function bucketLabel(string $bucket): string
    {
        return match ($bucket) {
            'unverified' => __('php/support/platform/platform_customers.bucket_unverified'),
            'never_signed_in' => __('php/support/platform/platform_customers.bucket_never_signed_in'),
            'via_x' => __('php/support/platform/platform_customers.bucket_via_x'),
            default => $bucket,
        };
    }

    /** I18N-01 — what a triage bucket means, in the reader's language. */
    public static function bucketDefinition(string $bucket): string
    {
        return match ($bucket) {
            'unverified' => __('php/support/platform/platform_customers.bucket_unverified_definition'),
            'never_signed_in' => __('php/support/platform/platform_customers.bucket_never_signed_in_definition'),
            'via_x' => __('php/support/platform/platform_customers.bucket_via_x_definition'),
            default => '',
        };
    }

    /** I18N-01 — an organisation status in words, by its stored code (an unknown code reads as itself, capitalised). */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => __('php/support/platform/platform_customers.status_active'),
            'trialing' => __('php/support/platform/platform_customers.status_trialing'),
            'suspended' => __('php/support/platform/platform_customers.status_suspended'),
            'cancelled' => __('php/support/platform/platform_customers.status_cancelled'),
            'archived' => __('php/support/platform/platform_customers.status_archived'),
            default => ucfirst($status),
        };
    }

    /**
     * I18N-01 — the status filter's options: code => label in the reader's language. The codes are {@see STATUSES}' keys.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        $out = [];
        foreach (array_keys(self::STATUSES) as $code) {
            $out[$code] = self::statusLabel($code);
        }

        return $out;
    }

    /** I18N-01 — a subscription's state in words, by its stored code (an unknown code reads as itself, capitalised). */
    public static function subscriptionStateLabel(string $state): string
    {
        return match ($state) {
            'trialing' => __('php/support/platform/platform_customers.subscription_trialing'),
            'active' => __('php/support/platform/platform_customers.subscription_active'),
            'past_due' => __('php/support/platform/platform_customers.subscription_past_due'),
            'canceled' => __('php/support/platform/platform_customers.subscription_canceled'),
            'incomplete' => __('php/support/platform/platform_customers.subscription_incomplete'),
            'paused' => __('php/support/platform/platform_customers.subscription_paused'),
            default => ucfirst($state),
        };
    }

    /**
     * The directory — every organisation, with its plan, status, signup source, verified flag, created date, last
     * sign-in and counts. Searchable, filterable, sortable, paginated. ⚠️ Archived organisations are hidden unless
     * the status filter asks for them.
     *
     * Each page item is a row array: {id, name, slug, status, archived, internal, plan, source, verified, properties,
     * members, last_login_at, created_at}.
     *
     * @param  array{search?: ?string, status?: ?string, kind?: ?string, source?: ?string, verified?: ?string, bucket?: ?string, sort?: ?string, dir?: ?string}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Organization::query()->select('organizations.*');
        $this->withFootprint($query);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'archived') {
            $query->onlyTrashed();
        } elseif ($status === 'trialing') {
            // ⚠️ ADMIN 03 · F2 — "On trial" is the SUBSCRIPTION's state: signup never sets an organisation's own status
            //    to `trialing`, so filtering that column returned nobody while every organisation was on a free trial.
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from('subscriptions')
                ->whereColumn('subscriptions.organization_id', 'organizations.id')
                ->where('subscriptions.status', 'trialing')->whereNull('subscriptions.deleted_at'));
        } elseif (array_key_exists($status, self::STATUSES)) {
            $query->where('status', $status);
        }

        // The directory lists every organisation by default (it is a directory, not a statistic); the kind
        // filter narrows it to customers or to Vyrobel's own internal orgs.
        match ((string) ($filters['kind'] ?? '')) {
            'customers' => $query->customers(),
            'internal' => $query->internal(),
            default => null,
        };

        $source = (string) ($filters['source'] ?? '');
        if ($source === 'unknown') {
            $query->whereNull('signup_source');
        } elseif (array_key_exists($source, TenantLifecycle::SIGNUP_SOURCES)) {
            $query->where('signup_source', $source);
        }

        match ((string) ($filters['verified'] ?? '')) {
            'yes' => $query->whereExists($this->verifiedMember()),
            'no' => $query->whereNotExists($this->verifiedMember()),
            default => null,
        };

        $bucket = (string) ($filters['bucket'] ?? '');
        if (array_key_exists($bucket, self::BUCKETS)) {
            $this->applyBucket($query, $bucket);
        }

        // Names read A→Z by default; every other column newest / largest first. `dir` flips either.
        $dirParam = (string) ($filters['dir'] ?? '');
        $desc = $dirParam === 'asc' ? 'asc' : 'desc';
        match ((string) ($filters['sort'] ?? '')) {
            'name' => $query->orderBy('name', $dirParam === 'desc' ? 'desc' : 'asc'),
            'last_login' => $query->orderBy('last_login_at', $desc),
            'properties' => $query->orderBy('properties_count', $desc),
            'users' => $query->orderBy('users_count', $desc),
            default => $query->orderBy('organizations.created_at', $desc),
        };
        $query->orderBy('organizations.id');

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();

        $ids = collect($paginator->items())->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $plans = $this->currentPlanNames($ids);

        return $paginator->through(fn (Organization $org): array => [
            'id' => (string) $org->id,
            'name' => (string) $org->name,
            'slug' => (string) $org->slug,
            'status' => (string) $org->status,
            'archived' => $org->trashed(),
            'internal' => $org->isInternal(),
            'plan' => $plans[(string) $org->id] ?? null,
            'source' => TenantLifecycle::sourceLabel($org->signup_source),
            'source_known' => array_key_exists((string) $org->signup_source, TenantLifecycle::SIGNUP_SOURCES),   // a code, never the label (rule 10)
            'verified' => (int) $org->getAttribute('verified_users') > 0,
            'properties' => (int) $org->getAttribute('properties_count'),
            'members' => (int) $org->getAttribute('users_count'),
            'last_login_at' => $this->asCarbon($org->getAttribute('last_login_at')),
            'created_at' => $org->created_at,
        ]);
    }

    /**
     * The triage counts — per bucket, customers only unless internal organisations are asked for. Archived
     * organisations are never in a queue: they have already been dealt with.
     *
     * @return array<string, array{label: string, definition: string, count: int}>
     */
    public function triage(bool $includeInternal = false): array
    {
        $out = [];
        foreach (array_keys(self::BUCKETS) as $key) {
            $query = Organization::query();
            if (! $includeInternal) {
                $query->customers();
            }
            $this->applyBucket($query, $key);
            $out[$key] = ['label' => self::bucketLabel($key), 'definition' => self::bucketDefinition($key), 'count' => $query->count()];
        }

        return $out;
    }

    /**
     * One organisation's detail — account facts, its subscription, its account holders, the reservation COUNTS a
     * suspension would reach, its lock, and the console's own actions against it. Read-only.
     *
     * @return array<string, mixed>
     */
    public function detail(Organization $org): array
    {
        $subscription = $org->currentSubscription();

        return [
            'org' => $org,
            'subscription' => $subscription,
            'plan_name' => $subscription?->plan?->name,
            'billing_label' => $org->billingStatus()->label(),
            'properties' => (int) DB::table('properties')->where('organization_id', $org->id)->whereNull('deleted_at')->count(),
            'members' => Membership::query()->where('organization_id', $org->id)->where('status', 'active')->count(),
            'users' => $this->accountHolders((string) $org->id),
            'impact' => TenantLifecycle::reservationImpact((string) $org->id),
            'lock' => TenantLifecycle::lockFor($org),
            'source' => TenantLifecycle::sourceLabel($org->signup_source),
            'console_actions' => PlatformAdminAction::query()
                ->with('platformAdmin:id,name')
                ->where('target_type', 'organization')
                ->where('target_id', $org->id)
                ->whereNotIn('action', ['platform.customers.view'])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
        ];
    }

    /**
     * ⚠️ THE ONE DEFINITION of each triage bucket — the counts and the filtered directory both call this.
     *
     * @param  Builder<Organization>  $query
     */
    public function applyBucket(Builder $query, string $bucket): void
    {
        match ($bucket) {
            'unverified' => $query
                ->where('organizations.created_at', '<', CarbonImmutable::now()->subDays(7))
                ->whereNotExists($this->verifiedMember()),
            'never_signed_in' => $query->whereNotExists(
                $this->activeMembers()->whereNotNull('users.last_login_at'),
            ),
            'via_x' => $query->where('signup_source', 'twitter'),
            default => throw new \InvalidArgumentException("Unknown triage bucket [{$bucket}]."),
        };
    }

    /**
     * The organisation's Vyrobel ACCOUNT HOLDERS — staff and owners who sign in. Not guests.
     *
     * @return list<array{name: string, email: string, roles: list<string>, verified: bool, last_login_at: ?Carbon, status: string}>
     */
    private function accountHolders(string $organizationId): array
    {
        $members = DB::table('organization_user')
            ->join('users', 'users.id', '=', 'organization_user.user_id')
            ->where('organization_user.organization_id', $organizationId)
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email', 'users.email_verified_at', 'users.last_login_at', 'organization_user.status']);

        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.organization_id', $organizationId)
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->whereIn('model_has_roles.model_id', $members->pluck('id')->all())
            ->get(['model_has_roles.model_id', 'roles.name'])
            ->groupBy('model_id');

        return $members->map(fn (object $m): array => [
            'name' => (string) $m->name,
            'email' => (string) $m->email,
            'roles' => collect($roles[$m->id] ?? [])->pluck('name')->map(fn ($r): string => RoleLabel::for((string) $r))->sort()->values()->all(),
            'verified' => $m->email_verified_at !== null,
            'last_login_at' => $this->asCarbon($m->last_login_at),
            'status' => (string) $m->status,
        ])->values()->all();
    }

    /**
     * The per-organisation footprint as sub-selects, so the directory can SORT on them and still paginate.
     *
     * @param  Builder<Organization>  $query
     */
    private function withFootprint(Builder $query): void
    {
        $query
            ->selectSub(DB::table('properties')->selectRaw('COUNT(*)')
                ->whereColumn('properties.organization_id', 'organizations.id')->whereNull('properties.deleted_at'), 'properties_count')
            ->selectSub(DB::table('organization_user')->selectRaw('COUNT(*)')
                ->whereColumn('organization_user.organization_id', 'organizations.id')->where('organization_user.status', 'active'), 'users_count')
            ->selectSub($this->activeMembers()->selectRaw('MAX(users.last_login_at)'), 'last_login_at')
            ->selectSub($this->activeMembers()->selectRaw('COUNT(*)')->whereNotNull('users.email_verified_at'), 'verified_users');
    }

    /** The organisation's active members joined to their user row — correlated on `organizations.id`. */
    private function activeMembers(): QueryBuilder
    {
        return DB::table('organization_user')
            ->join('users', 'users.id', '=', 'organization_user.user_id')
            ->whereColumn('organization_user.organization_id', 'organizations.id')
            ->where('organization_user.status', 'active');
    }

    /** "Verified" = at least one active member has confirmed an email address. */
    private function verifiedMember(): QueryBuilder
    {
        return $this->activeMembers()->selectRaw('1')->whereNotNull('users.email_verified_at');
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        return $value === null ? null : Carbon::parse((string) $value);
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

        // ⚠️ No scope bypass here — this only ever runs inside PlatformQuery::read(), which has already lifted
        //    the tenant scope. Called anywhere else, OrganizationScope throws for want of a tenant: loud, not a
        //    silent cross-tenant read (which is what the `withoutGlobalScopes` that used to sit here would do).
        $latest = Subscription::query()
            ->whereIn('organization_id', $ids)
            ->with('plan:id,name')
            ->latest()
            ->get(['id', 'organization_id', 'plan_id', 'status', 'created_at']);

        $out = [];
        foreach ($latest as $subscription) {
            // First (latest) per org wins. ⚠️ ADMIN 03 · F2 — a free trial says so beside the plan: it is the only place
            //    the directory can show it, because the organisation's own status is "Active" throughout a trial.
            if (! isset($out[$subscription->organization_id]) && $subscription->plan !== null) {
                $out[$subscription->organization_id] = $subscription->status === 'trialing'
                    ? __('php/support/platform/platform_customers.plan_on_free_trial', ['plan' => (string) $subscription->plan->name])
                    : (string) $subscription->plan->name;
            }
        }

        return $out;
    }
}

{{-- SUPER ADMIN · BUILD 1 · GATE 3 — the plan catalog (DB-backed, authoritative). Editing a plan's features
     drives real feature-gating (Organization::planAllows reads plan_features). --}}
@php use App\Support\Money\MoneyFormatter; @endphp
<x-admin.shell active="plans" title="Plans"
    subtitle="The subscription catalog — a plan's feature set gates which modules its customers get.">

    @if (session('admin_notice'))
        <div class="ad-flash">{{ session('admin_notice') }}</div>
    @endif
    @if (session('admin_error'))
        <div class="ad-flash ad-flash--error">{{ session('admin_error') }}</div>
    @endif

    @if (auth('admin')->user()?->may('billing.manage'))
        <div style="margin: 0 0 16px;"><a class="ad-btn" href="{{ route('admin.plans.create') }}">New plan</a></div>
    @endif

    <div class="ad-card" style="padding: 8px 10px;">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Price</th>
                    <th>Features</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    <tr>
                        <td>
                            <a class="ad-cell-link" href="{{ route('admin.plans.edit', $plan->id) }}">{{ $plan->name }}</a>
                            <div style="color: var(--ad-faint); font-size: 12px;">{{ $plan->code }} · {{ ucfirst($plan->interval) }}</div>
                        </td>
                        <td>{{ MoneyFormatter::display($plan->price_minor, $plan->currency) }} <span style="color: var(--ad-faint);">/ {{ $plan->interval === 'yearly' ? 'yr' : 'mo' }}</span></td>
                        <td>
                            @foreach ($plan->features as $f)
                                <span class="ad-pill {{ $f->enabled ? 'ad-pill--active' : 'ad-pill--cancelled' }}" style="margin: 2px 3px 2px 0;">
                                    {{ $f->key }}@if ($f->enabled && $f->limit !== null) · {{ $f->limit }}@elseif (! $f->enabled) · off @endif
                                </span>
                            @endforeach
                        </td>
                        <td>
                            @if ($plan->is_active)
                                <span class="ad-pill ad-pill--active">Active</span>
                            @else
                                <span class="ad-pill ad-pill--cancelled">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            {{-- ADMIN 01 · Phase 1 — plan controls only for a role that may change billing; each toggle carries a reason. --}}
                            @if (auth('admin')->user()?->may('billing.manage'))
                                <form method="POST" action="{{ route('admin.plans.toggle', $plan->id) }}" class="ad-inline-form" style="display: inline-flex;" data-guard-submit>
                                    @csrf
                                    <input class="ad-input" name="reason" required minlength="5" maxlength="500" placeholder="Reason" aria-label="Reason" style="width: 150px; flex: 0 0 auto;">
                                    <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ $plan->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="ad-btn ad-btn--ghost ad-btn--sm">Edit</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Audit fix P2-3 — honest about the catalog scope: the tiers are fixed + editable; new tiers are a follow-up. --}}
    <div class="ad-card" style="margin-top: 18px;">
        <h2 class="ad-panel-title">About the catalog</h2>
        <p class="ad-stat-sub" style="line-height: 1.65;">
            These are the current subscription tiers (Essential &amp; Pro, monthly &amp; yearly). You can edit each
            one's price and feature entitlements here, and assign any plan to a customer from their profile. Adding a
            brand-new tier is a planned follow-up — a new tier also needs its place on the public pricing page, which
            arrives with the marketing site.
        </p>
    </div>
</x-admin.shell>

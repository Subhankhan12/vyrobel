{{-- Customer detail: real operational facts + the platform lifecycle actions. #28 — operational facts only (never
     end-guest PII). Break-glass is a separate, later feature.

     ⚠️⚠️ ADMIN 01 · PHASE 1 — "SUSPEND" IS NOW LABELLED FOR WHAT IT DOES: "Close public storefront". Measured: the
     `suspended` status closes the hotel's public booking pages and does NOT stop its staff reaching /app. Every
     action here needs a written reason, and each control appears only for a role that may use it. --}}
@php
    $org = $d['org'];
    $sub = $d['subscription'];
    $me = auth('admin')->user();
    $canTenants = $me?->may('tenants.manage') ?? false;
    $canBilling = $me?->may('billing.manage') ?? false;
@endphp
<x-admin.shell active="customers" :title="$org->name" :subtitle="$org->slug"
    :back="route('admin.customers.index')" back-label="All customers">
    @if (session('admin_notice'))
        <div class="ad-flash">{{ session('admin_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="ad-flash ad-flash--error">{{ $errors->first() }}</div>
    @endif

    <div class="ad-card" style="display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
        <div>
            <div class="ad-label" style="margin-bottom: 8px;">Storefront</div>
            <span class="ad-pill ad-pill--{{ $org->status }}">{{ $org->status === 'suspended' ? 'Storefront closed' : ucfirst($org->status) }}</span>
            @if ($org->isInternal())
                <span class="ad-pill ad-pill--cancelled" style="margin-left: 6px;">Internal</span>
            @endif
        </div>

        @if ($canTenants)
            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 6px; max-width: 360px;">
                @if ($org->status === 'suspended')
                    <form method="POST" action="{{ route('admin.customers.reactivate', $org->id) }}" data-guard-submit style="width: 100%;">
                        @csrf
                        <label class="ad-label" for="reopen_reason">Reason</label>
                        <input id="reopen_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why is the storefront reopening?">
                        <button type="submit" class="ad-btn">Reopen public storefront</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.customers.suspend', $org->id) }}" data-guard-submit style="width: 100%;"
                          onsubmit="return confirm('Close the public storefront? Guests will not be able to book online. Staff can still sign in.');">
                        @csrf
                        {{-- ⚠️ RE-AUTH: the confirm() above is JS only — a direct POST walks past it. This does not. --}}
                        <label class="ad-label" for="suspend_reason">Reason</label>
                        <input id="suspend_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why is the storefront closing?">
                        <label class="ad-label" for="suspend_pw">Confirm with your password</label>
                        <input id="suspend_pw" type="password" name="current_password" class="ad-input" style="margin-bottom: 8px;" autocomplete="current-password" required aria-label="Current password">
                        <button type="submit" class="ad-btn ad-btn--danger">Close public storefront</button>
                    </form>
                @endif
                {{-- Honest about enforcement: this closes online booking only. --}}
                <span style="color: var(--ad-faint); font-size: 11.5px; text-align: right;">
                    Closing the storefront stops guests booking online. It does <strong>not</strong> sign the hotel's staff out or block the back office — a full suspension arrives in a later release.
                </span>
            </div>
        @endif
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        <div class="ad-card">
            <h2 class="ad-panel-title">Account</h2>
            <dl class="ad-dl">
                <dt>Organization</dt><dd>{{ $org->name }}</dd>
                <dt>Slug</dt><dd>{{ $org->slug }}</dd>
                <dt>Status</dt><dd>{{ $org->status === 'suspended' ? 'Storefront closed (suspended)' : ucfirst($org->status) }}</dd>
                <dt>Counted as</dt><dd>{{ $org->isInternal() ? 'Internal — excluded from statistics' : 'Customer' }}</dd>
                <dt>Currency</dt><dd>{{ $org->default_currency }}</dd>
                @if ($org->billing_company)
                    <dt>Billing name</dt><dd>{{ $org->billing_company }}</dd>
                @endif
                @if ($org->billing_email)
                    <dt>Billing email</dt><dd>{{ $org->billing_email }}</dd>
                @endif
                <dt>Joined</dt><dd>{{ $org->created_at?->format('j M Y') ?? '—' }}</dd>
            </dl>

            @if ($canTenants)
                <h2 class="ad-panel-title" style="margin-top: 22px;">Internal or customer</h2>
                <form method="POST" action="{{ route('admin.customers.internal', $org->id) }}" data-guard-submit>
                    @csrf
                    <input type="hidden" name="internal" value="{{ $org->isInternal() ? '0' : '1' }}">
                    <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500"
                           placeholder="{{ $org->isInternal() ? 'Why is this a real customer?' : 'Why is this one of Vyrobel\'s own orgs?' }}" aria-label="Reason">
                    <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ $org->isInternal() ? 'Count as a customer' : 'Mark as internal' }}</button>
                </form>
            @endif
        </div>

        <div class="ad-card">
            <h2 class="ad-panel-title">Subscription</h2>
            <dl class="ad-dl">
                <dt>Plan</dt><dd>{{ $d['plan_name'] ?? 'None' }}</dd>
                <dt>Billing state</dt><dd>{{ $d['billing_label'] }}</dd>
                @if ($sub)
                    <dt>Status</dt><dd>{{ ucfirst($sub->status) }}</dd>
                    @if ($sub->trial_ends_at)
                        <dt>Trial ends</dt><dd>{{ $sub->trial_ends_at->format('j M Y') }}</dd>
                    @endif
                    <dt>Stripe</dt><dd>{{ $sub->stripe_subscription_id ? 'Connected' : 'Not connected (billing account-gated)' }}</dd>
                @endif
            </dl>

            <h2 class="ad-panel-title" style="margin-top: 22px;">Footprint</h2>
            <div class="ad-row"><span class="ad-row-k">Properties</span><span class="ad-row-v">{{ number_format($d['properties']) }}</span></div>
            <div class="ad-row"><span class="ad-row-k">Staff members</span><span class="ad-row-v">{{ number_format($d['members']) }}</span></div>

            @if ($canBilling)
                <h2 class="ad-panel-title" style="margin-top: 22px;">Assign a plan</h2>
                <form method="POST" action="{{ route('admin.customers.assign-plan', $org->id) }}" data-guard-submit>
                    @csrf
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <select aria-label="Plan" name="plan_id" class="ad-input" style="flex: 1; min-width: 200px;" required>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected($sub && $sub->plan_id === $plan->id)>{{ $plan->name }} · {{ ucfirst($plan->interval) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="ad-btn ad-btn--sm">Assign</button>
                    </div>
                    <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason for the plan change">
                </form>
                <p class="ad-stat-sub" style="margin-top: 6px;">Sets the customer's plan &amp; features immediately. Billing (Stripe) is connected separately at go-live.</p>
            @endif
        </div>
    </div>

    {{-- Recent activity for this customer — business/audit events (#28: not guest PII), via the audited read. --}}
    <div class="ad-card" style="margin-top: 18px;">
        <h2 class="ad-panel-title">Recent activity</h2>
        @forelse ($recentActivity as $event)
            <div class="ad-row">
                <span class="ad-row-k" style="font-weight: 600; color: var(--ad-ink);">{{ $event['action'] }}</span>
                <span class="ad-row-v" style="color: var(--ad-muted); font-weight: 400;">{{ ucfirst($event['actor_type']) }} · {{ $event['created_at']?->diffForHumans() ?? '—' }}</span>
            </div>
        @empty
            <div class="ad-empty">No recorded activity for this customer yet.</div>
        @endforelse
    </div>
</x-admin.shell>

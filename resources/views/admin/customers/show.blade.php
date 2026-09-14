{{-- SUPER ADMIN · BUILD 1 · GATE 2 — customer detail: real operational facts + the platform lifecycle actions.
     #28 — operational facts only (never end-guest PII). Impersonation is deliberately NOT offered (deferred). --}}
@php $org = $d['org']; $sub = $d['subscription']; @endphp
<x-admin.shell active="customers" :title="$org->name">
    <a href="{{ route('admin.customers.index') }}" class="ad-back">← All customers</a>

    @if (session('admin_notice'))
        <div class="ad-flash">{{ session('admin_notice') }}</div>
    @endif

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div>
            <div class="ad-eyebrow">Customer</div>
            <h1 class="ad-h1">{{ $org->name }}</h1>
            <p class="ad-lead" style="margin-bottom: 0;">
                <span class="ad-pill ad-pill--{{ $org->status }}">{{ ucfirst($org->status) }}</span>
                <span style="color: var(--ad-faint); margin-left: 8px;">{{ $org->slug }}</span>
            </p>
        </div>
        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 6px;">
            @if ($org->status === 'suspended')
                <form method="POST" action="{{ route('admin.customers.reactivate', $org->id) }}" data-guard-submit>
                    @csrf
                    <button type="submit" class="ad-btn">Reactivate customer</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.customers.suspend', $org->id) }}" onsubmit="return confirm('Suspend {{ $org->name }}? This flags the account as suspended on the platform (it does not yet block their access).');" data-guard-submit>
                    @csrf
                    {{-- ⚠️ RE-AUTH (G3): the `onsubmit` confirm on this form is JS only — a direct POST walks past it.
                         This does not. `current_password:admin`, because /admin runs on the `admin` guard. --}}
                    <label class="ad-field-label" for="suspend_pw">Confirm with your password</label>
                    <input id="suspend_pw" type="password" name="current_password" class="ad-input"
                           autocomplete="current-password" required aria-label="Current password">
                    @error('current_password') <span style="color:#B4400F;font-size:11.5px">{{ $message }}</span> @enderror
                    <button type="submit" class="ad-btn ad-btn--danger">Suspend customer</button>
                </form>
            @endif
            {{-- Audit fix P2-2 — honest about enforcement: suspension is a lifecycle FLAG today, not an access lock. --}}
            <span style="color: var(--ad-faint); font-size: 11.5px; max-width: 230px; text-align: right;">Suspension is a status flag. Blocking the customer's access is a planned follow-up — it doesn't lock them out yet.</span>
        </div>
    </div>

    <div class="ad-grid-2" style="margin-top: 22px;">
        <div class="ad-card">
            <h2 class="ad-panel-title">Account</h2>
            <dl class="ad-dl">
                <dt>Organization</dt><dd>{{ $org->name }}</dd>
                <dt>Slug</dt><dd>{{ $org->slug }}</dd>
                <dt>Status</dt><dd>{{ ucfirst($org->status) }}</dd>
                <dt>Currency</dt><dd>{{ $org->default_currency }}</dd>
                @if ($org->billing_company)
                    <dt>Billing name</dt><dd>{{ $org->billing_company }}</dd>
                @endif
                @if ($org->billing_email)
                    <dt>Billing email</dt><dd>{{ $org->billing_email }}</dd>
                @endif
                <dt>Joined</dt><dd>{{ $org->created_at?->format('j M Y') ?? '—' }}</dd>
            </dl>
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

            {{-- Assign a plan (Gate 3) — sets the plan + features; Stripe billing is a separate go-live step. --}}
            <h2 class="ad-panel-title" style="margin-top: 22px;">Assign a plan</h2>
            <form method="POST" action="{{ route('admin.customers.assign-plan', $org->id) }}" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;" data-guard-submit>
                @csrf
                <select name="plan_id" class="ad-input" style="flex: 1; min-width: 200px;" required>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected($sub && $sub->plan_id === $plan->id)>{{ $plan->name }} · {{ ucfirst($plan->interval) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="ad-btn ad-btn--sm">Assign</button>
            </form>
            <p class="ad-stat-sub" style="margin-top: 10px;">Sets the customer's plan &amp; features immediately. Billing (Stripe) is connected separately at go-live.</p>
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

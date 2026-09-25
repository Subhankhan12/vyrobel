{{-- ADMIN 01 · PHASE 2 — one tenant: account metadata, its account holders, its subscription, and the lifecycle.
     ⚠️ Account metadata only — the users listed are Vyrobel ACCOUNT HOLDERS (staff, owners), never guests; the
     guest numbers are COUNTS, shown so nobody suspends a hotel without seeing whom it affects.
     ⚠️ Every action needs a written reason; suspend and archive also need the admin's password. A protected
     organisation shows why, and each control appears only for a role that may use it. --}}
@php
    $org = $d['org'];
    $sub = $d['subscription'];
    $impact = $d['impact'];
    $lock = $d['lock'];
    $me = auth('admin')->user();
    $canTenants = $me?->may('tenants.manage') ?? false;
    $canBilling = $me?->may('billing.manage') ?? false;
    $archived = $org->trashed();
    $suspended = $org->status === 'suspended';
    $guests = $impact['in_house'] + $impact['upcoming'];
    $statusLabel = $archived ? 'Archived' : (\App\Support\Platform\PlatformCustomers::STATUSES[$org->status] ?? ucfirst($org->status));
@endphp
<x-admin.shell active="customers" :title="$org->name" :subtitle="$org->slug"
    :back="route('admin.customers.index')" back-label="All organisations">

    {{-- ── Lifecycle ─────────────────────────────────────────────────────────────────────────────────────── --}}
    <div class="ad-card">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
            <span class="ad-pill ad-pill--{{ $archived ? 'cancelled' : $org->status }}">{{ $statusLabel }}</span>
            @if ($archived)
                <span class="ad-pill ad-pill--suspended">Suspended underneath</span>
            @endif
            @if ($org->isInternal())
                <span class="ad-pill ad-pill--cancelled">Internal</span>
            @endif
            @if ($lock === 'baseline')
                <span class="ad-hpill ad-hpill--red">Protected baseline — no console action may change it</span>
            @elseif ($lock === 'confirm')
                <span class="ad-hpill ad-hpill--amber">Protected — lifecycle actions need its name typed</span>
            @endif
        </div>

        <p class="ad-stat-sub" style="margin: 0 0 14px; line-height: 1.6;">
            @if ($archived)
                Archived: hidden from the directory, every record kept. Its staff and owners are refused and its storefront is closed.
            @elseif ($suspended)
                Suspended: its staff and owners are refused with a plain explanation, and its public storefront is closed. Guests can still reach their own bookings.
            @else
                Its staff and owners can sign in, and its storefront follows its status.
            @endif
            <strong>{{ $impact['in_house'] }}</strong> guest stay{{ $impact['in_house'] === 1 ? '' : 's' }} in house now ·
            <strong>{{ $impact['upcoming'] }}</strong> upcoming (not yet ended, not cancelled).
            Suspending or archiving touches <strong>no</strong> reservation, folio or payment.
        </p>

        @if ($canTenants && $lock !== 'baseline')
            @php
                // One partial per action would hide the differences; these four are deliberately written out.
                $confirmName = $lock === 'confirm';
            @endphp
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                @if (! $archived && ! $suspended)
                    <form method="POST" action="{{ route('admin.customers.suspend', $org->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="suspend_reason">Suspend — reason</label>
                        <input id="suspend_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why is this organisation being suspended?">
                        @include('admin.customers.partials.lifecycle-confirm', ['id' => 'suspend', 'password' => true])
                        <button type="submit" class="ad-btn ad-btn--danger" style="margin-top: 10px;">Suspend</button>
                    </form>
                @endif

                @if ($suspended && ! $archived)
                    <form method="POST" action="{{ route('admin.customers.reactivate', $org->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="reactivate_reason">Reactivate — reason</label>
                        <input id="reactivate_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why is access being restored?">
                        @include('admin.customers.partials.lifecycle-confirm', ['id' => 'reactivate', 'password' => false, 'noGuests' => true])
                        <button type="submit" class="ad-btn" style="margin-top: 10px;">Reactivate{{ $org->suspended_from_status ? ' (back to '.(\App\Support\Platform\PlatformCustomers::STATUSES[$org->suspended_from_status] ?? $org->suspended_from_status).')' : '' }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.customers.archive', $org->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="archive_reason">Archive — reason</label>
                        <input id="archive_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why is this organisation being archived?">
                        @include('admin.customers.partials.lifecycle-confirm', ['id' => 'archive', 'password' => true])
                        <button type="submit" class="ad-btn ad-btn--danger" style="margin-top: 10px;">Archive</button>
                        <p class="ad-reason-hint" style="margin: 6px 0 0;">Hidden from the directory, every record kept, reversible. There is no permanent delete.</p>
                    </form>
                @endif

                @if ($archived)
                    <form method="POST" action="{{ route('admin.customers.restore', $org->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="restore_reason">Restore from the archive — reason</label>
                        <input id="restore_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why is this organisation coming back?">
                        @include('admin.customers.partials.lifecycle-confirm', ['id' => 'restore', 'password' => false, 'noGuests' => true])
                        <button type="submit" class="ad-btn" style="margin-top: 10px;">Restore (stays suspended)</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        <div class="ad-card">
            <h2 class="ad-panel-title">Account</h2>
            <dl class="ad-dl">
                <dt>Organisation</dt><dd>{{ $org->name }}</dd>
                <dt>Slug</dt><dd>{{ $org->slug }}</dd>
                <dt>Account status</dt><dd>{{ $statusLabel }}</dd>
                <dt>Signed up with</dt><dd>{{ $d['source'] }}</dd>
                <dt>Counted as</dt><dd>{{ $org->isInternal() ? 'Internal — excluded from statistics' : 'Customer' }}</dd>
                <dt>Currency</dt><dd>{{ $org->default_currency }}</dd>
                @if ($org->billing_company)
                    <dt>Billing name</dt><dd>{{ $org->billing_company }}</dd>
                @endif
                @if ($org->billing_email)
                    <dt>Billing email</dt><dd>{{ $org->billing_email }}</dd>
                @endif
                <dt>Created</dt><dd><x-admin.time :at="$org->created_at" date /></dd>
            </dl>

            @if ($canTenants && $lock !== 'baseline')
                <h2 class="ad-panel-title" style="margin-top: 22px;">Internal or customer</h2>
                <form method="POST" action="{{ route('admin.customers.internal', $org->id) }}" data-guard-submit>
                    @csrf
                    <input type="hidden" name="internal" value="{{ $org->isInternal() ? '0' : '1' }}">
                    <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500"
                           placeholder="{{ $org->isInternal() ? 'Why is this a real customer?' : 'Why is this one of Vyrobel\'s own orgs?' }}">
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
                    <dt>Subscription</dt><dd>{{ \App\Support\Platform\PlatformCustomers::SUBSCRIPTION_STATES[$sub->status] ?? ucfirst((string) $sub->status) }}</dd>
                    @if ($sub->trial_ends_at)
                        <dt>Trial ends</dt><dd><x-admin.time :at="$sub->trial_ends_at" date /></dd>
                    @endif
                    <dt>Stripe</dt><dd>{{ $sub->stripe_subscription_id ? 'Billed through Stripe' : 'Not billed through Stripe' }}</dd>
                @endif
            </dl>

            <h2 class="ad-panel-title" style="margin-top: 22px;">Footprint</h2>
            <div class="ad-row"><span class="ad-row-k">Properties</span><span class="ad-row-v">{{ number_format($d['properties']) }}</span></div>
            <div class="ad-row"><span class="ad-row-k">Active users</span><span class="ad-row-v">{{ number_format($d['members']) }}</span></div>

            @if ($canBilling && $lock !== 'baseline')
                <h2 class="ad-panel-title" style="margin-top: 22px;">Assign a plan</h2>
                <form method="POST" action="{{ route('admin.customers.assign-plan', $org->id) }}" data-guard-submit>
                    @csrf
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <select aria-label="Plan" name="plan_id" class="ad-input" style="flex: 1; min-width: 200px;" required>
                            {{-- ⚠️ ADMIN 03 — with no placeholder the FIRST active plan was preselected, so "Assign" without
                                 choosing assigned whatever plan happened to sort first; and with none active it rendered
                                 empty. `required` + value="" makes the choice deliberate (the server refuses empty too). --}}
                            <option value="" @selected(! $sub || ! $plans->contains('id', $sub->plan_id))>
                                {{ $plans->isEmpty() ? 'No plan is active — turn one on under Plans first' : 'Choose a plan…' }}
                            </option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected($sub && $sub->plan_id === $plan->id)>{{ $plan->name }} · {{ ucfirst($plan->interval) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="ad-btn ad-btn--sm">Assign</button>
                    </div>
                    <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason for the plan change">
                </form>
                <p class="ad-stat-sub" style="margin-top: 6px;">Sets this organisation’s plan &amp; features immediately. Billing (Stripe) is connected separately at go-live.</p>
            @endif
        </div>
    </div>

    {{-- ── Account holders — Vyrobel users who sign in to this organisation. Not guests. ─────────────────────── --}}
    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">Users</h2>
        <p class="ad-stat-sub" style="margin: -8px 0 12px;">Vyrobel account holders — this organisation's staff and owners. Never its guests.</p>
        @if ($d['users'] === [])
            <div class="ad-empty">No users.</div>
        @else
            <table class="ad-table">
                <thead><tr><th>Name</th><th>Role</th><th>Email verified</th><th>Last sign-in</th><th>Membership</th></tr></thead>
                <tbody>
                    @foreach ($d['users'] as $u)
                        <tr>
                            <td><strong>{{ $u['name'] }}</strong><div style="font-size: 12px; color: var(--ad-faint);">{{ $u['email'] }}</div></td>
                            <td>{{ $u['roles'] === [] ? '—' : implode(', ', $u['roles']) }}</td>
                            <td>{{ $u['verified'] ? 'Yes' : 'No' }}</td>
                            <td style="color: var(--ad-muted);"><x-admin.time :at="$u['last_login_at']" empty="Never" /></td>
                            <td>{{ ucfirst($u['status']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        {{-- What the CONSOLE has done to this organisation — the append-only ledger, with each reason. --}}
        <div class="ad-card">
            <h2 class="ad-panel-title">Console actions</h2>
            @forelse ($d['console_actions'] as $action)
                <div class="ad-row" style="align-items: flex-start;">
                    <span class="ad-row-k" style="color: var(--ad-ink);">
                        <strong title="{{ $action->action }}">{{ \App\Support\Platform\ActionLabel::platform($action->action) }}</strong>
                        @if ($action->reason)
                            <div style="font-size: 12px; color: var(--ad-muted); font-weight: 400;">“{{ $action->reason }}”</div>
                        @endif
                    </span>
                    <span class="ad-row-v" style="color: var(--ad-muted); font-weight: 400; text-align: right;">
                        {{ $action->platformAdmin?->name ?? 'CLI' }}<br><x-admin.time :at="$action->created_at" />
                    </span>
                </div>
            @empty
                <div class="ad-empty">The console has not acted on this organisation.</div>
            @endforelse
        </div>

        {{-- Recent activity inside the tenant — business/audit events (#28: not guest PII), via the audited read. --}}
        <div class="ad-card">
            <h2 class="ad-panel-title">Recent activity</h2>
            @forelse ($recentActivity as $event)
                <div class="ad-row">
                    <span class="ad-row-k" style="font-weight: 600; color: var(--ad-ink);" title="{{ $event['action'] }}">{{ \App\Support\Platform\ActionLabel::event($event['action']) }}</span>
                    <span class="ad-row-v" style="color: var(--ad-muted); font-weight: 400;">{{ ucfirst($event['actor_type']) }} · <x-admin.time :at="$event['created_at']" /></span>
                </div>
            @empty
                <div class="ad-empty">No recorded activity for this organisation yet.</div>
            @endforelse
        </div>
    </div>
</x-admin.shell>

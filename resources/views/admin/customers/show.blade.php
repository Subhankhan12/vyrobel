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
    $statusLabel = \App\Support\Platform\PlatformCustomers::statusLabel($archived ? 'archived' : (string) $org->status);
@endphp
<x-admin.shell active="customers" :title="$org->name" :subtitle="$org->slug"
    :back="route('admin.customers.index')" :back-label="__('admin/customers/show.back_label')">

    {{-- ── Lifecycle ─────────────────────────────────────────────────────────────────────────────────────── --}}
    <div class="ad-card">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
            <span class="ad-pill ad-pill--{{ $archived ? 'cancelled' : $org->status }}">{{ $statusLabel }}</span>
            @if ($archived)
                <span class="ad-pill ad-pill--suspended">{{ __('admin/customers/show.pill_suspended_underneath') }}</span>
            @endif
            @if ($org->isInternal())
                <span class="ad-pill ad-pill--cancelled">{{ __('admin/customers/show.pill_internal') }}</span>
            @endif
            @if ($lock === 'baseline')
                <span class="ad-hpill ad-hpill--red">{{ __('admin/customers/show.lock_baseline') }}</span>
            @elseif ($lock === 'confirm')
                <span class="ad-hpill ad-hpill--amber">{{ __('admin/customers/show.lock_confirm') }}</span>
            @endif
        </div>

        <p class="ad-stat-sub" style="margin: 0 0 14px; line-height: 1.6;">
            @if ($archived)
                {{ __('admin/customers/show.state_archived') }}
            @elseif ($suspended)
                {{ __('admin/customers/show.state_suspended') }}
            @else
                {{ __('admin/customers/show.state_active') }}
            @endif
            {!! trans_choice('admin/customers/show.impact_counts', $impact['in_house'], ['count' => e((string) $impact['in_house']), 'upcoming' => e((string) $impact['upcoming'])]) !!}
            {!! __('admin/customers/show.impact_untouched') !!}
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
                        <label class="ad-label" for="suspend_reason">{{ __('admin/customers/show.suspend_reason_label') }}</label>
                        <input id="suspend_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/customers/show.suspend_reason_placeholder') }}">
                        @include('admin.customers.partials.lifecycle-confirm', ['id' => 'suspend', 'password' => true])
                        <button type="submit" class="ad-btn ad-btn--danger" style="margin-top: 10px;">{{ __('admin/customers/show.suspend_button') }}</button>
                    </form>
                @endif

                @if ($suspended && ! $archived)
                    <form method="POST" action="{{ route('admin.customers.reactivate', $org->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="reactivate_reason">{{ __('admin/customers/show.reactivate_reason_label') }}</label>
                        <input id="reactivate_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/customers/show.reactivate_reason_placeholder') }}">
                        @include('admin.customers.partials.lifecycle-confirm', ['id' => 'reactivate', 'password' => false, 'noGuests' => true])
                        <button type="submit" class="ad-btn" style="margin-top: 10px;">{{ $org->suspended_from_status ? __('admin/customers/show.reactivate_button_back_to', ['status' => isset(\App\Support\Platform\PlatformCustomers::STATUSES[$org->suspended_from_status]) ? \App\Support\Platform\PlatformCustomers::statusLabel($org->suspended_from_status) : $org->suspended_from_status]) : __('admin/customers/show.reactivate_button') }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.customers.archive', $org->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="archive_reason">{{ __('admin/customers/show.archive_reason_label') }}</label>
                        <input id="archive_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/customers/show.archive_reason_placeholder') }}">
                        @include('admin.customers.partials.lifecycle-confirm', ['id' => 'archive', 'password' => true])
                        <button type="submit" class="ad-btn ad-btn--danger" style="margin-top: 10px;">{{ __('admin/customers/show.archive_button') }}</button>
                        <p class="ad-reason-hint" style="margin: 6px 0 0;">{{ __('admin/customers/show.archive_hint') }}</p>
                    </form>
                @endif

                @if ($archived)
                    <form method="POST" action="{{ route('admin.customers.restore', $org->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="restore_reason">{{ __('admin/customers/show.restore_reason_label') }}</label>
                        <input id="restore_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/customers/show.restore_reason_placeholder') }}">
                        @include('admin.customers.partials.lifecycle-confirm', ['id' => 'restore', 'password' => false, 'noGuests' => true])
                        <button type="submit" class="ad-btn" style="margin-top: 10px;">{{ __('admin/customers/show.restore_button') }}</button>
                    </form>
                @endif
            </div>
        @endif

        {{-- ⚠️⚠️ ADMIN 05 — DELETE PERMANENTLY (the founder's ruling, 2026-09-26). Owner only, a suspended or archived
             organisation, never a protected one. It says exactly what goes BEFORE asking for anything, and shows the
             refusal instead of the form while something makes deletion unsafe. --}}
        @if ($deletePreview)
            <div class="ad-danger-zone" style="margin-top: 20px;">
                <h2 class="ad-panel-title" style="color: var(--ad-danger);">{{ __('admin/customers/show.delete_heading') }}</h2>
                @php
                    // the count and its noun come from ONE plural key, so every language picks its own form
                    $count = fn (string $key, string $transKey): string => trans_choice($transKey, $deletePreview[$key], ['count' => number_format($deletePreview[$key])]);
                @endphp
                {{-- ⚠️⚠️ ADMIN 06 (#3b) — EVERY row the deletion removes, counted from the SAME plan it runs. 🔬 It used to
                     count only the tables with an organisation column: 17 of a signup's 232 rows. --}}
                <p class="ad-stat-sub" style="margin: 0 0 8px; line-height: 1.6;">
                    {!! __('admin/customers/show.delete_erases', ['rows' => e($count('rows', 'admin/customers/show.n_records')), 'tables' => e($count('tables', 'admin/customers/show.n_tables'))]) !!}
                </p>
                <ul class="ad-stat-sub" style="margin: 0 0 12px; padding-left: 18px; line-height: 1.6;">
                    <li>
                        @if ($deletePreview['own'] > 0)
                            {{ __('admin/customers/show.delete_own_including', ['own' => $count('own', 'admin/customers/show.n_records'), 'reservations' => $count('reservations', 'admin/customers/show.n_reservations'), 'guests' => $count('guests', 'admin/customers/show.n_guests'), 'payments' => $count('payments', 'admin/customers/show.n_payments'), 'invoices' => $count('invoices', 'admin/customers/show.n_invoices')]) }}
                        @else
                            {{ __('admin/customers/show.delete_own_none', ['own' => $count('own', 'admin/customers/show.n_records')]) }}
                        @endif
                    </li>
                    <li>{{ __('admin/customers/show.delete_linked', ['linked' => $count('linked', 'admin/customers/show.n_records')]) }}</li>
                    <li>{{ trans_choice('admin/customers/show.delete_users', $deletePreview['users_deleted'], ['count' => number_format($deletePreview['users_deleted']), 'records' => $count('accounts', 'admin/customers/show.n_records')]) }}</li>
                    <li>{{ trans_choice('admin/customers/show.delete_files', $deletePreview['files'], ['count' => number_format($deletePreview['files'])]) }}</li>
                </ul>
                @if ($deletePreview['per_table'] !== [])
                    <details class="ad-stat-sub" style="margin: 0 0 12px;">
                        <summary style="cursor: pointer;">{{ __('admin/customers/show.delete_every_table', ['number' => count($deletePreview['per_table'])]) }}</summary>
                        <ul style="margin: 8px 0 0; padding-left: 18px; columns: 2; column-gap: 24px; line-height: 1.6;">
                            @foreach ($deletePreview['per_table'] as $table => $rows)
                                <li>{{ str_replace('_', ' ', $table) }} — {{ number_format($rows) }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                <p class="ad-stat-sub" style="margin: 0 0 12px; line-height: 1.6;">
                    @if ($deletePreview['users_kept'] > 0)
                        {{ trans_choice('admin/customers/show.delete_users_kept', $deletePreview['users_kept'], ['count' => number_format($deletePreview['users_kept'])]) }}
                    @endif
                    @if ($guests > 0)
                        {!! trans_choice('admin/customers/show.delete_guests_affected', $guests, ['count' => e((string) $guests)]) !!}
                    @endif
                    {!! __('admin/customers/show.delete_cannot_undo') !!}
                </p>
                @if ($deletePreview['refusal'])
                    <div class="ad-flash ad-flash--error" role="status">{{ $deletePreview['refusal'] }}</div>
                @else
                    <form method="POST" action="{{ route('admin.customers.delete', $org->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="delete_reason">{{ __('admin/customers/show.delete_reason_label') }}</label>
                        <input id="delete_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/customers/show.delete_reason_placeholder') }}" autocomplete="off">
                        <label class="ad-label" for="delete_confirm">{!! __('admin/customers/show.type_name_to_confirm', ['name' => e($org->name)]) !!}</label>
                        <input id="delete_confirm" class="ad-input" name="confirm_name" required autocomplete="off">
                        <label class="ad-label" for="delete_pw">{{ __('admin/customers/show.your_password') }}</label>
                        <input id="delete_pw" type="password" name="current_password" class="ad-input" autocomplete="current-password" required>
                        <label class="ad-switch-row" style="margin-top: 14px;">
                            <input type="checkbox" name="acknowledge_delete" value="1" required>
                            <span>{{ __('admin/customers/show.delete_acknowledge') }}</span>
                        </label>
                        <button type="submit" class="ad-btn ad-btn--destroy" style="margin-top: 12px;">{{ __('admin/customers/show.delete_button') }}</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/customers/show.account_heading') }}</h2>
            <dl class="ad-dl">
                <dt>{{ __('admin/customers/show.dt_organisation') }}</dt><dd>{{ $org->name }}</dd>
                <dt>{{ __('admin/customers/show.dt_slug') }}</dt><dd>{{ $org->slug }}</dd>
                <dt>{{ __('admin/customers/show.dt_account_status') }}</dt><dd>{{ $statusLabel }}</dd>
                <dt>{{ __('admin/customers/show.dt_signed_up_with') }}</dt><dd>{{ $d['source'] }}</dd>
                <dt>{{ __('admin/customers/show.dt_counted_as') }}</dt><dd>{{ $org->isInternal() ? __('admin/customers/show.counted_internal') : __('admin/customers/show.counted_customer') }}</dd>
                <dt>{{ __('admin/customers/show.dt_country') }}</dt><dd>{{ \App\Support\Countries::name($org->country) ?? __('admin/customers/show.country_not_set') }}</dd>
                <dt>{{ __('admin/customers/show.dt_language') }}</dt><dd>{{ strtoupper((string) ($org->default_locale ?? 'en')) }}</dd>
                <dt>{{ __('admin/customers/show.dt_currency') }}</dt><dd>{{ $org->default_currency }}</dd>
                @if ($org->billing_company)
                    <dt>{{ __('admin/customers/show.dt_billing_name') }}</dt><dd>{{ $org->billing_company }}</dd>
                @endif
                @if ($org->billing_email)
                    <dt>{{ __('admin/customers/show.dt_billing_email') }}</dt><dd>{{ $org->billing_email }}</dd>
                @endif
                <dt>{{ __('admin/customers/show.dt_created') }}</dt><dd><x-admin.time :at="$org->created_at" date /></dd>
            </dl>

            @if ($canTenants && $lock !== 'baseline')
                <h2 class="ad-panel-title" style="margin-top: 22px;">{{ __('admin/customers/show.internal_heading') }}</h2>
                <form method="POST" action="{{ route('admin.customers.internal', $org->id) }}" data-guard-submit>
                    @csrf
                    <input type="hidden" name="internal" value="{{ $org->isInternal() ? '0' : '1' }}">
                    <input aria-label="{{ __('admin/customers/show.reason_label') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500"
                           placeholder="{{ $org->isInternal() ? __('admin/customers/show.internal_placeholder_to_customer') : __('admin/customers/show.internal_placeholder_to_internal') }}">
                    <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ $org->isInternal() ? __('admin/customers/show.count_as_customer') : __('admin/customers/show.mark_as_internal') }}</button>
                </form>

                {{-- ADMIN 04 — edit the details. Currency and slug are deliberately not here (money / links). --}}
                <details style="margin-top: 22px;" @if ($errors->hasAny(['name', 'country', 'default_locale', 'billing_email', 'billing_company', 'billing_vat_id', 'billing_city', 'details'])) open @endif>
                    <summary class="ad-panel-title" style="cursor: pointer;">{{ __('admin/customers/show.edit_details') }}</summary>
                    <form method="POST" action="{{ route('admin.customers.update-details', $org->id) }}" data-guard-submit style="margin-top: 10px;">
                        @csrf
                        <label class="ad-label" for="d_name">{{ __('admin/customers/show.organisation_name') }}</label>
                        <input id="d_name" class="ad-input" name="name" required maxlength="255" value="{{ old('name', $org->name) }}" autocomplete="off" @error('name') aria-invalid="true" @enderror>
                        <label class="ad-label" for="d_country">{{ __('admin/customers/show.dt_country') }}</label>
                        <select id="d_country" class="ad-input" name="country" data-searchable data-flags>
                            <option value="">{{ __('admin/customers/show.country_option_not_set') }}</option>
                            @foreach (\App\Support\Countries::options() as $code => $countryName)
                                <option value="{{ $code }}" @selected(old('country', $org->country) === $code)>{{ $countryName }}</option>
                            @endforeach
                        </select>
                        <fieldset class="ad-choices" style="margin-top: 16px;">
                            <legend class="ad-label">{{ __('admin/customers/show.dt_language') }}</legend>
                            @php($currentLocale = (string) old('default_locale', $org->default_locale ?? 'en'))
                            @if ($currentLocale !== '' && ! in_array($currentLocale, \App\Support\I18n\Locales::offered(), true))
                                <label class="ad-choice"><input type="radio" name="default_locale" value="{{ $currentLocale }}" required checked><span>{{ strtoupper($currentLocale) }} <small>{{ __('admin/customers/show.locale_current') }}</small></span></label>
                            @endif
                            @foreach (\App\Support\I18n\Locales::offered() as $locale)
                                <label class="ad-choice"><input type="radio" name="default_locale" value="{{ $locale }}" required @checked($currentLocale === $locale)><span>{{ strtoupper($locale) }}</span></label>
                            @endforeach
                        </fieldset>
                        <label class="ad-label" for="d_billing_email">{{ __('admin/customers/show.dt_billing_email') }}</label>
                        <input id="d_billing_email" type="email" class="ad-input" name="billing_email" maxlength="255" value="{{ old('billing_email', $org->billing_email) }}" autocomplete="off" @error('billing_email') aria-invalid="true" @enderror>
                        <label class="ad-label" for="d_billing_company">{{ __('admin/customers/show.dt_billing_name') }}</label>
                        <input id="d_billing_company" class="ad-input" name="billing_company" maxlength="255" value="{{ old('billing_company', $org->billing_company) }}" autocomplete="off" @error('billing_company') aria-invalid="true" @enderror>
                        <label class="ad-label" for="d_vat">{{ __('admin/customers/show.vat_id') }}</label>
                        <input id="d_vat" class="ad-input" name="billing_vat_id" maxlength="255" value="{{ old('billing_vat_id', $org->billing_vat_id) }}" autocomplete="off" @error('billing_vat_id') aria-invalid="true" @enderror>
                        <label class="ad-label" for="d_city">{{ __('admin/customers/show.billing_city') }}</label>
                        <input id="d_city" class="ad-input" name="billing_city" maxlength="120" value="{{ old('billing_city', $org->billing_city) }}" autocomplete="off" @error('billing_city') aria-invalid="true" @enderror>
                        <label class="ad-label" for="details_reason">{{ __('admin/customers/show.reason_label') }}</label>
                        <input id="details_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/customers/show.details_reason_placeholder') }}" autocomplete="off">
                        @if ($lock === 'confirm')
                            <label class="ad-label" for="details_confirm">{!! __('admin/customers/show.details_protected_confirm', ['name' => e($org->name)]) !!}</label>
                            <input id="details_confirm" class="ad-input" name="confirm_name" required autocomplete="off">
                        @endif
                        <button type="submit" class="ad-btn ad-btn--sm" style="margin-top: 10px;">{{ __('admin/customers/show.save_details') }}</button>
                        <p class="ad-reason-hint" style="margin: 6px 0 0;">{{ __('admin/customers/show.details_fixed_hint', ['currency' => $org->default_currency, 'slug' => $org->slug]) }}</p>
                    </form>
                </details>
            @endif
        </div>

        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/customers/show.subscription_heading') }}</h2>
            <dl class="ad-dl">
                <dt>{{ __('admin/customers/show.dt_plan') }}</dt><dd>{{ $d['plan_name'] ?? __('admin/customers/show.plan_none') }}</dd>
                <dt>{{ __('admin/customers/show.dt_billing_state') }}</dt><dd>{{ $d['billing_label'] }}</dd>
                @if ($sub)
                    <dt>{{ __('admin/customers/show.dt_subscription') }}</dt><dd>{{ \App\Support\Platform\PlatformCustomers::subscriptionStateLabel((string) $sub->status) }}</dd>
                    @if ($sub->trial_ends_at)
                        <dt>{{ __('admin/customers/show.dt_trial_ends') }}</dt><dd><x-admin.time :at="$sub->trial_ends_at" date /></dd>
                    @endif
                    <dt>{{ __('admin/customers/show.dt_stripe') }}</dt><dd>{{ $sub->stripe_subscription_id ? __('admin/customers/show.stripe_billed') : __('admin/customers/show.stripe_not_billed') }}</dd>
                @endif
            </dl>

            <h2 class="ad-panel-title" style="margin-top: 22px;">{{ __('admin/customers/show.footprint_heading') }}</h2>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/customers/show.footprint_properties') }}</span><span class="ad-row-v">{{ number_format($d['properties']) }}</span></div>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/customers/show.footprint_active_users') }}</span><span class="ad-row-v">{{ number_format($d['members']) }}</span></div>

            @if ($canBilling && $lock !== 'baseline')
                <h2 class="ad-panel-title" style="margin-top: 22px;">{{ __('admin/customers/show.assign_plan_heading') }}</h2>
                <form method="POST" action="{{ route('admin.customers.assign-plan', $org->id) }}" data-guard-submit>
                    @csrf
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <select aria-label="{{ __('admin/customers/show.plan_label') }}" name="plan_id" class="ad-input" style="flex: 1; min-width: 200px;" required>
                            {{-- ⚠️ ADMIN 03 — with no placeholder the FIRST active plan was preselected, so "Assign" without
                                 choosing assigned whatever plan happened to sort first; and with none active it rendered
                                 empty. `required` + value="" makes the choice deliberate (the server refuses empty too). --}}
                            <option value="" @selected(! $sub || ! $plans->contains('id', $sub->plan_id))>
                                {{ $plans->isEmpty() ? __('admin/customers/show.plan_none_active') : __('admin/customers/show.plan_choose') }}
                            </option>
                            @foreach ($plans as $plan)
                                @php($intervalLabel = match ($plan->interval) {
                                    'monthly' => __('admin/customers/show.interval_monthly'),
                                    'yearly' => __('admin/customers/show.interval_yearly'),
                                    default => ucfirst((string) $plan->interval),
                                })
                                <option value="{{ $plan->id }}" @selected($sub && $sub->plan_id === $plan->id)>{{ __('admin/customers/show.plan_option', ['plan' => $plan->name, 'interval' => $intervalLabel]) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="ad-btn ad-btn--sm">{{ __('admin/customers/show.assign_button') }}</button>
                    </div>
                    <input aria-label="{{ __('admin/customers/show.reason_label') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/customers/show.plan_reason_placeholder') }}">
                </form>
                <p class="ad-stat-sub" style="margin-top: 6px;">{{ __('admin/customers/show.assign_plan_hint') }}</p>
            @endif
        </div>
    </div>

    {{-- ── Account holders — Vyrobel users who sign in to this organisation. Not guests. ─────────────────────── --}}
    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">{{ __('admin/customers/show.users_heading') }}</h2>
        <p class="ad-stat-sub" style="margin: -8px 0 12px;">{{ __('admin/customers/show.users_intro') }}</p>
        @if ($d['users'] === [])
            <div class="ad-empty">{{ __('admin/customers/show.users_empty') }}</div>
        @else
            <table class="ad-table">
                <thead><tr><th>{{ __('common.name') }}</th><th>{{ __('admin/customers/show.th_role') }}</th><th>{{ __('admin/customers/show.th_email_verified') }}</th><th>{{ __('admin/customers/show.th_last_sign_in') }}</th><th>{{ __('admin/customers/show.th_membership') }}</th></tr></thead>
                <tbody>
                    @foreach ($d['users'] as $u)
                        <tr>
                            <td><strong>{{ $u['name'] }}</strong><div style="font-size: 12px; color: var(--ad-faint);">{{ $u['email'] }}</div></td>
                            <td>{{ $u['roles'] === [] ? '—' : implode(', ', $u['roles']) }}</td>
                            <td>{{ $u['verified'] ? __('common.yes') : __('common.no') }}</td>
                            <td style="color: var(--ad-muted);"><x-admin.time :at="$u['last_login_at']" :empty="__('admin/customers/show.last_sign_in_never')" /></td>
                            <td>{{ match ($u['status']) {
                                'invited' => __('admin/customers/show.membership_invited'),
                                'active' => __('admin/customers/show.membership_active'),
                                'disabled' => __('admin/customers/show.membership_disabled'),
                                default => ucfirst($u['status']),
                            } }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ── ADMIN 04 — sign-in activity: when this organisation's people last signed in, who, from where, on what.
         The platform's own security record (the Activity page shows the same rows platform-wide). Never a guest. ── --}}
    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">{{ __('admin/customers/show.sign_ins_heading') }}</h2>
        <p class="ad-stat-sub" style="margin: -8px 0 12px;">
            {{ trans_choice('admin/customers/show.sign_ins_intro', $signIns['window_days'], ['count' => $signIns['window_days']]) }}
        </p>
        @if ($signIns['last'])
            <div class="ad-row" style="margin-bottom: 12px;">
                <span class="ad-row-k" style="color: var(--ad-ink);"><strong>{{ __('admin/customers/show.last_sign_in') }}</strong></span>
                <span class="ad-row-v" style="text-align: right;">
                    <x-admin.time :at="$signIns['last']['at']" /> · {{ $signIns['last']['name'] }}
                    <div style="font-size: 12px; color: var(--ad-muted); font-weight: 400;">{{ $signIns['last']['device'] }} · {{ __('admin/customers/show.ip_value', ['ip' => $signIns['last']['ip'] ?? '—']) }}</div>
                </span>
            </div>
        @endif
        @if ($signIns['rows'] === [])
            <div class="ad-empty">{{ trans_choice('admin/customers/show.sign_ins_empty', $signIns['window_days'], ['count' => $signIns['window_days']]) }}</div>
        @else
            <table class="ad-table">
                <thead><tr><th>{{ __('admin/customers/show.th_when') }}</th><th>{{ __('admin/customers/show.th_event') }}</th><th>{{ __('admin/customers/show.th_who') }}</th><th>{{ __('admin/customers/show.th_device') }}</th><th>{{ __('admin/customers/show.th_ip') }}</th></tr></thead>
                <tbody>
                    @foreach ($signIns['rows'] as $row)
                        <tr>
                            <td style="color: var(--ad-muted); white-space: nowrap;"><x-admin.time :at="$row['at']" /></td>
                            <td><span class="ad-pill {{ $row['event'] === 'login' ? 'ad-pill--active' : 'ad-pill--suspended' }}">{{ $row['label'] }}</span></td>
                            <td><strong>{{ $row['name'] }}</strong><div style="font-size: 12px; color: var(--ad-faint);">{{ $row['email'] }}</div></td>
                            <td>{{ $row['device'] }}</td>
                            <td style="color: var(--ad-faint); font-variant-numeric: tabular-nums;">{{ $row['ip'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        {{-- What the CONSOLE has done to this organisation — the append-only ledger, with each reason. --}}
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/customers/show.console_actions_heading') }}</h2>
            @forelse ($d['console_actions'] as $action)
                <div class="ad-row" style="align-items: flex-start;">
                    <span class="ad-row-k" style="color: var(--ad-ink);">
                        <strong title="{{ $action->action }}">{{ \App\Support\Platform\ActionLabel::platform($action->action) }}</strong>
                        @if ($action->reason)
                            <div style="font-size: 12px; color: var(--ad-muted); font-weight: 400;">{{ __('admin/customers/show.quoted_reason', ['reason' => $action->reason]) }}</div>
                        @endif
                    </span>
                    <span class="ad-row-v" style="color: var(--ad-muted); font-weight: 400; text-align: right;">
                        {{ $action->platformAdmin?->name ?? __('admin/customers/show.actor_cli') }}<br><x-admin.time :at="$action->created_at" />
                    </span>
                </div>
            @empty
                <div class="ad-empty">{{ __('admin/customers/show.console_actions_empty') }}</div>
            @endforelse
        </div>

        {{-- Recent activity inside the tenant — business/audit events (#28: not guest PII), via the audited read. --}}
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/customers/show.recent_activity_heading') }}</h2>
            @forelse ($recentActivity as $event)
                <div class="ad-row">
                    <span class="ad-row-k" style="font-weight: 600; color: var(--ad-ink);" title="{{ $event['action'] }}">{{ \App\Support\Platform\ActionLabel::event($event['action']) }}</span>
                    <span class="ad-row-v" style="color: var(--ad-muted); font-weight: 400;">{{ match ($event['actor_type']) {
                        'user' => __('admin/customers/show.actor_user'),
                        'agent' => __('admin/customers/show.actor_agent'),
                        'system' => __('admin/customers/show.actor_system'),
                        default => ucfirst($event['actor_type']),
                    } }} · <x-admin.time :at="$event['created_at']" /></span>
                </div>
            @empty
                <div class="ad-empty">{{ __('admin/customers/show.recent_activity_empty') }}</div>
            @endforelse
        </div>
    </div>
</x-admin.shell>

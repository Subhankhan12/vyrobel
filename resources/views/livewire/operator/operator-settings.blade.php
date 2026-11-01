{{-- Operator PROPERTY SETTINGS (task #98) — matched to `Operator Property Settings.html`. Sections are REAL,
     backed by the certified SettingsService (per-section save; the app's established pattern — no toast-only fake
     save). Owner commission is the REAL 18% (never the wireframe's 15%); the guest-brand live preview mirrors the
     shared email layout; the self-check-in DOOR CODE is guest-only (#33) and never shown here.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="settings" :title="__('livewire/operator/operator-settings.page_title')" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('settings_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('settings_notice') }} <span style="color: var(--vy-muted);">{{ __('livewire/operator/operator-settings.changes_live_now') }}</span></div>
            </div>
        @endif
        @if (session('settings_error'))
            <div class="vy-card vy-fade" style="border-left: 3px solid rgb(180,68,58); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: rgb(150,50,42);">{{ session('settings_error') }}</div>
            </div>
        @endif

        @if ($readOnly)
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-amber); margin-bottom: 16px;">
                <div style="font-size: 13px; color: var(--vy-ink);">{{ __('livewire/operator/operator-settings.read_only_notice') }}</div>
            </div>
        @endif

        @unless ($hasProperty)
            <div class="vy-card vy-fade" style="text-align: center; padding: 40px 24px;">
                <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">{{ __('livewire/operator/operator-settings.no_property_heading') }}</strong>
                <span style="font-size: 13px; color: var(--vy-muted);">{{ __('livewire/operator/operator-settings.no_property_body') }}</span>
            </div>
        @else
            @php $disabled = $readOnly ? 'disabled' : ''; @endphp

            {{-- ── 1 · PROPERTY BASICS — REAL (properties columns) ─────────────────────────────────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 14px;">{{ __('livewire/operator/operator-settings.basics_heading') }}</h2>
                <form method="POST" action="{{ route('app.settings.property') }}">
                    @csrf
                    <div class="vyn-grid3">
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_name">{{ __('livewire/operator/operator-settings.display_name') }}</label>
                            <input id="p_name" type="text" name="name" class="vy-input" value="{{ old('name', $property['name']) }}" required {{ $disabled }}>
                            @error('name') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_ci">{{ __('livewire/operator/operator-settings.check_in_from') }}</label>
                            <input id="p_ci" type="time" name="check_in_time" class="vy-input" value="{{ old('check_in_time', $property['check_in_time']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_co">{{ __('livewire/operator/operator-settings.check_out_by') }}</label>
                            <input id="p_co" type="time" name="check_out_time" class="vy-input" value="{{ old('check_out_time', $property['check_out_time']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_addr">{{ __('livewire/operator/operator-settings.address') }}</label>
                            <input id="p_addr" type="text" name="address_line" class="vy-input" placeholder="{{ __('livewire/operator/operator-settings.street_placeholder') }}" value="{{ old('address_line', $property['address_line']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_city">{{ __('livewire/operator/operator-settings.city') }}</label>
                            <input id="p_city" type="text" name="city" class="vy-input" value="{{ old('city', $property['city']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_country">{{ __('livewire/operator/operator-settings.country') }}</label>
                            <input id="p_country" type="text" name="country" class="vy-input" value="{{ old('country', $property['country']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_tz">{{ __('livewire/operator/operator-settings.time_zone') }}</label>
                            <select id="p_tz" name="timezone" class="vy-input" {{ $disabled }}>
                                @foreach ($timezones as $tz)
                                    <option value="{{ $tz }}" @selected(old('timezone', $property['timezone']) === $tz)>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_star">{{ __('livewire/operator/operator-settings.star_rating') }} <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.optional_hint') }}</span></label>
                            <input id="p_star" type="number" name="star_rating" class="vy-input" min="1" max="5" value="{{ old('star_rating', $property['star_rating']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_status">{{ __('common.status') }}</label>
                            <select id="p_status" name="status" class="vy-input" {{ $disabled }}>
                                <option value="active" @selected(old('status', $property['status']) === 'active')>{{ __('livewire/operator/operator-settings.property_status_active') }}</option>
                                <option value="inactive" @selected(old('status', $property['status']) === 'inactive')>{{ __('livewire/operator/operator-settings.property_status_inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    @unless ($readOnly)
                        <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">{{ __('livewire/operator/operator-settings.save_basics') }}</button>
                        </div>
                    @endunless
                </form>
            </div>

            {{-- ── 2 · POLICIES — REAL (property_policies) · door code stays GUEST-only (#33) ───────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-settings.policies_heading') }}</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">{{ __('livewire/operator/operator-settings.policies_intro') }}</p>
                <form method="POST" action="{{ route('app.settings.policies') }}">
                    @csrf
                    <div class="vy-field" style="margin: 0 0 14px;">
                        <label class="vy-field-label">{{ __('livewire/operator/operator-settings.cancellation_policy') }} <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.cancellation_policy_hint') }}</span></label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @foreach (['flexible' => __('livewire/operator/operator-settings.cancellation_flexible'), 'moderate' => __('livewire/operator/operator-settings.cancellation_moderate'), 'strict' => __('livewire/operator/operator-settings.cancellation_strict')] as $val => $label)
                                {{-- ⚠️ The selected state used to live in a SECOND `style` attribute on this label.
                                     HTML keeps the first duplicate attribute and discards the rest, so it never
                                     applied — and because the radio itself is `opacity:0`, there was NO visible
                                     indicator of which policy was active. One style attribute, appended
                                     conditionally. `aria-checked` is on the radio, which stays the accessible
                                     source of truth; the ring is the visual one (never colour alone — the
                                     selected option also gets a heavier border and a tint). --}}
                                @php $vySel = old('cancellation_type', $policy['cancellation_type']) === $val; @endphp
                                <label style="flex: 1; min-width: 90px; text-align: center; padding: 9px 12px; border: 1px solid {{ $vySel ? 'var(--vy-accent)' : 'var(--vy-hairline)' }}; border-radius: var(--vy-r-sm); font-size: 13px; cursor: pointer; color: {{ $vySel ? 'var(--vy-accent-text)' : 'var(--vy-ink)' }};{{ $vySel ? ' background: rgba(194,65,12,.06); font-weight: 600; box-shadow: inset 0 0 0 1px var(--vy-accent);' : '' }}">
                                    <input type="radio" name="cancellation_type" value="{{ $val }}" @checked($vySel) style="position:absolute;opacity:0;" {{ $disabled }}>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    {{-- The value the free-cancellation promise ACTUALLY hangs on: CancellationPolicy::freeUntil()
                         and the storefront both read `free_until_days`. It was previously unsettable from here,
                         so the card claimed to control cancellation while controlling nothing. --}}
                    <div class="vy-field" style="margin: 0 0 14px;">
                        <label class="vy-field-label" for="pol_free_days">{{ __('livewire/operator/operator-settings.free_cancel_window') }} <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.optional_hint') }}</span></label>
                        <input id="pol_free_days" type="number" name="free_until_days" class="vy-input" min="0" max="365" value="{{ old('free_until_days', $policy['free_until_days']) }}" placeholder="{{ __('livewire/operator/operator-settings.free_cancel_placeholder') }}" {{ $disabled }}>
                        <span style="font-size: 11px; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.free_cancel_help') }}</span>
                    </div>
                    <div class="vyn-grid3">
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="pol_pet">{{ __('livewire/operator/operator-settings.pets') }}</label>
                            <select id="pol_pet" name="pet" class="vy-input" {{ $disabled }}>
                                <option value="allowed" @selected(old('pet', $policy['pet']) === 'allowed')>{{ __('livewire/operator/operator-settings.pets_allowed') }}</option>
                                <option value="on_request" @selected(old('pet', $policy['pet']) === 'on_request')>{{ __('livewire/operator/operator-settings.pets_on_request') }}</option>
                                <option value="not_allowed" @selected(old('pet', $policy['pet']) === 'not_allowed')>{{ __('livewire/operator/operator-settings.pets_not_allowed') }}</option>
                            </select>
                            <span style="font-size: 11px; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.pets_help') }}</span>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="pol_min">{{ __('livewire/operator/operator-settings.min_stay') }}</label>
                            <input id="pol_min" type="number" name="min_stay" class="vy-input" min="1" max="365" value="{{ old('min_stay', $policy['min_stay']) }}" required {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="pol_max">{{ __('livewire/operator/operator-settings.max_stay') }} <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.optional_hint') }}</span></label>
                            <input id="pol_max" type="number" name="max_stay" class="vy-input" min="1" max="365" value="{{ old('max_stay', $policy['max_stay']) }}" {{ $disabled }}>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink);">
                            <input type="checkbox" name="deposit_required" value="1" @checked(old('deposit_required', $policy['deposit_required'])) {{ $disabled }}>
                            {{ __('livewire/operator/operator-settings.deposit_at_booking') }}
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink);">
                            <input type="checkbox" name="self_check_in" value="1" @checked(old('self_check_in', $policy['self_check_in'])) {{ $disabled }}>
                            {{ __('livewire/operator/operator-settings.self_check_in') }} <span style="color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.self_check_in_hint') }}</span>
                        </label>
                    </div>
                    @unless ($readOnly)
                        <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">{{ __('livewire/operator/operator-settings.save_policies') }}</button>
                        </div>
                    @endunless
                </form>
            </div>

            {{-- ── 3 · TAX & FEES SUMMARY — REAL (the folio source; detailed editor + sample = GATE 2) ──── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h2 class="vy-card-title" style="margin: 0;">{{ __('livewire/operator/operator-settings.tax_fees_heading') }}</h2>
                    <a href="#tax-detail" style="font-size: 12.5px; color: var(--vy-accent-text); text-decoration: none;">{{ __('livewire/operator/operator-settings.edit_tax_fees') }}</a>
                </div>
                @if ($taxes === [] && $fees === [])
                    <div style="font-size: 12.5px; color: var(--vy-muted);">{{ __('livewire/operator/operator-settings.no_tax_fees_property') }}</div>
                @else
                    @foreach (array_merge($taxes, $fees) as $row)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-top: 1px solid var(--vy-hairline); font-size: 13px;">
                            <span style="color: var(--vy-ink);">{{ $row['name'] }}</span>
                            <span style="font-family: var(--vy-display); font-weight: 600; color: var(--vy-ink);">{{ $row['basis'] }}@isset($row['inclusive']) <span style="font-weight: 500; color: var(--vy-faint); font-family: var(--vy-sans);">· {{ $row['inclusive'] ? __('livewire/operator/operator-settings.tax_included') : __('livewire/operator/operator-settings.tax_added') }}</span>@endisset</span>
                        </div>
                    @endforeach
                    <p style="font-size: 11px; color: var(--vy-faint); margin: 10px 0 0;">{{ __('livewire/operator/operator-settings.applied_every_folio') }}</p>
                @endif
            </div>

            {{-- ── #104b GATE 2 · INVOICE DETAILS + NUMBERING — REAL config on the ONE store; certified issuance
                 + SAF-T (PT) honestly deferred (never a fabricated invoice/SAF-T) ──────────────────────────── --}}
            @if ($invoice !== [] && $canManageOrg && ! $readOnly)
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-settings.invoice_heading') }}</h2>
                    <p class="vy-line-sub" style="font-size: 12.5px; margin: 0 0 12px;">{{ __('livewire/operator/operator-settings.invoice_intro') }}</p>
                    <form method="POST" action="{{ route('app.settings.invoice') }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <label class="vy-field"><span class="vy-field-label">{{ __('livewire/operator/operator-settings.legal_entity') }}</span><input type="text" name="billing_company" class="vy-input" maxlength="160" value="{{ $invoice['legal_entity'] }}" placeholder="Seaside Villas Lda"></label>
                            <label class="vy-field"><span class="vy-field-label">{{ __('livewire/operator/operator-settings.vat_number') }}</span><input type="text" name="billing_vat_id" class="vy-input" maxlength="40" value="{{ $invoice['vat_number'] }}" placeholder="PT123456789"></label>
                            <label class="vy-field"><span class="vy-field-label">{{ __('livewire/operator/operator-settings.billing_city') }}</span><input type="text" name="billing_city" class="vy-input" maxlength="120" value="{{ $invoice['billing_city'] }}" placeholder="Lisboa"></label>
                            <label class="vy-field"><span class="vy-field-label">{{ __('livewire/operator/operator-settings.series_prefix') }}</span><input type="text" name="invoice_series_prefix" class="vy-input" maxlength="16" value="{{ $invoice['series_prefix'] }}" placeholder="FT2026/"></label>
                            <label class="vy-field"><span class="vy-field-label">{{ __('livewire/operator/operator-settings.next_number') }}</span><input type="number" name="invoice_next_number" min="1" class="vy-input" value="{{ $invoice['next_number'] }}"></label>
                        </div>
                        <p style="font-size:12px;color:var(--vy-muted);margin:12px 0 6px;">{!! __('livewire/operator/operator-settings.next_invoice_issues_as', ['number' => e($invoice['series_prefix'].$invoice['next_number'])]) !!}</p>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:12px;">
                            <input type="hidden" name="invoice_auto_issue" value="0">
                            <input type="checkbox" name="invoice_auto_issue" value="1" @checked($invoice['auto_issue'])> {{ __('livewire/operator/operator-settings.auto_issue') }}
                        </label>
                        <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/operator-settings.save_invoice_details') }}</button>
                    </form>
                    <div style="margin-top:12px;border-top:1px solid var(--vy-hairline);padding-top:10px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="font-size:12.5px;color:var(--vy-muted);">{{ __('livewire/operator/operator-settings.certified_series') }}</div>
                        <span class="vy-btn is-soon" aria-disabled="true" style="padding:4px 11px;font-size:11.5px;">{{ __('livewire/operator/operator-settings.soon') }}</span>
                    </div>
                    <p style="font-size:11px;color:var(--vy-faint);margin:6px 0 0;">{{ __('livewire/operator/operator-settings.certified_coming') }}</p>
                </div>
            @endif

            {{-- ── #104b GATE 2 · YOUR PLAN — REAL billing state (#85); €99 a configurable placeholder ────────── --}}
            @if ($plan !== null)
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">{{ __('livewire/operator/operator-settings.plan_heading') }}</h2>
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:600;color:var(--vy-ink);">{{ $plan['tier'] }}@if ($plan['price'] !== null) · {{ $plan['price'] }}@endif</div>
                            <div style="font-size:12.5px;color:var(--vy-muted);">{{ $plan['status'] }}</div>
                        </div>
                        <a href="{{ $plan['manage_url'] }}" class="vy-btn vy-btn--ghost">{{ __('livewire/operator/operator-settings.manage_plan') }}</a>
                    </div>
                </div>
            @endif

            {{-- ── #104b GATE 2 · NOTIFICATION ROUTING — HONEST (per-user prefs are real #79; route-to-role is
                 net-new + not built; SMS/Push have no provider) ─────────────────────────────────────────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-settings.routing_heading') }}</h2>
                <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 10px;">{!! __('livewire/operator/operator-settings.routing_intro', ['url' => e(route('app.notification-preferences'))]) !!}</p>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 0;border-top:1px solid var(--vy-hairline);font-size:12.5px;color:var(--vy-muted);">
                    <span>{{ __('livewire/operator/operator-settings.routing_soon_items') }}</span>
                    <span class="vy-btn is-soon" aria-disabled="true" style="padding:4px 11px;font-size:11.5px;">{{ __('livewire/operator/operator-settings.soon') }}</span>
                </div>
            </div>

            {{-- ── #104b GATE 2 · GUEST MESSAGES — REAL (reuse the #83/#86 email templates) ─────────────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-settings.guest_messages_heading') }}</h2>
                <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 10px;">{!! __('livewire/operator/operator-settings.guest_messages_intro', ['url' => e(route('app.email-templates'))]) !!}</p>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-top:1px solid var(--vy-hairline);font-size:13px;"><span>{{ __('livewire/operator/operator-settings.booking_confirmation') }}</span><span class="vy-muted" style="font-size:12px;">{{ __('livewire/operator/operator-settings.email_instant') }}</span></div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-top:1px solid var(--vy-hairline);font-size:13px;"><span>{{ __('livewire/operator/operator-settings.pre_arrival_details') }}</span><span class="vy-muted" style="font-size:12px;">{{ __('livewire/operator/operator-settings.email_before_check_in') }}</span></div>
            </div>

            {{-- ── 4 · OWNER — REAL (Unit.owner_id → Owner; commission = ManagementAgreement, the real 18%) ─ --}}
            @if ($owner !== null)
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">{{ __('livewire/operator/operator-settings.owner_heading') }}</h2>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="width: 40px; height: 40px; border-radius: 50%; background: var(--vy-grad); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">{{ $owner['initials'] }}</span>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; font-weight: 600; color: var(--vy-ink);">{{ $owner['name'] }}</div>
                            <div style="font-size: 12.5px; color: var(--vy-muted);">{{ trans_choice('livewire/operator/operator-settings.unit_count', $owner['units'], ['count' => $owner['units']]) }} · {{ $owner['commission'] }}</div>
                        </div>
                        <a href="{{ route('app.owners.show', $owner['id']) }}" style="font-size: 13px; color: var(--vy-accent-text); text-decoration: none;">{{ __('livewire/operator/operator-settings.view_owner') }}</a>
                    </div>
                </div>
            @endif

            {{-- ── 5 · PROPERTY STATUS — Pause is REAL (fans out to units). Remove-from-group = GATE 2. ───── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">{{ __('livewire/operator/operator-settings.status_heading') }}</h2>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                    <div>
                        <div style="font-size: 13.5px; color: var(--vy-ink);">{{ __('livewire/operator/operator-settings.pause_new_bookings_title') }}</div>
                        <div style="font-size: 12px; color: var(--vy-muted);">{{ __('livewire/operator/operator-settings.pause_help') }}</div>
                    </div>
                    @unless ($readOnly)
                        <form method="POST" action="{{ route($property['bookings_paused'] ? 'app.settings.resume' : 'app.settings.pause') }}">
                            @csrf
                            <button type="submit" class="vy-btn {{ $property['bookings_paused'] ? 'vy-btn--accent' : '' }}" style="padding: 8px 16px; font-size: 12.5px;">
                                {{ $property['bookings_paused'] ? __('livewire/operator/operator-settings.paused_resume') : __('livewire/operator/operator-settings.pause_new_bookings_button') }}
                            </button>
                        </form>
                    @else
                        <span style="font-size: 12px; color: var(--vy-faint);">{{ $property['bookings_paused'] ? __('livewire/operator/operator-settings.paused') : __('livewire/operator/operator-settings.accepting_bookings') }}</span>
                    @endunless
                </div>
                @if ($property['bookings_paused'])
                    <div style="font-size: 12px; color: var(--vy-amber); padding-top: 6px;">{{ __('livewire/operator/operator-settings.bookings_paused_notice') }}</div>
                @endif

                {{-- Remove from group — a careful ARCHIVE-NOT-DESTROY (scheduled, cancellable, records retained) --}}
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0 0; margin-top: 8px; border-top: 1px solid var(--vy-hairline);">
                    <div>
                        <div style="font-size: 13.5px; color: var(--vy-ink);">{{ __('livewire/operator/operator-settings.remove_from_group') }}</div>
                        <div style="font-size: 12px; color: var(--vy-muted);">{{ __('livewire/operator/operator-settings.remove_help') }}</div>
                    </div>
                    @if ($removal !== null && $removal['scheduled'])
                        <form method="POST" action="{{ route('app.settings.remove.cancel') }}">
                            @csrf
                            <button type="submit" class="vy-btn" style="padding: 8px 16px; font-size: 12.5px;">{{ __('livewire/operator/operator-settings.keep_property') }}</button>
                        </form>
                    @elseif ($canManageProperty && ! $readOnly)
                        <button type="button" x-data @click="$dispatch('open-remove')" class="vy-btn" style="padding: 8px 16px; font-size: 12.5px; color: rgb(150,50,42); border-color: rgba(180,68,58,.3);">{{ __('livewire/operator/operator-settings.remove_button') }}</button>
                    @endif
                </div>
                @if ($removal !== null && $removal['scheduled'])
                    <div style="font-size: 12px; color: var(--vy-amber); padding-top: 8px;">
                        {!! __('livewire/operator/operator-settings.removal_scheduled_notice', ['date' => e($removal['effective'])]) !!}
                    </div>
                @endif
            </div>

            {{-- Remove-property confirm modal (Alpine) --}}
            @if ($removal !== null && ! $removal['scheduled'] && $canManageProperty && ! $readOnly)
                <div x-data="{ open: false, typed: '' }" x-on:open-remove.window="open = true" x-cloak>
                    <div x-show="open" style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;" @click.self="open = false" @keydown.escape.window="open = false">
                        <div class="vy-card" style="max-width: 460px; width: 100%;" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="open" aria-labelledby="vy-dlg-operator-settings-open-1">
                            <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 4px;" id="vy-dlg-operator-settings-open-1">{{ __('livewire/operator/operator-settings.remove_title', ['name' => $removal['name']]) }}</h3>
                            <div style="font-size: 12.5px; color: var(--vy-muted); margin-bottom: 12px;">{{ trans_choice('livewire/operator/operator-settings.unit_count', $removal['units'], ['count' => $removal['units']]) }}@if ($removal['in_house'] > 0)<span> · {{ trans_choice('livewire/operator/operator-settings.guests_in_house', $removal['in_house'], ['count' => $removal['in_house']]) }}</span>@endif</div>
                            <ul style="font-size: 12.5px; color: var(--vy-ink); line-height: 1.7; padding-left: 18px; margin: 0 0 14px;">
                                <li>{!! __('livewire/operator/operator-settings.remove_point_scheduled') !!}</li>
                                <li>{{ __('livewire/operator/operator-settings.remove_point_channels') }}</li>
                                <li>{{ __('livewire/operator/operator-settings.remove_point_owner') }}</li>
                                <li>{{ __('livewire/operator/operator-settings.remove_point_exports') }}</li>
                            </ul>
                            <form method="POST" action="{{ route('app.settings.remove.schedule') }}">
                                @csrf
                                <label class="vy-field-label" for="confirm_name">{{ __('livewire/operator/operator-settings.type_name_to_confirm') }}</label>
                                <input id="confirm_name" type="text" name="confirm_name" class="vy-input" x-model="typed" placeholder="{{ $removal['name'] }}" autocomplete="off">
                                <div style="display: flex; gap: 10px; margin-top: 14px; justify-content: flex-end;">
                                    <button type="button" @click="open = false" class="vy-btn" style="padding: 9px 16px; font-size: 12.5px;">{{ __('livewire/operator/operator-settings.keep_property') }}</button>
                                    <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px; font-size: 12.5px;" x-bind:disabled="typed.trim() !== @js($removal['name'])">{{ __('livewire/operator/operator-settings.schedule_removal') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ── 6 · WHO CAN ACCESS — REAL (property_user pivot-or-all; real roles) ──────────────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <h2 class="vy-card-title" style="margin: 0;">{{ __('livewire/operator/operator-settings.who_can_access', ['property' => $subtitle]) }}</h2>
                    @if ($canManageUsers && ! $readOnly)
                        <a href="{{ route('app.owners') }}" class="vy-btn vy-btn--accent" style="padding: 7px 14px; font-size: 12px;">{{ __('livewire/operator/operator-settings.invite_to_property') }}</a>
                    @endif
                </div>
                <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">{{ __('livewire/operator/operator-settings.access_intro') }}</p>

                @foreach ($team as $m)
                    <div style="display: flex; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid var(--vy-hairline);">
                        <span style="width: 34px; height: 34px; border-radius: 50%; background: rgba(194,65,12,.10); color: var(--vy-accent-text); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">{{ $m['initials'] }}</span>
                        <div style="flex: 1;">
                            <div style="font-size: 13.5px; color: var(--vy-ink);">{{ $m['name'] }}@if ($m['you']) <span style="color: var(--vy-faint); font-size: 12px;">{{ __('livewire/operator/operator-settings.you_marker') }}</span>@endif</div>
                            <div style="font-size: 12px; color: var(--vy-muted);">{{ $m['scope'] }}</div>
                        </div>
                        <span style="font-size: 12px; color: var(--vy-muted);">{{ $m['roles'] }}</span>
                    </div>
                @endforeach

                {{-- ⚠️ P-7 · CLUSTER J — THE PER-PROPERTY SCOPE EDITOR. Until this existed the `property_user`
                     pivot had no reachable writer, so every operator fell through to the ratified
                     "empty pivot ⇒ full org" rule (#38) and per-property scoping was decorative.
                     ⚠️ Spans the WHOLE organisation on purpose: the list above hides anyone scoped out of
                     this property, and editing from a list that hides its subjects is how someone gets
                     scoped somewhere you can no longer reach them. --}}
                @if ($canManageUsers && ! $readOnly && count($orgProperties) > 1)
                    <details style="margin-top: 14px; border-top: 1px solid var(--vy-hairline); padding-top: 12px;">
                        <summary style="cursor: pointer; font-size: 12.5px; font-weight: 600; color: var(--vy-accent-text);">{{ __('livewire/operator/operator-settings.scoping_summary') }}</summary>
                        <p style="font-size: 12px; color: var(--vy-muted); margin: 8px 0 12px;">{{ __('livewire/operator/operator-settings.scoping_intro') }}</p>

                        @foreach ($teamScopes as $m)
                            <form method="POST" action="{{ route('app.settings.team-scope') }}" style="border-top: 1px solid var(--vy-hairline); padding: 10px 0;">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $m['id'] }}">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 180px;">
                                        <div style="font-size: 13px; color: var(--vy-ink);">{{ $m['name'] }}@if ($m['you']) <span style="color: var(--vy-faint); font-size: 12px;">{{ __('livewire/operator/operator-settings.you_marker') }}</span>@endif</div>
                                        <div style="font-size: 11.5px; color: var(--vy-muted);">{{ $m['roles'] }}</div>
                                    </div>
                                    @if ($m['you'])
                                        {{-- ⚠️ You cannot change your OWN scope: a mis-click would lock you out of the
                                             property you are standing in, and the screen to undo it is the one you lost.
                                             The controller refuses it too — this only keeps the UI from inviting a refusal. --}}
                                        <span style="font-size: 11.5px; color: var(--vy-muted);">{{ __('livewire/operator/operator-settings.ask_another_admin') }}</span>
                                    @else
                                        <label style="font-size: 12px; color: var(--vy-muted); display: inline-flex; align-items: center; gap: 5px;">
                                            <input type="radio" name="scope" value="all" @checked($m['all'])> {{ __('livewire/operator/operator-settings.scope_all_properties') }}
                                        </label>
                                        <label style="font-size: 12px; color: var(--vy-muted); display: inline-flex; align-items: center; gap: 5px;">
                                            <input type="radio" name="scope" value="selected" @checked(! $m['all'])> {{ __('livewire/operator/operator-settings.scope_only_these') }}
                                        </label>
                                        @foreach ($orgProperties as $p)
                                            <label style="font-size: 12px; color: var(--vy-ink); display: inline-flex; align-items: center; gap: 5px;">
                                                <input type="checkbox" name="properties[]" value="{{ $p['id'] }}" @checked(in_array($p['id'], $m['ids'], true))> {{ $p['name'] }}
                                            </label>
                                        @endforeach
                                        {{-- ⚠️ RE-AUTH: this changes what a back-office principal can do.
                                             ⚠️ The id is per-ROW: this block is inside @foreach ($teamScopes), so a fixed
                                             id would repeat down the page and every row's <label for> would focus the
                                             FIRST row's box. Same reason the error is shown only on the row that posted
                                             (`user_id` is flashed back; `current_password` is not — dontFlash). --}}
                                        <label class="vy-field-label" for="scope_pw_{{ $m['id'] }}">{{ __('livewire/operator/operator-settings.confirm_with_password') }}</label>
                                        <input id="scope_pw_{{ $m['id'] }}" type="password" name="current_password" class="vy-input"
                                               autocomplete="current-password" required aria-label="{{ __('livewire/operator/operator-settings.current_password') }}">
                                        @if ((string) old('user_id') === (string) $m['id'])
                                            @error('current_password') <span style="color:#B4400F;font-size:11.5px">{{ $message }}</span> @enderror
                                        @endif
                                        <button type="submit" class="vy-btn" style="padding: 6px 12px; font-size: 12px;">{{ __('common.save') }}</button>
                                    @endif
                                </div>
                            </form>
                        @endforeach
                    </details>
                @endif

                @foreach ($pendingInvites as $inv)
                    <div style="display: flex; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid var(--vy-hairline);">
                        <span style="width: 34px; height: 34px; border-radius: 50%; background: rgba(178,106,8,.10); color: var(--vy-amber); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">&#64;</span>
                        <div style="flex: 1;">
                            <div style="font-size: 13.5px; color: var(--vy-ink);">{{ $inv['email'] }}</div>
                            <div style="font-size: 12px; color: var(--vy-muted);">{{ __('livewire/operator/operator-settings.invite_meta', ['role' => $inv['role'], 'sent' => $inv['sent'], 'expires' => $inv['expires']]) }}</div>
                        </div>
                        <span style="font-size: 11.5px; color: var(--vy-amber); font-weight: 600;">{{ __('livewire/operator/operator-settings.pending') }}</span>
                    </div>
                @endforeach

                <div style="margin-top: 16px; border-top: 1px solid var(--vy-hairline); padding-top: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 12.5px; font-weight: 600; color: var(--vy-ink);">{{ __('livewire/operator/operator-settings.role_capabilities_heading') }}</span>
                        <a href="{{ route('app.roles') }}" style="font-size: 12px; color: var(--vy-accent-text); text-decoration: none;">{{ __('livewire/operator/operator-settings.view_full_permissions') }}</a>
                    </div>
                    @foreach ($roleDefinitions as $rd)
                        <div style="padding: 7px 0; font-size: 12.5px;">
                            <span style="font-weight: 600; color: var(--vy-ink);">{{ $rd['name'] }}</span>
                            <span style="color: var(--vy-muted);"> — {{ $rd['blurb'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── 7 · GUEST-FACING BRAND — REAL (flows to the real emails + portal + review; live preview) ── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-settings.brand_heading') }}</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">{{ __('livewire/operator/operator-settings.brand_intro') }}</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">
                    {{-- editor --}}
                    <form method="POST" action="{{ route('app.settings.brand') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="vy-field">
                            <label class="vy-field-label" for="b_accent">{{ __('livewire/operator/operator-settings.accent_colour') }}</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input id="b_accent" type="color" name="brand_accent" value="{{ old('brand_accent', $brand['accent']) }}" style="width: 44px; height: 38px; border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-sm); background: none; cursor: pointer;" {{ $disabled }}>
                                <span style="font-size: 12px; color: var(--vy-muted);">{{ $brand['accent'] }}</span>
                            </div>
                            @error('brand_accent') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field">
                            <label class="vy-field-label" for="b_logo">{{ __('livewire/operator/operator-settings.logo') }} <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.logo_hint') }}</span></label>
                            @if ($brand['logo_url'])
                                <img src="{{ $brand['logo_url'] }}" alt="{{ __('livewire/operator/operator-settings.current_logo_alt') }}" style="width: 40px; height: 40px; border-radius: var(--vy-r-sm); object-fit: cover; margin-bottom: 6px;">
                            @endif
                            <input id="b_logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="vy-input" {{ $disabled }}>
                            @error('logo') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field">
                            <label class="vy-field-label" for="b_from">{{ __('livewire/operator/operator-settings.sender_name') }}</label>
                            <input id="b_from" type="text" name="brand_email_from_name" class="vy-input" placeholder="{{ $subtitle }}" value="{{ old('brand_email_from_name', $brand['from_name']) }}" {{ $disabled }}>
                            <span style="font-size: 11px; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.sent_from', ['address' => $brand['from_address']]) }}</span>
                        </div>
                        <div class="vy-field">
                            <label class="vy-field-label" for="b_reply">{{ __('livewire/operator/operator-settings.reply_to') }}</label>
                            <input id="b_reply" type="email" name="brand_reply_to" class="vy-input" placeholder="hello@yourhotel.com" value="{{ old('brand_reply_to', $brand['reply_to']) }}" {{ $disabled }}>
                            @error('brand_reply_to') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink); margin-top: 4px;">
                            <input type="checkbox" name="brand_powered_by" value="1" @checked(old('brand_powered_by', $brand['powered_by'])) {{ $disabled }}>
                            {{ __('livewire/operator/operator-settings.show_powered_by') }}
                        </label>
                        @unless ($readOnly)
                            <div style="margin-top: 14px;">
                                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">{{ __('livewire/operator/operator-settings.save_brand') }}</button>
                            </div>
                        @endunless
                    </form>

                    {{-- live preview — mirrors the shared emails.layout with the resolved brand --}}
                    <div>
                        <div style="font-size: 11.5px; color: var(--vy-faint); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px;">{{ __('livewire/operator/operator-settings.live_preview') }}</div>
                        <div style="background: #F6EBE2; border-radius: var(--vy-r-md); padding: 18px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                                @if ($brandPreview->logoUrl !== null)
                                    <img src="{{ $brandPreview->logoUrl }}" alt="" style="width: 34px; height: 34px; border-radius: 9px; object-fit: cover;">
                                @else
                                    <span style="width: 34px; height: 34px; border-radius: 9px; background: {{ $brandPreview->accent }}; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;">{{ $brandPreview->initial }}</span>
                                @endif
                                <span style="font-size: 15px; font-weight: 600; color: #2B2320;">{{ $brandPreview->displayName }}</span>
                            </div>
                            <div style="background: #FFFDFB; border: 1px solid #EFE3D9; border-radius: 14px; padding: 20px;">
                                <div style="font-size: 15px; font-weight: 600; color: #2B2320; margin-bottom: 6px;">{{ __('livewire/operator/operator-settings.preview_heading') }}</div>
                                <div style="font-size: 12.5px; color: #6b615a; line-height: 1.5; margin-bottom: 14px;">{{ __('livewire/operator/operator-settings.preview_body') }}</div>
                                <span style="display: inline-block; background: {{ $brandPreview->accent }}; color: #fff; font-size: 12.5px; font-weight: 600; padding: 9px 18px; border-radius: 10px;">{{ __('livewire/operator/operator-settings.preview_button') }}</span>
                            </div>
                            @if ($brandPreview->poweredBy)
                                <div style="text-align: center; font-size: 10.5px; color: var(--vy-faint); margin-top: 12px;">{{ __('livewire/operator/operator-settings.preview_powered_by') }}</div>
                            @endif
                        </div>
                        <p style="font-size: 11px; color: var(--vy-faint); margin: 8px 0 0;">{{ __('livewire/operator/operator-settings.accent_flows') }}</p>
                    </div>
                </div>
            </div>

            {{-- ── 7b · GUEST SUPPORT CONTACT — REAL (DO-G13-1). Everything here is optional, and an EMPTY box is
                 stored as NULL, which removes the affordance from the guest Help page entirely. Nothing on this
                 form has a default: a support number or an hours line the operator never typed would be an
                 operational promise the product invented (the DO-EMAIL-9 class).

                 ⚠️ The reply norm is a fixed catalogue, not a text box. The operator picks a bucket; the sentence
                 a guest reads is written in App\Support\Guest\PropertySupport and always begins "usually", so a
                 GUARANTEE is not expressible through this field. The preview under the select is that exact
                 sentence — the operator sees the claim in the guest's words. ── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-settings.support_heading') }}</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">
                    {!! __('livewire/operator/operator-settings.support_intro') !!}
                </p>
                <form method="POST" action="{{ route('app.settings.support') }}">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="vy-field">
                            <label class="vy-field-label" for="s_phone">{{ __('livewire/operator/operator-settings.phone_number') }}</label>
                            <input id="s_phone" type="tel" name="support_phone" class="vy-input" placeholder="+351 21 000 0000"
                                   value="{{ old('support_phone', $support['phone']) }}" {{ $disabled }}>
                            <span style="font-size: 11px; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.phone_help') }}</span>
                            @error('support_phone') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field">
                            <label class="vy-field-label" for="s_hours">{{ __('livewire/operator/operator-settings.reachable_when') }}</label>
                            <input id="s_hours" type="text" name="support_hours" class="vy-input" placeholder="{{ __('livewire/operator/operator-settings.hours_placeholder') }}"
                                   maxlength="120" value="{{ old('support_hours', $support['hours']) }}" {{ $disabled }}>
                            <span style="font-size: 11px; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.hours_help') }}</span>
                            @error('support_hours') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="vy-field" style="max-width: 420px;">
                        <label class="vy-field-label" for="s_norm">{{ __('livewire/operator/operator-settings.reply_time') }}</label>
                        <select id="s_norm" name="support_reply_norm" class="vy-input" {{ $disabled }}>
                            <option value="">{{ __('livewire/operator/operator-settings.no_reply_time') }}</option>
                            @foreach ($replyNorms as $key => $phrase)
                                <option value="{{ $key }}" @selected(old('support_reply_norm', $support['reply_norm']) === $key)>{{ ucfirst($phrase) }}</option>
                            @endforeach
                        </select>
                        <span style="font-size: 11px; color: var(--vy-faint);">
                            {{ __('livewire/operator/operator-settings.reply_time_help', ['preview' => $support['preview'] ?? __('livewire/operator/operator-settings.reply_time_fallback')]) }}
                        </span>
                        @error('support_reply_norm') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                    </div>
                    @unless ($readOnly)
                        <div style="margin-top: 10px;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">{{ __('livewire/operator/operator-settings.save_support') }}</button>
                        </div>
                    @endunless
                </form>
                @unless ($support['configured'])
                    <p style="font-size: 11.5px; color: var(--vy-muted); margin: 14px 0 0;">
                        {{ __('livewire/operator/operator-settings.support_not_set') }}
                    </p>
                @endunless
            </div>

            {{-- ── Detailed taxes editor (bounded amounts — GATE 2 makes the sample reconcile + adds custom fees) ── --}}
            <div class="vy-card vy-fade" id="tax-detail" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-settings.taxes_heading') }}</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">{{ __('livewire/operator/operator-settings.applied_every_folio') }}</p>
                @if ($taxes === [] && $fees === [])
                    <div style="font-size: 12.5px; color: var(--vy-muted);">{{ __('livewire/operator/operator-settings.no_tax_fees') }}</div>
                @else
                    <form method="POST" action="{{ route('app.settings.tax-fees') }}">
                        @csrf
                        @foreach (array_merge(
                            array_map(fn ($t) => $t + ['label' => $t['kind'], 'field' => 'taxes'], $taxes),
                            array_map(fn ($f) => $f + ['label' => __('livewire/operator/operator-settings.fee_label'), 'field' => 'fees'], $fees),
                        ) as $row)
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 8px 0; border-top: 1px solid var(--vy-hairline); font-size: 13px;">
                                <span style="color: var(--vy-ink);">{{ $row['name'] }} <span style="color: var(--vy-faint);">· {{ $row['label'] }}</span></span>
                                @if ($canManageProperty && ! $readOnly)
                                    <span style="display: flex; align-items: center; gap: 10px;">
                                        {{-- #104 GATE 2c — the VAT Incl./Added toggle now PERSISTS (drives folio math via
                                             StayEstimator). Hidden 0 + checkbox 1 → a reliable boolean. Percentage taxes only. --}}
                                        @if ($row['field'] === 'taxes' && ($row['percentage'] ?? false))
                                            <input type="hidden" name="tax_inclusive[{{ $row['id'] }}]" value="0">
                                            <label style="display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--vy-muted); white-space: nowrap;">
                                                <input type="checkbox" name="tax_inclusive[{{ $row['id'] }}]" value="1" @checked($row['inclusive'] ?? false)>
                                                {{ __('livewire/operator/operator-settings.included_in_price') }}
                                            </label>
                                        @endif
                                        <input class="vy-input" type="text" inputmode="decimal" autocomplete="off" style="width: 92px; text-align: right;"
                                               name="{{ $row['field'] }}[{{ $row['id'] }}]"
                                               value="{{ $row['percentage'] ? $row['rate'] : $row['amount'] }}"
                                               aria-label="{{ __('livewire/operator/operator-settings.row_value_aria', ['name' => $row['name']]) }}">
                                        <span style="font-size: 12px; color: var(--vy-muted); min-width: 34px;">{{ $row['percentage'] ? '%' : $row['currency'] }}</span>
                                    </span>
                                @else
                                    <span style="font-family: var(--vy-display); font-weight: 600; color: var(--vy-ink);">{{ $row['basis'] }}</span>
                                @endif
                            </div>
                        @endforeach
                        @if ($canManageProperty && ! $readOnly)
                            <button type="submit" class="vy-btn vy-btn--accent" style="margin-top: 14px; padding: 11px 20px;">{{ __('livewire/operator/operator-settings.save_tax_fees') }}</button>
                        @endif
                    </form>
                @endif

                {{-- Add a fee (real create — per-stay/night/guest, optional-at-booking) --}}
                @if ($canManageProperty && ! $readOnly)
                    <div x-data="{ open: false }" style="margin-top: 16px; border-top: 1px solid var(--vy-hairline); padding-top: 14px;">
                        <button type="button" @click="open = !open" class="vy-btn" style="padding: 8px 14px; font-size: 12.5px;">{{ __('livewire/operator/operator-settings.add_a_fee') }}</button>
                        <div x-show="open" x-cloak style="margin-top: 12px;">
                            <form method="POST" action="{{ route('app.settings.fees.add') }}">
                                @csrf
                                <div class="vyn-grid3">
                                    <div class="vy-field" style="margin: 0;">
                                        <label class="vy-field-label" for="fee_name">{{ __('livewire/operator/operator-settings.fee_name') }}</label>
                                        <input id="fee_name" type="text" name="name" class="vy-input" placeholder="{{ __('livewire/operator/operator-settings.fee_name_placeholder') }}" required>
                                    </div>
                                    <div class="vy-field" style="margin: 0;">
                                        <label class="vy-field-label" for="fee_amount">{{ __('livewire/operator/operator-settings.fee_amount', ['currency' => $sample['currency'] ?? 'EUR']) }}</label>
                                        <input id="fee_amount" type="text" inputmode="decimal" autocomplete="off" name="amount" class="vy-input" required>
                                    </div>
                                    <div class="vy-field" style="margin: 0;">
                                        <label class="vy-field-label" for="fee_basis">{{ __('livewire/operator/operator-settings.fee_charged') }}</label>
                                        <select id="fee_basis" name="basis" class="vy-input">
                                            <option value="per_stay">{{ __('livewire/operator/operator-settings.per_stay') }}</option>
                                            <option value="per_night">{{ __('livewire/operator/operator-settings.per_night') }}</option>
                                            <option value="per_guest">{{ __('livewire/operator/operator-settings.per_guest') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink); margin-top: 10px;">
                                    <input type="checkbox" name="optional" value="1">
                                    {{ __('livewire/operator/operator-settings.optional_at_booking') }} <span style="color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.optional_at_booking_hint') }}</span>
                                </label>
                                <button type="submit" class="vy-btn vy-btn--accent" style="margin-top: 12px; padding: 10px 18px; font-size: 12.5px;">{{ __('livewire/operator/operator-settings.add_fee') }}</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- The sample 4-night stay — reconciles to a REAL folio via the shared StayEstimator (#17) --}}
                @if ($sample !== null)
                    <div style="margin-top: 16px; border-top: 1px solid var(--vy-hairline); padding-top: 14px;">
                        <div style="font-size: 12.5px; font-weight: 600; color: var(--vy-ink); margin-bottom: 2px;">{{ __('livewire/operator/operator-settings.sample_heading') }}</div>
                        <div style="font-size: 11.5px; color: var(--vy-faint); margin-bottom: 10px;">{{ __('livewire/operator/operator-settings.sample_sub') }}</div>
                        @foreach ($sample['lines'] as $line)
                            <div style="display: flex; justify-content: space-between; align-items: baseline; padding: 5px 0; font-size: 13px;">
                                <span style="color: var(--vy-ink);">{{ $line['label'] }} @if ($line['detail'] !== '')<span style="color: var(--vy-faint);">· {{ $line['detail'] }}</span>@endif</span>
                                <span style="font-family: var(--vy-display); color: {{ $line['informational'] ? 'var(--vy-faint)' : 'var(--vy-ink)' }};">{{ $line['amount'] }}</span>
                            </div>
                        @endforeach
                        <div style="display: flex; justify-content: space-between; align-items: baseline; padding: 9px 0 0; margin-top: 6px; border-top: 1px solid var(--vy-hairline); font-size: 14px; font-weight: 700;">
                            <span style="color: var(--vy-ink);">{{ __('livewire/operator/operator-settings.guest_pays') }}</span>
                            <span style="font-family: var(--vy-display); color: var(--vy-accent-text);">{{ $sample['total'] }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ── Organization — REAL (organizations columns) · gated settings.manage. Retained from the
                 de-conflicted settings surface: org-level config still needs a home (KIND-3 vs the property-only
                 wireframe). ─────────────────────────────────────────────────────────────────────────────── --}}
            @can('settings.manage')
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 14px;">{{ __('livewire/operator/operator-settings.organization_heading') }}</h2>
                    <form method="POST" action="{{ route('app.settings.organization') }}">
                        @csrf
                        <div class="vyn-grid3">
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_cur">{{ __('livewire/operator/operator-settings.default_currency') }}</label>
                                <input id="o_cur" type="text" name="default_currency" class="vy-input" maxlength="3" style="text-transform: uppercase;" value="{{ old('default_currency', $org['default_currency']) }}" required {{ $disabled }}>
                                @error('default_currency') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_loc">{{ __('livewire/operator/operator-settings.default_locale') }}</label>
                                <input id="o_loc" type="text" name="default_locale" class="vy-input" value="{{ old('default_locale', $org['default_locale']) }}" required {{ $disabled }}>
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_email">{{ __('livewire/operator/operator-settings.billing_email') }} <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">{{ __('livewire/operator/operator-settings.optional_hint') }}</span></label>
                                <input id="o_email" type="email" name="billing_email" class="vy-input" value="{{ old('billing_email', $org['billing_email']) }}" {{ $disabled }}>
                                @error('billing_email') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @unless ($readOnly)
                            <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">{{ __('livewire/operator/operator-settings.save_organization') }}</button>
                            </div>
                        @endunless
                    </form>
                </div>
            @endcan

            {{-- #104b review fix: the old "More settings → Invoice numbering / Notification routing · Soon" stub was
                 removed — both are now represented by the REAL Invoice-details form + the honest Notification-routing
                 card above, so the "Soon" rows contradicted them. --}}

        @endunless

    </x-operator.shell>
</div>

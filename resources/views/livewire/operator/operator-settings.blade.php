{{-- Operator SETTINGS (Wave 4 · Sub-gate 3) — ONE settings surface (Operator + Property settings
     de-conflicted). Only BACKED columns are editable; unbacked design fields are stubbed "Soon", never a
     fake save. See App\Livewire\Operator\OperatorSettings for the REAL / READ-ONLY / STUBBED map.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="settings" title="Settings" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('settings_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('settings_notice') }}</div>
            </div>
        @endif

        @unless ($hasProperty)
            <div class="vy-card vy-fade" style="text-align: center; padding: 40px 24px;">
                <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No property configured</strong>
                <span style="font-size: 13px; color: var(--vy-muted);">Add a property to configure its settings.</span>
            </div>
        @else

            {{-- ── Property details — BACKED (properties columns) · gated properties.manage ──────────── --}}
            @can('properties.manage')
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 14px;">Property details</h2>
                    <form method="POST" action="{{ route('app.settings.property') }}">
                        @csrf
                        <div class="vyn-grid3">
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="p_name">Name</label>
                                <input id="p_name" type="text" name="name" class="vy-input" value="{{ old('name', $property['name']) }}" required>
                                @error('name') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="p_tz">Time zone</label>
                                <select id="p_tz" name="timezone" class="vy-input">
                                    @foreach ($timezones as $tz)
                                        <option value="{{ $tz }}" @selected(old('timezone', $property['timezone']) === $tz)>{{ $tz }}</option>
                                    @endforeach
                                </select>
                                @error('timezone') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="p_star">Star rating <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                                <input id="p_star" type="number" name="star_rating" class="vy-input" min="1" max="5" value="{{ old('star_rating', $property['star_rating']) }}">
                                @error('star_rating') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="p_ci">Check-in time</label>
                                <input id="p_ci" type="time" name="check_in_time" class="vy-input" value="{{ old('check_in_time', $property['check_in_time']) }}">
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="p_co">Check-out time</label>
                                <input id="p_co" type="time" name="check_out_time" class="vy-input" value="{{ old('check_out_time', $property['check_out_time']) }}">
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="p_status">Status</label>
                                <select id="p_status" name="status" class="vy-input">
                                    <option value="active" @selected(old('status', $property['status']) === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $property['status']) === 'inactive')>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save property details</button>
                        </div>
                    </form>
                </div>

                {{-- ── Stay policies — BACKED (property_policies scalar columns) · gated properties.manage ── --}}
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 4px;">Stay policies</h2>
                    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">The pet, smoking and length-of-stay rules shown on the booking screens.</p>
                    <form method="POST" action="{{ route('app.settings.policies') }}">
                        @csrf
                        <div class="vyn-grid3">
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="pol_pet">Pets</label>
                                <select id="pol_pet" name="pet" class="vy-input">
                                    <option value="allowed" @selected(old('pet', $policy['pet']) === 'allowed')>Allowed</option>
                                    <option value="on_request" @selected(old('pet', $policy['pet']) === 'on_request')>On request</option>
                                    <option value="not_allowed" @selected(old('pet', $policy['pet']) === 'not_allowed')>Not allowed</option>
                                </select>
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="pol_smoke">Smoking</label>
                                <select id="pol_smoke" name="smoking" class="vy-input">
                                    <option value="allowed" @selected(old('smoking', $policy['smoking']) === 'allowed')>Allowed</option>
                                    <option value="on_request" @selected(old('smoking', $policy['smoking']) === 'on_request')>On request</option>
                                    <option value="not_allowed" @selected(old('smoking', $policy['smoking']) === 'not_allowed')>Not allowed</option>
                                </select>
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="pol_min">Minimum stay (nights)</label>
                                <input id="pol_min" type="number" name="min_stay" class="vy-input" min="1" max="365" value="{{ old('min_stay', $policy['min_stay']) }}" required>
                                @error('min_stay') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="pol_max">Maximum stay (nights) <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                                <input id="pol_max" type="number" name="max_stay" class="vy-input" min="1" max="365" value="{{ old('max_stay', $policy['max_stay']) }}">
                                @error('max_stay') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save stay policies</button>
                        </div>
                    </form>
                </div>
            @endcan

            {{-- ── Organization — BACKED (organizations columns) · gated settings.manage (Property Owner) ── --}}
            @can('settings.manage')
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 14px;">Organization</h2>
                    <form method="POST" action="{{ route('app.settings.organization') }}">
                        @csrf
                        <div class="vyn-grid3">
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_cur">Default currency</label>
                                <input id="o_cur" type="text" name="default_currency" class="vy-input" maxlength="3" style="text-transform: uppercase;" value="{{ old('default_currency', $org['default_currency']) }}" required>
                                @error('default_currency') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_loc">Default locale</label>
                                <input id="o_loc" type="text" name="default_locale" class="vy-input" value="{{ old('default_locale', $org['default_locale']) }}" required>
                                @error('default_locale') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_email">Billing email <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                                <input id="o_email" type="email" name="billing_email" class="vy-input" value="{{ old('billing_email', $org['billing_email']) }}">
                                @error('billing_email') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save organization settings</button>
                        </div>
                    </form>
                </div>
            @endcan

            {{-- ── Taxes & fees — Batch C · GATE 4: EDITABLE (bounded rate/amount) via the certified
                 SettingsService (rate clamped 0–100, amount ≥ 0 integer minor #17). properties.manage-gated;
                 a non-admin sees the read-only figure. The applied basis on every folio uses these values. ── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">Taxes &amp; fees</h2>
                @if ($taxes === [] && $fees === [])
                    <div style="font-size: 12.5px; color: var(--vy-muted);">No taxes or fees are configured.</div>
                @else
                    <form method="POST" action="{{ route('app.settings.tax-fees') }}">
                        @csrf
                        @foreach (array_merge(
                            array_map(fn ($t) => $t + ['label' => $t['kind'], 'field' => 'taxes'], $taxes),
                            array_map(fn ($f) => $f + ['label' => 'Fee', 'field' => 'fees'], $fees),
                        ) as $row)
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 8px 0; border-top: 1px solid var(--vy-hairline); font-size: 13px;">
                                <span style="color: var(--vy-ink);">{{ $row['name'] }} <span style="color: var(--vy-faint);">· {{ $row['label'] }}</span></span>
                                @if ($canManageProperty)
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <input class="vy-input" type="number" step="{{ $row['percentage'] ? '0.1' : '0.01' }}" min="0" style="width: 92px; text-align: right;"
                                               name="{{ $row['field'] }}[{{ $row['id'] }}]"
                                               value="{{ $row['percentage'] ? $row['rate'] : $row['amount'] }}"
                                               aria-label="{{ $row['name'] }} value">
                                        <span style="font-size: 12px; color: var(--vy-muted); min-width: 34px;">{{ $row['percentage'] ? '%' : $row['currency'] }}</span>
                                    </span>
                                @else
                                    <span style="font-family: var(--vy-display); font-weight: 600; color: var(--vy-ink);">{{ $row['basis'] }}</span>
                                @endif
                            </div>
                        @endforeach

                        @if ($canManageProperty)
                            <button type="submit" class="vy-btn vy-btn--accent" style="margin-top: 14px; padding: 11px 20px;">Save taxes &amp; fees</button>
                        @endif
                    </form>
                @endif
            </div>

            {{-- ── STUBBED — net-new subsystems, honestly "Soon" (NOT a fake save) ────────────────────── --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">More settings</h2>
                <div class="vyn-grid3">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 12px; border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-sm);">
                        <span style="font-size: 13px; color: var(--vy-muted);">Branding &amp; white-label</span>
                        <span class="vy-btn is-soon" aria-disabled="true" style="padding: 5px 11px; font-size: 11.5px;">Soon</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 12px; border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-sm);">
                        <span style="font-size: 13px; color: var(--vy-muted);">Invoice numbering</span>
                        <span class="vy-btn is-soon" aria-disabled="true" style="padding: 5px 11px; font-size: 11.5px;">Soon</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 12px; border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-sm);">
                        <span style="font-size: 13px; color: var(--vy-muted);">Notification routing</span>
                        <span class="vy-btn is-soon" aria-disabled="true" style="padding: 5px 11px; font-size: 11.5px;">Soon</span>
                    </div>
                </div>
                <p style="font-size: 11.5px; color: var(--vy-faint); margin: 12px 0 0;">These are net-new subsystems, not wired yet — shown so nothing here pretends to save.</p>
            </div>

        @endunless

    </x-operator.shell>
</div>

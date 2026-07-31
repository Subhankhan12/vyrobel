{{-- Operator PROPERTY SETTINGS (task #98) — matched to `Operator Property Settings.html`. Sections are REAL,
     backed by the certified SettingsService (per-section save; the app's established pattern — no toast-only fake
     save). Owner commission is the REAL 18% (never the wireframe's 15%); the guest-brand live preview mirrors the
     shared email layout; the self-check-in DOOR CODE is guest-only (#33) and never shown here.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="settings" title="Property settings" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('settings_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('settings_notice') }} <span style="color: var(--vy-muted);">· changes are live now</span></div>
            </div>
        @endif
        @if (session('settings_error'))
            <div class="vy-card vy-fade" style="border-left: 3px solid rgb(180,68,58); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: rgb(150,50,42);">{{ session('settings_error') }}</div>
            </div>
        @endif

        @if ($readOnly)
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-amber); margin-bottom: 16px;">
                <div style="font-size: 13px; color: var(--vy-ink);">Your workspace is read-only right now — settings changes are paused.</div>
            </div>
        @endif

        @unless ($hasProperty)
            <div class="vy-card vy-fade" style="text-align: center; padding: 40px 24px;">
                <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No property configured</strong>
                <span style="font-size: 13px; color: var(--vy-muted);">Add a property to configure its settings.</span>
            </div>
        @else
            @php $disabled = $readOnly ? 'disabled' : ''; @endphp

            {{-- ── 1 · PROPERTY BASICS — REAL (properties columns) ─────────────────────────────────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 14px;">Property basics</h2>
                <form method="POST" action="{{ route('app.settings.property') }}">
                    @csrf
                    <div class="vyn-grid3">
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_name">Display name</label>
                            <input id="p_name" type="text" name="name" class="vy-input" value="{{ old('name', $property['name']) }}" required {{ $disabled }}>
                            @error('name') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_ci">Check-in from</label>
                            <input id="p_ci" type="time" name="check_in_time" class="vy-input" value="{{ old('check_in_time', $property['check_in_time']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_co">Check-out by</label>
                            <input id="p_co" type="time" name="check_out_time" class="vy-input" value="{{ old('check_out_time', $property['check_out_time']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_addr">Address</label>
                            <input id="p_addr" type="text" name="address_line" class="vy-input" placeholder="Street" value="{{ old('address_line', $property['address_line']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_city">City</label>
                            <input id="p_city" type="text" name="city" class="vy-input" value="{{ old('city', $property['city']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_country">Country</label>
                            <input id="p_country" type="text" name="country" class="vy-input" value="{{ old('country', $property['country']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_tz">Time zone</label>
                            <select id="p_tz" name="timezone" class="vy-input" {{ $disabled }}>
                                @foreach ($timezones as $tz)
                                    <option value="{{ $tz }}" @selected(old('timezone', $property['timezone']) === $tz)>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_star">Star rating <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                            <input id="p_star" type="number" name="star_rating" class="vy-input" min="1" max="5" value="{{ old('star_rating', $property['star_rating']) }}" {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="p_status">Status</label>
                            <select id="p_status" name="status" class="vy-input" {{ $disabled }}>
                                <option value="active" @selected(old('status', $property['status']) === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $property['status']) === 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                    @unless ($readOnly)
                        <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save basics</button>
                        </div>
                    @endunless
                </form>
            </div>

            {{-- ── 2 · POLICIES — REAL (property_policies) · door code stays GUEST-only (#33) ───────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Policies</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">Cancellation, deposit, pets and self check-in — shown on the booking screens and confirmation.</p>
                <form method="POST" action="{{ route('app.settings.policies') }}">
                    @csrf
                    <div class="vy-field" style="margin: 0 0 14px;">
                        <label class="vy-field-label">Cancellation policy <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· how much notice guests need</span></label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @foreach (['flexible' => 'Flexible', 'moderate' => 'Moderate', 'strict' => 'Strict'] as $val => $label)
                                <label style="flex: 1; min-width: 90px; text-align: center; padding: 9px 12px; border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-sm); font-size: 13px; cursor: pointer; color: var(--vy-ink);"
                                       @if (old('cancellation_tier', $policy['cancellation_tier']) === $val) style="border-color: var(--vy-accent); color: var(--vy-accent); background: rgba(194,65,12,.06);" @endif>
                                    <input type="radio" name="cancellation_tier" value="{{ $val }}" @checked(old('cancellation_tier', $policy['cancellation_tier']) === $val) style="position:absolute;opacity:0;" {{ $disabled }}>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="vyn-grid3">
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="pol_pet">Pets</label>
                            <select id="pol_pet" name="pet" class="vy-input" {{ $disabled }}>
                                <option value="allowed" @selected(old('pet', $policy['pet']) === 'allowed')>Allowed</option>
                                <option value="on_request" @selected(old('pet', $policy['pet']) === 'on_request')>On request</option>
                                <option value="not_allowed" @selected(old('pet', $policy['pet']) === 'not_allowed')>Not allowed</option>
                            </select>
                            <span style="font-size: 11px; color: var(--vy-faint);">A cleaning surcharge may apply per your house rules.</span>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="pol_min">Minimum stay (nights)</label>
                            <input id="pol_min" type="number" name="min_stay" class="vy-input" min="1" max="365" value="{{ old('min_stay', $policy['min_stay']) }}" required {{ $disabled }}>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="pol_max">Maximum stay (nights) <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                            <input id="pol_max" type="number" name="max_stay" class="vy-input" min="1" max="365" value="{{ old('max_stay', $policy['max_stay']) }}" {{ $disabled }}>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink);">
                            <input type="checkbox" name="deposit_required" value="1" @checked(old('deposit_required', $policy['deposit_required'])) {{ $disabled }}>
                            Deposit at booking
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink);">
                            <input type="checkbox" name="self_check_in" value="1" @checked(old('self_check_in', $policy['self_check_in'])) {{ $disabled }}>
                            Self check-in <span style="color: var(--vy-faint);">· smart-lock codes sent to the guest 48h before arrival</span>
                        </label>
                    </div>
                    @unless ($readOnly)
                        <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save policies</button>
                        </div>
                    @endunless
                </form>
            </div>

            {{-- ── 3 · TAX & FEES SUMMARY — REAL (the folio source; detailed editor + sample = GATE 2) ──── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h2 class="vy-card-title" style="margin: 0;">Tax &amp; fees</h2>
                    <a href="#tax-detail" style="font-size: 12.5px; color: var(--vy-accent); text-decoration: none;">Edit tax &amp; fees ↓</a>
                </div>
                @if ($taxes === [] && $fees === [])
                    <div style="font-size: 12.5px; color: var(--vy-muted);">No taxes or fees are configured for this property.</div>
                @else
                    @foreach (array_merge($taxes, $fees) as $row)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-top: 1px solid var(--vy-hairline); font-size: 13px;">
                            <span style="color: var(--vy-ink);">{{ $row['name'] }}</span>
                            <span style="font-family: var(--vy-display); font-weight: 600; color: var(--vy-ink);">{{ $row['basis'] }}@isset($row['inclusive']) <span style="font-weight: 500; color: var(--vy-faint); font-family: var(--vy-sans);">· {{ $row['inclusive'] ? 'included' : 'added' }}</span>@endisset</span>
                        </div>
                    @endforeach
                    <p style="font-size: 11px; color: var(--vy-faint); margin: 10px 0 0;">Applied automatically to every folio for this property.</p>
                @endif
            </div>

            {{-- ── #104b GATE 2 · INVOICE DETAILS + NUMBERING — REAL config on the ONE store; certified issuance
                 + SAF-T (PT) honestly deferred (never a fabricated invoice/SAF-T) ──────────────────────────── --}}
            @if ($invoice !== [] && $canManageOrg && ! $readOnly)
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 4px;">Invoice details</h2>
                    <p class="vy-line-sub" style="font-size: 12.5px; margin: 0 0 12px;">The legal entity and numbering that appear on guest invoices.</p>
                    <form method="POST" action="{{ route('app.settings.invoice') }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <label class="vy-field"><span class="vy-field-label">Legal entity</span><input type="text" name="billing_company" class="vy-input" maxlength="160" value="{{ $invoice['legal_entity'] }}" placeholder="Seaside Villas Lda"></label>
                            <label class="vy-field"><span class="vy-field-label">VAT number (NIF)</span><input type="text" name="billing_vat_id" class="vy-input" maxlength="40" value="{{ $invoice['vat_number'] }}" placeholder="PT123456789"></label>
                            <label class="vy-field"><span class="vy-field-label">Billing city</span><input type="text" name="billing_city" class="vy-input" maxlength="120" value="{{ $invoice['billing_city'] }}" placeholder="Lisboa"></label>
                            <label class="vy-field"><span class="vy-field-label">Series prefix</span><input type="text" name="invoice_series_prefix" class="vy-input" maxlength="16" value="{{ $invoice['series_prefix'] }}" placeholder="FT2026/"></label>
                            <label class="vy-field"><span class="vy-field-label">Next number</span><input type="number" name="invoice_next_number" min="1" class="vy-input" value="{{ $invoice['next_number'] }}"></label>
                        </div>
                        <p style="font-size:12px;color:var(--vy-muted);margin:12px 0 6px;">Next invoice issues as <strong>{{ $invoice['series_prefix'] }}{{ $invoice['next_number'] }}</strong>.</p>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:12px;">
                            <input type="hidden" name="invoice_auto_issue" value="0">
                            <input type="checkbox" name="invoice_auto_issue" value="1" @checked($invoice['auto_issue'])> Auto-issue an invoice at check-out
                        </label>
                        <button type="submit" class="vy-btn vy-btn--accent">Save invoice details</button>
                    </form>
                    <div style="margin-top:12px;border-top:1px solid var(--vy-hairline);padding-top:10px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="font-size:12.5px;color:var(--vy-muted);">Certified series · SAF-T (PT) export</div>
                        <span class="vy-btn is-soon" aria-disabled="true" style="padding:4px 11px;font-size:11.5px;">Soon</span>
                    </div>
                    <p style="font-size:11px;color:var(--vy-faint);margin:6px 0 0;">Numbered, tax-authority-certified issuance and the SAF-T export are coming — the details above are saved now, but no certified invoice or SAF-T file is generated yet.</p>
                </div>
            @endif

            {{-- ── #104b GATE 2 · YOUR PLAN — REAL billing state (#85); €99 a configurable placeholder ────────── --}}
            @if ($plan !== null)
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Your plan</h2>
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:600;color:var(--vy-ink);">{{ $plan['tier'] }} · {{ $plan['price'] }}</div>
                            <div style="font-size:12.5px;color:var(--vy-muted);">{{ $plan['status'] }}</div>
                        </div>
                        <a href="{{ $plan['manage_url'] }}" class="vy-btn vy-btn--ghost">Manage plan</a>
                    </div>
                </div>
            @endif

            {{-- ── #104b GATE 2 · NOTIFICATION ROUTING — HONEST (per-user prefs are real #79; route-to-role is
                 net-new + not built; SMS/Push have no provider) ─────────────────────────────────────────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Notification routing</h2>
                <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 10px;">Email and in-app notifications are on and configurable per person in <a href="{{ route('app.notification-preferences') }}" style="color:var(--vy-accent);">notification preferences</a>.</p>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 0;border-top:1px solid var(--vy-hairline);font-size:12.5px;color:var(--vy-muted);">
                    <span>Route events to a specific role · SMS · Push</span>
                    <span class="vy-btn is-soon" aria-disabled="true" style="padding:4px 11px;font-size:11.5px;">Soon</span>
                </div>
            </div>

            {{-- ── #104b GATE 2 · GUEST MESSAGES — REAL (reuse the #83/#86 email templates) ─────────────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Guest messages</h2>
                <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 10px;">The automated emails guests receive. Edit the wording in <a href="{{ route('app.email-templates') }}" style="color:var(--vy-accent);">email templates</a>.</p>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-top:1px solid var(--vy-hairline);font-size:13px;"><span>Booking confirmation</span><span class="vy-muted" style="font-size:12px;">Email · instant</span></div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-top:1px solid var(--vy-hairline);font-size:13px;"><span>Pre-arrival details</span><span class="vy-muted" style="font-size:12px;">Email · before check-in</span></div>
            </div>

            {{-- ── 4 · OWNER — REAL (Unit.owner_id → Owner; commission = ManagementAgreement, the real 18%) ─ --}}
            @if ($owner !== null)
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Owner</h2>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="width: 40px; height: 40px; border-radius: 50%; background: var(--vy-grad); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">{{ $owner['initials'] }}</span>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; font-weight: 600; color: var(--vy-ink);">{{ $owner['name'] }}</div>
                            <div style="font-size: 12.5px; color: var(--vy-muted);">{{ $owner['units'] }} {{ \Illuminate\Support\Str::plural('unit', $owner['units']) }} · {{ $owner['commission'] }}</div>
                        </div>
                        <a href="{{ route('app.owners.show', $owner['id']) }}" style="font-size: 13px; color: var(--vy-accent); text-decoration: none;">View ›</a>
                    </div>
                </div>
            @endif

            {{-- ── 5 · PROPERTY STATUS — Pause is REAL (fans out to units). Remove-from-group = GATE 2. ───── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">Property status</h2>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                    <div>
                        <div style="font-size: 13.5px; color: var(--vy-ink);">Pause new bookings</div>
                        <div style="font-size: 12px; color: var(--vy-muted);">Existing stays keep running — only new bookings are blocked.</div>
                    </div>
                    @unless ($readOnly)
                        <form method="POST" action="{{ route($property['bookings_paused'] ? 'app.settings.resume' : 'app.settings.pause') }}">
                            @csrf
                            <button type="submit" class="vy-btn {{ $property['bookings_paused'] ? 'vy-btn--accent' : '' }}" style="padding: 8px 16px; font-size: 12.5px;">
                                {{ $property['bookings_paused'] ? 'Paused · resume' : 'Pause new bookings' }}
                            </button>
                        </form>
                    @else
                        <span style="font-size: 12px; color: var(--vy-faint);">{{ $property['bookings_paused'] ? 'Paused' : 'Accepting bookings' }}</span>
                    @endunless
                </div>
                @if ($property['bookings_paused'])
                    <div style="font-size: 12px; color: var(--vy-amber); padding-top: 6px;">New bookings are paused for this property.</div>
                @endif

                {{-- Remove from group — a careful ARCHIVE-NOT-DESTROY (scheduled, cancellable, records retained) --}}
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0 0; margin-top: 8px; border-top: 1px solid var(--vy-hairline);">
                    <div>
                        <div style="font-size: 13.5px; color: var(--vy-ink);">Remove from group</div>
                        <div style="font-size: 12px; color: var(--vy-muted);">Archives the property — records are kept &amp; exportable, billing stops.</div>
                    </div>
                    @if ($removal !== null && $removal['scheduled'])
                        <form method="POST" action="{{ route('app.settings.remove.cancel') }}">
                            @csrf
                            <button type="submit" class="vy-btn" style="padding: 8px 16px; font-size: 12.5px;">Keep this property</button>
                        </form>
                    @elseif ($canManageProperty && ! $readOnly)
                        <button type="button" x-data @click="$dispatch('open-remove')" class="vy-btn" style="padding: 8px 16px; font-size: 12.5px; color: rgb(150,50,42); border-color: rgba(180,68,58,.3);">Remove…</button>
                    @endif
                </div>
                @if ($removal !== null && $removal['scheduled'])
                    <div style="font-size: 12px; color: var(--vy-amber); padding-top: 8px;">
                        Removal is scheduled — the property stays live until the last checkout on <strong>{{ $removal['effective'] }}</strong>. You can cancel any time before it runs.
                    </div>
                @endif
            </div>

            {{-- Remove-property confirm modal (Alpine) --}}
            @if ($removal !== null && ! $removal['scheduled'] && $canManageProperty && ! $readOnly)
                <div x-data="{ open: false, typed: '' }" x-on:open-remove.window="open = true" x-cloak>
                    <div x-show="open" style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;" @click.self="open = false">
                        <div class="vy-card" style="max-width: 460px; width: 100%;">
                            <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 4px;">Remove {{ $removal['name'] }}</h3>
                            <div style="font-size: 12.5px; color: var(--vy-muted); margin-bottom: 12px;">{{ $removal['units'] }} {{ \Illuminate\Support\Str::plural('unit', $removal['units']) }}@if ($removal['in_house'] > 0)<span> · {{ $removal['in_house'] }} guests in house right now</span>@endif</div>
                            <ul style="font-size: 12.5px; color: var(--vy-ink); line-height: 1.7; padding-left: 18px; margin: 0 0 14px;">
                                <li>Removal is <strong>scheduled</strong> — the property stays live until the last checkout.</li>
                                <li>Channels disconnect — new OTA bookings stop importing.</li>
                                <li>The owner loses owner-portal access to these units.</li>
                                <li>Reservations, folios &amp; statements stay exportable for 90 days.</li>
                            </ul>
                            <form method="POST" action="{{ route('app.settings.remove.schedule') }}">
                                @csrf
                                <label class="vy-field-label" for="confirm_name">Type the property name to confirm</label>
                                <input id="confirm_name" type="text" name="confirm_name" class="vy-input" x-model="typed" placeholder="{{ $removal['name'] }}" autocomplete="off">
                                <div style="display: flex; gap: 10px; margin-top: 14px; justify-content: flex-end;">
                                    <button type="button" @click="open = false" class="vy-btn" style="padding: 9px 16px; font-size: 12.5px;">Keep this property</button>
                                    <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px; font-size: 12.5px;" x-bind:disabled="typed.trim() !== @js($removal['name'])">Schedule removal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ── 6 · WHO CAN ACCESS — REAL (property_user pivot-or-all; real roles) ──────────────────── --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <h2 class="vy-card-title" style="margin: 0;">Who can access {{ $subtitle }}</h2>
                    @if ($canManageUsers && ! $readOnly)
                        <a href="{{ route('app.owners') }}" class="vy-btn vy-btn--accent" style="padding: 7px 14px; font-size: 12px;">Invite to property</a>
                    @endif
                </div>
                <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">Group admins see every property · others are scoped here.</p>

                @foreach ($team as $m)
                    <div style="display: flex; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid var(--vy-hairline);">
                        <span style="width: 34px; height: 34px; border-radius: 50%; background: rgba(194,65,12,.10); color: var(--vy-accent); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">{{ $m['initials'] }}</span>
                        <div style="flex: 1;">
                            <div style="font-size: 13.5px; color: var(--vy-ink);">{{ $m['name'] }}@if ($m['you']) <span style="color: var(--vy-faint); font-size: 12px;">· You</span>@endif</div>
                            <div style="font-size: 12px; color: var(--vy-muted);">{{ $m['scope'] }}</div>
                        </div>
                        <span style="font-size: 12px; color: var(--vy-muted);">{{ $m['roles'] }}</span>
                    </div>
                @endforeach

                @foreach ($pendingInvites as $inv)
                    <div style="display: flex; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid var(--vy-hairline);">
                        <span style="width: 34px; height: 34px; border-radius: 50%; background: rgba(178,106,8,.10); color: var(--vy-amber); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">&#64;</span>
                        <div style="flex: 1;">
                            <div style="font-size: 13.5px; color: var(--vy-ink);">{{ $inv['email'] }}</div>
                            <div style="font-size: 12px; color: var(--vy-muted);">{{ $inv['role'] }} · invited {{ $inv['sent'] }} · expires {{ $inv['expires'] }}</div>
                        </div>
                        <span style="font-size: 11.5px; color: var(--vy-amber); font-weight: 600;">Pending</span>
                    </div>
                @endforeach

                <div style="margin-top: 16px; border-top: 1px solid var(--vy-hairline); padding-top: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 12.5px; font-weight: 600; color: var(--vy-ink);">What each role can do here</span>
                        <a href="{{ route('app.roles') }}" style="font-size: 12px; color: var(--vy-accent); text-decoration: none;">View full permissions ›</a>
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
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Guest-facing brand</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">Applied to emails, the guest portal &amp; the review page for this property.</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">
                    {{-- editor --}}
                    <form method="POST" action="{{ route('app.settings.brand') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="vy-field">
                            <label class="vy-field-label" for="b_accent">Accent colour</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input id="b_accent" type="color" name="brand_accent" value="{{ old('brand_accent', $brand['accent']) }}" style="width: 44px; height: 38px; border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-sm); background: none; cursor: pointer;" {{ $disabled }}>
                                <span style="font-size: 12px; color: var(--vy-muted);">{{ $brand['accent'] }}</span>
                            </div>
                            @error('brand_accent') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field">
                            <label class="vy-field-label" for="b_logo">Logo <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· PNG or JPG, square, ≥ 240px</span></label>
                            @if ($brand['logo_url'])
                                <img src="{{ $brand['logo_url'] }}" alt="Current logo" style="width: 40px; height: 40px; border-radius: var(--vy-r-sm); object-fit: cover; margin-bottom: 6px;">
                            @endif
                            <input id="b_logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="vy-input" {{ $disabled }}>
                            @error('logo') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field">
                            <label class="vy-field-label" for="b_from">Emails show sender name</label>
                            <input id="b_from" type="text" name="brand_email_from_name" class="vy-input" placeholder="{{ $subtitle }}" value="{{ old('brand_email_from_name', $brand['from_name']) }}" {{ $disabled }}>
                            <span style="font-size: 11px; color: var(--vy-faint);">Sent from {{ $brand['from_address'] }} (verified) · only the display name and reply-to vary.</span>
                        </div>
                        <div class="vy-field">
                            <label class="vy-field-label" for="b_reply">Reply-to address</label>
                            <input id="b_reply" type="email" name="brand_reply_to" class="vy-input" placeholder="hello@yourhotel.com" value="{{ old('brand_reply_to', $brand['reply_to']) }}" {{ $disabled }}>
                            @error('brand_reply_to') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                        </div>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink); margin-top: 4px;">
                            <input type="checkbox" name="brand_powered_by" value="1" @checked(old('brand_powered_by', $brand['powered_by'])) {{ $disabled }}>
                            Show “Powered by Vyrobel” in the footer of guest emails
                        </label>
                        @unless ($readOnly)
                            <div style="margin-top: 14px;">
                                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save brand</button>
                            </div>
                        @endunless
                    </form>

                    {{-- live preview — mirrors the shared emails.layout with the resolved brand --}}
                    <div>
                        <div style="font-size: 11.5px; color: var(--vy-faint); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px;">Live preview</div>
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
                                <div style="font-size: 15px; font-weight: 600; color: #2B2320; margin-bottom: 6px;">You’re all set, Lukas</div>
                                <div style="font-size: 12.5px; color: #6b615a; line-height: 1.5; margin-bottom: 14px;">Your booking is confirmed. Everything’s in one place.</div>
                                <span style="display: inline-block; background: {{ $brandPreview->accent }}; color: #fff; font-size: 12.5px; font-weight: 600; padding: 9px 18px; border-radius: 10px;">View your booking</span>
                            </div>
                            @if ($brandPreview->poweredBy)
                                <div style="text-align: center; font-size: 10.5px; color: #A99E95; margin-top: 12px;">Powered by Vyrobel · hotel management, done properly.</div>
                            @endif
                        </div>
                        <p style="font-size: 11px; color: var(--vy-faint); margin: 8px 0 0;">This accent flows to all guest emails, the portal &amp; the review page.</p>
                    </div>
                </div>
            </div>

            {{-- ── Detailed taxes editor (bounded amounts — GATE 2 makes the sample reconcile + adds custom fees) ── --}}
            <div class="vy-card vy-fade" id="tax-detail" style="margin-bottom: 16px;">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Taxes</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">Applied automatically to every folio for this property.</p>
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
                                @if ($canManageProperty && ! $readOnly)
                                    <span style="display: flex; align-items: center; gap: 10px;">
                                        {{-- #104 GATE 2c — the VAT Incl./Added toggle now PERSISTS (drives folio math via
                                             StayEstimator). Hidden 0 + checkbox 1 → a reliable boolean. Percentage taxes only. --}}
                                        @if ($row['field'] === 'taxes' && ($row['percentage'] ?? false))
                                            <input type="hidden" name="tax_inclusive[{{ $row['id'] }}]" value="0">
                                            <label style="display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--vy-muted); white-space: nowrap;">
                                                <input type="checkbox" name="tax_inclusive[{{ $row['id'] }}]" value="1" @checked($row['inclusive'] ?? false)>
                                                Included in price
                                            </label>
                                        @endif
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
                        @if ($canManageProperty && ! $readOnly)
                            <button type="submit" class="vy-btn vy-btn--accent" style="margin-top: 14px; padding: 11px 20px;">Save taxes &amp; fees</button>
                        @endif
                    </form>
                @endif

                {{-- Add a fee (real create — per-stay/night/guest, optional-at-booking) --}}
                @if ($canManageProperty && ! $readOnly)
                    <div x-data="{ open: false }" style="margin-top: 16px; border-top: 1px solid var(--vy-hairline); padding-top: 14px;">
                        <button type="button" @click="open = !open" class="vy-btn" style="padding: 8px 14px; font-size: 12.5px;">+ Add a fee</button>
                        <div x-show="open" x-cloak style="margin-top: 12px;">
                            <form method="POST" action="{{ route('app.settings.fees.add') }}">
                                @csrf
                                <div class="vyn-grid3">
                                    <div class="vy-field" style="margin: 0;">
                                        <label class="vy-field-label" for="fee_name">Fee name</label>
                                        <input id="fee_name" type="text" name="name" class="vy-input" placeholder="e.g. Resort fee" required>
                                    </div>
                                    <div class="vy-field" style="margin: 0;">
                                        <label class="vy-field-label" for="fee_amount">Amount ({{ $sample['currency'] ?? 'EUR' }})</label>
                                        <input id="fee_amount" type="number" step="0.01" min="0" name="amount" class="vy-input" required>
                                    </div>
                                    <div class="vy-field" style="margin: 0;">
                                        <label class="vy-field-label" for="fee_basis">Charged</label>
                                        <select id="fee_basis" name="basis" class="vy-input">
                                            <option value="per_stay">Per stay</option>
                                            <option value="per_night">Per night</option>
                                            <option value="per_guest">Per guest</option>
                                        </select>
                                    </div>
                                </div>
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink); margin-top: 10px;">
                                    <input type="checkbox" name="optional" value="1">
                                    Optional at booking <span style="color: var(--vy-faint);">· guest can add it themselves, vs always applied</span>
                                </label>
                                <button type="submit" class="vy-btn vy-btn--accent" style="margin-top: 12px; padding: 10px 18px; font-size: 12.5px;">Add fee</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- The sample 4-night stay — reconciles to a REAL folio via the shared StayEstimator (#17) --}}
                @if ($sample !== null)
                    <div style="margin-top: 16px; border-top: 1px solid var(--vy-hairline); padding-top: 14px;">
                        <div style="font-size: 12.5px; font-weight: 600; color: var(--vy-ink); margin-bottom: 2px;">On a sample 4-night stay</div>
                        <div style="font-size: 11.5px; color: var(--vy-faint); margin-bottom: 10px;">2 guests · 4 nights · matches what a real folio charges for these settings</div>
                        @foreach ($sample['lines'] as $line)
                            <div style="display: flex; justify-content: space-between; align-items: baseline; padding: 5px 0; font-size: 13px;">
                                <span style="color: var(--vy-ink);">{{ $line['label'] }} @if ($line['detail'] !== '')<span style="color: var(--vy-faint);">· {{ $line['detail'] }}</span>@endif</span>
                                <span style="font-family: var(--vy-display); color: {{ $line['informational'] ? 'var(--vy-faint)' : 'var(--vy-ink)' }};">{{ $line['amount'] }}</span>
                            </div>
                        @endforeach
                        <div style="display: flex; justify-content: space-between; align-items: baseline; padding: 9px 0 0; margin-top: 6px; border-top: 1px solid var(--vy-hairline); font-size: 14px; font-weight: 700;">
                            <span style="color: var(--vy-ink);">Guest pays</span>
                            <span style="font-family: var(--vy-display); color: var(--vy-accent);">{{ $sample['total'] }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ── Organization — REAL (organizations columns) · gated settings.manage. Retained from the
                 de-conflicted settings surface: org-level config still needs a home (KIND-3 vs the property-only
                 wireframe). ─────────────────────────────────────────────────────────────────────────────── --}}
            @can('settings.manage')
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 14px;">Organization</h2>
                    <form method="POST" action="{{ route('app.settings.organization') }}">
                        @csrf
                        <div class="vyn-grid3">
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_cur">Default currency</label>
                                <input id="o_cur" type="text" name="default_currency" class="vy-input" maxlength="3" style="text-transform: uppercase;" value="{{ old('default_currency', $org['default_currency']) }}" required {{ $disabled }}>
                                @error('default_currency') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_loc">Default locale</label>
                                <input id="o_loc" type="text" name="default_locale" class="vy-input" value="{{ old('default_locale', $org['default_locale']) }}" required {{ $disabled }}>
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="o_email">Billing email <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                                <input id="o_email" type="email" name="billing_email" class="vy-input" value="{{ old('billing_email', $org['billing_email']) }}" {{ $disabled }}>
                                @error('billing_email') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @unless ($readOnly)
                            <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save organization settings</button>
                            </div>
                        @endunless
                    </form>
                </div>
            @endcan

            {{-- ── STUBBED — genuinely-unbuilt subsystems, honestly "Soon" (branding is now REAL above) ──── --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">More settings</h2>
                <div class="vyn-grid3">
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
